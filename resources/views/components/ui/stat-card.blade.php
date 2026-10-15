@props(['label', 'value', 'note', 'icon', 'tone' => 'info'])

@php($tone = in_array($tone, ['info', 'success', 'warning'], true) ? $tone : 'info')

<article {{ $attributes->class(['sv-stat']) }}>
    <div>
        <h2 class="sv-stat-label">{{ $label }}</h2>
        <p class="sv-stat-number">{{ $value }}</p>
        <p class="sv-stat-note">{{ $note }}</p>
    </div>
    <div class="sv-icon sv-tone-{{ $tone }}" aria-hidden="true">@svg($icon)</div>
</article>
