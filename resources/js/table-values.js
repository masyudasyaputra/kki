/** Comparable values for text, Indonesian currency, counts, and displayed dates. */
export function comparable(value) {
    const text = String(value ?? '').replace(/\s+/g, ' ').trim();
    const date = text.match(/(?:^|\s)(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})(?:$|\s)/);
    if (date) return { type: 'number', value: Date.UTC(+date[3], +date[2] - 1, +date[1]) };
    const months = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];
    const named = text.toLowerCase().match(/^(\d{1,2}) ([a-z]+) (\d{4})$/);
    if (named) {
        const month = months.indexOf(({ may: 'mei', aug: 'agu', oct: 'okt', dec: 'des' })[named[2].slice(0, 3)] ?? named[2].slice(0, 3));
        if (month >= 0) return { type: 'number', value: Date.UTC(+named[3], month, +named[1]) };
    }
    const numeric = text.replace(/^(Rp\.?\s*|#)/i, '').match(/^(-?[\d.,]+)(?:\s*(?:orang|dojo|personel|anggota))?$/i);
    if (numeric) {
        const value = Number(numeric[1].replace(/\.(?=\d{3}(?:\D|$))/g, '').replace(',', '.'));
        if (Number.isFinite(value)) return { type: 'number', value };
    }
    return { type: 'text', value: text };
}

export function compareValues(left, right) {
    const a = comparable(left), b = comparable(right);
    if (a.type === 'number' && b.type === 'number') return a.value - b.value;
    return String(a.value).localeCompare(String(b.value), 'id', { numeric: true, sensitivity: 'base' });
}
