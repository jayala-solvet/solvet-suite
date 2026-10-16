<x-filament-panels::page>
<div class="sv-document">
@forelse($this->documents() as $doc)
<x-ui.panel :title="$doc->title" :description="'Versión '.$doc->version.' · publicada '.$doc->published_at->format('d/m/Y').' · '.($doc->effect==='optional'?'Finalidad opcional':($doc->effect==='contract'?'Condiciones de uso':'Información de privacidad; no es consentimiento'))">
<div style="white-space: pre-wrap">{{ $doc->body }}</div>
<p class="sv-history-hash">Identificador de integridad: {{ $doc->sha256 }}</p>
<div class="sv-actions">
@if($doc->effect==='contract')<x-filament::button wire:click="record({{ $doc->id }}, 'accepted')" wire:confirm="Confirmás que revisaste y aceptás esta versión de los términos?">Aceptar esta versión</x-filament::button>
@elseif($doc->effect==='optional')
@if($this->lastAction($doc->id)==='consented')<x-filament::button color="gray" wire:click="record({{ $doc->id }}, 'withdrawn')">Retirar autorización</x-filament::button>
@else<x-filament::button wire:click="record({{ $doc->id }}, 'consented')" wire:confirm="Querés autorizar esta finalidad opcional?">Autorizar finalidad opcional</x-filament::button>@endif
@else<x-filament::button color="gray" wire:click="record({{ $doc->id }}, 'acknowledged')">Dejar constancia de lectura</x-filament::button>@endif
</div>
</x-ui.panel>
@empty<x-ui.panel title="Documentación pendiente" description="No hay versiones legales publicadas. Los borradores requieren datos completos y revisión jurídica antes de su publicación." />@endforelse
<x-ui.panel title="Tu historial" description="Cada evento conserva la versión y su huella. Fechas en America/Asuncion.">
<div class="sv-table-wrap"><table class="sv-table"><thead><tr><th>Fecha</th><th>Documento</th><th>Acción</th><th>Versión / preferencias</th></tr></thead><tbody>
@php($history=$this->history())
@foreach($history as $event)<tr><td>{{ $event->created_at->timezone('America/Asuncion')->format('d/m/Y H:i:s P') }}</td><td><a href="{{ route('legal.document',$event->legal_document_id) }}">{{ $event->purpose }}</a></td><td>{{ ['accepted'=>'Aceptación contractual','acknowledged'=>'Constancia de lectura','consented'=>'Consentimiento opcional','withdrawn'=>'Retiro'][$event->action] }}</td><td>{{ $event->document->version }}@if($event->action==='consented' && $this->lastAction($event->legal_document_id)==='consented') <button type="button" wire:click="record({{ $event->legal_document_id }}, 'withdrawn')">Retirar autorización de esta versión</button>@endif</td></tr>@endforeach
</tbody></table></div>
<x-filament::pagination :paginator="$history" />
</x-ui.panel>
</div>
</x-filament-panels::page>
