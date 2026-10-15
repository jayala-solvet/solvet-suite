@php($taglineMask = 'sv-brand-tagline-'.\Illuminate\Support\Str::uuid())

{{-- The viewBox frames the original PNG's visible artwork, including a small clear margin. --}}
<div class="sv-brand">
    <svg
        viewBox="447 354 1153 737"
        preserveAspectRatio="xMidYMid meet"
        role="img"
        aria-label="Solvet-IT · Soluciones Informáticas"
        focusable="false"
    >
        <defs>
            <mask id="{{ $taglineMask }}" maskUnits="userSpaceOnUse" maskContentUnits="userSpaceOnUse" x="455" y="1024" width="1137" height="68" mask-type="alpha">
                <image href="{{ asset('brand/solvet-it-logo.png') }}" width="2048" height="1443" />
            </mask>
        </defs>
        <image href="{{ asset('brand/solvet-it-logo.png') }}" width="2048" height="1443" />
        {{-- Only the tagline's original alpha shapes get a lighter ink in dark mode. --}}
        <rect class="sv-brand-tagline" x="455" y="1024" width="1137" height="68" mask="url(#{{ $taglineMask }})" />
    </svg>
</div>
