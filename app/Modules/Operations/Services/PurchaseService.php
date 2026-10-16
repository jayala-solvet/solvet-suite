<?php

namespace App\Modules\Operations\Services;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Costs\Services\CostCalculator;
use App\Modules\Operations\Models\OrderLine;
use App\Modules\Operations\Models\Purchase;
use App\Modules\Operations\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseService
{
    public function save(User $actor, array $data, ?Purchase $purchase = null): Purchase
    {
        OperationRules::authorize($actor);
        $data = Validator::make($data, [
            'supplier_name' => 'required|string|max:200', 'supplier_tax_id' => 'required|string|max:30', 'reference' => 'required|string|max:200',
            'currency' => 'required|in:PYG,USD', 'purchase_date' => 'required|date|before_or_equal:today', 'freight_amount' => 'required', 'freight_tax_rate' => 'required|in:0,5,10',
            'freight_vat_treatment' => 'required|in:recoverable,non_recoverable', 'freight_method' => 'required|in:quantity,value,manual',
            'lines' => 'required|array|min:1|max:100', 'lines.*.catalog_item_id' => 'required|integer|exists:catalog_items,id', 'lines.*.quantity' => 'required',
            'lines.*.unit_cost' => 'required', 'lines.*.tax_rate' => 'required|in:0,5,10', 'lines.*.vat_treatment' => 'required|in:recoverable,non_recoverable',
            'lines.*.order_line_id' => 'nullable|integer|exists:order_lines,id', 'lines.*.extra_cost' => 'nullable', 'lines.*.freight_share' => 'nullable',
        ])->validate();
        $currency = $data['currency'];
        $freight = OperationRules::money((string) $data['freight_amount'], $currency);
        $freightNet = OperationRules::effective($freight, (int) $data['freight_tax_rate'], $data['freight_vat_treatment']);
        $items = CatalogItem::whereIn('id', array_column($data['lines'], 'catalog_item_id'))->get()->keyBy('id');
        $lines = [];
        $weights = [];
        $sumWeight = '0';
        $grossTotal = $freight;
        $manualTotal = '0';
        foreach (array_values($data['lines']) as $i => $raw) {
            $item = $items->get($raw['catalog_item_id']);
            if (! $item->can_buy || $item->currency !== $currency) {
                OperationRules::fail('La compra requiere ítems comprables de la misma moneda.');
            }
            $q = OperationRules::quantity((string) $raw['quantity']);
            $unit = OperationRules::money((string) $raw['unit_cost'], $currency);
            $gross = CostCalculator::round(bcmul($unit, $q, 8));
            $base = OperationRules::effective($gross, (int) $raw['tax_rate'], $raw['vat_treatment']);
            $extra = OperationRules::money((string) ($raw['extra_cost'] ?? '0'), $currency);
            $share = OperationRules::money((string) ($raw['freight_share'] ?? '0'), $currency);
            if ($item->kind === 'service' && bccomp($share, '0', 0) > 0) {
                OperationRules::fail('El envío solo se distribuye entre bienes.');
            }
            if ($raw['order_line_id'] ?? null) {
                $orderLine = OrderLine::with('order')->findOrFail($raw['order_line_id']);
                if ((int) $orderLine->catalog_item_id !== $item->id || $orderLine->order->currency !== $currency || $orderLine->unit !== $item->unit || $orderLine->order->status !== 'confirmed') {
                    OperationRules::fail('La línea del pedido no corresponde al ítem, moneda o estado de la compra.');
                }
                if ($item->kind === 'service' && $orderLine->source === 'own') {
                    OperationRules::fail('Esta intervención fue planificada como trabajo propio. Seleccioná un costo de tercero al presupuestar; no se cobran ambas intervenciones.');
                }
            }
            $lines[] = ['catalog_item_id' => $item->id, 'description' => $item->name, 'kind' => $item->kind, 'unit' => $item->unit, 'track_stock' => $item->track_stock, 'quantity' => $q, 'unit_cost_minor' => $unit, 'gross_minor' => $gross, 'tax_rate' => (int) $raw['tax_rate'], 'vat_treatment' => $raw['vat_treatment'], 'base_minor' => $base, 'extra_minor' => $extra, 'freight_share_minor' => $share, 'order_line_id' => $raw['order_line_id'] ?? null];
            if ($item->kind === 'product') {
                $weight = match ($data['freight_method']) {
                    'quantity' => $q,'value' => $base,default => $share
                };
                $weights[$i] = $weight;
                $sumWeight = bcadd($sumWeight, $weight, 8);
                $manualTotal = bcadd($manualTotal, $share, 0);
            }
            $grossTotal = bcadd($grossTotal, bcadd($gross, $extra, 0), 0);
        }
        if ($data['freight_method'] === 'manual' && bccomp($manualTotal, $freight, 0) !== 0) {
            OperationRules::fail('La suma de los envíos asignados debe coincidir exactamente con el envío total con IVA.');
        }
        if (bccomp($freight, '0', 0) > 0 && bccomp($sumWeight, '0', 8) <= 0) {
            OperationRules::fail('El envío requiere bienes y una base de reparto mayor a cero.');
        }
        $allocated = '0';
        $last = array_key_last($weights);
        foreach ($lines as $i => &$line) {
            $freightAllocated = '0';
            if (isset($weights[$i]) && bccomp($sumWeight, '0', 8) > 0) {
                $freightAllocated = $i === $last ? bcsub($freightNet, $allocated, 8) : bcdiv(bcmul($freightNet, $weights[$i], 12), $sumWeight, 8);
                $allocated = bcadd($allocated, $freightAllocated, 8);
            }
            $line['freight_net_minor'] = $freightAllocated;
            $line['effective_total_minor'] = bcadd(bcadd($line['base_minor'], $line['extra_minor'], 8), $freightAllocated, 8);
            $line['effective_unit_minor'] = bcdiv($line['effective_total_minor'], $line['quantity'], 8);
        }
        unset($line);
        unset($data['freight_amount']);

        return DB::transaction(function () use ($actor, $data, $lines, $grossTotal, $freight, $purchase) {
            $purchase = $purchase ? Purchase::lockForUpdate()->findOrFail($purchase->id) : new Purchase(['created_by' => $actor->id, 'status' => 'draft']);
            if ($purchase->exists && $purchase->status !== 'draft') {
                OperationRules::fail('Solo se editan compras en borrador.');
            }
            $purchase->fill(array_merge($data, ['lines' => $lines, 'total_minor' => $grossTotal, 'freight_minor' => $freight]))->save();
            OperationRules::audit($actor, 'purchase.saved', 'purchase', $purchase->id);

            return $purchase;
        });
    }

    public function confirm(User $actor, Purchase $purchase): Purchase
    {
        OperationRules::authorize($actor);

        return DB::transaction(function () use ($actor, $purchase) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
            if (in_array($purchase->status, ['ordered', 'received'], true)) {
                return $purchase;
            }
            if ($purchase->status !== 'draft') {
                OperationRules::fail('La compra no admite confirmación.');
            }
            $purchase->update(['status' => 'ordered']);
            OperationRules::audit($actor, 'purchase.confirmed', 'purchase', $purchase->id);

            return $purchase;
        });
    }

    public function cancel(User $actor, Purchase $purchase, string $reason): void
    {
        OperationRules::authorize($actor);
        Validator::make(compact('reason'), ['reason' => 'required|string|min:10|max:1000'])->validate();
        DB::transaction(function () use ($actor, $purchase, $reason) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status === 'cancelled') {
                return;
            }if (! in_array($purchase->status, ['draft', 'ordered'], true)) {
                OperationRules::fail('Una compra recibida se corrige con devoluciones o anulación de su ejecución, no borrando la recepción.');
            }$purchase->update(['status' => 'cancelled']);
            OperationRules::audit($actor, 'purchase.cancelled', 'purchase', $purchase->id, ['reason' => $reason]);
        });
    }

    public function receive(User $actor, Purchase $purchase): Purchase
    {
        OperationRules::authorize($actor);

        return DB::transaction(function () use ($actor, $purchase) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status === 'received') {
                return $purchase;
            }
            if ($purchase->status !== 'ordered') {
                OperationRules::fail('Confirmá la compra antes de recibir.');
            }
            $lines = collect($purchase->lines)->sortBy('catalog_item_id');
            // Lock customer orders before inventory, in the same order as deliveries.
            $orderIds = OrderLine::whereIn('id', array_filter(array_column($purchase->lines, 'order_line_id')))->pluck('sales_order_id')->unique()->sort();
            foreach ($orderIds as $id) {
                $order = SalesOrder::lockForUpdate()->findOrFail($id);
                if ($order->status !== 'confirmed') {
                    OperationRules::fail('Un pedido vinculado no está abierto.');
                }
            }
            foreach ($lines as $i => $line) {
                $key = 'receipt:'.$purchase->id.':'.$i;
                $hash = hash('sha256', json_encode($line));
                $item = CatalogItem::findOrFail($line['catalog_item_id']);
                if ($item->kind !== $line['kind'] || $item->currency !== $purchase->currency || $item->unit !== $line['unit'] || (bool) $item->track_stock !== $line['track_stock']) {
                    OperationRules::fail('Cambió la configuración del ítem desde la compra. Revisá el documento antes de recibir.');
                }
                if ($line['track_stock']) {
                    $stock = app(InventoryService::class);
                    $balance = $stock->balance($actor, $item->id);
                    $stock->move($actor, $balance, $line['quantity'], $line['effective_total_minor'], ['kind' => 'receipt', 'purchase_id' => $purchase->id, 'operation_key' => $key, 'request_hash' => $hash, 'reason' => 'Recepción '.$purchase->referenceLabel()]);
                } elseif ($line['order_line_id']) {
                    app(OrderService::class)->recordPurchased($actor, OrderLine::findOrFail($line['order_line_id']), $purchase, $line['quantity'], $line['effective_total_minor'], $key, $hash);
                }
            }
            $purchase->update(['status' => 'received', 'received_at' => now()]);
            OperationRules::audit($actor, 'purchase.received', 'purchase', $purchase->id);

            return $purchase;
        });
    }
}
