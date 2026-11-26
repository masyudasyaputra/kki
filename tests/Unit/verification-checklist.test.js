import test from 'node:test';
import assert from 'node:assert/strict';
import verificationChecklist from '../../resources/js/verification-checklist.js';

test('select all and clear keep every field and its submission model in sync', () => {
    const state = verificationChecklist(['name', 'dojo', 'belt_diploma'], ['name']);
    assert.equal(state.someChecked, true);
    assert.equal(state.allChecked, false);
    state.toggleAll(true);
    assert.deepEqual(state.checked, ['name', 'dojo', 'belt_diploma']);
    assert.equal(state.allChecked, true);
    assert.equal(state.someChecked, false);
    state.checked.splice(1, 1);
    assert.equal(state.allChecked, false);
    assert.equal(state.someChecked, true);
    state.toggleAll(false);
    assert.deepEqual(state.checked, []);
    assert.equal(state.someChecked, false);
});

test('restored numeric IDs are normalized and unavailable reviewers are excluded', () => {
    const state = verificationChecklist([2, 5, 5], [2, '5', 999, 2]);
    assert.deepEqual(state.checked, ['2', '5']);
    assert.equal(state.allChecked, true);
    state.checked = ['2'];
    assert.equal(state.someChecked, true);
});

test('an empty or unselected checklist does not report all selected', () => {
    const state = verificationChecklist([], null);
    state.toggleAll(true);
    assert.equal(state.allChecked, false);
    assert.equal(state.someChecked, false);
    assert.deepEqual(state.checked, []);
});
