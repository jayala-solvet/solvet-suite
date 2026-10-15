<div class="sv-live-totals" aria-live="polite">
    @if($totals)
        <dl class="sv-totals">
            @foreach(['subtotal_minor' => 'Subtotal', 'discount_minor' => 'Descuentos', 'tax_minor' => 'IVA incluido', 'total_minor' => 'Total '.$currency] as $field => $label)
                <div @class(['sv-total' => $field === 'total_minor'])><dt>{{ $label }}</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($totals[$field], $currency) }}</dd></div>
            @endforeach
        </dl>
        <p>Precios con IVA incluido. El cálculo se valida nuevamente al guardar.</p>
    @else
        <p>Completá los ítems con valores válidos para calcular el total.</p>
    @endif
</div>
