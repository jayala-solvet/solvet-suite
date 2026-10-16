<div class="sv-cost-summary" aria-live="polite">
    <p><strong>Rentabilidad interna · estimación</strong> · No se incluye en el PDF del cliente.</p>
    @if($profitability)
        <p>Costos definidos: {{ $profitability['covered_lines'] }} de {{ $profitability['total_lines'] }} ítems.</p>
        @if($profitability['stale_lines'])<p class="sv-cost-warning">Hay {{ $profitability['stale_lines'] }} costos con vigencia vencida al guardar. Revisá su precio con el proveedor.</p>@endif
        @if($profitability['complete'])
            <dl class="sv-totals">
                <div><dt>Venta sin IVA</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($profitability['sales_net_minor'], $currency) }}</dd></div>
                <div><dt>Costo efectivo estimado</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($profitability['cost_minor'], $currency) }}</dd></div>
                <div class="sv-total"><dt>Utilidad bruta estimada</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($profitability['profit_minor'], $currency) }}</dd></div>
                <div><dt>Margen sobre venta sin IVA</dt><dd>{{ $profitability['margin_percent'] !== null ? str_replace('.', ',', $profitability['margin_percent']).'%' : 'No aplica: venta cero' }}</dd></div>
            </dl>
            @if(bccomp($profitability['profit_minor'], '0', 0) < 0)<p class="sv-cost-warning">La propuesta está por debajo del costo estimado. Revisá precio, descuento y costos antes de emitir.</p>@endif
        @else<p class="sv-cost-warning">Faltan costos o su tratamiento de IVA. No se calcula una utilidad total hasta completar todos los ítems.</p>@endif
    @else<p>Sin estimación de costos guardada. Completá los ítems o registrá sus costos en un borrador. Los documentos anteriores no se recalculan.</p>@endif
    <p>Costos y descuentos incluidos en la estimación. No descuenta gastos generales no cargados ni impuestos a la renta. No es una liquidación de IVA.</p>
</div>
