const WIDTH = 1016;
const HEIGHT = 640;
const FONT = 'Arial, sans-serif';

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('Foto, logo, atau QR gagal dimuat. Muat ulang halaman lalu coba lagi.'));
        image.src = src;
    });
}

function rounded(ctx, x, y, width, height, radius, fill, stroke) {
    ctx.beginPath();
    ctx.roundRect(x, y, width, height, radius);
    if (fill) { ctx.fillStyle = fill; ctx.fill(); }
    if (stroke) { ctx.strokeStyle = stroke; ctx.lineWidth = 1.5; ctx.stroke(); }
}

function text(ctx, value, x, y, size, color = ctx.cardInk ?? '#f7f4ed', weight = 400) {
    ctx.font = `${weight} ${size}px ${FONT}`;
    ctx.fillStyle = color;
    ctx.fillText(String(value), x, y);
}

// Fit whole values into their reserved area, including unusually long names.
function fitText(ctx, value, x, y, width, size, minSize = 16, color = ctx.cardInk ?? '#f7f4ed', weight = 400) {
    let actual = size;
    do { ctx.font = `${weight} ${actual}px ${FONT}`; }
    while (ctx.measureText(value).width > width && actual-- > minSize);
    let output = value;
    if (ctx.measureText(output).width > width) {
        while (output.length && ctx.measureText(output + '…').width > width) output = output.slice(0, -1);
        output += '…';
    }
    text(ctx, output, x, y, actual, color, weight);
}

function lines(ctx, value, x, y, width, size, maxLines = 2, color = ctx.cardInk ?? '#f7f4ed', weight = 400) {
    ctx.font = `${weight} ${size}px ${FONT}`;
    const words = String(value).split(/\s+/);
    let line = '';
    let row = 0;
    while (words.length && row < maxLines) {
        const next = words.shift();
        if (line && ctx.measureText(`${line} ${next}`).width > width) {
            if (row === maxLines - 1) {
                fitText(ctx, [line, next, ...words].join(' '), x, y + row * size * 1.3, width, size, size, color, weight);
                return;
            }
            fitText(ctx, line, x, y + row * size * 1.3, width, size, size, color, weight);
            row++;
            line = next;
        } else line = line ? `${line} ${next}` : next;
    }
    if (line && row < maxLines) fitText(ctx, line, x, y + row * size * 1.3, width, size, size, color, weight);
}

function rule(ctx, x1, y1, x2, y2, color = (ctx.cardAccent ?? '#d7b66e') + '55') {
    ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2);
    ctx.strokeStyle = color; ctx.lineWidth = 1; ctx.stroke();
}

function cropPhoto(ctx, image, x, y, width, height) {
    const ratio = Math.max(width / image.width, height / image.height);
    const sourceWidth = width / ratio;
    const sourceHeight = height / ratio;
    ctx.save();
    rounded(ctx, x, y, width, height, 10);
    ctx.clip();
    ctx.drawImage(image, (image.width - sourceWidth) / 2, (image.height - sourceHeight) / 2, sourceWidth, sourceHeight, x, y, width, height);
    ctx.restore();
}

function containImage(ctx, image, x, y, width, height) {
    const scale = Math.min(width / image.width, height / image.height);
    const w = image.width * scale;
    const h = image.height * scale;
    ctx.drawImage(image, x + (width - w) / 2, y + (height - h) / 2, w, h);
}

export async function renderMemberCard(data, side) {
    const theme = data.theme ?? {start:'#27282b',base:'#111214',end:'#070809',ink:'#f7f4ed',muted:'#ddd5c7',accent:'#e6c984'};
    const GOLD = theme.accent;
    const [logo, photo, qr] = await Promise.all([
        loadImage(data.logo),
        side === 'front' && data.photo ? loadImage(data.photo) : null,
        side === 'back' ? loadImage(data.qr) : null,
    ]);
    const canvas = document.createElement('canvas');
    // Fixed 2× output: independent of viewport, device pixel ratio, and page CSS.
    canvas.width = WIDTH * 2;
    canvas.height = HEIGHT * 2;
    const ctx = canvas.getContext('2d');
    ctx.scale(2, 2);
    ctx.textBaseline = 'top';
    ctx.cardInk = theme.ink; ctx.cardAccent = theme.accent;
    ctx.save();
    rounded(ctx, 1, 1, WIDTH - 2, HEIGHT - 2, 30);
    ctx.clip();
    const background = ctx.createLinearGradient(0, 0, WIDTH, HEIGHT);
    background.addColorStop(0, theme.start); background.addColorStop(.45, theme.base); background.addColorStop(1, theme.end);
    ctx.fillStyle = background; ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.save();
    ctx.globalAlpha = .07;
    containImage(ctx, logo, 570, 100, 405, 425);
    ctx.restore();
    rounded(ctx, 13, 13, WIDTH - 26, HEIGHT - 26, 23, null, '#d7b66e33');

    if (side === 'front') {
        containImage(ctx, logo, 42, 36, 90, 108);
        text(ctx, 'KKI', 150, 42, 54, theme.ink, 800);
        text(ctx, 'KUSHIN RYU M. KARATE-DO INDONESIA', 152, 105, 15, GOLD, 700);
        text(ctx, 'KARAKTER · KEHORMATAN · INTEGRITAS', 152, 132, 10, theme.muted);
        rule(ctx, 852, 48, 852, 108);
        text(ctx, 'KARTU', 875, 56, 15, GOLD, 700);
        text(ctx, 'ANGGOTA', 875, 82, 15, GOLD, 700);

        text(ctx, 'NOMOR ANGGOTA', 48, 230, 13, GOLD, 700);
        fitText(ctx, data.number, 48, 257, 675, 27);
        text(ctx, 'NAMA KARATEKA', 48, 313, 13, GOLD, 700);
        lines(ctx, data.name.toLocaleUpperCase('id-ID'), 48, 340, 675, 31, 2, theme.ink, 700);
        if (data.qualification) {
            rounded(ctx, 48, 408, 210, 26, 6, '#d7b66e20', '#d7b66e66');
            text(ctx, data.qualification, 60, 414, 13, GOLD, 700);
        }
        text(ctx, 'DOJO', 48, 448, 13, GOLD, 700);
        lines(ctx, data.dojo, 48, 475, 335, 21);
        rule(ctx, 409, 446, 409, 524);
        text(ctx, 'TINGKATAN', 435, 448, 13, GOLD, 700);
        lines(ctx, data.rank, 435, 475, 280, 21);

        rounded(ctx, 784, 253, 184, 242, 10, theme.end);
        if (photo) cropPhoto(ctx, photo, 784, 253, 184, 242);
        else text(ctx, Array.from(data.name)[0].toUpperCase(), 844, 337, 64, GOLD, 700);
        rounded(ctx, 784, 253, 184, 242, 10, null, GOLD);
        rule(ctx, 48, 576, 968, 576);
        text(ctx, 'OFFICIAL MEMBERSHIP CARD', 48, 596, 11, GOLD);
        text(ctx, 'PP KKI · INDONESIA', 838, 596, 11, GOLD);
    } else {
        ctx.fillStyle = '#050607'; ctx.fillRect(0, 45, WIDTH, 72);
        text(ctx, 'IDENTITAS DIGITAL', 55, 168, 13, GOLD, 700);
        rounded(ctx, 55, 197, 238, 238, 8, '#fff');
        ctx.imageSmoothingEnabled = false;
        ctx.drawImage(qr, 66, 208, 216, 216);
        ctx.imageSmoothingEnabled = true;
        text(ctx, 'PINDAI NOMOR ANGGOTA', 55, 451, 11, theme.muted);
        text(ctx, 'SATU KELUARGA.', 367, 176, 31, theme.ink, 700);
        text(ctx, 'SATU SEMANGAT.', 367, 221, 31, GOLD, 700);
        ['Menjunjung tinggi kehormatan', 'Disiplin dalam setiap latihan', 'Tumbuh bersama keluarga KKI'].forEach((value, index) => {
            const y = 307 + index * 49;
            rounded(ctx, 369, y + 3, 9, 9, 2, GOLD);
            text(ctx, value, 399, y, 21, theme.muted);
        });
        rule(ctx, 55, 510, 961, 510);
        lines(ctx, 'Kartu ini adalah identitas pribadi anggota KKI dan tidak dapat dipindahtangankan.', 55, 531, 850, 15, 2, theme.muted);
        text(ctx, 'KUSHIN RYU M. KARATE-DO INDONESIA', 55, 592, 12, GOLD, 700);
    }
    if (theme.stripe) { const stripe = ctx.createLinearGradient(975, 0, 987, 0); stripe.addColorStop(0, '#ac7b25'); stripe.addColorStop(.5, '#f5d782'); stripe.addColorStop(1, '#c49338'); ctx.fillStyle = stripe; ctx.fillRect(975, 18, 12, 607); }
    const gold = ctx.createLinearGradient(0, 0, WIDTH, 0);
    gold.addColorStop(0, '#856532'); gold.addColorStop(.5, '#e6c984'); gold.addColorStop(1, '#aa8140');
    ctx.fillStyle = gold; ctx.fillRect(0, 631, WIDTH, 9);
    ctx.beginPath(); ctx.moveTo(785, 631); ctx.lineTo(WIDTH, 615); ctx.lineTo(WIDTH, HEIGHT); ctx.lineTo(765, HEIGHT); ctx.closePath(); ctx.fill();
    ctx.restore();
    rounded(ctx, 1, 1, WIDTH - 2, HEIGHT - 2, 30, null, '#927b4f');
    return canvas;
}

export async function downloadMemberCard(data, side) {
    const canvas = await renderMemberCard(data, side);
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
    if (!blob) throw new Error('PNG belum dapat dibuat. Silakan coba lagi.');
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.download = `KKI-kartu-anggota-${side}.png`;
    link.href = url;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 60000);
}
