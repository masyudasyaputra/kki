import test from 'node:test';
import assert from 'node:assert/strict';
import { compareValues } from '../../resources/js/table-values.js';

test('sorts Indonesian currency and counts numerically', () => {
    assert.ok(compareValues('Rp 9.000', 'Rp 100.000') < 0);
    assert.ok(compareValues('12 Orang', '2 Orang') > 0);
    assert.ok(compareValues('#2', '#10') < 0);
    assert.ok(compareValues('1.500,50', '1.500,75') < 0);
});
test('sorts displayed dates chronologically, including dates after status text', () => {
    assert.ok(compareValues('31/12/2025', '01/01/2026') < 0);
    assert.ok(compareValues('Aktif s/d 31/12/2025', 'Aktif s/d 01/01/2026') < 0);
    assert.ok(compareValues('10 Okt 2026', '02 Nov 2026') < 0);
});
test('sorts names naturally and ignores case', () => {
    assert.ok(compareValues('Dojo 2', 'Dojo 10') < 0);
    assert.equal(compareValues('ADI', 'adi'), 0);
    assert.ok(compareValues(null, 'Adi') < 0);
});
