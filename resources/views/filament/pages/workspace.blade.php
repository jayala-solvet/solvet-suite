<x-filament-panels::page>
 @php($s=$this->summary())
 <div>
  <section class="sv-context">
   <div><div class="sv-eyebrow">SOLVET-IT · GESTIÓN COMERCIAL</div><h2>Todo listo para tu próxima propuesta.</h2><p>Prepará presupuestos, organizá tus clientes y mantené a la vista cada decisión.</p></div>
   @if(auth()->user()->canWrite())<a class="sv-primary" href="{{ \App\Filament\Resources\QuoteResource::getUrl('create') }}">@svg('heroicon-o-plus') Nuevo presupuesto</a>@endif
  </section>
  <section class="sv-stat-grid" aria-label="Resumen de actividad">
   @foreach([['draft','En preparación','Borradores por completar','heroicon-o-pencil-square','orange'],['issued','Esperando respuesta','Presupuestos emitidos','heroicon-o-paper-airplane',''],['accepted','Aceptados','Aprobaciones registradas','heroicon-o-check-circle','green'],['customers','Clientes','Contactos en tu cartera','heroicon-o-building-office-2','']] as [$key,$label,$note,$icon,$color])
    <div class="sv-stat"><div><div class="sv-stat-label">{{ $label }}</div><div class="sv-stat-number">{{ $s[$key] }}</div><div class="sv-stat-note">{{ $note }}</div></div><div class="sv-icon {{ $color }}">@svg($icon)</div></div>
   @endforeach
  </section>
  <div class="sv-main-grid">
   <div>
    <section class="sv-panel"><div class="sv-panel-head"><h3>Presupuestos recientes</h3><a href="{{ \App\Filament\Resources\QuoteResource::getUrl('index') }}">Ver todos →</a></div>
     <div class="sv-table-wrap"><table class="sv-table"><thead><tr><th>Documento / referencia</th><th>Cliente</th><th>Estado</th><th style="text-align:right">Total</th></tr></thead><tbody>
      @forelse($this->recent() as $q)<tr><td><strong>{{ $q->reference() }}</strong><small>{{ $q->subject }}</small></td><td>{{ $q->customer->name }}</td><td><span class="sv-status {{ $q->status }}">{{ $q->statusLabel() }}</span></td><td class="amount">{{ $q->formattedTotal() }}</td></tr>
      @empty<tr><td colspan="4"><div class="sv-empty">Todavía no hay presupuestos. Registrá un cliente y prepará tu primera propuesta.</div></td></tr>@endforelse
     </tbody></table></div>
    </section>
    <p class="sv-footnote">Los precios se gestionan con IVA incluido. Cada presupuesto emitido conserva su PDF y los datos comerciales de ese momento.</p>
   </div>
   <aside>
    <section class="sv-panel"><div class="sv-panel-head"><h3>Tu flujo de trabajo</h3></div>
     <div class="sv-step"><span class="sv-step-num">01</span><div><strong>Prepará la propuesta</strong><p>Cliente, alcance, precios y condiciones.</p></div></div>
     <div class="sv-step"><span class="sv-step-num">02</span><div><strong>Emití el presupuesto</strong><p>Correlativo y PDF conservado. Compartilo con tu cliente.</p></div></div>
     <div class="sv-step"><span class="sv-step-num">03</span><div><strong>Registrá la respuesta</strong><p>Guardá la decisión y su referencia para mantener el historial.</p></div></div>
    </section>
    <section class="sv-panel" style="margin-top:18px"><div class="sv-panel-head"><h3>Módulos de tu sistema</h3></div><div class="sv-module-list"><div class="sv-module">Presupuestos <span class="on">Disponible</span></div><div class="sv-module">Clientes <span class="on">Disponible</span></div><div class="sv-module">Órdenes de trabajo <span>Previsto</span></div><div class="sv-module">Integración GLPI <span>Previsto</span></div></div></section>
   </aside>
  </div>
 </div>
</x-filament-panels::page>
