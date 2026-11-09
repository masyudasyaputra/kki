<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="page-title">Pengaturan Sabuk Kyu/DAN</h1>
            <p class="page-subtitle">Atur tingkatan, urutan kenaikan, dan biaya iuran sabuk anggota.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{{ session('error') }}</div>
        @endif

        <section class="grid gap-4 sm:grid-cols-3" aria-label="Ringkasan tingkatan sabuk">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Total tingkatan</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $beltLevels->count() }}</p><p class="mt-1 text-xs text-slate-500">Kyu dan DAN terdaftar</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Tingkatan Kyu</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $kyuCount }}</p><p class="mt-1 text-xs text-slate-500">Kenaikan tingkat sabuk Kyu</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Tingkatan DAN</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $danCount }}</p><p class="mt-1 text-xs text-slate-500">Tingkatan sabuk hitam</p></article>
        </section>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><p class="font-semibold">Periksa kembali data berikut:</p><ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <x-table-toolbar title="Daftar Tingkatan" :subtitle="$beltLevels->count() . ' tingkatan tersusun berdasarkan urutan kenaikan'">
            <x-button-add href="{{ route('admin.belt-levels.create') }}">Tambah Tingkatan</x-button-add>
        </x-table-toolbar>

        @if ($beltLevels->isEmpty())
            <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center shadow-sm">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-[#762137]" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 4h14v16H5zM9 8h6M9 12h6M9 16h3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Belum ada tingkatan sabuk</h2>
                <p class="mx-auto mt-1 max-w-lg text-sm leading-6 text-slate-500">Muat katalog awal 7 tingkatan Kyu dan 6 tingkatan DAN, atau tambahkan tingkatan satu per satu.</p>
                <div class="mt-5 flex flex-col justify-center gap-2 sm:flex-row">
                    <form method="POST" action="{{ route('admin.belt-levels.restore-defaults') }}" onsubmit="return confirm('Muat katalog standar 7 Kyu dan 6 DAN?')">@csrf<button class="inline-flex items-center justify-center rounded-lg bg-[#641b2d] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#4a1421] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2">Muat Katalog Standar</button></form>
                    <a href="{{ route('admin.belt-levels.create') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tambah Manual</a>
                </div>
            </section>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-5 py-3">Urutan</th><th scope="col" class="px-5 py-3">Tingkatan</th><th scope="col" class="px-5 py-3">Jenis</th><th scope="col" class="px-5 py-3">Nama Sabuk</th><th scope="col" class="px-5 py-3">Biaya Iuran</th><th scope="col" class="px-5 py-3">Status</th><th scope="col" class="px-5 py-3 text-right">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($beltLevels as $belt)
                                @php $isKyu = str_starts_with(strtolower((string) $belt->kyu_dan), 'kyu'); @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-500">{{ $belt->order }}</td>
                                    <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $isKyu ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-800' }}">{{ $belt->kyu_dan ?: 'Belum ditentukan' }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $isKyu ? 'Kyu' : 'DAN' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900">{{ $belt->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-700">Rp{{ number_format($belt->membership_fee, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $belt->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $belt->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right"><a href="{{ route('admin.belt-levels.edit', $belt) }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-[#762137] hover:text-[#641b2d]">Ubah</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="border-t border-slate-100 px-5 py-3 text-xs leading-5 text-slate-500">Tingkatan nonaktif tetap tersimpan untuk menjaga riwayat anggota dan hasil ujian.</p>
            </div>
        @endif
    </div>
</x-app-layout>
