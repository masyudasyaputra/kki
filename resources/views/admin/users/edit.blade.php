<x-app-layout>
    @if($user->registration_type === 'official')
        <div class="mx-auto max-w-7xl px-6 pt-6 text-sm text-amber-900">Pendaftar pengurus non-karateka. Jabatan yang diajukan: <strong>{{ $user->requested_position }}</strong>. Periksa penugasan sebelum memberikan hak akses.</div>
    @endif
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="page-title">
                    Edit Akun User
                </h1>
                <p class="page-subtitle">
                    Modifikasi data akun: <span class="font-semibold text-slate-700">{{ $user->name }}</span>
                </p>
            </div>
            <a href="{{ route('admin.users.index') }}"
                class="btn btn-secondary hidden sm:inline-flex">
                Kembali
            </a>
        </div>
    </x-slot>

    @if(session('success'))<div class="mx-auto max-w-4xl px-6 pt-6 text-sm text-emerald-800" role="status">{{ session('success') }}</div>@endif
    @if(!$user->hasRole(\App\Services\RoleAccess::NATIONAL) || $user->official)
        @include('admin.users.appointment')
    @endif

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
                        <h2 class="text-sm font-bold text-slate-900">Formulir Pembaruan Akun</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Sesuaikan wewenang, status aktif, atau wilayah penugasan</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                        ID: #{{ $user->id }}
                    </span>
                </div>

                <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="p-6 sm:p-8 space-y-8">
                    @csrf
                    @method('PUT')

                    @include('admin.users.role-fields')

                    {{-- SECTION 2: PROFIL --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Data Akun & Status</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Email Login <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Status Akun <span class="text-red-500">*</span>
                                </label>
                                <select name="is_active" required
                                    class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                    <option value="1" {{ old('is_active', (string) $user->is_active) === '1' ? 'selected' : '' }}>
                                        Aktif
                                    </option>
                                    <option value="0" {{ old('is_active', (string) $user->is_active) === '0' ? 'selected' : '' }}>
                                        Nonaktif
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Password Baru (Kosongkan jika tidak ingin mengubah)
                                </label>
                                <input type="password" name="password"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 3: WILAYAH --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Cakupan Wilayah Wewenang</h3>
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
                                        <option value="{{ $p->id }}" {{ old('province_id', $user->province_id) == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kota/Cabang
                                </label>
                                <select name="city_id" id="city_id" data-selected="{{ old('city_id', $user->city_id) }}"
                                    class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                    <option value="">Pilih Kota</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Dojo
                                </label>
                                <select name="dojo_id" id="dojo_id" data-selected="{{ old('dojo_id', $user->dojo_id) }}"
                                    class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                    <option value="">Pilih Dojo</option>
                                </select>
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-400 italic">
                            * Kosongkan Dojo jika Admin Pengcab. Kosongkan Kota jika Admin Pengurus Provinsi.
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
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Script AJAX Dropdown --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province_id');
            const citySelect = document.getElementById('city_id');
            const dojoSelect = document.getElementById('dojo_id');

            const initCityId = citySelect?.getAttribute('data-selected');
            const initDojoId = dojoSelect?.getAttribute('data-selected');

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
                fetchCities(provinceSelect.value, initCityId).then(() => {
                    if (initCityId) fetchDojos(initCityId, initDojoId);
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
