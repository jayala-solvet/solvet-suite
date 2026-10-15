<x-filament-panels::page>
    @php($summary = $this->summary())

    <div class="sv-workspace">
        <div class="sv-document">
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


        </div>
    </div>
</x-filament-panels::page>
