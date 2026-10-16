<?php

namespace App\Modules\Quotes\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuoteCalculator
{
    public static function pricePattern(string $currency): string
    {
        return $currency === 'PYG' ? '/^\d{1,9}(\.0{1,2})?$/' : '/^\d{1,9}(\.\d{1,2})?$/';
    }

    private function round(string $value): string
    {
        return bcadd($value, '0.5', 0);
    }

    public function calculate(array $lines, string $currency): array
    {
        Validator::make(['currency' => $currency, 'lines' => $lines], [
            'currency' => 'required|in:PYG,USD', 'lines' => 'required|array|min:1|max:100',
            'lines.*.description' => 'required|string|max:1000',
            'lines.*.quantity' => ['required', 'regex:/^\d{1,6}(\.\d{1,3})?$/', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'regex:/^\d{1,9}(\.\d{1,2})?$/', 'numeric', 'gte:0'],
            'lines.*.discount' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'lines.*.tax_rate' => 'required|in:0,5,10',
        ])->validate();
        $scale = $currency === 'USD' ? 2 : 0;
        $factor = (string) (10 ** $scale);
        $subtotal = $discount = $total = $tax = '0';
        $normalized = [];
        foreach (array_values($lines) as $line) {
            if ($currency === 'PYG' && bccomp((string) $line['unit_price'], bcadd((string) $line['unit_price'], '0', 0), 2) !== 0) {
                throw ValidationException::withMessages(['lines' => 'Los precios en guaraníes deben ser enteros.']);
            }
            $gross = $this->round(bcmul(bcmul((string) $line['quantity'], (string) $line['unit_price'], 5), $factor, 5));
            $off = $this->round(bcdiv(bcmul($gross, (string) $line['discount'], 4), '100', 4));
            $net = bcsub($gross, $off, 0);
            $vat = $this->round(bcdiv(bcmul($net, (string) $line['tax_rate'], 4), (string) (100 + (int) $line['tax_rate']), 4));
            $subtotal = bcadd($subtotal, $gross, 0);
            $discount = bcadd($discount, $off, 0);
            $total = bcadd($total, $net, 0);
            $tax = bcadd($tax, $vat, 0);
            $normalized[] = ['description' => $line['description'], 'quantity' => (string) $line['quantity'], 'unit_price' => (string) $line['unit_price'], 'discount' => (string) $line['discount'], 'tax_rate' => (string) $line['tax_rate'], 'total_minor' => $net, 'tax_minor' => $vat, 'catalog_item_id' => $line['catalog_item_id'] ?? null, 'sku' => $line['sku'] ?? null];
        }

        return ['lines' => $normalized, 'subtotal_minor' => $subtotal, 'discount_minor' => $discount, 'total_minor' => $total, 'tax_minor' => $tax];
    }
}
