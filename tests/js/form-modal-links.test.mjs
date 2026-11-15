import test from 'node:test';
import assert from 'node:assert/strict';
import { isFormModalLink } from '../../resources/js/form-modal-links.js';

const current = 'http://localhost/admin/official-verifications';
const link = (href, attributes = [], target = '') => ({ href, target, hasAttribute: name => attributes.includes(name) });

test('admin creation and editing forms open in the modal across modules', () => {
    for (const path of [
        '/admin/officials/create?user_id=12', '/admin/users/4/edit', '/admin/provinces/1/edit',
        '/admin/dojos/5/edit', '/admin/belt-levels/create', '/admin/belt-levels/7/edit',
        '/admin/nomor-id/4/edit', '/admin/nomor-id/pengurus/2/edit', '/admin/exams/3/examiners/edit',
    ]) assert.equal(isFormModalLink(link(new URL(path, current).href), current), true, path);
});

test('ordinary navigation, external links, downloads and explicit opt-outs retain normal behavior', () => {
    for (const item of [
        link('http://localhost/admin/officials'), link('http://localhost/admin/exams/3'),
        link('https://external.example/admin/users/create'), link('http://localhost/profile/edit'),
        link('http://localhost/admin/users/create', ['download']),
        link('http://localhost/admin/users/create', ['data-no-modal']),
        link('http://localhost/admin/users/create', [], '_blank'),
    ]) assert.equal(isFormModalLink(item, current), false, item.href);
});

test('explicit form modal markers support forms without create or edit suffixes', () => {
    assert.equal(isFormModalLink(link('http://localhost/admin/custom-form', ['data-form-modal']), current), true);
});
