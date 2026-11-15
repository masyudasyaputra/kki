import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import { isFormModalLink } from '../../resources/js/form-modal-links.js';

test('touch activation opens the modal without navigation or keyboard autofocus and tracks the mobile viewport', () => {
    const nodes = [], clicks = [], viewportEvents = {};
    let focused = false;
    class Node {
        constructor(tag) { this.tag = tag; this.children = []; this.events = {}; this.attrs = {}; this.style = { setProperty: (key, value) => this.attrs[key] = value }; nodes.push(this); }
        append(...children) { this.children.push(...children); }
        setAttribute(key, value) { this.attrs[key] = value; }
        getAttribute(key) { return key === 'src' ? this.src : this.attrs[key]; }
        addEventListener(key, callback) { this.events[key] = callback; }
        querySelector(tag) { return this.children.find(child => child.tag === tag) || this.children.map(child => child.querySelector?.(tag)).find(Boolean); }
        showModal() { this.open = true; }
        focus() {}
    }
    const window = {
        innerHeight: 740, visualViewport: { height: 640, offsetTop: 0, addEventListener: (event, callback) => viewportEvents[event] = callback },
        addEventListener() {}, matchMedia: () => ({ matches: true }),
    };
    window.self = window; window.top = window;
    const location = { href: 'http://localhost/admin/official-verifications', origin: 'http://localhost', reload: () => assert.fail('Opening modal must not refresh the page') };
    const document = { readyState: 'complete', body: new Node('body'), createElement: tag => new Node(tag), addEventListener: (type, callback) => { if (type === 'click') clicks.push(callback); } };
    const context = { window, document, location, URL, sessionStorage: { getItem: () => null }, isFormModalLink: link => isFormModalLink(link, location.href) };
    const source = fs.readFileSync(new URL('../../resources/js/create-form-modal.js', import.meta.url), 'utf8').replace(/^import .*;\s*/, '');
    vm.runInNewContext(source, context);
    const link = { href: 'http://localhost/admin/officials/create?user_id=7', dataset: {}, textContent: 'Periksa & tetapkan', target: '', hasAttribute: () => false, getAttribute: () => null };
    const event = { target: { closest: () => link }, preventDefault() { this.defaultPrevented = true; } };
    clicks[0](event);
    const dialog = nodes.find(node => node.tag === 'dialog');
    assert.equal(event.defaultPrevented, true);
    assert.equal(dialog.open, true);
    assert.equal(dialog.attrs['--form-viewport-height'], '640px');
    const frame = nodes.find(node => node.tag === 'iframe');
    assert.match(frame.src, /user_id=7/);
    assert.match(frame.src, /_form_modal=1/);
    frame.contentDocument = { body: { dataset: {} }, querySelector: () => ({ focus() { focused = true; } }) };
    frame.events.load();
    assert.equal(frame.hidden, false);
    assert.equal(focused, false);
    window.visualViewport.height = 360; window.visualViewport.offsetTop = 20;
    viewportEvents.resize();
    assert.equal(dialog.attrs['--form-viewport-height'], '360px');
    assert.equal(dialog.attrs['--form-viewport-top'], '20px');
});
