<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Verifikasi Kartu Anggota KKI</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 px-4 py-10 text-slate-800">
    <main class="mx-auto max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="flex items-center gap-4 bg-red-900 px-6 py-6 text-white">
            <img src="{{ asset('images/logo-kki-dark.png') }}" alt="Logo KKI" class="h-14 w-14 object-contain">
            <div><p class="text-xs font-semibold uppercase tracking-widest">Pengurus Pusat KKI</p><h1 class="mt-1 text-xl font-bold">Verifikasi kartu anggota</h1></div>
        </header>
        <div class="p-6 sm:p-8">
            <div role="status" class="rounded-xl border p-4 {{ $verified ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
                <h2 class="text-lg font-bold">{{ $verified ? 'Anggota Terverifikasi' : 'Anggota Belum Terverifikasi' }}</h2>
                <p class="mt-1 text-sm">{{ $verified ? 'Data anggota ini telah diverifikasi oleh pengurus KKI.' : 'Data anggota ini belum mendapat persetujuan verifikasi saat ini. Hubungi pengurus KKI untuk konfirmasi.' }}</p>
            </div>
            @if($verified)
                <div class="mt-6 flex items-center gap-4">
                    @if($cardData['photo'])<img src="{{ $cardData['photo'] }}" alt="Pasfoto anggota" class="h-24 w-20 rounded-lg object-cover">@endif
                    <div class="min-w-0"><h2 class="break-words text-lg font-bold">{{ $cardData['name'] }}</h2><p class="mt-1 break-all font-mono text-sm">{{ $cardData['number'] }}</p></div>
                </div>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach(['Dojo' => $cardData['dojo'], 'Tingkatan' => $cardData['rank'], 'Provinsi' => $cardData['province'], 'Kabupaten / kota' => $cardData['city'], 'Status keanggotaan' => $cardData['status'], 'Berlaku hingga' => $cardData['expiry']] as $label => $value)
                        <div class="min-w-0"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words text-sm font-semibold">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            @endif
            <p class="mt-6 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500">Status ditampilkan berdasarkan data terbaru dalam sistem KKI pada {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB. Status verifikasi data dan status aktif keanggotaan ditampilkan secara terpisah.</p>
        </div>
    </main>
</body>
</html>
