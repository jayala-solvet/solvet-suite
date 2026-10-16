<?php

namespace App\Modules\Costs\Services;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Costs\Models\CostProfile;
use App\Modules\Quotes\Services\QuoteCalculator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuoteProfitability
{
    public function preview(User $actor, array $lines, string $currency): array
    {
        Gate::forUser($actor)->authorize('view-costs');
        $calculated = app(QuoteCalculator::class)->calculate($lines, $currency);

        return $this->snapshot($actor, $lines, $calculated, $currency);
    }

    public function snapshot(User $actor, array $raw, array $calculated, string $currency): array
    {
        Gate::forUser($actor)->authorize('view-costs');
        Validator::make(['lines' => $raw], [
            'lines.*.cost_profile_id' => 'nullable|integer|exists:cost_profiles,id',
            'lines.*.internal_unit_cost' => ['nullable', 'regex:'.QuoteCalculator::pricePattern($currency)],
        ])->validate();
        $items = CatalogItem::with('currentCost')->whereIn('id', array_filter(array_column($raw, 'catalog_item_id')))->get()->keyBy('id');
        $profiles = CostProfile::whereIn('id', array_filter(array_column($raw, 'cost_profile_id')))->get()->keyBy('id');
        $cost = '0';
        $covered = $stale = 0;
        $lines = [];
        foreach (array_values($raw) as $index => $line) {
            $item = $items->get($line['catalog_item_id'] ?? null);
            $profile = ! empty($line['cost_profile_id']) ? $profiles->get($line['cost_profile_id']) : $item?->currentCost;
            if (! empty($line['catalog_item_id']) && (! $item || $item->currency !== $currency)) {
                throw ValidationException::withMessages(['lines.'.$index.'.catalog_item_id' => 'El catálogo no corresponde a esta moneda.']);
            }
            if ($profile && (! $item || (int) $profile->catalog_item_id !== (int) $item->id || $profile->currency !== $currency)) {
                throw ValidationException::withMessages(['lines.'.$index.'.cost_profile_id' => 'El costo no corresponde al ítem y moneda de esta línea.']);
            }
            $unit = $profile?->effective_unit_minor;
            $manual = ! $item && isset($line['internal_unit_cost']) && $line['internal_unit_cost'] !== '';
            if ($manual) {
                $unit = CostCalculator::toMinor((string) $line['internal_unit_cost'], $currency);
            }
            $lineCost = $unit !== null ? CostCalculator::round(bcmul($unit, $calculated['lines'][$index]['quantity'], 8)) : null;
            if ($lineCost !== null) {
                $covered++;
                $cost = bcadd($cost, $lineCost, 0);
            }
            $expired = $profile?->expired() ?? false;
            $stale += (int) $expired;
            $lines[] = [
                'catalog_item_id' => $item?->id, 'profile_id' => $profile?->id,
                'unit_net_minor' => $unit, 'cost_minor' => $lineCost,
                'source' => $manual ? 'manual' : $profile?->source,
                'supplier' => $profile?->supplier_name, 'reference' => $profile?->reference,
                'cost_date' => $profile?->cost_date?->toDateString(), 'valid_until' => $profile?->valid_until?->toDateString(),
                'vat_treatment' => $profile?->vat_treatment, 'stale' => $expired,
            ];
        }
        $complete = $covered === count($raw);
        $sales = bcsub($calculated['total_minor'], $calculated['tax_minor'], 0);
        $profit = $complete ? bcsub($sales, $cost, 0) : null;

        return ['version' => 1, 'currency' => $currency, 'captured_at' => now()->toIso8601String(), 'lines' => $lines,
            'covered_lines' => $covered, 'total_lines' => count($raw), 'stale_lines' => $stale, 'complete' => $complete,
            'cost_minor' => $cost, 'sales_net_minor' => $sales, 'profit_minor' => $profit,
            'margin_percent' => $complete && bccomp($sales, '0', 0) > 0 ? bcdiv(bcmul($profit, '100', 2), $sales, 2) : null];
    }
}
