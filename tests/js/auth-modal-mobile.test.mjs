import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

test('mobile login tap opens the inline modal on the current host without keyboard autofocus', () => {
    const nodes = [], viewportEvents = {}, clickHandlers = [];
    let inputFocused = false;
    class Element {
        closest() { return this.link; }
    }
    class Node extends Element {
        constructor(tag) {
            super(); this.tag = tag; this.children = []; this.attrs = {}; this.events = {};
            this.style = {setProperty: (key, value) => this.attrs[key] = value};
            this.classList = {add(){}, remove(){}, toggle(){}};
            nodes.push(this);
        }
        append(...children) { this.children.push(...children); }
        setAttribute(key, value) { this.attrs[key] = value; }
        addEventListener(name, callback) { this.events[name] = callback; }
        replaceChildren(...children) { this.children = children; }
        showModal() { this.open = true; }
        contains() { return false; }
        querySelector() { return {focus() { inputFocused = true; }}; }
    }
    const window = {scrollY: 300, innerHeight: 740, visualViewport: {height: 640, offsetTop: 0, addEventListener: (name, callback) => viewportEvents[name] = callback}, addEventListener(){}, matchMedia: () => ({matches: true})};
    window.self = window; window.top = window;
    const body = new Node('body');
    const document = {readyState: 'complete', body, createElement: tag => new Node(tag),
        querySelector: selector => selector === 'template[data-auth-form="login"]' ? {content: {cloneNode: () => new Node('login-form')}} : null,
        addEventListener: (name, callback, capture) => { if (name === 'click') clickHandlers.push({callback, capture}); },
    };
    const context = {window, document, Element, URL, location: {href: 'http://192.168.1.5:8000/', origin: 'http://192.168.1.5:8000'}, requestAnimationFrame: callback => callback()};
    vm.runInNewContext(fs.readFileSync(new URL('../../resources/js/auth-modal.js', import.meta.url), 'utf8'), context);
    const link = new Element(); link.href = 'http://192.168.1.5:8000/login';
    const target = new Element(); target.link = link;
    const event = {target, preventDefault() { this.defaultPrevented = true; }};
    assert.equal(clickHandlers[0].capture, true);
    clickHandlers[0].callback(event);
    const dialog = nodes.find(node => node.tag === 'dialog');
    assert.equal(event.defaultPrevented, true);
    assert.equal(dialog.open, true);
    assert.equal(dialog.attrs['--auth-viewport-height'], '640px');
    assert.equal(nodes.find(node => node.className === 'auth-modal-content').children[0].tag, 'login-form');
    assert.equal(inputFocused, false);
    window.visualViewport.height = 360; viewportEvents.resize();
    assert.equal(dialog.attrs['--auth-viewport-height'], '360px');
});
