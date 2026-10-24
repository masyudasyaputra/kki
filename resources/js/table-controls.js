import { compareValues } from './table-values';

const mobile = window.matchMedia('(max-width: 767px)');
const clean = value => String(value ?? '').replace(/\s+/g, ' ').trim();
const element = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    return node;
};
const button = (label, handler, style = 'btn btn-secondary') => {
    const node = element('button', style, label);
    node.type = 'button';
    node.addEventListener('click', handler);
    return node;
};
const option = (value, label) => new Option(label, value);
const cellText = cell => {
    if (!cell) return '';
    if (cell.dataset.sortValue !== undefined) return cell.dataset.sortValue;
    const input = cell.querySelector('input:not([type="hidden"]):not([type="checkbox"]),select');
    if (input) return input instanceof HTMLSelectElement ? input.selectedOptions[0]?.textContent : input.value;
    const copy = cell.cloneNode(true);
    copy.querySelectorAll('button,script,[role="tooltip"],.pointer-events-none,.absolute').forEach(node => node.remove());
    return clean(copy.textContent);
};

// Move the same controls into a native modal on mobile: no duplicate IDs or listeners.
function responsiveFilters(controls, mount, apply, summary) {
    const home = element('div', 'table-filter-home');
    mount.append(home);
    const trigger = button('\u2637 Filter & Cari', () => dialog.showModal());
    trigger.classList.add('table-filter-trigger');
    trigger.setAttribute('aria-haspopup', 'dialog');
    const note = element('span', 'table-filter-summary');
    const dialog = element('dialog', 'table-filter-dialog');
    const heading = element('div', 'table-filter-heading');
    const title = element('h2', '', 'Filter & cari data');
    title.id = `filter-title-${document.querySelectorAll('.table-filter-dialog').length}`;
    dialog.setAttribute('aria-labelledby', title.id);
    heading.append(title, button('�', () => dialog.close()));
    const body = element('div', 'table-filter-body');
    const footer = element('div', 'table-filter-footer');
    footer.append(button('Tutup', () => dialog.close()), button('Terapkan', () => { apply(); dialog.close(); }, 'btn btn-primary'));
    dialog.append(heading, body, footer);
    (controls.closest('[x-data]') ?? document.body).append(dialog);
    mount.prepend(trigger, note);
    dialog.addEventListener('click', event => { if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close(); } });
    const place = () => {
        if (dialog.open) dialog.close();
        const move = () => (mobile.matches ? body : home).append(controls);
        if (window.Alpine) window.Alpine.mutateDom(move); else move();
    };
    const update = () => { note.textContent = summary(); };
    controls.addEventListener('change', update);
    controls.addEventListener('input', update);
    mobile.addEventListener('change', place);
    place(); update();
    return update;
}

function addSortSelect(container, columns, onSort, active = '', direction = 'asc') {
    const select = element('select', 'table-sort-select');
    select.setAttribute('aria-label', 'Urutkan data');
    select.append(option('', 'Urutan bawaan'));
    columns.forEach(column => {
        select.append(option(`${column.key}:asc`, `${column.label} \u2191`), option(`${column.key}:desc`, `${column.label} \u2193`));
    });
    select.value = active ? `${active}:${direction}` : '';
    select.addEventListener('change', () => onSort(...select.value.split(':')));
    container.append(select);
    return select;
}

function headings(table, server) {
    return Array.from(table.tHead?.rows[0]?.cells ?? []).map((th, index) => ({ th, index, label: clean(th.textContent), key: server ? th.dataset.sortKey : String(index) }))
        .filter(column => column.label && !/^(aksi|action|pilih|no\.?$)/i.test(column.label) && !column.th.querySelector('input') && (!server || column.key));
}

function sortHeaders(columns, sort) {
    columns.forEach(column => {
        column.th.scope = 'col';
        column.th.setAttribute('aria-sort', 'none');
        const control = button(column.label, () => sort(column.key, column.th.getAttribute('aria-sort') === 'ascending' ? 'desc' : 'asc'), 'table-sort-button');
        control.append(element('span', 'table-sort-indicator', '\u2195'));
        column.th.replaceChildren(control);
    });
}
function showOrder(columns, key, direction) {
    columns.forEach(({ th, key: current }) => {
        const active = current === key;
        th.setAttribute('aria-sort', active ? direction === 'desc' ? 'descending' : 'ascending' : 'none');
        th.querySelector('.table-sort-indicator').textContent = active ? direction === 'desc' ? '\u2193' : '\u2191' : '\u2195';
    });
}

// Keep creation actions beside the filter controls, outside the mobile modal.
function attachTableActions(table, mount) {
    let scope = table.parentElement;
    while (scope && scope !== document.body) {
        const candidates = Array.from(scope.querySelectorAll('[data-table-add-actions]'))
            .filter(node => !node.closest('.table-filter-toolbar') && (node.compareDocumentPosition(table) & Node.DOCUMENT_POSITION_FOLLOWING));
        if (candidates.length) {
            const actions = candidates[candidates.length - 1];
            actions.classList.add('table-add-actions');
            const move = () => mount.append(actions);
            if (window.Alpine) window.Alpine.mutateDom(move); else move();
            return;
        }
        scope = scope.parentElement;
    }
}

function serverTable(table, form) {
    if (!form) return;
    const columns = headings(table, true);
    const params = new URLSearchParams(location.search);
    const mount = element('div', 'table-filter-toolbar');
    form.before(mount);
    form.parentElement.classList.add('table-filter-host');
    form.classList.add('table-filter-fields');
    const hidden = (name, value) => {
        let field = form.querySelector(`input[name="${name}"]`);
        if (!field) { field = document.createElement('input'); field.type = 'hidden'; field.name = name; form.append(field); }
        field.value = value ?? '';
        return field;
    };
    const key = hidden('sort', params.get('sort'));
    const direction = hidden('direction', params.get('direction') ?? 'asc');
    const submit = () => { form.requestSubmit(); };
    const sort = (column, order = 'asc') => { key.value = column ?? ''; direction.value = order; if (!mobile.matches) submit(); };
    const sortSelect = addSortSelect(form, columns, sort, key.value, direction.value);
    sortHeaders(columns, (column, order) => { sort(column, order); if (mobile.matches) submit(); });
    showOrder(columns, key.value, direction.value);
    const fields = Array.from(form.querySelectorAll('input:not([type="hidden"]),select:not(.table-sort-select)'));
    fields.forEach(field => {
        const label = field.closest('div')?.querySelector('label')?.textContent;
        if (!field.getAttribute('aria-label')) field.setAttribute('aria-label', clean(label || field.getAttribute('placeholder') || field.options?.[0]?.textContent || field.name));
    });
    let timer;
    form.addEventListener('input', event => {
        if (event.target.matches('input:not([type="hidden"])') && !mobile.matches) { clearTimeout(timer); timer = setTimeout(submit, 500); }
    });
    form.addEventListener('change', async event => {
        if (event.target === sortSelect) return;
        const province = event.target.name === 'province_id';
        const city = form.querySelector('[name="city_id"]');
        if (province && city) {
            city.replaceChildren(option('', 'Semua Kota')); city.disabled = !event.target.value;
            if (event.target.value) {
                const requested = event.target.value;
                try { const response = await fetch(`/api/cities/${encodeURIComponent(requested)}`); if (!response.ok) throw new Error('cities'); const cities = await response.json(); if (event.target.value === requested) cities.forEach(item => city.add(option(item.id, item.name))); }
                catch { city.disabled = true; }
            }
        }
        if (!mobile.matches) submit();
    });
    form.addEventListener('submit', () => { clearTimeout(timer); });
    responsiveFilters(form, mount, submit, () => {
        const count = fields.filter(field => !field.disabled && field.value !== '').length;
        return count ? `${count} filter aktif` : '';
    });
    attachTableActions(table, mount);
    // Keep sorting when a GET form changes the filters, but restart pagination.
    form.querySelectorAll('[name="page"]').forEach(field => field.remove());
}

function localTable(table) {
    const columns = headings(table, false);
    const tbody = table.tBodies[0];
    if (!tbody || !columns.length) return;
    const rows = Array.from(tbody.rows).filter(row => !row.querySelector('td[colspan]'));
    const mount = element('div', 'table-filter-toolbar');
    let boundary = table.closest('.table-surface') ?? table;
    // A few tables are inside a separate desktop-only wrapper.
    if (boundary.parentElement?.classList.contains('hidden')) boundary = boundary.parentElement;
    boundary.before(mount);
    const controls = element('div', 'table-filter-fields table-local-fields');
    const search = element('input'); search.type = 'search'; search.placeholder = 'Cari data...'; search.setAttribute('aria-label', 'Cari data tabel'); controls.append(search);
    const filters = [];
    columns.filter(column => /status|masa|wilayah|sabuk|kyu|dan|lokasi|tingkatan/i.test(column.label)).forEach(column => {
        const filterText = cell => /status|masa/i.test(column.label) ? clean(cell?.querySelector('span')?.textContent ?? cellText(cell)) : cellText(cell);
        const values = Array.from(new Set(rows.map(row => filterText(row.cells[column.index])))).filter(Boolean).sort(compareValues);
        if (!values.length || values.length > 50) return;
        const select = element('select'); select.setAttribute('aria-label', column.label);
        select.append(option('', `Semua ${column.label}`));
        values.forEach(value => select.append(option(value, value)));
        controls.append(select); filters.push({ select, index: column.index, filterText });
    });
    let sortKey = '', sortDirection = 'asc';
    const empty = element('tr'); const emptyCell = element('td', '', 'Tidak ada data yang sesuai.'); emptyCell.colSpan = table.tHead.rows[0].cells.length; emptyCell.style.textAlign = 'center'; empty.append(emptyCell); empty.hidden = true; tbody.append(empty);
    const mobileEmpty = element('p', 'table-mobile-empty', 'Tidak ada data yang sesuai.'); mobileEmpty.hidden = true; mount.after(mobileEmpty);
    const cards = new Map(rows.map(row => [row, row.dataset.recordKey ? document.querySelector(`[data-card-key="${CSS.escape(row.dataset.recordKey)}"]`) : null]));
    const apply = () => {
        const query = clean(search.value).toLocaleLowerCase('id');
        let visible = 0;
        const sorted = [...rows];
        if (sortKey !== '') sorted.sort((a,b) => compareValues(cellText(a.cells[Number(sortKey)]), cellText(b.cells[Number(sortKey)])) * (sortDirection === 'desc' ? -1 : 1));
        sorted.forEach(row => {
            const text = columns.map(column => cellText(row.cells[column.index])).join(' ').toLocaleLowerCase('id');
            const match = text.includes(query) && filters.every(({ select, index, filterText }) => !select.value || filterText(row.cells[index]) === select.value);
            row.hidden = !match; if (match) visible++;
            tbody.append(row);
            const card = cards.get(row); if (card) { card.hidden = !match; card.parentElement.append(card); }
        });
        tbody.append(empty); empty.hidden = !rows.length || visible > 0;
        mobileEmpty.hidden = !rows.length || visible > 0 || !Array.from(cards.values()).some(Boolean);
        showOrder(columns, sortKey, sortDirection);
    };
    const sort = (key = '', direction = 'asc') => { sortKey = key; sortDirection = direction; sortSelect.value = key === '' ? '' : `${key}:${direction}`; if (!mobile.matches) apply(); };
    const sortSelect = addSortSelect(controls, columns, sort);
    controls.append(button('Reset', () => { search.value = ''; filters.forEach(({select}) => { select.value = ''; }); sortKey = ''; sortSelect.value = ''; apply(); update(); }));
    sortHeaders(columns, (key, direction) => { sort(key, direction); apply(); });
    controls.addEventListener('input', () => { if (!mobile.matches) apply(); });
    controls.addEventListener('change', () => { if (!mobile.matches) apply(); });
    const update = responsiveFilters(controls, mount, apply, () => {
        const count = Number(Boolean(search.value)) + filters.filter(({select}) => select.value).length;
        return count ? `${count} filter aktif` : '';
    });
    attachTableActions(table, mount);
}

function init() {
    document.querySelectorAll('[data-inline-filters]').forEach(controls => {
        const mount = element('div', 'table-filter-toolbar');
        controls.before(mount);
        controls.classList.add('table-filter-fields');
        controls.querySelectorAll('input,select').forEach(field => field.setAttribute('aria-label', clean(field.parentElement.querySelector('label')?.textContent || field.placeholder || 'Filter data')));
        responsiveFilters(controls, mount, () => {}, () => {
            const count = Array.from(controls.querySelectorAll('input,select')).filter(field => field.value !== '').length;
            return count ? count + ' filter aktif' : '';
        });
    });
    document.querySelectorAll('table.data-table').forEach(table => {
        if (table.dataset.controlsReady) return;
        table.dataset.controlsReady = 'true';
        if (table.dataset.serverSort) serverTable(table, document.querySelector('form[data-table-filters]'));
        else localTable(table);
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
