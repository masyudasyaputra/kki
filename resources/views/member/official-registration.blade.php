<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Pendaftaran Pengurus Non-Karateka</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">Pengajuan Anda sudah tercatat</h2>
            <p class="mt-2 text-sm text-slate-600">Administrator akan memeriksa jabatan dan wilayah tugas sebelum memberikan akses pengurus. Hubungi pengurus wilayah untuk konfirmasi dan kelengkapan SK.</p>
            <dl class="mt-5 space-y-3 text-sm">
                <div><dt class="text-slate-500">Nama</dt><dd>{{ $user->name }}</dd></div>
                <div><dt class="text-slate-500">Jabatan yang diajukan</dt><dd>{{ $user->requested_position }}</dd></div>
                <div><dt class="text-slate-500">Wilayah</dt><dd>{{ $user->province?->name ?? 'Belum diisi' }} / {{ $user->city?->name ?? 'Belum diisi' }}</dd></div>
            </dl>
        </section>
    </div>
</x-app-layout>
