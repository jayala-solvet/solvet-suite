<x-filament-panels::page>
@php($order=$this->getRecord())
<div class="sv-document">
<p class="sv-footnote">{{ ['completed'=>'Ejecución completada','cancelled'=>'Pedido anulado','confirmed'=>'Pedido en ejecución'][$order->status] }} · {{ $order->quote->reference() }} · {{ $order->quote->subject }}</p>
<x-ui.panel title="Componentes y cumplimiento" description="La venta se cuenta una sola vez desde el presupuesto. Los componentes detallan la ejecución interna.">
<div class="sv-table-wrap"><table class="sv-table"><thead><tr><th>Intervención / ítem</th><th class="amount">Pedido</th><th class="amount">Reservado</th><th class="amount">Realizado / entregado</th></tr></thead><tbody>
@foreach($order->lines as $line)<tr><td>{{ $line->description }}<small>Intervención #{{ $line->id }} · {{ \App\Modules\Catalog\Models\CatalogItem::UNITS[$line->unit]??$line->unit }} · {{ $line->track_stock?'Bien con stock':'Sin stock' }}</small></td><td class="amount">{{ \App\Modules\Operations\Services\OperationRules::quantityLabel($line->quantity) }}</td><td class="amount">{{ \App\Modules\Operations\Services\OperationRules::quantityLabel($line->reserved_quantity) }}</td><td class="amount">{{ \App\Modules\Operations\Services\OperationRules::quantityLabel($line->done_quantity) }}</td></tr>@endforeach
</tbody></table></div>
</x-ui.panel>
@can('view-costs')
@php($result=$this->result())
<x-ui.panel title="Resultado operativo del pedido" description="Venta acordada sin IVA frente a costos registrados. No es utilidad neta ni dinero cobrado.">
<div class="sv-cost-summary"><dl class="sv-totals">
<div><dt>Venta acordada sin IVA</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($result['sales_net_minor'],$order->currency) }}</dd></div>
<div><dt>Costo estimado original</dt><dd>{{ ($result['estimated']['complete']??false)?\App\Modules\Quotes\Models\Quote::money($result['estimated']['cost_minor'],$order->currency):'Sin costos completos' }}</dd></div>
<div><dt>Costo ejecutado registrado{{ $result['complete']?'':' (parcial)' }}</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($result['cost_minor']),$order->currency) }}</dd></div>
@if($result['complete'])<div class="sv-total"><dt>Utilidad bruta de ejecución</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($result['profit_minor']),$order->currency) }}</dd></div><div><dt>Margen sobre venta</dt><dd>{{ $result['margin_percent']!==null?str_replace('.',',',$result['margin_percent']).'%':'No aplica' }}</dd></div>@endif
</dl><p>{{ $result['complete']?'Ejecución cerrada con todas las cantidades cumplidas.':'Faltan entregas o servicios: no se presenta el resultado parcial como margen final.' }} Se consideran los costos cargados, sin gastos generales no registrados ni impuestos a la renta.</p></div>
</x-ui.panel>
<x-ui.panel title="Resultado por línea comercial" description="Los componentes suman costos dentro de su línea, sin duplicar su venta.">
<div class="sv-table-wrap"><table class="sv-table"><thead><tr><th>Línea</th><th class="amount">Venta sin IVA</th><th class="amount">Costo ejecutado</th><th class="amount">Utilidad bruta</th><th class="amount">Margen</th></tr></thead><tbody>
@foreach($result['commercial_lines'] as $row)<tr><td>{{ $row['description'] }}</td><td class="amount">{{ \App\Modules\Quotes\Models\Quote::money($row['sales_net_minor'],$order->currency) }}</td><td class="amount">{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($row['cost_minor']),$order->currency) }}</td><td class="amount">{{ $result['complete']?\App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($row['profit_minor']),$order->currency):'Pendiente de cierre' }}</td><td class="amount">{{ $result['complete']&&$row['margin_percent']!==null?str_replace('.',',',$row['margin_percent']).'%':'—' }}</td></tr>@endforeach
</tbody></table></div>
</x-ui.panel>
<x-ui.panel title="Servicios y costos realizados" description="Las compras vinculadas se imputan al confirmar su recepción; no deben volver a registrarse como trabajo propio.">
<div class="sv-table-wrap"><table class="sv-table"><thead><tr><th>Fecha / registro</th><th>Detalle</th><th class="amount">Cantidad</th><th class="amount">Costo</th><th>Estado</th></tr></thead><tbody>@foreach($this->entries() as $entry)<tr><td>{{ $entry->created_at->format('d/m/Y H:i') }}<small>#{{ $entry->id }} · {{ $entry->actor?->name??('Usuario #'.$entry->actor_id) }}</small></td><td>{{ $entry->reference }}@if($entry->purchase_id)<small>Compra #{{ $entry->purchase_id }}</small>@endif</td><td class="amount">{{ \App\Modules\Operations\Services\OperationRules::quantityLabel($entry->quantity) }}</td><td class="amount">{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($entry->cost_minor),$order->currency) }}</td><td>{{ $entry->voided_at?'Anulado: '.$entry->void_reason:'Vigente' }}</td></tr>@endforeach</tbody></table></div>
</x-ui.panel>
@endcan
</div>
</x-filament-panels::page>
