export function isFormModalLink(link, currentUrl = window.location.href) {
    if (!link || link.hasAttribute('data-no-modal') || link.hasAttribute('download') || link.target) return false;
    const url = new URL(link.href, currentUrl);
    if (url.origin !== new URL(currentUrl).origin || !url.pathname.startsWith('/admin/')) return false;
    return link.hasAttribute('data-create-modal') || link.hasAttribute('data-form-modal')
        || link.hasAttribute('data-member-number-modal') || /\/(?:create|edit)\/?$/.test(url.pathname);
}
