@php
$pkFirstPaint = '#33003B';
try {
    if (request()->is('admin/*') || request()->is('studio/*') || request()->is('dashboard') || request()->is('panel/*')) {
        $pkFirstPaint = '#202637';
    } else {
        $pkFirstPaint = \App\Support\PublicSiteStyle::settings()['background'] ?? '#33003B';
    }
} catch (\Throwable $e) {
    $pkFirstPaint = '#33003B';
}
@endphp
<html lang="{{ config('app.locale') }}" style="background-color:{{ $pkFirstPaint }};">
<script>
(() => {
    try {
        let mode = localStorage.getItem('color-mode');
        if (!['light', 'dark', 'auto'].includes(mode)) {
            mode = 'auto';
            localStorage.setItem('color-mode', mode);
        }

        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const useDark = mode === 'dark' || (mode === 'auto' && prefersDark);

        document.documentElement.classList.remove('light', 'dark', 'auto');
        document.documentElement.classList.add(mode);
        if (useDark) document.documentElement.classList.add('dark');
        document.documentElement.classList.add('pk-theme-ready');
    } catch (error) {
        document.documentElement.classList.add('dark', 'pk-theme-ready');
    }
})();
</script>
