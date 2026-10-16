<x-filament-panels::page>
    @php
        $report = $this->report();
        $money = fn ($value) => \App\Modules\Quotes\Models\Quote::money($value, $this->currency);
        $buckets = $report['buckets'];
    @endphp
    <div class="sv-dashboard">
        <div class="sv-dashboard-toolbar">
            <div><p class="sv-dashboard-eyebrow">PRESUPUESTOS · VISTA GENERAL</p><p class="sv-footnote">Emitidos del {{ $report['range'] }} · estado actual</p></div>
            <div class="sv-dashboard-filters">
                <div class="sv-dashboard-filter"><label for="dashboard-period">Período de emisión</label><x-filament::input.wrapper><x-filament::input.select id="dashboard-period" wire:model.live="period">
                    @foreach(\App\Modules\Dashboard\Services\CommercialDashboard::PERIODS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </x-filament::input.select></x-filament::input.wrapper></div>
                <div class="sv-dashboard-filter"><label for="dashboard-currency">Moneda</label><x-filament::input.wrapper><x-filament::input.select id="dashboard-currency" wire:model.live="currency"><option value="PYG">Guaraníes · PYG</option><option value="USD">Dólares · USD</option></x-filament::input.select></x-filament::input.wrapper></div>
            </div>
        </div>
        <div wire:loading wire:target="period,currency" role="status" class="sv-footnote">Actualizando resumen…</div>
        <div wire:loading.class="sv-dashboard-loading" wire:target="period,currency" class="sv-dashboard-content">
            <dl class="sv-dashboard-metrics" aria-label="Importes comerciales del período">
                <div><dt>Total cotizado</dt><dd>{{ $money($report['total']) }}<p>{{ $report['count'] }} documentos emitidos · todos los estados</p></dd></div>
                <div><dt>Monto aprobado</dt><dd>{{ $money($buckets['accepted']['minor']) }}<p>{{ $buckets['accepted']['count'] }} presupuestos aceptados</p></dd></div>
                <div><dt>Oportunidad vigente</dt><dd>{{ $money($buckets['pending']['minor']) }}<p>{{ $buckets['pending']['count'] }} propuestas pendientes de respuesta</p></dd></div>
            </dl>
            @if($report['count'] === 0)
                <x-ui.panel title="Todavía no hay emisiones en esta selección">
                    <x-ui.empty-state title="El resumen empieza al emitir un presupuesto" description="Los borradores no suman como oportunidades. Probá otro período o moneda, o continuá trabajando en Presupuestos.">
                        <a class="sv-dashboard-link" href="{{ \App\Filament\Resources\QuoteResource::getUrl('index') }}">Ir a Presupuestos</a>
                    </x-ui.empty-state>
                </x-ui.panel>
            @else
                <div class="sv-dashboard-columns">
                    <x-ui.panel title="Destino de lo cotizado" description="Distribución por importe. Cada documento se cuenta una sola vez.">
                        <div class="sv-dashboard-breakdown">
                            @foreach($buckets as $key => $bucket)
                                <div class="sv-dashboard-breakdown-row">
                                    <div><span>{{ $bucket['label'] }}</span><strong>{{ $money($bucket['minor']) }}</strong></div>
                                    <div class="sv-dashboard-bar" aria-hidden="true"><span @class(['sv-dashboard-bar-fill', 'is-approved' => $key === 'accepted']) style="width: {{ $bucket['share'] }}%"></span></div>
                                    <small>{{ $bucket['count'] }} documentos · {{ str_replace('.', ',', $bucket['share']) }}% del importe</small>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.panel>
                    <x-ui.panel title="Próximo seguimiento" description="Propuestas sin respuesta de la selección, por vigencia más próxima.">
                        <x-slot:actions><a href="{{ \App\Filament\Resources\QuoteResource::getUrl('index') }}">Presupuestos @svg('heroicon-o-arrow-right')</a></x-slot:actions>
                        <p class="sv-dashboard-followup-note">{{ $buckets['expired']['count'] }} vencidas · {{ $report['dueSoon'] }} vencen entre hoy y los próximos 7 días</p>
                        <div class="sv-dashboard-followup">
                            @forelse($report['followUp'] as $quote)
                                <a class="sv-dashboard-followup-row" href="{{ \App\Filament\Resources\QuoteResource::getUrl('view', ['record' => $quote]) }}">
                                    <div><strong>{{ $quote->reference() }}</strong><span>{{ data_get($quote->snapshot, 'customer.name') ?? $quote->customer->name }}</span><small>{{ $quote->subject }}</small></div>
                                    <div><strong>{{ $quote->formattedTotal() }}</strong><span @class(['sv-dashboard-expired' => $quote->valid_until->toDateString() < $report['today']])>{{ $quote->valid_until->toDateString() < $report['today'] ? 'Venció' : 'Vigente hasta' }} {{ $quote->valid_until->format('d/m/Y') }}</span><small>Abrir presupuesto →</small></div>
                                </a>
                            @empty
                                <x-ui.empty-state title="Sin propuestas pendientes" description="Todos los documentos de esta selección tienen una decisión registrada." />
                            @endforelse
                        </div>
                        @if($buckets['pending']['count'] + $buckets['expired']['count'] > 6)<p class="sv-dashboard-followup-note">Se muestran los primeros 6. Consultá el resto en Presupuestos.</p>@endif
                    </x-ui.panel>
                </div>
            @endif
            @if($report['financials'] !== null)
                <x-ui.panel title="Utilidad bruta estimada de aprobados" description="Solo documentos con costos completos capturados. No representa cobros ni utilidad neta después de gastos e impuestos a la renta.">
                    <div class="sv-cost-summary">
                        <dl class="sv-totals"><div class="sv-total"><dt>Utilidad estimada</dt><dd>{{ $report['financials']['covered'] ? $money($report['financials']['profit_minor']) : 'Sin costos completos' }}</dd></div>
                        <div><dt>Margen sobre venta sin IVA</dt><dd>{{ $report['financials']['margin_percent'] !== null ? str_replace('.', ',', $report['financials']['margin_percent']).'%' : '—' }}</dd></div></dl>
                        <p>Cobertura: {{ $report['financials']['covered'] }} de {{ $report['financials']['accepted'] }} presupuestos aprobados. Los históricos sin costos quedan fuera de esta estimación.</p>
                    </div>
                </x-ui.panel>
            @endif
            <div class="sv-dashboard-method">
                <p><strong>Cómo leer este resumen</strong> · Importes con IVA incluido, separados por moneda. Se usa la fecha de emisión y el estado actual; una aprobación puede haberse registrado después del período elegido.</p>
                @if($report['financials'] === null)
                    <p><strong>Rentabilidad interna restringida.</strong> El monto aprobado no representa ganancia ni cobro. Los costos y márgenes son información interna con acceso restringido.</p>
                @else
                    <p>El monto aprobado no representa ganancia ni cobro. La estimación usa costos guardados; para un resultado real faltan compras, horas ejecutadas y gastos finales.</p>
                @endif
                <span>Consulta al {{ $report['asOf'] }} · America/Asuncion · Actualizá la página para ver cambios de otros usuarios.</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
