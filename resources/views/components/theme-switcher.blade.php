<div
    class="sv-theme-switcher"
    role="group"
    aria-label="Apariencia"
    x-data="{ mode: localStorage.getItem('filament:theme:mode') ?? 'light' }"
    x-on:theme-changed.window="mode = $event.detail"
>
    @foreach(['light' => ['Claro', 'heroicon-o-sun'], 'dark' => ['Oscuro', 'heroicon-o-moon'], 'system' => ['Sistema', 'heroicon-o-computer-desktop']] as $mode => [$label, $icon])
        <button
            type="button"
            class="sv-theme-option"
            aria-label="Modo {{ strtolower($label) }}"
            title="{{ $label }}"
            x-bind:aria-pressed="mode === @js($mode)"
            x-on:click="$dispatch('theme-changed', @js($mode))"
        >
            @svg($icon, 'sv-theme-icon')
            <span class="sv-sr-only">{{ $label }}</span>
        </button>
    @endforeach
</div>
