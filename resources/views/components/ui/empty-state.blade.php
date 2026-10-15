@props(['title', 'description', 'icon' => 'heroicon-o-document-plus'])

<div {{ $attributes->class(['sv-empty']) }}>
    <div class="sv-empty-icon" aria-hidden="true">@svg($icon)</div>
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>
    @if($slot->isNotEmpty())
        <div class="sv-empty-actions">{{ $slot }}</div>
    @endif
</div>
