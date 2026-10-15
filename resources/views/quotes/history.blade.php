<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial · {{ $quote->reference() }}</title>
    <x-theme-init />
    <link rel="stylesheet" href="{{ asset('fonts/filament/filament/inter/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/solvet.css') }}?v={{ substr(hash_file('sha256', public_path('css/solvet.css')), 0, 16) }}">
</head>
<body class="sv-history">
    <main class="sv-history-main">
        <a class="sv-secondary" href="{{ \App\Filament\Resources\QuoteResource::getUrl('index') }}">← Presupuestos</a>
        <h1 class="sv-history-heading">{{ $quote->reference() }}</h1>
        <p class="sv-history-meta">{{ $quote->subject }}</p>
        <x-ui.status :state="$quote->status" :label="$quote->statusLabel()" />

        <x-ui.panel class="sv-history-panel" title="Historial del documento" description="Actividad registrada y referencias de cada decisión.">
            <div class="sv-table-wrap">
                <table class="sv-table">
                    <thead><tr><th>Fecha</th><th>Acción</th><th>Usuario</th><th>Referencia</th></tr></thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr>
                                <td>{{ $event->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $event->label() }}</td>
                                <td>{{ $event->actor?->name ?? 'Sistema' }}</td>
                                <td class="sv-history-evidence">{{ $event->metadata['evidence'] ?? ($event->metadata['number'] ?? '—') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.panel>
        @if($quote->pdf_sha256)
            <p class="sv-history-hash">SHA-256 del PDF emitido: {{ $quote->pdf_sha256 }}</p>
        @endif
    </main>
</body>
</html>
