@props(['title', 'description' => null])

<section {{ $attributes->class(['sv-panel']) }}>
    <header class="sv-panel-head">
        <div>
            <h2 class="sv-panel-title">{{ $title }}</h2>
            @if($description)
                <p class="sv-panel-description">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="sv-panel-actions">{{ $actions }}</div>
        @endisset
    </header>
    {{ $slot }}
</section>
