<?php

namespace App\Modules\Costs\Services;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Costs\Models\CostProfile;
use App\Modules\Operations\Services\OperationRules;
use App\Modules\Quotes\Services\QuoteCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuoteProfitability
{
    private Collection $items;

    private Collection $profiles;

    public function preview(User $actor, array $lines, string $currency): array
    {
        Gate::forUser($actor)->authorize('view-costs');

        return $this->snapshot($actor, $lines, app(QuoteCalculator::class)->calculate($lines, $currency), $currency);
    }

    public function snapshot(User $actor, array $raw, array $calculated, string $currency): array
    {
        Gate::forUser($actor)->authorize('view-costs');
        Validator::make(['lines' => $raw], ['lines.*.components' => 'nullable|array|max:30', 'lines.*.components.*' => 'required|array', 'lines.*.catalog_item_id' => 'nullable|integer', 'lines.*.cost_profile_id' => 'nullable|integer', 'lines.*.components.*.catalog_item_id' => 'nullable|integer', 'lines.*.components.*.cost_profile_id' => 'nullable|integer'])->validate();
        $flat = [];
        foreach ($raw as $input) {
            foreach (! empty($input['components']) ? $input['components'] : [$input] as $part) {
                $flat[] = $part;
            }
        }
        if (count($flat) > 300) {
            OperationRules::fail('El presupuesto admite hasta 300 componentes internos en total.');
        }
        $this->items = CatalogItem::with('currentCost')->whereIn('id', array_filter(array_column($flat, 'catalog_item_id')))->get()->keyBy('id');
        $this->profiles = CostProfile::whereIn('id', array_filter(array_column($flat, 'cost_profile_id')))->get()->keyBy('id');
        $lines = [];
        $totalExact = '0';
        $covered = $stale = 0;
        foreach (array_values($raw) as $index => $line) {
            $parentQ = $calculated['lines'][$index]['quantity'];
            $parts = [];
            $exact = '0';
            $complete = true;
            $components = ! empty($line['components']) ? array_values($line['components']) : null;
            foreach ($components ?? [$line] as $component) {
                $q = $components ? bcmul(OperationRules::quantity((string) ($component['quantity'] ?? '1')), $parentQ, 6) : $parentQ;
                if (bccomp($q, bcadd($q, '0', 3), 6) !== 0) {
                    OperationRules::fail('La cantidad total de un componente debe tener como máximo tres decimales.');
                }
                $part = $this->part($component, $currency, $q);
                $part['unit_quantity'] = $components ? (string) ($component['quantity'] ?? '1') : '1';
                if ($part['exact_cost_minor'] === null) {
                    $complete = false;
                } else {
                    $exact = bcadd($exact, $part['exact_cost_minor'], 8);
                }
                $stale += (int) $part['stale'];
                $parts[] = $part;
            }
            $saleNet = bcsub($calculated['lines'][$index]['total_minor'], $calculated['lines'][$index]['tax_minor'], 0);
            $profit = $complete ? bcsub($saleNet, CostCalculator::round($exact), 0) : null;
            $snapshot = $components ? ['components' => $parts, 'source' => 'bundle', 'catalog_item_id' => null, 'profile_id' => null, 'unit_net_minor' => null, 'stale' => collect($parts)->contains('stale', true)] : $parts[0];
            $snapshot = array_merge($snapshot, ['description' => $line['description'], 'quantity' => $parentQ, 'price_total_minor' => $calculated['lines'][$index]['total_minor'], 'sales_tax_minor' => $calculated['lines'][$index]['tax_minor'], 'exact_cost_minor' => $complete ? $exact : null, 'cost_minor' => $complete ? CostCalculator::round($exact) : null, 'sales_net_minor' => $saleNet, 'profit_minor' => $profit, 'margin_percent' => $complete && bccomp($saleNet, '0', 0) > 0 ? bcdiv(bcmul(bcsub($saleNet, $exact, 8), '100', 8), $saleNet, 5) : null]);
            $lines[] = $snapshot;
            if ($complete) {
                $covered++;
                $totalExact = bcadd($totalExact, $exact, 8);
            }
        }
        $complete = $covered === count($raw);
        $sales = bcsub($calculated['total_minor'], $calculated['tax_minor'], 0);
        $cost = CostCalculator::round($totalExact);
        $profit = $complete ? bcsub($sales, $cost, 0) : null;

        return ['version' => 2, 'currency' => $currency, 'captured_at' => now()->toIso8601String(), 'lines' => $lines, 'covered_lines' => $covered, 'total_lines' => count($raw), 'stale_lines' => $stale, 'complete' => $complete, 'cost_minor' => $cost, 'exact_cost_minor' => $totalExact, 'sales_net_minor' => $sales, 'profit_minor' => $profit, 'margin_percent' => $complete && bccomp($sales, '0', 0) > 0 ? bcdiv(bcmul($profit, '100', 2), $sales, 2) : null, 'margin_percent_precise' => $complete && bccomp($sales, '0', 0) > 0 ? bcdiv(bcmul(bcsub($sales, $totalExact, 8), '100', 8), $sales, 5) : null];
    }

    public static function restoreInputs(array $line, array $snapshot, string $currency): array
    {
        if (! array_key_exists('cost_profile_id', $line)) {
            $line['cost_profile_id'] = $snapshot['profile_id'] ?? null;
        }
        if (($snapshot['source'] ?? null) === 'manual' && ! array_key_exists('internal_unit_cost', $line)) {
            $line['internal_unit_cost'] = bcdiv($snapshot['unit_net_minor'], $currency === 'USD' ? '100' : '1', $currency === 'USD' ? 2 : 0);
        }
        if (isset($snapshot['components']) && ! array_key_exists('components', $line)) {
            $line['components'] = array_map(function ($part) use ($currency) {
                $input = ['catalog_item_id' => $part['catalog_item_id'], 'description' => $part['description'], 'quantity' => $part['unit_quantity'], 'cost_profile_id' => $part['profile_id']];
                if ($part['source'] === 'manual') {
                    $input['internal_unit_cost'] = bcdiv($part['unit_net_minor'], $currency === 'USD' ? '100' : '1', $currency === 'USD' ? 2 : 0);
                }

                return $input;
            }, $snapshot['components']);
        }

        return $line;
    }

    private function part(array $line, string $currency, string $quantity): array
    {
        Validator::make($line, ['description' => 'nullable|string|max:1000', 'catalog_item_id' => 'nullable|integer', 'cost_profile_id' => 'nullable|integer', 'internal_unit_cost' => ['nullable', 'regex:'.QuoteCalculator::pricePattern($currency)]])->validate();
        $item = isset($line['catalog_item_id']) ? $this->items->get($line['catalog_item_id']) : null;
        if (! empty($line['catalog_item_id']) && ! $item) {
            OperationRules::fail('No se encontró el componente de catálogo.');
        }
        if ($item && ($item->currency !== $currency || ! $item->can_sell)) {
            OperationRules::fail('El componente debe ser vendible y usar la moneda del presupuesto.');
        }
        $profile = ! empty($line['cost_profile_id']) ? $this->profiles->get($line['cost_profile_id']) : (array_key_exists('cost_profile_id', $line) ? null : $item?->currentCost);
        if (! empty($line['cost_profile_id']) && ! $profile) {
            OperationRules::fail('No se encontró la referencia de costo.');
        }
        if ($profile && (! $item || (int) $profile->catalog_item_id !== $item->id || $profile->currency !== $currency || $profile->unit !== $item->unit)) {
            throw ValidationException::withMessages(['cost_profile_id' => 'El costo no corresponde al ítem, unidad y moneda.']);
        }
        $unit = $profile?->effective_unit_minor;
        $manual = ! $item && isset($line['internal_unit_cost']) && $line['internal_unit_cost'] !== '';
        if ($manual) {
            $unit = CostCalculator::toMinor((string) $line['internal_unit_cost'], $currency);
        }
        $exact = $unit !== null ? bcmul($unit, $quantity, 8) : null;
        $freight = $profile?->extra_minor ?? '0';
        $extraNet = OperationRules::effective($freight, (int) ($profile?->extra_tax_rate ?? 0), $profile?->extra_vat_treatment ?? 'non_recoverable');

        return ['catalog_item_id' => $item?->id, 'description' => $line['description'] ?? $item?->name ?? 'Servicio manual', 'kind' => $item?->kind ?? 'service', 'unit' => $item?->unit ?? 'unit', 'track_stock' => $item?->track_stock ?? false, 'quantity' => $quantity, 'profile_id' => $profile?->id, 'unit_net_minor' => $unit, 'exact_cost_minor' => $exact, 'cost_minor' => $exact !== null ? CostCalculator::round($exact) : null, 'source' => $manual ? 'manual' : $profile?->source, 'supplier' => $profile?->supplier_name, 'supplier_tax_id' => $profile?->supplier_tax_id, 'reference' => $profile?->reference, 'cost_date' => $profile?->cost_date?->toDateString(), 'valid_until' => $profile?->valid_until?->toDateString(), 'vat_treatment' => $profile?->vat_treatment, 'purchase_tax_rate' => $profile?->purchase_tax_rate, 'paid_unit_minor' => $profile?->purchase_minor, 'extra_unit_minor' => $freight, 'extra_effective_unit_minor' => $extraNet, 'stale' => $profile?->expired() ?? false];
    }
}
