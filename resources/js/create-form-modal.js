import { isFormModalLink } from './form-modal-links';

function initCreateForms() {
    // The embedded document runs its own form scripts, dependent fields and validation.
    // Never intercept creation links again from inside a form modal.
    if (window.self !== window.top) {
        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');
            if (!link || event.ctrlKey || event.metaKey) return;
            const url = new URL(link.href, location.href);
            if (url.origin === location.origin && /\/admin\/(?:dashboard|dojos|officials|official-verifications|wasit-juri|provinces|users|members|belt-levels|exams|nomor-id(?:\/pengurus)?)$/.test(url.pathname)) {
                event.preventDefault();
                window.parent.postMessage({ type: 'create-modal:cancel' }, location.origin);
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !document.querySelector('dialog[open]')) window.parent.postMessage({ type: 'create-modal:cancel' }, location.origin);
        });
        return;
    }

    const savedMessage = sessionStorage.getItem('create-modal:success');
    if (savedMessage) {
        sessionStorage.removeItem('create-modal:success');
        const notice = document.createElement('div');
        notice.className = 'create-form-notice';
        notice.setAttribute('role', 'status');
        notice.textContent = savedMessage;
        document.body.append(notice);
        setTimeout(() => notice.remove(), 6000);
    }
    let dialog, frame, feedback, opener;
    const close = () => { dialog?.close(); opener?.focus(); };
    const fitViewport = () => {
        if (!dialog?.open) return;
        const viewport = window.visualViewport;
        dialog.style.setProperty('--form-viewport-height', `${viewport?.height ?? window.innerHeight}px`);
        dialog.style.setProperty('--form-viewport-top', `${viewport?.offsetTop ?? 0}px`);
    };
    window.visualViewport?.addEventListener('resize', fitViewport);
    window.visualViewport?.addEventListener('scroll', fitViewport);
    window.addEventListener('resize', fitViewport);
    const build = () => {
        dialog = document.createElement('dialog'); dialog.className = 'create-form-dialog';
        dialog.setAttribute('aria-labelledby', 'create-form-title');
        const header = document.createElement('div'); header.className = 'create-form-heading';
        const title = document.createElement('h2'); title.id = 'create-form-title';
        const dismiss = document.createElement('button'); dismiss.type = 'button'; dismiss.className = 'btn btn-secondary'; dismiss.textContent = 'Tutup'; dismiss.addEventListener('click', close);
        header.append(title, dismiss);
        feedback = document.createElement('p'); feedback.className = 'create-form-feedback'; feedback.setAttribute('role', 'status');
        frame = document.createElement('iframe'); frame.className = 'create-form-frame'; frame.title = 'Form tambah data';
        frame.addEventListener('load', () => {
            if (!dialog.open || frame.getAttribute('src') === 'about:blank') return;
            try {
                const doc = frame.contentDocument;
                if (!doc?.body) return;
                if (doc.body.dataset.modalStatus === 'saved') {
                    sessionStorage.setItem('create-modal:success', doc.body.dataset.modalMessage || 'Data berhasil disimpan.');
                    location.reload();
                    return;
                }
                feedback.hidden = true;
                frame.hidden = false;
                // Same-origin validation redirects keep their original form and old inputs.
                // Mobile users choose the field themselves so opening the modal
                // does not immediately summon the keyboard and obscure the form.
                if (!window.matchMedia('(pointer: coarse)').matches) {
                    doc.querySelector('input:not([type="hidden"]):not([disabled]),select:not([disabled]),textarea:not([disabled])')?.focus({ preventScroll: true });
                }
            } catch {
                feedback.hidden = false;
                feedback.textContent = 'Form tidak dapat dimuat. Tutup modal lalu coba lagi.';
            }
        });
        dialog.append(header, feedback, frame);
        dialog.addEventListener('close', () => { frame.src = 'about:blank'; opener?.focus(); });
        dialog.addEventListener('click', event => { if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) close(); } });
        document.body.append(dialog);
    };
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || (event.button !== undefined && event.button !== 0) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href, location.href);
        if (!isFormModalLink(link)) return;
        const numberEditor = /\/admin\/nomor-id\//.test(url.pathname);
        event.preventDefault();
        if (!dialog) build();
        opener = link;
        dialog.querySelector('h2').textContent = link.dataset.modalTitle || link.getAttribute('aria-label') || link.textContent.trim() || 'Form data';
        frame.title = numberEditor ? 'Kelola Nomor ID' : dialog.querySelector('h2').textContent;
        feedback.textContent = 'Memuat form...'; feedback.hidden = false; frame.hidden = true;
        url.searchParams.set('_form_modal', '1');
        frame.src = url.href;
        dialog.showModal();
        fitViewport();
    });
    window.addEventListener('message', event => {
        if (!frame || event.source !== frame.contentWindow || event.origin !== location.origin || !dialog.open) return;
        if (event.data?.type === 'create-modal:cancel') close();
        if (event.data?.type === 'create-modal:checkout') {
            try {
                const checkout = new URL(event.data.url);
                if (checkout.protocol === 'https:' || checkout.origin === location.origin) location.assign(checkout.href);
            } catch { feedback.hidden = false; feedback.textContent = 'Alamat pembayaran tidak valid.'; }
        }
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCreateForms);
else initCreateForms();
