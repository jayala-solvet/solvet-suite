{{-- Standalone views share Filament's browser preference; run before the CSS paints. --}}
<script>
    (() => {
        const key = 'filament:theme:mode';
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const apply = () => {
            let mode = 'light';
            try { mode = localStorage.getItem(key) ?? 'light'; } catch {}
            document.documentElement.classList.toggle('dark', mode === 'dark' || (mode === 'system' && media.matches));
        };
        apply();
        media.addEventListener('change', apply);
        window.addEventListener('storage', (event) => {
            if (event.key === key || event.key === null) apply();
        });
    })();
</script>
