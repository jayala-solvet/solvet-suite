<x-filament-panels::page>
@php($item=$this->getRecord())
@php($movements=$this->movements())
<x-ui.panel title="Existencias y movimientos" :description="'Físico: '.($item->stock?->quantity??'0.000').' · Reservado: '.($item->stock?->reserved??'0.000').' · Disponible: '.($item->stock?->available()??'0.000')">
<div class="sv-table-wrap"><table class="sv-table"><thead><tr><th>Fecha / usuario</th><th>Movimiento / documento</th><th class="amount">Cantidad</th>@can('view-costs')<th class="amount">Valor aplicado</th>@endcan</tr></thead><tbody>
@foreach($movements as $move)<tr><td>{{ $move->created_at->format('d/m/Y H:i:s') }}<small>{{ $move->actor?->name??('Usuario #'.$move->actor_id) }}</small></td><td>{{ ['receipt'=>'Recepción','delivery'=>'Entrega','adjustment'=>'Ajuste','customer_return'=>'Devolución cliente','supplier_return'=>'Devolución proveedor'][$move->kind]??$move->kind }}<small>{{ $move->reason }}</small>@if($move->purchase_id)<small>Compra #{{ $move->purchase_id }}</small>@endif @if($move->order_line_id)<small>Intervención #{{ $move->order_line_id }}</small>@endif</td><td class="amount">{{ \App\Modules\Operations\Services\OperationRules::quantityLabel($move->quantity) }}</td>@can('view-costs')<td class="amount">{{ \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($move->value_minor),$item->currency) }}</td>@endcan</tr>@endforeach
</tbody></table></div>
</x-ui.panel>
<x-filament::pagination :paginator="$movements" />
<p class="sv-footnote">Se muestran hasta 25 movimientos recientes por página de consulta. Las reservas no son entradas ni salidas. El promedio solo cambia por movimientos valorados, nunca por una cotización de proveedor.</p>
</x-filament-panels::page>
