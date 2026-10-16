<div class="sv-cost-summary">
    <p><strong>{{ $profile->item->name }} · Versión #{{ $profile->id }}</strong></p>
    <p>{{ \App\Modules\Costs\Models\CostProfile::SOURCES[$profile->source] }} · {{ $profile->supplier_name ?? 'Trabajo propio' }} · RUC: {{ $profile->supplier_tax_id ?? 'No informado' }}</p>
    <p>Referencia: {{ $profile->reference ?? 'No informada' }} · Fecha: {{ $profile->cost_date->format('d/m/Y') }}</p>
    <p>{{ \App\Modules\Costs\Models\CostProfile::VAT[$profile->vat_treatment] }} · IVA del costo {{ $profile->purchase_tax_rate }}%</p>
    <p>{{ \App\Modules\Costs\Models\CostProfile::MODES[$profile->pricing_mode] }}: {{ $profile->pricing_value }} {{ $profile->pricing_mode === 'fixed_profit' ? $profile->currency : '%' }}</p>
    <dl class="sv-totals">
        <div><dt>Costo informado</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($profile->purchase_minor, $profile->currency) }}</dd></div>
        <div><dt>Otros costos unitarios</dt><dd>{{ \App\Modules\Quotes\Models\Quote::money($profile->extra_minor, $profile->currency) }}</dd></div>
        <div><dt>Costo efectivo</dt><dd>{{ $profile->effective_unit_minor === null ? 'Pendiente' : \App\Modules\Quotes\Models\Quote::money(\App\Modules\Costs\Services\CostCalculator::round($profile->effective_unit_minor), $profile->currency) }}</dd></div>
        <div class="sv-total"><dt>Precio sugerido con IVA</dt><dd>{{ $profile->suggested_price_minor === null ? 'Pendiente' : \App\Modules\Quotes\Models\Quote::money($profile->suggested_price_minor, $profile->currency) }}</dd></div>
    </dl>
    <p>{{ $profile->notes }}</p><p class="sv-footnote">Registro interno creado el {{ $profile->created_at->format('d/m/Y H:i') }}. No es una compra contabilizada ni una liquidación de IVA.</p>
</div>
