<x-filament-panels::page>
    @php($summary = $this->summary())

    <div class="sv-workspace">
        <section class="sv-context" aria-labelledby="sv-workspace-heading">
            <div>
                <p class="sv-eyebrow">SOLVET-IT · GESTIÓN COMERCIAL</p>
                <h2 id="sv-workspace-heading">Todo listo para tu próxima propuesta.</h2>
                <p>Prepará presupuestos, organizá tus clientes y mantené a la vista cada decisión.</p>
            </div>
            @if(auth()->user()->canWrite())
                <a class="sv-primary" href="{{ \App\Filament\Resources\QuoteResource::getUrl('create') }}">
                    @svg('heroicon-o-plus') Nuevo presupuesto
                </a>
            @endif
        </section>

        <section class="sv-stat-grid" aria-label="Resumen de actividad">
            <x-ui.stat-card label="En preparación" :value="$summary['draft']" note="Borradores por completar" icon="heroicon-o-pencil-square" tone="warning" />
            <x-ui.stat-card label="Esperando respuesta" :value="$summary['issued']" note="Presupuestos emitidos" icon="heroicon-o-paper-airplane" />
            <x-ui.stat-card label="Aceptados" :value="$summary['accepted']" note="Aprobaciones registradas" icon="heroicon-o-check-circle" tone="success" />
            <x-ui.stat-card label="Clientes" :value="$summary['customers']" note="Contactos en tu cartera" icon="heroicon-o-building-office-2" />
        </section>

        <div class="sv-main-grid">
            <div>
                <x-ui.panel title="Presupuestos recientes" description="Los últimos documentos de tu actividad comercial.">
                    <x-slot:actions>
                        <a href="{{ \App\Filament\Resources\QuoteResource::getUrl('index') }}">Ver todos @svg('heroicon-o-arrow-right')</a>
                    </x-slot:actions>
                    <div class="sv-table-wrap">
                        <table class="sv-table">
                            <thead>
                                <tr><th>Documento / referencia</th><th>Cliente</th><th>Estado</th><th class="amount">Total</th></tr>
                            </thead>
                            <tbody>
                                @forelse($this->recent() as $quote)
                                    <tr>
                                        <td><strong>{{ $quote->reference() }}</strong><small>{{ $quote->subject }}</small></td>
                                        <td>{{ $quote->customer->name }}</td>
                                        <td><x-ui.status :state="$quote->status" :label="$quote->statusLabel()" /></td>
                                        <td class="amount">{{ $quote->formattedTotal() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <x-ui.empty-state title="Tu primer presupuesto empieza aquí" description="Registrá un cliente y prepará tu primera propuesta." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.panel>
                <p class="sv-footnote">Los precios incluyen IVA. Cada presupuesto emitido conserva su PDF y los datos comerciales de ese momento.</p>
            </div>

            <aside class="sv-aside-stack" aria-label="Guía de trabajo y módulos">
                <x-ui.panel title="Tu flujo de trabajo">
                    <ol class="sv-steps">
                        <li class="sv-step"><span class="sv-step-num" aria-hidden="true">01</span><div><strong>Prepará la propuesta</strong><p>Cliente, alcance, precios y condiciones.</p></div></li>
                        <li class="sv-step"><span class="sv-step-num" aria-hidden="true">02</span><div><strong>Emití el presupuesto</strong><p>Correlativo y PDF conservado. Compartilo con tu cliente.</p></div></li>
                        <li class="sv-step"><span class="sv-step-num" aria-hidden="true">03</span><div><strong>Registrá la respuesta</strong><p>Guardá la decisión y su referencia en el historial.</p></div></li>
                    </ol>
                </x-ui.panel>
                <x-ui.panel title="Módulos de tu sistema">
                    <ul class="sv-module-list">
                        <li class="sv-module">Presupuestos <span class="on">Disponible</span></li>
                        <li class="sv-module">Clientes <span class="on">Disponible</span></li>
                        <li class="sv-module">Órdenes de trabajo <span>Previsto</span></li>
                        <li class="sv-module">Integración GLPI <span>Previsto</span></li>
                    </ul>
                </x-ui.panel>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
