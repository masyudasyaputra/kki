import { PDFDocument, StandardFonts, degrees, rgb } from 'pdf-lib';

export const PT_PER_MM = 72 / 25.4;
export const fontNames = {
    serif: [StandardFonts.TimesRoman, StandardFonts.TimesRomanBold],
    sans: [StandardFonts.Helvetica, StandardFonts.HelveticaBold],
    mono: [StandardFonts.Courier, StandardFonts.CourierBold],
};

export function fieldText(row, values) {
    const text = String(row.key === 'custom' ? row.text ?? '' : values[row.key] ?? '');
    return row.wrap ? text.split(/\r?\n/).map(line => line.replace(/\s+/g, ' ').trim()).join('\n').trim() : text.replace(/\s+/g, ' ').trim();
}

export function textLayout(row, text, font, pageWidth, pageHeight) {
    const lineHeight = Number(row.line_height ?? Number(row.font_size) * 1.2);
    if (!Number.isFinite(lineHeight) || lineHeight < 4 || lineHeight > 144) throw new Error('Tinggi baris harus antara 4 dan 144 pt.');
    const lines = [];
    if (row.wrap) {
        const maxWidth = Number(row.width) * PT_PER_MM;
        for (const paragraph of text.split('\n')) {
            let line = '';
            for (const word of paragraph.split(' ')) {
                const candidate = line ? line + ' ' + word : word;
                if (line && font.widthOfTextAtSize(candidate, Number(row.font_size)) > maxWidth) { lines.push(line); line = ''; }
                if (font.widthOfTextAtSize(word, Number(row.font_size)) > maxWidth) {
                    for (const character of word) {
                        if (line && font.widthOfTextAtSize(line + character, Number(row.font_size)) > maxWidth) { lines.push(line); line = ''; }
                        line += character;
                    }
                } else { line = line ? line + ' ' + word : word; }
            }
            lines.push(line);
        }
    } else lines.push(text);
    const positioned = lines.map((line, index) => ({ text: line, ...textGeometry({ ...row, y: Number(row.y) + index * lineHeight / PT_PER_MM }, line, font, pageWidth, pageHeight) }));
    return { lines: positioned, lineHeight, size: positioned[0]?.size ?? Number(row.font_size) };
}

export function textGeometry(row, text, font, pageWidth, pageHeight) {
    const x = Number(row.x) * PT_PER_MM;
    const top = Number(row.y) * PT_PER_MM;
    const width = Number(row.width) * PT_PER_MM;
    let size = Number(row.font_size);
    if (![x, top, width, size].every(Number.isFinite) || x < 0 || top < 0 || width <= 0 || size < 4 || size > 72 || x + width > pageWidth + .3) {
        throw new Error('Koordinat atau lebar teks berada di luar halaman PDF.');
    }
    const measured = font.widthOfTextAtSize(text, size);
    if (measured > width) size = Math.max(4, size * width / measured);
    if (font.widthOfTextAtSize(text, size) > width + .1) throw new Error('Teks terlalu panjang. Perbesar lebar kotak atau kurangi teks.');
    const ascent = font.heightAtSize(size, { descender: false });
    if (top + size > pageHeight + .3) throw new Error('Posisi teks melewati batas bawah halaman PDF.');
    const textWidth = font.widthOfTextAtSize(text, size);
    const offset = row.align === 'center' ? (width - textWidth) / 2 : row.align === 'right' ? width - textWidth : 0;
    return { x: x + offset, y: pageHeight - top - ascent, size, width };
}

async function svgPngBytes(svg) {
    const image = new Image();
    image.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
    await image.decode();
    const canvas = document.createElement('canvas');
    canvas.width = image.naturalWidth || 240;
    canvas.height = image.naturalHeight || 240;
    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
    if (!blob) throw new Error('QR verifikasi tidak dapat disiapkan untuk PDF.');
    return new Uint8Array(await blob.arrayBuffer());
}

export async function buildCertificatePdf(bytes, layout, values, draft = false) {
    const source = await PDFDocument.load(bytes);
    if (!source.getPageCount() || source.getPageCount() > 10) throw new Error('PDF harus berisi 1 sampai 10 halaman.');
    const output = await PDFDocument.create();
    const fonts = {};
    for (const [family, names] of Object.entries(fontNames)) fonts[family] = await Promise.all(names.map(name => output.embedFont(name)));
    const pages = [];
    for (const original of source.getPages()) {
        const crop = original.getCropBox();
        const embedded = await output.embedPage(original, { left: crop.x, bottom: crop.y, right: crop.x + crop.width, top: crop.y + crop.height });
        const rotation = ((original.getRotation().angle % 360) + 360) % 360;
        if (![0, 90, 180, 270].includes(rotation)) throw new Error('Rotasi halaman PDF tidak didukung.');
        const swapped = rotation === 90 || rotation === 270;
        const page = output.addPage(swapped ? [crop.height, crop.width] : [crop.width, crop.height]);
        const origin = { 0: [0, 0], 90: [0, crop.width], 180: [crop.width, crop.height], 270: [crop.height, 0] }[rotation];
        page.drawPage(embedded, { x: origin[0], y: origin[1], rotate: degrees(-rotation) });
        pages.push(page);
    }
    for (const row of layout) {
        const page = pages[Number(row.page) - 1];
        if (!page) throw new Error('Nomor halaman pada koordinat tidak ada dalam PDF.');
        if (row.key === 'verification_qr') {
            if (!values.verification_qr_svg) continue;
            const width = Number(row.width) * PT_PER_MM;
            const height = Number(row.height ?? row.width) * PT_PER_MM;
            const x = Number(row.x) * PT_PER_MM;
            const top = Number(row.y) * PT_PER_MM;
            if (![x, top, width, height].every(Number.isFinite) || x < 0 || top < 0 || width <= 0 || height <= 0 || x + width > page.getWidth() + .3 || top + height > page.getHeight() + .3) {
                throw new Error('Koordinat QR berada di luar halaman PDF.');
            }
            const image = await output.embedPng(await svgPngBytes(values.verification_qr_svg));
            page.drawImage(image, { x, y: page.getHeight() - top - height, width, height });
            continue;
        }
        const text = fieldText(row, values);
        if (!text) continue;
        const font = fonts[row.font]?.[row.bold ? 1 : 0];
        if (!font) throw new Error('Jenis huruf tidak didukung.');
        const positions = textLayout(row, text, font, page.getWidth(), page.getHeight());
        if (!/^#[\da-f]{6}$/i.test(row.color)) throw new Error('Warna teks tidak valid.');
        const channels = row.color.slice(1).match(/../g).map(channel => parseInt(channel, 16) / 255);
        for (const position of positions.lines) page.drawText(position.text, { x: position.x, y: position.y, size: position.size, font, color: rgb(...channels) });
    }
    if (draft) {
        for (const page of pages) {
            const size = Math.min(90, page.getWidth() / 5);
            page.drawText('DRAF', { x: page.getWidth() * .22, y: page.getHeight() * .4, font: fonts.sans[1], size, rotate: degrees(25), color: rgb(.8, .1, .1), opacity: .22 });
        }
    }
    return output.save();
}
