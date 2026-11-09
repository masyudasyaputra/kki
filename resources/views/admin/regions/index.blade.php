<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="page-title">Kabupaten / Kota & Kecamatan</h1>
            <p class="page-subtitle">Kelola data wilayah yang digunakan pada pendaftaran anggota.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Wilayah belum dapat disimpan. Periksa data berikut:</p>
                <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-3" aria-label="Ringkasan data wilayah">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Provinsi dalam cakupan</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($provinces->count()) }}</p><p class="mt-1 text-xs text-slate-500">Provinsi yang dapat dikelola akun ini</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Kabupaten / kota</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($cityCount) }}</p><p class="mt-1 text-xs text-slate-500">Wilayah administratif terdaftar</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Kecamatan</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($districtCount) }}</p><p class="mt-1 text-xs text-slate-500">Kecamatan di seluruh kabupaten / kota</p></article>
        </section>

        <section aria-label="Tambah wilayah" class="grid items-start gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('admin.regions.store') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                <input type="hidden" name="type" value="city">
                <div class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-[#762137]" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 21h18M5 21V5l7-2v18m0-12 7-2v14M8 8h1m-1 4h1m-1 4h1m7-4h1m-1 4h1" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-[#762137]">Tambah wilayah · 01</p><h2 class="mt-1 text-base font-semibold text-slate-900">Kabupaten / kota</h2><p class="mt-1 text-sm text-slate-500">Pilih provinsi lalu masukkan nama kabupaten atau kota.</p></div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="province_id" class="mb-1.5 block text-sm font-medium text-slate-700">Provinsi <span class="text-rose-700">*</span></label>
                        <select id="province_id" name="province_id" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#762137] focus:ring-[#762137]">
                            <option value="">Pilih provinsi</option>
                            @foreach ($provinces as $province)<option value="{{ $province->id }}" @selected((string) old('province_id') === (string) $province->id)>{{ $province->name }}</option>@endforeach
                        </select>
                        @error('province_id')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="city_name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama kabupaten / kota <span class="text-rose-700">*</span></label>
                        <input id="city_name" name="name" type="text" maxlength="150" required value="{{ old('type') === 'city' ? old('name') : '' }}" placeholder="Contoh: Kota Bandung" class="w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-[#762137] focus:ring-[#762137]">
                        @error('name')@if(old('type') === 'city')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@endif @enderror
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#641b2d] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a1421] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2 sm:w-auto"><svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 4v12M4 10h12" stroke-linecap="round"/></svg>Tambah kabupaten / kota</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.regions.store') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                <input type="hidden" name="type" value="district">
                <div class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 21h16M6 21V7l6-4 6 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01M10 21v-4h4v4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tambah wilayah · 02</p><h2 class="mt-1 text-base font-semibold text-slate-900">Kecamatan</h2><p class="mt-1 text-sm text-slate-500">Pilih kabupaten / kota induk untuk kecamatan ini.</p></div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="city_id" class="mb-1.5 block text-sm font-medium text-slate-700">Kabupaten / kota <span class="text-rose-700">*</span></label>
                        <select id="city_id" name="city_id" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#762137] focus:ring-[#762137]">
                            <option value="">Pilih kabupaten / kota</option>
                            @foreach ($cityOptions as $city)<option value="{{ $city->id }}" @selected((string) old('city_id') === (string) $city->id)>{{ $city->name }} — {{ $city->province?->name }}</option>@endforeach
                        </select>
                        @error('city_id')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="district_name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama kecamatan <span class="text-rose-700">*</span></label>
                        <input id="district_name" name="name" type="text" maxlength="150" required value="{{ old('type') === 'district' ? old('name') : '' }}" placeholder="Contoh: Coblong" class="w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-[#762137] focus:ring-[#762137]">
                        @error('name')@if(old('type') === 'district')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@endif @enderror
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-[#762137] hover:text-[#641b2d] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2 sm:w-auto"><svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 4v12M4 10h12" stroke-linecap="round"/></svg>Tambah kecamatan</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div><h2 class="text-base font-semibold text-slate-900">Daftar wilayah</h2><p class="mt-1 text-sm text-slate-500">{{ number_format($cities->total()) }} kabupaten / kota ditemukan</p></div>
                <form method="GET" action="{{ route('admin.regions.index') }}" role="search" class="flex w-full gap-2 sm:max-w-md">
                    <label for="region-search" class="sr-only">Cari provinsi, kabupaten/kota, atau kecamatan</label>
                    <input id="region-search" type="search" name="search" value="{{ request('search') }}" placeholder="Cari provinsi, kabupaten, kecamatan..." class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-[#762137] focus:ring-[#762137]">
                    <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#641b2d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4a1421] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4" stroke-linecap="round"/></svg><span class="hidden sm:inline">Cari</span></button>
                    @if(request()->filled('search'))<a href="{{ route('admin.regions.index') }}" class="inline-flex shrink-0 items-center rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</a>@endif
                </form>
            </div>

            @if ($cities->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-5 py-3 sm:px-6">Provinsi</th><th scope="col" class="px-5 py-3">Kabupaten / kota</th><th scope="col" class="px-5 py-3 sm:px-6">Kecamatan</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($cities as $city)
                                <tr class="align-top transition hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-600 sm:px-6">{{ $city->province?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">{{ $city->name }}</td>
                                    <td class="px-5 py-4 sm:px-6">
                                        @if ($city->districts->isNotEmpty())
                                            <div class="flex max-w-xl flex-wrap items-center gap-1.5">
                                                @foreach ($city->districts->take(3) as $district)<span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $district->name }}</span>@endforeach
                                                @if ($city->districts->count() > 3)<span class="text-xs font-medium text-slate-500">+{{ $city->districts->count() - 3 }} lainnya</span>@endif
                                                <span class="ml-1 text-xs text-slate-400">{{ $city->districts->count() }} kecamatan</span>
                                            </div>
                                        @else
                                            <span class="text-sm text-slate-400">Belum ada kecamatan</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6"><x-pagination :paginator="$cities" label="kabupaten / kota" /></div>
            @else
                <div class="px-5 py-14 text-center sm:px-6">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 21h18M5 21V5l7-2v18m0-12 7-2v14M8 8h1m-1 4h1m-1 4h1m7-4h1m-1 4h1" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    @if(request()->filled('search'))
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Wilayah tidak ditemukan</h3><p class="mt-1 text-sm text-slate-500">Coba kata kunci provinsi, kabupaten / kota, atau kecamatan yang berbeda.</p><a href="{{ route('admin.regions.index') }}" class="mt-4 inline-flex text-sm font-semibold text-[#641b2d]">Hapus pencarian</a>
                    @else
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum ada kabupaten / kota</h3><p class="mt-1 text-sm text-slate-500">Tambahkan kabupaten / kota menggunakan formulir di atas.</p>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
