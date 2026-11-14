import * as pdfjs from 'pdfjs-dist/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { PDFDocument } from 'pdf-lib';
import { buildCertificatePdf, isQrField, fieldText, fontNames, PT_PER_MM, textLayout } from './certificate-pdf-core';

pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;
const byId = id => document.getElementById(id);
const readConfig = id => JSON.parse(byId(id).textContent);
const round = value => Math.round(value * 100) / 100;
async function fetchPdf(url) {
    const response = await fetch(url);
    if (!response.ok) throw new Error('File desain PDF tidak dapat dimuat. Periksa file template.');
    return new Uint8Array(await response.arrayBuffer());
}
function errorMessage(error) {
    if (/encrypt|password/i.test(error.message)) return 'PDF terkunci. Unggah PDF tanpa kata sandi.';
    return error.message || 'PDF tidak dapat diproses. Periksa kembali berkas desain.';
}

async function startEditor() {
    const config = readConfig('certificate-editor-config');
    const status = byId('editor-status');
    const setStatus = (text, error = false) => { status.textContent = text; status.style.color = error ? '#b91c1c' : '#64748b'; };
    let dirty = false, saving = false, selected = -1, pageNumber = 1, renderTask = null, generation = 0;
    const layout = structuredClone(config.layout);
    layout.forEach(row => { row.line_height ??= Math.min(144, row.font_size * 1.2); row.wrap ??= false; row.height ??= row.width; });
    const bytes = await fetchPdf(config.pdfUrl);
    const pdf = await pdfjs.getDocument({ data: bytes.slice(), isEvalSupported: false }).promise;
    if (!pdf.numPages || pdf.numPages > 10) throw new Error('PDF harus berisi 1 sampai 10 halaman.');
    const pages = [];
    for (let i = 1; i <= pdf.numPages; i++) {
        const page = await pdf.getPage(i);
        const viewport = page.getViewport({ scale: 1 });
        pages.push({ width: round(viewport.width / PT_PER_MM), height: round(viewport.height / PT_PER_MM) });
        byId('pdf-page').add(new Option('Halaman ' + i + ' · ' + pages[i - 1].width + ' × ' + pages[i - 1].height + ' mm', i));
    }
    const metricsDoc = await PDFDocument.create();
    const fonts = {};
    for (const [family, names] of Object.entries(fontNames)) fonts[family] = await Promise.all(names.map(name => metricsDoc.embedFont(name)));
    const properties = ['x', 'y', 'width', 'height', 'font_size', 'line_height', 'wrap', 'align', 'font', 'bold', 'color', 'text'];
    let scale = 1;
    function changed() { dirty = true; setStatus('Ada perubahan belum disimpan.'); }
    function syncProperties() {
        const row = layout[selected];
        byId('field-properties').disabled = !row;
        byId('custom-text-label').hidden = !row || row.key !== 'custom';
        const isQr = isQrField(row?.key);
        byId('prop-height').closest('label').hidden = !isQr;
        byId('text-field-properties').hidden = isQr;
        byId('qr-field-help').hidden = !isQr;
        if (row) for (const key of properties) {
            const input = byId('prop-' + key);
            if (key === 'bold' || key === 'wrap') input.checked = row[key];
            else input.value = row[key] ?? '';
        }
    }
    function drawOverlays() {
        const list = byId('selected-field'); list.replaceChildren();
        const container = byId('pdf-overlays'); container.replaceChildren();
        layout.forEach((row, index) => {
            if (Number(row.page) !== pageNumber) return;
            list.add(new Option(config.fields[row.key] + (row.key === 'custom' ? ': ' + row.text : ''), index, false, selected === index));
            const box = document.createElement('div');
            const isQr = isQrField(row.key);
            if (isQr) {
                const size = Number(row.height ?? row.width);
                Object.assign(box.style, { position: 'absolute', left: row.x * PT_PER_MM * scale + 'px', top: row.y * PT_PER_MM * scale + 'px',
                    width: row.width * PT_PER_MM * scale + 'px', height: size * PT_PER_MM * scale + 'px', outline: index === selected ? '2px solid #762137' : '1px dashed #64748b',
                    background: index === selected ? '#fff4' : 'white', cursor: 'move', userSelect: 'none', touchAction: 'none' });
                if (config.values[row.key + '_svg']) {
                    const image = document.createElement('img');
                    image.alt = 'QR verifikasi sertifikat'; image.draggable = false;
                    image.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(config.values[row.key + '_svg'])}`;
                    Object.assign(image.style, { width: '100%', height: '100%', display: 'block', pointerEvents: 'none' });
                    box.append(image);
                } else box.textContent = 'QR TTE';
            } else {
            let text = fieldText(row, config.values) || config.fields[row.key];
            let size = Number(row.font_size);
            try {
                const positioned = textLayout(row, text, fonts[row.font][row.bold ? 1 : 0], pages[pageNumber - 1].width * PT_PER_MM, pages[pageNumber - 1].height * PT_PER_MM);
                size = positioned.size; text = positioned.lines.map(line => line.text).join('\n');
            } catch { box.title = 'Posisi teks berada di luar halaman'; }
            Object.assign(box.style, { position: 'absolute', left: row.x * PT_PER_MM * scale + 'px', top: row.y * PT_PER_MM * scale + 'px',
                width: row.width * PT_PER_MM * scale + 'px', minHeight: size * scale + 'px', fontSize: size * scale + 'px', lineHeight: row.line_height * scale + 'px',
                fontFamily: { serif: 'Times New Roman, serif', sans: 'Arial, sans-serif', mono: 'Courier New, monospace' }[row.font],
                fontWeight: row.bold ? 'bold' : 'normal', color: row.color, textAlign: row.align, whiteSpace: 'pre',
                outline: index === selected ? '2px solid #dc2626' : '1px dashed #64748b', background: index === selected ? '#fff4' : 'transparent',
                cursor: 'move', userSelect: 'none', touchAction: 'none' });
            box.textContent = text;
            }
            box.addEventListener('pointerdown', event => {
                if (saving) return;
                event.preventDefault(); event.stopPropagation();
                selected = index;
                list.value = String(index); syncProperties();
                const start = { x: event.clientX, y: event.clientY, rowX: Number(row.x), rowY: Number(row.y) };
                const move = e => {
                    const bounds = pages[pageNumber - 1];
                    row.x = round(Math.max(0, Math.min(bounds.width - row.width, start.rowX + (e.clientX - start.x) / (PT_PER_MM * scale))));
                    const rowHeight = isQrField(row.key) ? Number(row.height ?? row.width) : row.font_size / PT_PER_MM;
                    row.y = round(Math.max(0, Math.min(bounds.height - rowHeight, start.rowY + (e.clientY - start.y) / (PT_PER_MM * scale))));
                    box.style.left = row.x * PT_PER_MM * scale + 'px'; box.style.top = row.y * PT_PER_MM * scale + 'px'; syncProperties(); changed();
                };
                const up = () => { document.removeEventListener('pointermove', move); document.removeEventListener('pointerup', up); drawOverlays(); };
                document.addEventListener('pointermove', move); document.addEventListener('pointerup', up, { once: true });
            });
            container.append(box);
        });
        syncProperties();
    }
    async function renderPage() {
        const current = ++generation;
        if (renderTask) { renderTask.cancel(); try { await renderTask.promise; } catch { /* cancelled page */ } }
        const page = await pdf.getPage(pageNumber);
        if (current !== generation) return;
        scale = Math.max(.2, Math.min(1.5, (byId('pdf-canvas-container').clientWidth - 28) / page.getViewport({ scale: 1 }).width));
        const viewport = page.getViewport({ scale });
        const canvas = byId('pdf-canvas'); const ratio = window.devicePixelRatio || 1;
        canvas.width = Math.ceil(viewport.width * ratio); canvas.height = Math.ceil(viewport.height * ratio);
        canvas.style.width = viewport.width + 'px'; canvas.style.height = viewport.height + 'px';
        Object.assign(byId('pdf-stage').style, { width: viewport.width + 'px', height: viewport.height + 'px' });
        renderTask = page.render({ canvasContext: canvas.getContext('2d'), viewport, transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0] });
        try { await renderTask.promise; } catch (error) { if (error.name !== 'RenderingCancelledException') throw error; }
        if (current === generation) drawOverlays();
    }
    byId('pdf-page').addEventListener('change', async event => { pageNumber = Number(event.target.value); selected = -1; await renderPage(); });
    byId('selected-field').addEventListener('change', event => { selected = Number(event.target.value); drawOverlays(); });
    byId('add-field').addEventListener('click', () => {
        if (saving) return;
        if (layout.length >= 150) return setStatus('Maksimal 150 isian per template.', true);
        const key = byId('field-key').value;
        const isQr = isQrField(key);
        layout.push({ key, page: pageNumber, x: 20, y: 30, width: isQr ? 22 : Math.min(100, pages[pageNumber - 1].width - 20), height: isQr ? 22 : 8, font_size: 14, line_height: 17, wrap: false, align: 'left', font: 'serif', bold: false, color: '#000000', text: 'Teks tambahan' });
        selected = layout.length - 1; changed(); drawOverlays();
    });
    for (const key of properties) byId('prop-' + key).addEventListener('input', event => {
        if (!layout[selected] || saving) return;
        const numeric = ['x', 'y', 'width', 'height', 'font_size', 'line_height'].includes(key);
        layout[selected][key] = key === 'bold' || key === 'wrap' ? event.target.checked : numeric ? Number(event.target.value) : event.target.value;
        changed(); drawOverlays();
    });
    byId('remove-field').addEventListener('click', () => { if (selected < 0 || saving) return; layout.splice(selected, 1); selected = -1; changed(); drawOverlays(); });
    byId('save-layout').addEventListener('click', async () => {
        saving = true; byId('save-layout').disabled = true;
        try {
            // The exact renderer also checks bounds, long text, and valid PDF pages.
            await buildCertificatePdf(bytes, layout, config.values, true);
            const response = await fetch(config.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf }, body: JSON.stringify({ version: config.version, layout, pages }) });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Koordinat gagal disimpan.');
            config.version = result.version; dirty = false; setStatus(result.message);
        } catch (error) { setStatus(errorMessage(error), true); }
        finally { saving = false; byId('save-layout').disabled = false; }
    });
    byId('preview-layout').addEventListener('click', async () => {
        const tab = window.open('', '_blank');
        try {
            const output = await buildCertificatePdf(bytes, layout, config.values, true);
            const url = URL.createObjectURL(new Blob([output], { type: 'application/pdf' }));
            if (tab) tab.location.href = url;
            else { const a = document.createElement('a'); a.href = url; a.download = 'DRAF-template.pdf'; a.click(); }
            setTimeout(() => URL.revokeObjectURL(url), 120000);
        } catch (error) { tab?.close(); setStatus(errorMessage(error), true); }
    });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
    let resizeTimer;
    window.addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(() => renderPage().catch(error => setStatus(errorMessage(error), true)), 200); });
    await renderPage();
    for (const id of ['save-layout', 'preview-layout', 'add-field']) byId(id).disabled = false;
    setStatus('PDF siap. Pilih isian dan atur posisinya.');
}

async function startOutput() {
    const config = readConfig('certificate-output-config');
    const bytes = await fetchPdf(config.pdfUrl);
    const output = await buildCertificatePdf(bytes, config.layout, config.values, config.draft);
    const url = URL.createObjectURL(new Blob([output], { type: 'application/pdf' }));
    byId('pdf-result').src = url;
    byId('output-status').textContent = config.draft ? 'Pratinjau DRAF' : 'PDF siap dicetak';
    byId('download-pdf').disabled = false; byId('print-pdf').disabled = false;
    byId('download-pdf').addEventListener('click', () => { const a = document.createElement('a'); a.href = url; a.download = config.filename; a.click(); });
    byId('print-pdf').addEventListener('click', () => {
        try { byId('pdf-result').contentWindow.focus(); byId('pdf-result').contentWindow.print(); }
        catch { window.open(url, '_blank', 'noopener'); }
    });
    window.addEventListener('pagehide', () => URL.revokeObjectURL(url), { once: true });
}

if (byId('certificate-editor')) startEditor().catch(error => { byId('editor-status').textContent = errorMessage(error); byId('editor-status').style.color = '#b91c1c'; });
if (byId('certificate-output-config')) startOutput().catch(error => { byId('output-status').textContent = 'PDF belum siap'; byId('pdf-status').textContent = errorMessage(error); });
