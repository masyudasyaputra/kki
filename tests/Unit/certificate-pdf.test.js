import test from 'node:test';
import assert from 'node:assert/strict';
import { PDFDocument, StandardFonts, degrees } from 'pdf-lib';
import { readFile } from 'node:fs/promises';
import { buildCertificatePdf, fieldText, PT_PER_MM, textGeometry, textLayout } from '../../resources/js/certificate-pdf-core.js';

const row = overrides => ({ key: 'name', page: 1, x: 30, y: 50, width: 120, font_size: 18, align: 'center', font: 'serif', bold: true, color: '#000000', ...overrides });
async function sourcePdf(rotation = 0) {
    const doc = await PDFDocument.create();
    const page = doc.addPage([842, 595]);
    page.drawText('DESAIN ASLI', { x: 40, y: 550 });
    if (rotation) page.setRotation(degrees(rotation));
    const back = doc.addPage([842, 595]); back.drawText('LEMBAR NILAI', { x: 40, y: 550 });
    return doc.save();
}

test('PDF embeds the QR belonging to each of three signers rather than reusing the certificate QR', async () => {
    const png = await readFile(new URL('../../public/images/logo-kki-dark.png', import.meta.url));
    const originalImage = globalThis.Image, originalDocument = globalThis.document;
    const decoded = [];
    globalThis.Image = class { naturalWidth = 240; naturalHeight = 240; async decode() { decoded.push(decodeURIComponent(this.src)); } };
    globalThis.document = { createElement: () => ({ getContext: () => ({ drawImage() {} }), toBlob: callback => callback(new Blob([png], { type: 'image/png' })) }) };
    try {
        const values = {}, rows = [];
        for (const [index, side] of ['left', 'center', 'right'].entries()) {
            const key = `signer_${side}_qr`;
            values[key + '_svg'] = `<svg data-signer="${side}" xmlns="http://www.w3.org/2000/svg"></svg>`;
            rows.push(row({ key, x: 30 + index * 40, width: 22, height: 22 }));
        }
        const pdf = await PDFDocument.load(await buildCertificatePdf(await sourcePdf(), rows, values));
        assert.equal(pdf.getPageCount(), 2);
        assert.equal(decoded.length, 3);
        for (const [index, side] of ['left', 'center', 'right'].entries()) assert.ok(decoded[index].includes(`data-signer="${side}"`));
    } finally {
        if (originalImage === undefined) delete globalThis.Image; else globalThis.Image = originalImage;
        if (originalDocument === undefined) delete globalThis.document; else globalThis.document = originalDocument;
    }
});

test('top-left millimeters convert to PDF baseline and alignment precisely', async () => {
    const doc = await PDFDocument.create(); const font = await doc.embedFont(StandardFonts.TimesRomanBold);
    const item = row(); const text = 'NAMA PESERTA';
    const result = textGeometry(item, text, font, 842, 595);
    assert.ok(Math.abs(result.y - (595 - 50 * PT_PER_MM - font.heightAtSize(18, { descender: false }))) < .0001);
    assert.ok(Math.abs(result.x - (30 * PT_PER_MM + (120 * PT_PER_MM - font.widthOfTextAtSize(text, 18)) / 2)) < .0001);
    assert.equal(result.size, 18);
    assert.throws(() => textGeometry(row({ x: 290 }), text, font, 842, 595), /luar halaman/);
});

test('long text fits its box and free text remains literal', async () => {
    const doc = await PDFDocument.create(); const font = await doc.embedFont(StandardFonts.Helvetica);
    const geometry = textGeometry(row({ width: 70 }), 'NAMA PESERTA YANG SANGAT PANJANG SEKALI', font, 842, 595);
    assert.ok(geometry.size < 18);
    assert.ok(font.widthOfTextAtSize('NAMA PESERTA YANG SANGAT PANJANG SEKALI', geometry.size) <= 70 * PT_PER_MM + .1);
    assert.equal(fieldText(row({ key: 'custom', text: '<script>uji</script>' }), {}), '<script>uji</script>');
    assert.equal(fieldText(row(), { name: 'Nama\n Peserta' }), 'Nama Peserta');
});

test('PDF output preserves page count, dimensions and rotated visual orientation', async () => {
    for (const angle of [0, 90, 180, 270]) {
        const output = await buildCertificatePdf(await sourcePdf(angle), [row({ width: 90 }), row({ page: 2, key: 'score_kihon' })], { name: 'PESERTA', score_kihon: '75' }, true);
        const pdf = await PDFDocument.load(output);
        assert.equal(pdf.getPageCount(), 2);
        const page = pdf.getPage(0);
        assert.equal(page.getWidth(), [90, 270].includes(angle) ? 595 : 842);
        assert.equal(page.getHeight(), [90, 270].includes(angle) ? 842 : 595);
        assert.equal(page.getRotation().angle, 0);
        assert.ok(output.length > 1000);
    }
});

test('invalid pages and out-of-page coordinates cannot produce a certificate', async () => {
    const source = await sourcePdf();
    await assert.rejects(() => buildCertificatePdf(source, [row({ page: 3 })], { name: 'UJI' }), /Nomor halaman/);
    await assert.rejects(() => buildCertificatePdf(source, [row({ y: 220 })], { name: 'UJI' }), /batas bawah/);
});

test('wrapped text follows the configured line height and stays inside the page', async () => {
    const doc = await PDFDocument.create(); const font = await doc.embedFont(StandardFonts.Helvetica);
    const item = row({ key: 'custom', wrap: true, width: 55, line_height: 26 });
    const text = 'Atas keberhasilan mengikuti ujian kenaikan tingkat karate dan dinyatakan lulus.';
    const result = textLayout(item, text, font, 842, 595);
    assert.ok(result.lines.length > 1);
    assert.ok(Math.abs(result.lines[0].y - result.lines[1].y - 26) < .001);
    for (const line of result.lines) assert.ok(font.widthOfTextAtSize(line.text, line.size) <= 55 * PT_PER_MM + .1);
    assert.throws(() => textLayout({ ...item, y: 195 }, text, font, 842, 595), /batas bawah/);
});
