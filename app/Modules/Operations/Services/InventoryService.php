<?php

namespace App\Modules\Operations\Services;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Operations\Models\OrderLine;
use App\Modules\Operations\Models\SalesOrder;
use App\Modules\Operations\Models\StockBalance;
use App\Modules\Operations\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryService
{
    public function balance(User $actor, int $itemId): StockBalance
    {
        OperationRules::authorize($actor);
        $item = CatalogItem::lockForUpdate()->findOrFail($itemId);
        if ($item->kind !== 'product' || ! $item->track_stock) {
            OperationRules::fail('El ítem no es un bien con control de stock.');
        }
        StockBalance::firstOrCreate(['catalog_item_id' => $itemId], ['quantity' => '0', 'reserved' => '0', 'value_minor' => '0']);

        return StockBalance::whereKey($itemId)->lockForUpdate()->firstOrFail();
    }

    public function move(User $actor, StockBalance $balance, string $quantity, string $value, array $origin): StockMovement
    {
        OperationRules::authorize($actor);
        $key = $origin['operation_key'];
        if ($old = StockMovement::where('operation_key', $key)->first()) {
            OperationRules::same($old->request_hash, $origin['request_hash']);

            return $old;
        }
        if (bccomp($quantity, '0', 3) < 0 && bccomp($balance->available(), bcsub('0', $quantity, 3), 3) < 0) {
            OperationRules::fail('Stock disponible insuficiente; existen reservas o faltan unidades.');
        }
        $newQty = bcadd($balance->quantity, $quantity, 3);
        $newValue = bcadd($balance->value_minor, $value, 8);
        if (bccomp($newQty, '0', 3) < 0 || bccomp($newValue, '0', 8) < 0) {
            OperationRules::fail('El movimiento dejaría un saldo o valor negativo.');
        }
        $balance->update(['quantity' => $newQty, 'value_minor' => bccomp($newQty, '0', 3) === 0 ? '0' : $newValue]);

        return StockMovement::create($origin + ['catalog_item_id' => $balance->catalog_item_id, 'actor_id' => $actor->id, 'quantity' => $quantity, 'value_minor' => $value, 'unit_cost_minor' => bcdiv($value, $quantity, 8)]);
    }

    public function outgoingValue(StockBalance $balance, string $quantity): string
    {
        return bccomp($quantity, $balance->quantity, 3) === 0 ? $balance->value_minor : bcdiv(bcmul($balance->value_minor, $quantity, 12), $balance->quantity, 8);
    }

    public function adjust(User $actor, CatalogItem $item, string $direction, string $quantity, ?string $unitCost, string $reason, string $token): StockMovement
    {
        OperationRules::authorize($actor);
        OperationRules::token($token);
        $quantity = OperationRules::quantity($quantity);
        Validator::make(compact('direction', 'reason', 'unitCost'), ['direction' => 'required|in:in,out', 'reason' => 'required|string|min:10|max:1000', 'unitCost' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,8})?$/']])->validate();
        if ($direction === 'in' && $unitCost === null) {
            OperationRules::fail('La entrada necesita costo efectivo, incluido cero explícito.');
        }
        $hash = hash('sha256', json_encode([$item->id, $direction, $quantity, $unitCost, $reason]));
        $key = 'adjust:'.$actor->id.':'.$token;

        return DB::transaction(function () use ($actor, $item, $direction, $quantity, $unitCost, $reason, $hash, $key) {
            $balance = $this->balance($actor, $item->id);
            if ($old = StockMovement::where('operation_key', $key)->first()) {
                OperationRules::same($old->request_hash, $hash);

                return $old;
            }
            $value = $direction === 'in' ? bcmul(bcmul($unitCost, $item->currency === 'USD' ? '100' : '1', 8), $quantity, 8) : $this->outgoingValueSafe($balance, $quantity);
            $movement = $this->move($actor, $balance, $direction === 'in' ? $quantity : bcsub('0', $quantity, 3), $direction === 'in' ? $value : bcsub('0', $value, 8), ['kind' => 'adjustment', 'operation_key' => $key, 'request_hash' => $hash, 'reason' => $reason]);
            OperationRules::audit($actor, 'stock.adjusted', 'stock_movement', $movement->id, ['reason' => $reason]);

            return $movement;
        });
    }

    private function outgoingValueSafe(StockBalance $balance, string $quantity): string
    {
        if (bccomp($balance->available(), $quantity, 3) < 0) {
            OperationRules::fail('Stock disponible insuficiente.');
        }

        return $this->outgoingValue($balance, $quantity);
    }

    public function returnMovement(User $actor, StockMovement $original, string $quantity, string $reason, string $token): StockMovement
    {
        OperationRules::authorize($actor);
        OperationRules::token($token);
        $quantity = OperationRules::quantity($quantity);
        Validator::make(compact('reason'), ['reason' => 'required|string|min:10|max:1000'])->validate();
        $hash = hash('sha256', json_encode([$original->id, $quantity, $reason]));
        $key = 'return:'.$actor->id.':'.$token;

        return DB::transaction(function () use ($actor, $original, $quantity, $reason, $hash, $key) {
            $line = $original->order_line_id ? OrderLine::findOrFail($original->order_line_id) : null;
            $order = $line ? SalesOrder::lockForUpdate()->findOrFail($line->sales_order_id) : null;
            $balance = $this->balance($actor, $original->catalog_item_id);
            if ($old = StockMovement::where('operation_key', $key)->first()) {
                OperationRules::same($old->request_hash, $hash);

                return $old;
            }
            $original = StockMovement::lockForUpdate()->findOrFail($original->id);
            if (! in_array($original->kind, ['receipt', 'delivery'], true)) {
                OperationRules::fail('Solo se devuelven entradas de compra o entregas. Para otros casos usá un ajuste con motivo.');
            }
            $absolute = ltrim($original->quantity, '-');
            if (bccomp(bcadd($original->returned_quantity, $quantity, 3), $absolute, 3) > 0) {
                OperationRules::fail('La devolución supera la cantidad original pendiente de devolver.');
            }
            $customerReturn = $original->kind === 'delivery';
            $value = $customerReturn ? bcdiv(bcmul(ltrim($original->value_minor, '-'), $quantity, 12), $absolute, 8) : $this->outgoingValueSafe($balance, $quantity);
            if ($customerReturn && bccomp(bcadd($original->returned_quantity, $quantity, 3), $absolute, 3) === 0) {
                $already = '0';
                foreach (StockMovement::where('original_movement_id', $original->id)->where('kind', 'customer_return')->get() as $prior) {
                    $already = bcadd($already, $prior->value_minor, 8);
                }
                $value = bcsub(ltrim($original->value_minor, '-'), $already, 8);
            }
            $movement = $this->move($actor, $balance, $customerReturn ? $quantity : bcsub('0', $quantity, 3), $customerReturn ? $value : bcsub('0', $value, 8), ['kind' => $customerReturn ? 'customer_return' : 'supplier_return', 'purchase_id' => $original->purchase_id, 'order_line_id' => $original->order_line_id, 'original_movement_id' => $original->id, 'operation_key' => $key, 'request_hash' => $hash, 'reason' => $reason]);
            $original->update(['returned_quantity' => bcadd($original->returned_quantity, $quantity, 3)]);
            if ($customerReturn && $line) {
                $line->refresh()->update(['done_quantity' => bcsub($line->done_quantity, $quantity, 3)]);
                $order->update(['status' => 'confirmed', 'completed_at' => null]);
            }
            OperationRules::audit($actor, 'stock.returned', 'stock_movement', $movement->id, ['original' => $original->id, 'reason' => $reason]);

            return $movement;
        });
    }
}
