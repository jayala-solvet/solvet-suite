<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $document->title }}</title>
<x-theme-init />
<link rel="stylesheet" href="{{ asset('fonts/filament/filament/inter/index.css') }}">
<link rel="stylesheet" href="{{ asset('css/solvet.css') }}?v={{ substr(hash_file('sha256', public_path('css/solvet.css')),0,16) }}">
</head><body class="sv-history"><main class="sv-history-main">
<a class="sv-secondary" href="/app">Volver al sistema</a><h1 class="sv-history-heading">{{ $document->title }}</h1><p class="sv-history-meta">Versión {{ $document->version }} · Publicada {{ $document->published_at->format('d/m/Y') }}</p>
<x-ui.panel :title="$document->title" description="Copia conservada de esta versión publicada.">@include('filament.legal.text')</x-ui.panel>
</main></body></html>
