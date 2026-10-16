<x-filament-panels::page>
@php($purchase=$this->getRecord())
<div class="sv-document">
<p class="sv-footnote">{{ ['draft'=>'Borrador','ordered'=>'Confirmada, pendiente de recepción','received'=>'Recibida','cancelled'=>'Anulada'][$purchase->status] }} · {{ $purchase->purchase_date->format('d/m/Y') }} · {{ $purchase->currency }}</p>
<x-ui.panel :title="$purchase->supplier_name" :description="'RUC '.$purchase->supplier_tax_id.' · '.$purchase->reference">
<div class="sv-table-wrap"><table class="sv-table"><thead><tr><th>Ítem / pedido</th><th class="amount">Cantidad</th><th class="amount">Proveedor con IVA</th><th class="amount">IVA recuperable previsto</th><th class="amount">Envío neto</th><th class="amount">Costo efectivo total</th></tr></thead><tbody>
@foreach($purchase->lines as $line)<tr><td>{{ $line['description'] }}<small>{{ $line['kind']==='service'?'Servicio sin stock':($line['track_stock']?'Bien con stock':'Bien sin stock') }} · {{ \App\Modules\Catalog\Models\CatalogItem::UNITS[$line['unit']]??$line['unit'] }}@if($line['order_line_id']) · Intervención #{{ $line['order_line_id'] }}@endif</small></td><td class="amount">{{ \App\Modules\Operations\Services\OperationRules::quantityLabel($line['quantity']) }}</td><td class="amount">{{ \App\Modules\Quotes\Models\Quote::money($line['gross_minor'],$purchase->currency) }}</td><td class="amount">{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round(bcsub($line['gross_minor'],$line['base_minor'],8)),$purchase->currency) }}</td><td class="amount">{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($line['freight_net_minor']),$purchase->currency) }}</td><td class="amount">{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($line['effective_total_minor']),$purchase->currency) }}</td></tr>@endforeach
</tbody></table></div>
<dl class="sv-totals"><div><dt>Envío con IVA</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($purchase->freight_minor,$purchase->currency) }}</dd></div><div class="sv-total"><dt>Total informado</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($purchase->total_minor,$purchase->currency) }}</dd></div></dl>
</x-ui.panel>
<p class="sv-footnote">Los gastos adicionales se agregan una sola vez. La recepción es completa y única para esta compra; para entregas parciales del proveedor registrá documentos separados. Los servicios no generan stock. Una cotización no revaloriza existencias.</p>
</div>
</x-filament-panels::page>
