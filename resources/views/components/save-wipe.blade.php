<style>
html, body { background-color:#33003B; }
#pk-save-wipe {
    position:fixed;
    inset:0;
    z-index:2147483647;
    background:#FF5DD3;
    transform:scaleX(0);
    transform-origin:left center;
    pointer-events:none;
    transition:transform 240ms cubic-bezier(.4,0,.2,1);
}
html.pk-save-cover #pk-save-wipe { transform:scaleX(1); }
html.pk-save-reveal #pk-save-wipe {
    transform:scaleX(0);
    transform-origin:right center;
    transition-duration:360ms;
}
@media (prefers-reduced-motion: reduce) {
    #pk-save-wipe { transition:opacity 120ms linear; transform:none; opacity:0; }
    html.pk-save-cover #pk-save-wipe { transform:none; opacity:1; }
    html.pk-save-reveal #pk-save-wipe { transform:none; opacity:0; }
}
</style>
<div id="pk-save-wipe" aria-hidden="true"></div>
<script>
(() => {
    const key = 'pk-save-transition';
    if (sessionStorage.getItem(key) === '1') {
        document.documentElement.classList.add('pk-save-cover');
        sessionStorage.removeItem(key);
        requestAnimationFrame(() => requestAnimationFrame(() => {
            document.documentElement.classList.add('pk-save-reveal');
            setTimeout(() => document.documentElement.classList.remove('pk-save-cover', 'pk-save-reveal'), 420);
        }));
    }

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.noSaveWipe === '1') return;
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (!['POST','PUT','PATCH','DELETE'].includes(method) || form.dataset.pkSubmitting === '1') return;

        event.preventDefault();
        form.dataset.pkSubmitting = '1';
        sessionStorage.setItem(key, '1');
        document.documentElement.classList.add('pk-save-cover');
        window.setTimeout(() => form.submit(), window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 80 : 230);
    }, true);
})();
</script>
