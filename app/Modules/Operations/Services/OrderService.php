<?php

namespace App\Modules\Operations\Services;

use App\Models\User;
use App\Modules\Access\Services\AccessService;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Operations\Models\ExecutionEntry;
use App\Modules\Operations\Models\OrderLine;
use App\Modules\Operations\Models\Purchase;
use App\Modules\Operations\Models\SalesOrder;
use App\Modules\Operations\Models\StockMovement;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderService
{
    public function fromQuote(User $actor, Quote $quote): SalesOrder
    {
        AccessService::authorize($actor, 'orders.create');

        return DB::transaction(function () use ($actor, $quote) {
            $quote = Quote::lockForUpdate()->findOrFail($quote->id);
            if ($existing = SalesOrder::where('quote_id', $quote->id)->first()) {
                return $existing;
            }
            if ($quote->status !== 'accepted') {
                OperationRules::fail('El presupuesto debe estar aceptado para confirmar un pedido.');
            }
            $order = SalesOrder::create(['quote_id' => $quote->id, 'created_by' => $actor->id, 'currency' => $quote->currency, 'status' => 'confirmed', 'sales_minor' => $quote->total_minor, 'sales_tax_minor' => $quote->tax_minor, 'estimated_snapshot' => $quote->cost_snapshot]);
            foreach ($quote->lines as $i => $commercial) {
                $snapshot = $quote->cost_snapshot['lines'][$i] ?? null;
                $parts = $snapshot['components'] ?? [$snapshot ?? []];
                foreach ($parts as $part) {
                    $item = CatalogItem::find(array_key_exists('catalog_item_id', $part) ? $part['catalog_item_id'] : ($commercial['catalog_item_id'] ?? null));
                    if ($item && isset($part['unit']) && ($item->unit !== $part['unit'] || $item->kind !== $part['kind'] || $item->track_stock !== $part['track_stock'])) {
                        OperationRules::fail('Cambió la unidad, tipo o control de stock de un componente. Revisá la propuesta antes de confirmar el pedido.');
                    }
                    if ($item && $item->currency !== $quote->currency) {
                        OperationRules::fail('La moneda de un ítem cambió. Revisá el presupuesto de origen.');
                    }
                    OrderLine::create(['sales_order_id' => $order->id, 'quote_line_index' => $i, 'catalog_item_id' => $item?->id,
                        'description' => $part['description'] ?? $commercial['description'], 'kind' => $part['kind'] ?? $item?->kind ?? 'service', 'unit' => $part['unit'] ?? $item?->unit ?? 'unit',
                        'track_stock' => $part['track_stock'] ?? $item?->track_stock ?? false, 'quantity' => $part['quantity'] ?? $commercial['quantity'],
                        'estimated_unit_minor' => $part['unit_net_minor'] ?? null, 'source' => $part['source'] ?? null, 'cost_profile_id' => $part['profile_id'] ?? null]);
                }
            }
            OperationRules::audit($actor, 'order.confirmed', 'sales_order', $order->id, ['quote_id' => $quote->id]);

            return $order;
        });
    }

    public function assign(User $actor, SalesOrder $order, ?int $userId): void
    {
        AccessService::authorize($actor, 'orders.update');
        DB::transaction(function () use ($actor, $order, $userId) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
            if ($userId) {
                $user = User::findOrFail($userId);
                abort_unless($user->allows('orders.view-own'), 422);
            }
            if ($order->assigned_to === $userId) {
                return;
            }
            $before = $order->assigned_to;
            $order->update(['assigned_to' => $userId]);
            OperationRules::audit($actor, 'order.assigned', 'sales_order', $order->id, ['before' => $before, 'assigned_to' => $userId]);
        });
    }

    public function reserve(User $actor, SalesOrder $order): SalesOrder
    {
        AccessService::authorize($actor, 'orders.update');

        return DB::transaction(function () use ($actor, $order) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
            $this->open($order);
            $changed = false;
            foreach ($order->lines()->where('track_stock', true)->orderBy('catalog_item_id')->orderBy('id')->get() as $line) {
                $balance = app(InventoryService::class)->balance($actor, $line->catalog_item_id);
                $remaining = bcsub(bcsub($line->quantity, $line->done_quantity, 3), $line->reserved_quantity, 3);
                $reserve = bccomp($remaining, $balance->available(), 3) <= 0 ? $remaining : $balance->available();
                if (bccomp($reserve, '0', 3) > 0) {
                    $balance->update(['reserved' => bcadd($balance->reserved, $reserve, 3)]);
                    $line->update(['reserved_quantity' => bcadd($line->reserved_quantity, $reserve, 3)]);
                    $changed = true;
                }
            }
            if ($changed) {
                OperationRules::audit($actor, 'order.reserved', 'sales_order', $order->id);
            }

            return $order;
        });
    }

    public function release(User $actor, SalesOrder $order): SalesOrder
    {
        AccessService::authorize($actor, 'orders.update');

        return DB::transaction(function () use ($actor, $order) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
            $this->open($order);
            foreach ($order->lines()->where('track_stock', true)->orderBy('catalog_item_id')->orderBy('id')->get() as $line) {
                if (bccomp($line->reserved_quantity, '0', 3) <= 0) {
                    continue;
                }
                $balance = app(InventoryService::class)->balance($actor, $line->catalog_item_id);
                $balance->update(['reserved' => bcsub($balance->reserved, $line->reserved_quantity, 3)]);
                $line->update(['reserved_quantity' => '0']);
            }
            OperationRules::audit($actor, 'order.released', 'sales_order', $order->id);

            return $order;
        });
    }

    public function deliver(User $actor, SalesOrder $order, string $token): SalesOrder
    {
        AccessService::authorize($actor, 'orders.update');
        OperationRules::token($token);
        $key = 'delivery:'.$order->id.':'.$token;
        $hash = hash('sha256', (string) $order->id);

        return DB::transaction(function () use ($actor, $order, $key, $hash, $token) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
            if ($batch = DB::table('operation_batches')->where('operation_key', $key)->first()) {
                OperationRules::same($batch->request_hash, $hash);

                return $order;
            }
            $this->open($order);
            $count = 0;
            $stock = app(InventoryService::class);
            foreach ($order->lines()->where('track_stock', true)->orderBy('catalog_item_id')->orderBy('id')->get() as $line) {
                $q = $line->reserved_quantity;
                if (bccomp($q, '0', 3) <= 0) {
                    continue;
                }
                $balance = $stock->balance($actor, $line->catalog_item_id);
                $balance->update(['reserved' => bcsub($balance->reserved, $q, 3)]);
                $value = $stock->outgoingValue($balance, $q);
                $stock->move($actor, $balance, bcsub('0', $q, 3), bcsub('0', $value, 8), ['kind' => 'delivery', 'order_line_id' => $line->id, 'operation_key' => $key.':'.$line->id, 'request_hash' => $hash, 'reason' => 'Entrega '.$order->reference()]);
                $line->update(['reserved_quantity' => '0', 'done_quantity' => bcadd($line->done_quantity, $q, 3)]);
                $count++;
            }
            if (! $count) {
                OperationRules::fail('No hay unidades reservadas para entregar.');
            }
            DB::table('operation_batches')->insert(['operation_key' => $key, 'request_hash' => $hash, 'created_at' => now(), 'updated_at' => now()]);
            OperationRules::audit($actor, 'order.delivered', 'sales_order', $order->id, ['token' => $token]);

            return $order;
        });
    }

    private function open(SalesOrder $order): void
    {
        if ($order->status !== 'confirmed') {
            OperationRules::fail('El pedido no está abierto.');
        }
    }

    public function chooseExecutor(User $actor, OrderLine $line, string $source, string $reason): void
    {
        AccessService::authorize($actor, 'orders.update');
        Validator::make(compact('source', 'reason'), ['source' => 'required|in:own,external', 'reason' => 'required|string|min:10|max:1000'])->validate();
        DB::transaction(function () use ($actor, $line, $source, $reason) {
            $order = SalesOrder::lockForUpdate()->findOrFail($line->sales_order_id);
            $this->open($order);
            $line = OrderLine::lockForUpdate()->findOrFail($line->id);
            if ($line->kind !== 'service' || bccomp($line->done_quantity, '0', 3) > 0) {
                OperationRules::fail('Solo se cambia ejecutor de servicios sin ejecución vigente.');
            }
            $before = $line->source;
            $line->update(['source' => $source]);
            OperationRules::audit($actor, 'order.executor_changed', 'order_line', $line->id, ['before' => $before, 'after' => $source, 'reason' => $reason]);
        });
    }

    public function recordOwn(User $actor, OrderLine $line, string $quantity, string $unitNetCost, string $reference, string $token): ExecutionEntry
    {
        AccessService::authorize($actor, 'orders.update');
        AccessService::authorize($actor, 'costs.view');
        OperationRules::token($token);
        $quantity = OperationRules::quantity($quantity);
        Validator::make(compact('unitNetCost', 'reference'), ['unitNetCost' => ['required', 'regex:/^\d{1,9}(\.\d{1,8})?$/'], 'reference' => 'required|string|min:10|max:1000'])->validate();
        $hash = hash('sha256', json_encode([$line->id, $quantity, $unitNetCost, $reference]));
        $key = 'own:'.$actor->id.':'.$token;

        return DB::transaction(function () use ($actor, $line, $quantity, $unitNetCost, $reference, $hash, $key) {
            $order = SalesOrder::lockForUpdate()->findOrFail($line->sales_order_id);
            if ($old = ExecutionEntry::where('operation_key', $key)->first()) {
                OperationRules::same($old->request_hash, $hash);

                return $old;
            }
            $this->open($order);
            $line = OrderLine::lockForUpdate()->findOrFail($line->id);
            if ($line->kind !== 'service' || $line->track_stock || $line->source === 'external') {
                OperationRules::fail('El trabajo de tercero se registra mediante su compra recibida; no se suma trabajo propio a esa intervención.');
            }
            $cost = bcmul(bcmul($unitNetCost, $order->currency === 'USD' ? '100' : '1', 8), $quantity, 8);

            return $this->entry($actor, $line, $quantity, $cost, 'own', $reference, $key, $hash, null);
        });
    }

    public function recordPurchased(User $actor, OrderLine $line, Purchase $purchase, string $quantity, string $cost, string $key, string $hash): ExecutionEntry
    {
        AccessService::authorize($actor, 'purchases.approve');
        // Only invoked by a confirmed receipt inside its transaction; validate every binding again.
        $order = SalesOrder::lockForUpdate()->findOrFail($line->sales_order_id);
        $this->open($order);
        $line = OrderLine::lockForUpdate()->findOrFail($line->id);
        if ($purchase->status !== 'ordered' || $purchase->currency !== $order->currency || $line->track_stock || $line->source === 'own') {
            OperationRules::fail('La compra no corresponde a una ejecución externa de esta línea.');
        }
        if ($old = ExecutionEntry::where('operation_key', $key)->first()) {
            OperationRules::same($old->request_hash, $hash);

            return $old;
        }

        return $this->entry($actor, $line, $quantity, $cost, 'external', $purchase->reference.' · '.$purchase->supplier_name, $key, $hash, $purchase->id);
    }

    private function entry(User $actor, OrderLine $line, string $q, string $cost, string $source, string $reference, string $key, string $hash, ?int $purchaseId): ExecutionEntry
    {
        if (bccomp(bcadd($line->done_quantity, $q, 3), $line->quantity, 3) > 0) {
            OperationRules::fail('La ejecución supera la cantidad pendiente. No se puede cargar dos veces la misma intervención.');
        }
        $entry = ExecutionEntry::create(['order_line_id' => $line->id, 'actor_id' => $actor->id, 'purchase_id' => $purchaseId, 'source' => $source, 'quantity' => $q, 'cost_minor' => $cost, 'reference' => $reference, 'operation_key' => $key, 'request_hash' => $hash]);
        $line->update(['done_quantity' => bcadd($line->done_quantity, $q, 3)]);
        OperationRules::audit($actor, 'order.executed', 'execution_entry', $entry->id);

        return $entry;
    }

    public function voidExecution(User $actor, ExecutionEntry $entry, string $reason): void
    {
        AccessService::authorize($actor, 'orders.update');
        AccessService::authorize($actor, 'costs.view');
        Validator::make(compact('reason'), ['reason' => 'required|string|min:10|max:1000'])->validate();
        DB::transaction(function () use ($actor, $entry, $reason) {
            $line = OrderLine::findOrFail($entry->order_line_id);
            $order = SalesOrder::lockForUpdate()->findOrFail($line->sales_order_id);
            $entry = ExecutionEntry::lockForUpdate()->findOrFail($entry->id);
            if ($entry->voided_at) {
                return;
            }
            $entry->update(['voided_at' => now(), 'void_reason' => $reason]);
            $line->refresh()->update(['done_quantity' => bcsub($line->done_quantity, $entry->quantity, 3)]);
            $order->update(['status' => 'confirmed', 'completed_at' => null]);
            OperationRules::audit($actor, 'execution.voided', 'execution_entry', $entry->id, ['reason' => $reason]);
        });
    }

    public function cancel(User $actor, SalesOrder $order, string $reason): void
    {
        AccessService::authorize($actor, 'orders.approve');
        Validator::make(compact('reason'), ['reason' => 'required|string|min:10|max:1000'])->validate();
        DB::transaction(function () use ($actor, $order, $reason) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->status === 'cancelled') {
                return;
            }$this->open($order);
            foreach ($order->lines as $line) {
                if (bccomp($line->done_quantity, '0', 3) > 0) {
                    OperationRules::fail('Devolvé las entregas y anulá las ejecuciones antes de cancelar.');
                }
            }$this->release($actor, $order);
            $order->update(['status' => 'cancelled']);
            OperationRules::audit($actor, 'order.cancelled', 'sales_order', $order->id, ['reason' => $reason]);
        });
    }

    public function complete(User $actor, SalesOrder $order): SalesOrder
    {
        AccessService::authorize($actor, 'orders.approve');

        return DB::transaction(function () use ($actor, $order) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->status === 'completed') {
                return $order;
            } $this->open($order);
            foreach ($order->lines as $line) {
                if (bccomp($line->done_quantity, $line->quantity, 3) !== 0) {
                    OperationRules::fail('Faltan entregas o servicios realizados para cerrar el pedido.');
                }
            }
            $order->update(['status' => 'completed', 'completed_at' => now()]);
            OperationRules::audit($actor, 'order.completed', 'sales_order', $order->id);

            return $order;
        });
    }

    public function result(User $actor, SalesOrder $order): array
    {
        AccessService::authorize($actor, 'costs.view');
        abort_unless($actor->allows('orders.view') || ($actor->allows('orders.view-own') && $order->assigned_to === $actor->id), 403);
        $cost = '0';
        $lines = [];
        $commercial = [];
        $orderLines = $order->lines;
        $movements = StockMovement::whereIn('order_line_id', $orderLines->modelKeys())->whereIn('kind', ['delivery', 'customer_return'])->get()->groupBy('order_line_id');
        $executions = ExecutionEntry::whereIn('order_line_id', $orderLines->modelKeys())->whereNull('voided_at')->get()->groupBy('order_line_id');
        foreach ($orderLines as $line) {
            $actual = '0';
            foreach ($movements->get($line->id, collect()) as $move) {
                $actual = bcsub($actual, $move->value_minor, 8);
            }
            foreach ($executions->get($line->id, collect()) as $entry) {
                $actual = bcadd($actual, $entry->cost_minor, 8);
            }
            $lines[$line->id] = $actual;
            $cost = bcadd($cost, $actual, 8);
            $index = $line->quote_line_index;
            if (! isset($commercial[$index])) {
                $sale = $order->quote->lines[$index];
                $commercial[$index] = ['description' => $sale['description'], 'sales_net_minor' => bcsub($sale['total_minor'], $sale['tax_minor'], 0), 'cost_minor' => '0'];
            }
            $commercial[$index]['cost_minor'] = bcadd($commercial[$index]['cost_minor'], $actual, 8);
        }
        foreach ($commercial as &$row) {
            $row['profit_minor'] = bcsub($row['sales_net_minor'], $row['cost_minor'], 8);
            $row['margin_percent'] = bccomp($row['sales_net_minor'], '0', 0) > 0 ? bcdiv(bcmul($row['profit_minor'], '100', 8), $row['sales_net_minor'], 5) : null;
        }unset($row);
        $net = bcsub($order->sales_minor, $order->sales_tax_minor, 0);
        $profit = bcsub($net, $cost, 8);

        return ['cost_minor' => $cost, 'profit_minor' => $profit, 'sales_net_minor' => $net, 'margin_percent' => bccomp($net, '0', 0) > 0 ? bcdiv(bcmul($profit, '100', 8), $net, 5) : null, 'complete' => $order->status === 'completed', 'lines' => $lines, 'commercial_lines' => $commercial, 'estimated' => $order->estimated_snapshot];
    }
}
