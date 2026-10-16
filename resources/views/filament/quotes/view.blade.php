<x-filament-panels::page>
    @php
        $quote = $this->getRecord();
        $company = $quote->snapshot['company'] ?? \App\Modules\Core\Models\Company::first()?->toArray() ?? [];
        $customer = $quote->snapshot['customer'] ?? $quote->customer->toArray();
    @endphp
    <div class="sv-document">
        <div class="sv-document-meta">
            <x-ui.status :state="$quote->status" :label="$quote->statusLabel()" />
            <span>{{ $quote->currency }}</span>
            <span>Emisión: {{ $quote->issued_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</span>
            <span>Vigencia: {{ $quote->valid_until->format('d/m/Y') }}</span>
            @if($quote->status === 'issued' && $quote->valid_until->isBefore(today()))
                <span class="sv-expired">Vencido · sin respuesta registrada</span>
            @endif
        </div>
        @if($quote->status === 'draft')
            <p class="sv-document-notice">Borrador sin correlativo comercial. Revisá el cliente, los importes y las condiciones antes de emitir.</p>
        @else
            <p class="sv-document-notice">Documento emitido y conservado. Los datos que ves corresponden al momento de emisión.</p>
        @endif
        <x-ui.panel :title="$quote->subject">
            <div class="sv-parties">
                <div><span class="sv-eyebrow">CLIENTE</span><h2>{{ $customer['name'] }}</h2>
                    <p>RUC: {{ $customer['tax_id'] ?? 'Sin registrar' }}</p>
                    <p>{{ $customer['contact'] ?? '' }}</p><p>{{ $customer['email'] ?? '' }}</p>
                    <p>{{ $customer['phone'] ?? '' }}</p><p>{{ $customer['address'] ?? '' }}</p>
                </div>
                <div><span class="sv-eyebrow">EMISOR</span><h2>{{ $company['name'] ?? 'Empresa sin configurar' }}</h2>
                    <p>RUC: {{ $company['tax_id'] ?? 'Sin registrar' }}</p>
                    <p>{{ $company['email'] ?? '' }}</p><p>{{ $company['phone'] ?? '' }}</p>
                    <p>{{ $company['address'] ?? '' }}</p>
                </div>
            </div>
            <div class="sv-table-wrap"><table class="sv-table">
                <thead><tr><th>Producto / servicio</th><th class="amount">Cantidad</th><th class="amount">Precio unitario</th><th class="amount">Dto.</th><th class="amount">IVA</th><th class="amount">Importe</th></tr></thead>
                <tbody>@foreach($quote->lines as $line)
                    <tr><td class="sv-line-description">@if($line['sku'] ?? null)<small>{{ $line['sku'] }}</small>@endif{{ $line['description'] }}</td><td class="amount">{{ $line['quantity'] }}</td>
                        <td class="amount">{{ \App\Modules\Quotes\Models\Quote::money(bcmul($line['unit_price'], $quote->currency === 'USD' ? '100' : '1', 0), $quote->currency) }}</td>
                        <td class="amount">{{ $line['discount'] }}%</td><td class="amount">{{ $line['tax_rate'] }}%</td>
                        <td class="amount">{{ \App\Modules\Quotes\Models\Quote::money($line['total_minor'], $quote->currency) }}</td></tr>
                @endforeach</tbody>
            </table></div>
            <dl class="sv-totals">
                @foreach(['subtotal_minor' => 'Subtotal', 'discount_minor' => 'Descuentos', 'tax_minor' => 'IVA incluido'] as $field => $label)
                    <div><dt>{{ $label }}</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($quote->$field, $quote->currency) }}</dd></div>
                @endforeach
                <div class="sv-total"><dt>Total {{ $quote->currency }}</dt><dd>{{ $quote->formattedTotal() }}</dd></div>
            </dl>
            <div class="sv-document-terms"><h2>Pago y entrega</h2><p><strong>{{ $quote->paymentLabel() }}</strong></p>
                @if($quote->payment_notes)<p>{{ $quote->payment_notes }}</p>@endif
                @if($quote->delivery_terms)<p>Entrega: {{ $quote->delivery_terms }}</p>@endif
                @if($quote->warranty_terms)<p>Garantía / soporte: {{ $quote->warranty_terms }}</p>@endif
            </div>
            <div class="sv-document-terms"><h2>Términos y condiciones</h2><p>{{ $quote->terms ?: 'Sin condiciones adicionales.' }}</p></div>
        </x-ui.panel>
        <x-ui.panel title="Historial del documento" description="Actividad y referencias de las decisiones registradas.">
            <div class="sv-table-wrap"><table class="sv-table"><thead><tr><th>Fecha</th><th>Actividad</th><th>Usuario</th><th>Referencia</th></tr></thead><tbody>
                @foreach($this->events() as $event)
                    <tr><td>{{ $event->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $event->label() }}</td><td>{{ $event->actor?->name ?? 'Sistema' }}</td><td class="sv-history-evidence">{{ $event->metadata['evidence'] ?? ($event->metadata['number'] ?? '—') }}</td></tr>
                @endforeach
            </tbody></table></div>
        </x-ui.panel>
        @if($quote->pdf_sha256)<p class="sv-history-hash">SHA-256 del PDF conservado: {{ $quote->pdf_sha256 }}</p>@endif
    </div>
</x-filament-panels::page>
