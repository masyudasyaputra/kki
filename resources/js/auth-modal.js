function initAuthModal() {
    if (window.self !== window.top) return;
    let dialog, content, feedback, title, opener, scrollPosition = 0, loadVersion = 0, submitting = false;
    const viewport = window.visualViewport;
    const titles = { login: 'Masuk', register: 'Daftar Anggota', 'forgot-password': 'Lupa Kata Sandi' };
    const modeFor = url => url.pathname.match(/\/(login|register|forgot-password)\/?$/)?.[1];
    const fitViewport = () => {
        if (!dialog?.open) return;
        dialog.style.setProperty('--auth-viewport-height', (viewport?.height ?? window.innerHeight) + 'px');
        dialog.style.setProperty('--auth-viewport-top', (viewport?.offsetTop ?? 0) + 'px');
    };
    viewport?.addEventListener('resize', fitViewport);
    viewport?.addEventListener('scroll', fitViewport);
    window.addEventListener('resize', fitViewport);
    const showFeedback = (message, error = false) => {
        feedback.textContent = message;
        feedback.hidden = !message;
        feedback.setAttribute('role', error ? 'alert' : 'status');
        feedback.classList.toggle('auth-error-summary', error);
    };
    const ensureDialog = () => {
        if (dialog) return;
        dialog = document.createElement('dialog');
        dialog.className = 'create-form-dialog auth-dialog';
        dialog.setAttribute('aria-labelledby', 'auth-modal-title');
        const header = document.createElement('div');
        header.className = 'create-form-heading';
        title = document.createElement('h2'); title.id = 'auth-modal-title';
        const close = document.createElement('button');
        close.type = 'button'; close.className = 'btn btn-secondary'; close.textContent = 'Tutup';
        close.addEventListener('click', () => dialog.close());
        header.append(title, close);
        feedback = document.createElement('div');
        feedback.className = 'create-form-feedback'; feedback.hidden = true; feedback.tabIndex = -1;
        content = document.createElement('div'); content.className = 'auth-modal-content';
        dialog.append(header, feedback, content);
        document.body.append(dialog);
        dialog.addEventListener('close', () => {
            ++loadVersion;
            content.replaceChildren();
            document.body.classList.remove('auth-modal-open');
            document.body.style.removeProperty('top');
            window.scrollTo(0, scrollPosition);
            opener?.focus({ preventScroll: true });
        });
        dialog.addEventListener('submit', async event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            const action = new URL(form.action, location.href);
            if (action.origin !== location.origin) return;
            event.preventDefault();
            if (submitting) return;
            submitting = true;
            const version = loadVersion;
            const buttons = [...form.querySelectorAll('[type="submit"]')];
            buttons.forEach(button => { button.disabled = true; });
            form.querySelectorAll('[data-auth-field-error]').forEach(node => node.remove());
            form.querySelectorAll('[aria-invalid="true"]').forEach(node => node.removeAttribute('aria-invalid'));
            showFeedback('Memproses...');
            try {
                const response = await fetch(action, {
                    method: form.method, body: new FormData(form), credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = response.headers.get('content-type')?.includes('application/json') ? await response.json() : {};
                if (response.ok && data.redirect) {
                    const destination = new URL(data.redirect, location.href);
                    if (destination.origin !== location.origin) throw new Error('Tujuan masuk tidak valid.');
                    location.assign(destination.href);
                    return;
                }
                if (version !== loadVersion || !dialog.open) return;
                if (response.status === 422 && data.errors) {
                    const messages = [];
                    for (const [name, errors] of Object.entries(data.errors)) {
                        const message = Array.isArray(errors) ? errors[0] : String(errors);
                        messages.push(message);
                        const input = form.elements.namedItem(name);
                        if (input instanceof HTMLElement) {
                            input.setAttribute('aria-invalid', 'true');
                            const hint = document.createElement('p');
                            hint.className = 'mt-1 text-xs text-red-700'; hint.dataset.authFieldError = 'true'; hint.textContent = message;
                            input.insertAdjacentElement('afterend', hint);
                        }
                    }
                    showFeedback(messages.join(' '), true);
                    form.querySelector('[aria-invalid="true"]')?.focus();
                    return;
                }
                if (!response.ok) throw new Error(response.status === 419 ? 'Sesi telah berakhir. Muat ulang halaman lalu coba lagi.' : response.status === 429 ? 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.' : 'Formulir belum dapat diproses. Silakan coba lagi.');
                showFeedback(data.message || 'Permintaan berhasil diproses.');
            } catch (error) {
                if (version === loadVersion && dialog.open) showFeedback(error.message || 'Koneksi bermasalah. Silakan coba lagi.', true);
            } finally {
                submitting = false;
                buttons.forEach(button => { button.disabled = false; });
            }
        });
    };
    const open = async (href, mode, source) => {
        if (submitting) return;
        ensureDialog();
        if (!dialog.open) {
            opener = source;
            scrollPosition = window.scrollY;
            document.body.style.top = '-' + scrollPosition + 'px';
            document.body.classList.add('auth-modal-open');
            dialog.showModal();
        }
        const version = ++loadVersion;
        title.textContent = titles[mode];
        dialog.classList.toggle('auth-dialog-wide', mode === 'register');
        content.replaceChildren();
        content.setAttribute('aria-busy', 'true');
        showFeedback('Memuat formulir...');
        fitViewport();
        try {
            const url = new URL(href, location.href);
            url.searchParams.set('_auth_modal', '1');
            const response = await fetch(url, { credentials: 'same-origin', headers: { 'X-Auth-Modal': '1', Accept: 'text/html' } });
            if (!response.ok) throw new Error('Formulir belum dapat dimuat. Tutup dan coba lagi.');
            const html = await response.text();
            if (version !== loadVersion || !dialog.open) return;
            const template = document.createElement('template');
            template.innerHTML = html;
            const card = template.content.querySelector('.auth-modal-card');
            if (!card) throw new Error('Formulir belum dapat dimuat. Muat ulang halaman lalu coba lagi.');
            content.replaceChildren(card);
            showFeedback('');
            // Alpine initializes inserted forms through its existing DOM observer.
            requestAnimationFrame(() => content.querySelector('input:not([type="hidden"]):not([disabled])')?.focus({ preventScroll: true }));
        } catch (error) {
            if (version === loadVersion && dialog.open) showFeedback(error.message, true);
        } finally { if (version === loadVersion) content.removeAttribute('aria-busy'); }
    };
    document.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href, location.href), mode = modeFor(url);
        if (!mode || url.origin !== location.origin) return;
        if (mode === 'forgot-password' && !dialog?.contains(link)) return;
        event.preventDefault();
        open(url.href, mode, dialog?.contains(link) ? opener : link);
    });
    const current = new URL(location.href), mode = current.searchParams.get('auth');
    if (mode === 'login' || mode === 'register') {
        open(new URL(mode, new URL('.', location.href)).href, mode, null);
        current.searchParams.delete('auth'); history.replaceState({}, '', current.href);
    }
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAuthModal);
else initAuthModal();
