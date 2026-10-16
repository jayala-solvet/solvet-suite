<?php

namespace App\Modules\Costs\Services;

use App\Modules\Costs\Models\CostProfile;
use App\Modules\Quotes\Services\QuoteCalculator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CostCalculator
{
    public static function round(string $minor): string
    {
        return bccomp($minor, '0', 8) < 0 ? bcsub($minor, '0.5', 0) : bcadd($minor, '0.5', 0);
    }

    public static function toMinor(string $amount, string $currency): string
    {
        Validator::make(['amount' => $amount], ['amount' => ['required', 'regex:'.QuoteCalculator::pricePattern($currency)]])->validate();

        return bcmul($amount, $currency === 'USD' ? '100' : '1', 0);
    }

    public function priceForProfile(CostProfile $profile, int $salesTax): ?string
    {
        $factor = $profile->currency === 'USD' ? '100' : '1';
        $scale = $profile->currency === 'USD' ? 2 : 0;

        return $this->calculate([
            'source' => $profile->source, 'unit_cost' => bcdiv($profile->purchase_minor, $factor, $scale),
            'extra_cost' => bcdiv($profile->extra_minor, $factor, $scale), 'purchase_tax_rate' => $profile->purchase_tax_rate,
            'vat_treatment' => $profile->vat_treatment, 'pricing_mode' => $profile->pricing_mode, 'pricing_value' => $profile->pricing_value,
        ], $profile->currency, $salesTax)['suggested_price_minor'];
    }

    public function calculate(array $data, string $currency, int $salesTax): array
    {
        Validator::make(array_merge($data, ['currency' => $currency, 'sales_tax' => $salesTax]), [
            'currency' => 'required|in:PYG,USD', 'sales_tax' => 'required|in:0,5,10',
            'source' => 'required|in:purchase,own,external', 'unit_cost' => ['required', 'regex:'.QuoteCalculator::pricePattern($currency)],
            'extra_cost' => ['required', 'regex:'.QuoteCalculator::pricePattern($currency)],
            'purchase_tax_rate' => 'required|in:0,5,10', 'vat_treatment' => 'required|in:recoverable,non_recoverable,pending',
            'pricing_mode' => 'required|in:margin,markup,fixed_profit',
            'pricing_value' => ['required', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
        ])->validate();
        if ($data['pricing_mode'] === 'margin' && bccomp((string) $data['pricing_value'], '100', 2) >= 0) {
            throw ValidationException::withMessages(['pricing_value' => 'El margen debe ser menor al 100%.']);
        }
        if ($data['pricing_mode'] === 'markup' && bccomp((string) $data['pricing_value'], '1000', 2) > 0) {
            throw ValidationException::withMessages(['pricing_value' => 'El recargo máximo es 1000%.']);
        }
        if ($data['source'] === 'own' && ((int) $data['purchase_tax_rate'] !== 0 || $data['vat_treatment'] !== 'non_recoverable')) {
            throw ValidationException::withMessages(['purchase_tax_rate' => 'El trabajo propio es costo interno: no genera IVA crédito.']);
        }
        if ($data['pricing_mode'] === 'fixed_profit') {
            Validator::make($data, ['pricing_value' => ['required', 'regex:'.QuoteCalculator::pricePattern($currency)]])->validate();
        }
        $purchase = self::toMinor((string) $data['unit_cost'], $currency);
        $extra = self::toMinor((string) $data['extra_cost'], $currency);
        if ($data['vat_treatment'] === 'pending') {
            return ['purchase_minor' => $purchase, 'extra_minor' => $extra, 'effective_unit_minor' => null, 'suggested_price_minor' => null];
        }
        $effective = $data['vat_treatment'] === 'recoverable'
            ? bcdiv(bcmul($purchase, '100', 8), (string) (100 + (int) $data['purchase_tax_rate']), 8) : $purchase;
        $effective = bcadd($effective, $extra, 8);
        $netSale = match ($data['pricing_mode']) {
            'margin' => bcdiv(bcmul($effective, '100', 8), bcsub('100', (string) $data['pricing_value'], 2), 8),
            'markup' => bcmul($effective, bcadd('1', bcdiv((string) $data['pricing_value'], '100', 6), 6), 8),
            'fixed_profit' => bcadd($effective, self::toMinor((string) $data['pricing_value'], $currency), 8),
        };
        // Round price upward to avoid quoting below the requested margin through currency rounding.
        $gross = bcdiv(bcmul($netSale, (string) (100 + $salesTax), 8), '100', 8);
        $price = bcadd($gross, '0', 0);
        if (bccomp($gross, $price, 8) > 0) {
            $price = bcadd($price, '1', 0);
        }
        $max = $currency === 'USD' ? '99999999999' : '999999999';
        if (bccomp($price, $max, 0) > 0) {
            throw ValidationException::withMessages(['pricing_value' => 'El precio calculado supera el límite del catálogo.']);
        }

        return ['purchase_minor' => $purchase, 'extra_minor' => $extra, 'effective_unit_minor' => bcadd($effective, '0', 8), 'suggested_price_minor' => $price];
    }
}
