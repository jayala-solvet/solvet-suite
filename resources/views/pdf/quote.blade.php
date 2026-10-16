<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><style>
@page { margin: 30px 36px 54px; }
body { font-family: DejaVu Sans, sans-serif; color: #292E32; font-size: 9px; line-height: 1.5; }
table { border-collapse: collapse; }
.header { width: 100%; border-bottom: 2px solid #3B647F; margin-bottom: 14px; }
.header td { vertical-align: middle; padding-bottom: 12px; }
.logo-frame { width: 125px; height: 82px; overflow: hidden; position: relative; }
.logo { position: absolute; width: 223px; max-width: none; left: -49px; top: -39px; }
.title { text-align: right; }
h1 { font-size: 23px; font-weight: bold; margin: 2px 0 4px; line-height: 1.2; }
.reference { font-size: 13px; color: #3B647F; font-weight: bold; }
.eyebrow { font-size: 7px; text-transform: uppercase; letter-spacing: 1px; color: #5D666B; }
.muted { color: #5D666B; }
.parties { width: 100%; margin-bottom: 16px; }
.parties td { width: 50%; vertical-align: top; padding: 8px 12px; background: #F5F5F2; }
.parties td:first-child { border-right: 5px solid #fff; }
.parties strong { font-size: 11px; }
.subject { font-size: 11px; margin: 0 0 10px; }
.items { width: 100%; table-layout: fixed; }
.items thead { display: table-header-group; }
.items th { padding: 8px 5px; background: #ECEFED; color: #3B4246; font-size: 7px; text-align: left; }
.items td { padding: 9px 5px; border-bottom: 1px solid #D9DDDC; vertical-align: top; word-wrap: break-word; }
.items tr { page-break-inside: avoid; }
.description { white-space: pre-line; }
.sku { font-size: 7px; color: #5D666B; }
.right { text-align: right !important; }
.totals { width: 245px; margin: 14px 0 14px auto; page-break-inside: avoid; }
.totals td { padding: 4px 8px; }
.total td { background: #ECEFED; font-size: 13px; font-weight: bold; padding: 9px 8px; border-top: 1px solid #BFC8CB; }
.section-title { font-size: 9px; font-weight: bold; margin: 15px 0 6px; padding-bottom: 5px; border-bottom: 1px solid #D9DDDC; page-break-after: avoid; }
.conditions { width: 100%; }
.conditions tr { page-break-inside: avoid; }
.conditions td { padding: 4px 0; vertical-align: top; word-wrap: break-word; }
.conditions .key { width: 105px; color: #5D666B; }
.terms p { white-space: pre-line; font-size: 8px; line-height: 1.6; color: #4D565C; margin: 0 0 7px; word-wrap: break-word; }
.footer { position: fixed; bottom: -33px; left: 0; right: 0; border-top: 1px solid #D9DDDC; padding-top: 7px; font-size: 7px; color: #5D666B; }
.page-number:before { content: counter(page); }
.draft { border: 1px solid #C89057; padding: 5px; color: #875020; text-align: center; font-size: 8px; font-weight: bold; margin-bottom: 10px; }
</style></head>
<body>
@php($company = $quote->snapshot['company'])
@php($client = $quote->snapshot['customer'])
<div class="footer">{{ $company['name'] }} · {{ $quote->reference() }} · Propuesta comercial. No constituye factura.<span style="float:right">Página <span class="page-number"></span></span></div>
@if($draft)<div class="draft">BORRADOR · DOCUMENTO SIN EMITIR</div>@endif
<table class="header"><tr>
    <td><div class="logo-frame"><img class="logo" src="{{ public_path('brand/solvet-it-logo.png') }}"></div></td>
    <td class="title"><div class="eyebrow">Propuesta comercial</div><h1>Presupuesto</h1><div class="reference">{{ $quote->reference() }}</div>
        <div class="muted">Emisión: {{ $quote->issued_at?->format('d/m/Y') ?? 'Pendiente' }} · Vigencia: {{ $quote->valid_until->format('d/m/Y') }}</div></td>
</tr></table>
<table class="parties"><tr>
    <td><div class="eyebrow">Preparado para</div><strong>{{ $client['name'] }}</strong>
        @if($client['tax_id'] ?? null)<br>RUC: {{ $client['tax_id'] }}@endif
        @foreach(['contact','email','phone','address'] as $field)@if($client[$field] ?? null)<br>{{ $client[$field] }}@endif @endforeach</td>
    <td><div class="eyebrow">Emitido por</div><strong>{{ $company['name'] }}</strong>
        @if($company['tax_id'] ?? null)<br>RUC: {{ $company['tax_id'] }}@endif
        @foreach(['email','phone','address'] as $field)@if($company[$field] ?? null)<br>{{ $company[$field] }}@endif @endforeach</td>
</tr></table>
<div class="subject">{{ $quote->subject }}</div>
<table class="items"><thead><tr>
    <th style="width:39%">PRODUCTO / SERVICIO</th><th class="right" style="width:7%">CANT.</th><th class="right" style="width:17%">PRECIO UNIT.</th><th class="right" style="width:7%">DTO.</th><th class="right" style="width:7%">IVA</th><th class="right" style="width:23%">IMPORTE</th>
</tr></thead><tbody>
@foreach($quote->lines as $line)<tr>
    <td>@if($line['sku'] ?? null)<div class="sku">{{ $line['sku'] }}</div>@endif<div class="description">{{ $line['description'] }}</div></td>
    <td class="right">{{ $line['quantity'] }}</td><td class="right">{{ \App\Modules\Quotes\Models\Quote::money(bcmul($line['unit_price'], $quote->currency === 'USD' ? '100' : '1', 0), $quote->currency) }}</td>
    <td class="right">{{ $line['discount'] }}%</td><td class="right">{{ $line['tax_rate'] }}%</td><td class="right">{{ \App\Modules\Quotes\Models\Quote::money($line['total_minor'], $quote->currency) }}</td>
</tr>@endforeach
</tbody></table>
<table class="totals">
    @foreach(['subtotal_minor' => 'Subtotal', 'discount_minor' => 'Descuentos', 'tax_minor' => 'IVA incluido'] as $field => $label)
        <tr><td>{{ $label }}</td><td class="right">{{ \App\Modules\Quotes\Models\Quote::money($quote->$field, $quote->currency) }}</td></tr>
    @endforeach
    <tr class="total"><td>TOTAL {{ $quote->currency }}</td><td class="right">{{ $quote->formattedTotal() }}</td></tr>
</table>
<h2 class="section-title">Condiciones comerciales</h2>
<table class="conditions">
    <tr><td class="key">Forma de pago</td><td><strong>{{ $quote->paymentLabel() }}</strong>@if($quote->payment_notes)<br>{{ $quote->payment_notes }}@endif</td></tr>
    @if($quote->delivery_terms)<tr><td class="key">Entrega</td><td>{{ $quote->delivery_terms }}</td></tr>@endif
    @if($quote->warranty_terms)<tr><td class="key">Garantía / soporte</td><td>{{ $quote->warranty_terms }}</td></tr>@endif
</table>
@if($quote->terms)
<h2 class="section-title">Términos y condiciones</h2>
<div class="terms">@foreach(preg_split('/\R\s*\R/u', $quote->terms) as $paragraph)<p>{{ $paragraph }}</p>@endforeach</div>
@endif
</body></html>
