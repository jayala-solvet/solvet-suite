<div class="sv-cost-summary" aria-live="polite">
    @if($item)<p>{{ $item->name }} · {{ $currency }} · {{ $item->unit === 'hour' ? 'Por hora' : 'Por unidad' }} · Venta con IVA {{ $item->tax_rate }}%</p>@endif
    @if($calculation && $calculation['effective_unit_minor'] !== null)
        @php($breakdown=app(\App\Modules\Costs\Services\CostCalculator::class)->breakdown($calculation,(int)$item->tax_rate))
        <dl class="sv-totals"><div><dt>Importe informado más gastos con IVA</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($breakdown['paid_minor'],$currency) }}</dd></div><div><dt>IVA recuperable previsto</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($breakdown['credit_minor']),$currency) }}</dd></div><div><dt>Costo efectivo sin IVA recuperable</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($calculation['effective_unit_minor']), $currency) }}</dd></div>
        <div class="sv-total"><dt>Precio sugerido con IVA</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($calculation['suggested_price_minor'], $currency) }}</dd></div>
        <div><dt>Venta sin IVA</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($breakdown['sales_net_minor']),$currency) }}</dd></div>
        <div><dt>IVA de venta</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($breakdown['sales_vat_minor']),$currency) }}</dd></div>
        <div><dt>Utilidad bruta por unidad</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($breakdown['profit_minor']),$currency) }}</dd></div><div><dt>Margen sobre venta</dt><dd>{{ $breakdown['margin_percent']!==null?str_replace('.',',',$breakdown['margin_percent']).'%':'No aplica' }}</dd></div></dl>
        @if(bccomp($breakdown['profit_minor'],'0',8)<0)<p class="sv-cost-warning">El precio final está por debajo del costo efectivo.</p>@endif
        <p>La regla porcentual se redondea hacia arriba; el precio manual se conserva. Guardar esta referencia no publica el precio: usá Aplicar precio al catálogo.</p>
    @elseif($calculation)<p>Definí el tratamiento del IVA para calcular costo y precio. Podés guardar la referencia sin aplicar un precio.</p>
    @else<p>Completá los valores para calcular. PYG entero; USD hasta dos decimales.</p>@endif
</div>
