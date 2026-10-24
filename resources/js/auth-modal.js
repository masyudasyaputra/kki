function initAuthModal() {
    if (window.self !== window.top) {
        window.parent.postMessage({ type: 'auth-modal:ready' }, location.origin);
        // Do not rely on Sec-Fetch-Dest: some mobile browsers omit it.
        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');
            if (!link || link.target === '_top' || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            const url = new URL(link.href, location.href);
            if (url.origin !== location.origin) return;
            if (/\/(login|register|forgot-password|reset-password)(\/|$)/.test(url.pathname)) {
                event.preventDefault();
                url.searchParams.set('_auth_modal', '1');
                location.assign(url.href);
            }
        });
        document.querySelectorAll('form[action]').forEach(form => {
            const url = new URL(form.action, location.href);
            if (url.origin === location.origin) { url.searchParams.set('_auth_modal', '1'); form.action = url.href; }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !document.querySelector('dialog[open]')) window.parent.postMessage({ type: 'auth-modal:close' }, location.origin);
        });
        return;
    }
    let dialog, frame, opener, loading, scrollPosition = 0;
    const viewport = window.visualViewport;
    const fitViewport = () => {
        if (!dialog?.open) return;
        dialog.style.setProperty('--auth-viewport-height', (viewport?.height ?? window.innerHeight) + 'px');
        dialog.style.setProperty('--auth-viewport-top', (viewport?.offsetTop ?? 0) + 'px');
    };
    viewport?.addEventListener('resize', fitViewport);
    viewport?.addEventListener('scroll', fitViewport);
    window.addEventListener('resize', fitViewport);
    const showFrame = () => {
        if (!dialog?.open || frame.getAttribute('src') === 'about:blank') return;
        try {
            const doc = frame.contentDocument;
            if (!doc?.body || frame.contentWindow.location.href === 'about:blank') return;
            if (doc.body.dataset.authenticated === 'true') { location.assign(frame.contentWindow.location.href); return; }
            const registration = /\/register\/?$/.test(frame.contentWindow.location.pathname);
            dialog.classList.toggle('auth-dialog-wide', registration);
            dialog.querySelector('h2').textContent = registration ? 'Daftar Anggota' : 'Masuk';
            loading.hidden = true; frame.hidden = false;
            fitViewport();
        } catch { loading.hidden = false; loading.textContent = 'Formulir belum dapat dimuat. Tutup dan coba lagi.'; }
    };
    const open = (href, mode, source) => {
        if (!dialog) {
            dialog = document.createElement('dialog'); dialog.className = 'create-form-dialog auth-dialog'; dialog.setAttribute('aria-labelledby', 'auth-modal-title');
            const header = document.createElement('div'); header.className = 'create-form-heading';
            const title = document.createElement('h2'); title.id = 'auth-modal-title';
            const close = document.createElement('button'); close.type = 'button'; close.className = 'btn btn-secondary'; close.textContent = 'Tutup'; close.onclick = () => { dialog.close(); opener?.focus(); };
            header.append(title, close);
            loading = document.createElement('p'); loading.className = 'create-form-feedback'; loading.setAttribute('role', 'status'); loading.textContent = 'Memuat formulir...';
            frame = document.createElement('iframe'); frame.className = 'create-form-frame'; frame.title = 'Masuk atau daftar anggota Shokaido';
            frame.addEventListener('load', showFrame);
            dialog.append(header, loading, frame);
            dialog.addEventListener('close', () => {
                frame.src = 'about:blank';
                document.body.classList.remove('auth-modal-open');
                document.body.style.removeProperty('top');
                window.scrollTo(0, scrollPosition);
                opener?.focus({ preventScroll: true });
            });
            document.body.append(dialog);
        }
        opener = source;
        dialog.querySelector('h2').textContent = mode === 'register' ? 'Daftar Anggota' : 'Masuk';
        dialog.classList.toggle('auth-dialog-wide', mode === 'register');
        const url = new URL(href, location.href); url.searchParams.set('_auth_modal', '1');
        loading.hidden = false; frame.hidden = true; frame.src = url.href;
        if (!dialog.open) {
            scrollPosition = window.scrollY;
            document.body.style.top = '-' + scrollPosition + 'px';
            document.body.classList.add('auth-modal-open');
            dialog.showModal();
        }
        fitViewport();
    };
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href, location.href);
        const mode = url.pathname.match(/\/(login|register)\/?$/)?.[1];
        if (!mode || url.origin !== location.origin) return;
        event.preventDefault(); open(url.href, mode, link);
    });
    window.addEventListener('message', event => {
        if (!frame || event.source !== frame.contentWindow || event.origin !== location.origin) return;
        if (event.data?.type === 'auth-modal:close') dialog.close();
        if (event.data?.type === 'auth-modal:ready') showFrame();
    });
    const current = new URL(location.href), mode = current.searchParams.get('auth');
    if (mode === 'login' || mode === 'register') {
        open(new URL(mode, new URL('.', location.href)).href, mode, null);
        current.searchParams.delete('auth'); history.replaceState({}, '', current.href);
    }
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAuthModal);
else initAuthModal();
