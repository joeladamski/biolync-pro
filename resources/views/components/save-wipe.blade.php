<style>
#pk-save-loader {
    position: fixed;
    top: 18px;
    left: 50%;
    z-index: 2147483647;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 178px;
    max-width: calc(100vw - 32px);
    padding: 10px 15px;
    border: 1px solid rgba(255, 93, 211, .58);
    border-radius: 999px;
    background: rgba(22, 18, 29, .96);
    color: #fff;
    box-shadow: 0 12px 34px rgba(0, 0, 0, .32);
    opacity: 0;
    transform: translate(-50%, -18px);
    pointer-events: none;
    transition: opacity 160ms ease, transform 180ms ease;
    backdrop-filter: blur(10px);
}
#pk-save-loader.pk-show {
    opacity: 1;
    transform: translate(-50%, 0);
}
#pk-save-loader .pk-save-spinner {
    width: 20px;
    height: 20px;
    flex: 0 0 20px;
    border: 2px solid rgba(255,255,255,.24);
    border-top-color: #FF5DD3;
    border-right-color: #FF5DD3;
    border-radius: 50%;
    animation: pk-save-spin .72s linear infinite;
}
#pk-save-loader .pk-save-check {
    display: none;
    width: 20px;
    height: 20px;
    flex: 0 0 20px;
    border-radius: 50%;
    place-items: center;
    background: #FF5DD3;
    color: #160019;
    font-size: 13px;
    font-weight: 900;
}
#pk-save-loader.pk-saved .pk-save-spinner { display: none; }
#pk-save-loader.pk-saved .pk-save-check { display: grid; }
#pk-save-loader .pk-save-label {
    font-size: 13px;
    font-weight: 750;
    letter-spacing: .01em;
    white-space: nowrap;
}
#pk-save-loader::after {
    content: "";
    position: absolute;
    left: 15px;
    right: 15px;
    bottom: 4px;
    height: 2px;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, #FF5DD3, transparent);
    background-size: 180% 100%;
    animation: pk-save-progress 1.05s linear infinite;
    opacity: .9;
}
#pk-save-loader.pk-saved::after { display: none; }

@keyframes pk-save-spin { to { transform: rotate(360deg); } }
@keyframes pk-save-progress { from { background-position: 180% 0; } to { background-position: -180% 0; } }

@media (prefers-reduced-motion: reduce) {
    #pk-save-loader,
    #pk-save-loader .pk-save-spinner,
    #pk-save-loader::after {
        transition: none;
        animation: none;
    }
}
</style>

<div id="pk-save-loader" role="status" aria-live="polite" aria-atomic="true">
    <span class="pk-save-spinner" aria-hidden="true"></span>
    <span class="pk-save-check" aria-hidden="true">✓</span>
    <span class="pk-save-label">Saving changes…</span>
</div>

<script>
(() => {
    const key = 'pk-save-loader-return';
    const loader = document.getElementById('pk-save-loader');
    if (!loader) return;

    const label = loader.querySelector('.pk-save-label');

    const isSaveForm = form => {
        if (!(form instanceof HTMLFormElement) || form.dataset.noSaveWipe === '1') return false;
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        return ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);
    };

    const showSaving = () => {
        sessionStorage.setItem(key, '1');
        loader.classList.remove('pk-saved');
        label.textContent = 'Saving changes…';
        loader.classList.add('pk-show');
    };

    const showSaved = () => {
        loader.classList.add('pk-show', 'pk-saved');
        label.textContent = 'Saved';
        window.setTimeout(() => loader.classList.remove('pk-show'), 900);
    };

    // Normal form submissions: do not delay or replace native submission.
    document.addEventListener('submit', event => {
        if (isSaveForm(event.target)) showSaving();
    }, true);

    // LinkStack's legacy config toggles call form.submit() directly, which bypasses
    // the submit event. Wrap the native method only to display the loader; preserve
    // the browser's original submission behavior and all form values.
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (isSaveForm(this)) showSaving();
        return nativeSubmit.call(this);
    };

    if (sessionStorage.getItem(key) === '1') {
        sessionStorage.removeItem(key);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', showSaved, { once: true });
        } else {
            showSaved();
        }
    }
})();
</script>
