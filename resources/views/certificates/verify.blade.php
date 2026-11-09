<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Sertifikat KKI</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 px-4 py-10 text-slate-800">
    @php
        $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d F Y') : '—';
        $signers = $data['template'] ?? [];
    @endphp
    <main class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-r from-[#4a1421] via-[#6e1d31] to-[#87283c] px-6 py-7 text-white sm:px-8">
                <div class="flex items-center gap-4"><img src="{{ asset('images/logo-kki.png') }}" alt="Logo KKI" class="h-12 w-12 rounded-lg bg-white p-1.5"><div><p class="text-xs font-semibold uppercase tracking-widest text-rose-100/80">Pengurus Pusat KKI</p><h1 class="mt-1 text-xl font-bold">Verifikasi sertifikat</h1></div></div>
            </div>
            <div class="p-6 sm:p-8">
                <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-lg font-bold" aria-hidden="true">✓</span><div><h2 class="font-semibold">Sertifikat terdaftar</h2><p class="mt-1 text-sm leading-5 text-emerald-800">Data sertifikat ini cocok dengan catatan penerbitan KKI.</p></div></div>
                <dl class="mt-6 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    @foreach([
                        'Nomor sertifikat' => $certificate->number,
                        'Nama peserta' => $data['name'] ?? '—',
                        'Nomor anggota KKI' => $data['member_number'] ?? '—',
                        'No. ID MSH KKI' => $data['no_id_msh'] ?? '—',
                        'Tingkat' => trim(($data['rank'] ?? '').' · '.($data['belt'] ?? ''), ' ·') ?: '—',
                        'Ujian' => $data['exam_name'] ?? '—',
                        'Tanggal ujian' => $formatDate($data['exam_date'] ?? null),
                        'Dojo' => $data['dojo'] ?? '—',
                    ] as $label => $value)
                        <div class="min-w-0"><dt class="text-xs font-medium text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                <div class="mt-7 border-t border-slate-100 pt-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Penandatangan</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach(['left' => 'Penandatangan 1', 'right' => 'Penandatangan 2'] as $side => $label)
                            <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">{{ $signers['signer_'.$side.'_title'] ?? $label }}</p><p class="mt-1 font-semibold text-slate-900">{{ $signers['signer_'.$side.'_name'] ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $signers['signer_'.$side.'_rank'] ?? '' }}</p></div>
                        @endforeach
                    </div>
                </div>
                <p class="mt-6 text-xs leading-5 text-slate-400">QR memuat tautan verifikasi terlindungi. Halaman ini mencocokkan sertifikat dengan catatan yang tersimpan di sistem KKI.</p>
            </div>
        </div>
    </main>
</body>
</html>
