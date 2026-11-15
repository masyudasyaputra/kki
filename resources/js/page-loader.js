import { isFormModalLink } from './form-modal-links';

const loader = document.getElementById('kki-page-loader');

if (loader) {
    let hideTimer;

    const showLoader = () => {
        window.clearTimeout(hideTimer);
        loader.hidden = false;
        loader.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => loader.classList.add('is-visible'));
    };

    const hideLoader = () => {
        loader.classList.remove('is-visible');
        loader.setAttribute('aria-hidden', 'true');
        hideTimer = window.setTimeout(() => { loader.hidden = true; }, 220);
    };

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (link.hasAttribute('data-no-loading') || link.hasAttribute('download') || link.target || link.relList.contains('external')) return;
        if (isFormModalLink(link)) return;

        let destination;
        try { destination = new URL(link.href, window.location.href); } catch { return; }
        if (destination.origin !== window.location.origin || destination.hash && destination.pathname === window.location.pathname && destination.search === window.location.search) return;

        // Wait until modal handlers have had a chance to cancel navigation.
        window.setTimeout(() => { if (!event.defaultPrevented) showLoader(); }, 0);
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-loading') || form.target || form.method.toLowerCase() === 'dialog') return;
        window.setTimeout(() => { if (!event.defaultPrevented) showLoader(); }, 0);
    }, true);

    window.addEventListener('pageshow', hideLoader);
    window.addEventListener('auth-modal:closed', () => {
        window.clearTimeout(hideTimer);
        loader.classList.remove('is-visible');
        loader.hidden = true;
        loader.setAttribute('aria-hidden', 'true');
    });
    window.addEventListener('pagehide', hideLoader);
}
