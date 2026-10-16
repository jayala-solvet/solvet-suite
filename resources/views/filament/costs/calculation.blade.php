<div class="sv-cost-summary" aria-live="polite">
    @if($item)<p>{{ $item->name }} · {{ $currency }} · {{ $item->unit === 'hour' ? 'Por hora' : 'Por unidad' }} · Venta con IVA {{ $item->tax_rate }}%</p>@endif
    @if($calculation && $calculation['effective_unit_minor'] !== null)
        <dl class="sv-totals"><div><dt>Costo efectivo sin IVA recuperable</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($calculation['effective_unit_minor']), $currency) }}</dd></div>
        <div class="sv-total"><dt>Precio sugerido con IVA</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($calculation['suggested_price_minor'], $currency) }}</dd></div></dl>
        <p>Precio redondeado hacia arriba a la unidad mínima de la moneda. El porcentaje se valida nuevamente al guardar.</p>
    @elseif($calculation)<p>Definí el tratamiento del IVA para calcular costo y precio. Podés guardar la referencia sin aplicar un precio.</p>
    @else<p>Completá los valores para calcular. PYG entero; USD hasta dos decimales.</p>@endif
</div>
