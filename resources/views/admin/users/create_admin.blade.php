<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="page-title">
                    Buat Akun Administrator
                </h1>
                <p class="page-subtitle">
                    Registrasi akun pengurus, penguji, atau admin dojo
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Pesan Error Validasi --}}
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="min-w-0">
                            <h3 class="text-xs font-bold text-red-800">Terdapat kesalahan pada isian form:</h3>
                            <ul class="mt-1 list-disc list-inside text-xs text-red-700 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                {{-- Header Form --}}
                <div class="px-6 py-5 border-b border-slate-200/80 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Formulir Pembuatan Akun</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Tentukan role wewenang, kredensial login, dan wilayah tugas</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                        Admin Account
                    </span>
                </div>

                <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 sm:p-8 space-y-8">
                    @csrf

                    @include('admin.users.role-fields')

                    {{-- SECTION 2: IDENTITAS --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Identitas Akun Login</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required placeholder="Nama lengkap pengurus/penguji">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Email Login <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required placeholder="admin@domain.com">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Password Awal <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="password"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required value="password123">
                                <p class="mt-1 text-[11px] text-slate-400 italic">
                                    * Pengguna dapat mengubah kata sandi setelah login.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 3: WILAYAH --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Cakupan Wilayah & Penugasan</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Provinsi <span class="text-red-500">*</span>
                                </label>
                                <select name="province_id" id="province_id"
                                    class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach ($provinces as $p)
                                        <option value="{{ $p->id }}" {{ old('province_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kota/Cabang
                                </label>
                                <select name="city_id" id="city_id"
                                    class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                    <option value="">{{ old('city_id') ? 'Memuat...' : 'Pilih Kota' }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Dojo (Opsional)
                                </label>
                                <select name="dojo_id" id="dojo_id"
                                    class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                    <option value="">{{ old('dojo_id') ? 'Memuat...' : 'Pilih Dojo' }}</option>
                                </select>
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-400 italic">
                            * Kosongkan Dojo jika mendaftarkan Admin Pengcab. Kosongkan Kota jika mendaftarkan Pengurus Provinsi.
                        </p>
                    </div>

<div><x-input-label for="first_coach" value="Pelatih awal" /><x-text-input id="first_coach" name="first_coach" type="text" :value="old('first_coach', $user->first_coach ?? '')" maxlength="255" /><x-input-error :messages="$errors->get('first_coach')" class="mt-1" /></div>                    {{-- Actions --}}
                    <div class="pt-5 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.users.index') }}"
                            class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit"
                            class="btn btn-primary inline-flex">
                            <span>Daftarkan Admin</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    {{-- Script AJAX Dropdown Dinamis --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province_id');
            const citySelect = document.getElementById('city_id');
            const dojoSelect = document.getElementById('dojo_id');

            const oldCityId = "{{ old('city_id') }}";
            const oldDojoId = "{{ old('dojo_id') }}";

            const fillSelect = (select, placeholder, items, selectedId = null) => {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                (items || []).forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    if (selectedId && String(selectedId) === String(item.id)) opt.selected = true;
                    select.appendChild(opt);
                });
            };

            const fetchCities = async (provinceId, selected = null) => {
                if (!provinceId) {
                    fillSelect(citySelect, 'Pilih Kota', []);
                    fillSelect(dojoSelect, 'Pilih Dojo', []);
                    return;
                }
                citySelect.innerHTML = `<option value="">Memuat...</option>`;
                fillSelect(dojoSelect, 'Pilih Dojo', []);
                try {
                    const res = await fetch(`/api/cities/${provinceId}`);
                    const data = await res.json();
                    fillSelect(citySelect, 'Pilih Kota', data, selected);
                } catch (e) {
                    fillSelect(citySelect, 'Gagal memuat', []);
                }
            };

            const fetchDojos = async (cityId, selected = null) => {
                if (!cityId) {
                    fillSelect(dojoSelect, 'Pilih Dojo', []);
                    return;
                }
                dojoSelect.innerHTML = `<option value="">Memuat...</option>`;
                try {
                    const res = await fetch(`/api/dojos/${cityId}`);
                    const data = await res.json();
                    fillSelect(dojoSelect, 'Pilih Dojo', data, selected);
                } catch (e) {
                    fillSelect(dojoSelect, 'Gagal memuat', []);
                }
            };

            if (provinceSelect && provinceSelect.value) {
                fetchCities(provinceSelect.value, oldCityId).then(() => {
                    if (oldCityId) fetchDojos(oldCityId, oldDojoId);
                });
            }

            provinceSelect?.addEventListener('change', function() {
                fetchCities(this.value, null);
            });

            citySelect?.addEventListener('change', function() {
                fetchDojos(this.value, null);
            });
        });
    </script>
</x-app-layout>
