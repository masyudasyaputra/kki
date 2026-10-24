<x-app-layout>
    {{-- Dependencies --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">
                    Pendaftaran <span class="text-red-600">Kolektif</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 font-medium">
                    <span>Dojo:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                        {{ auth()->user()->dojo->name ?? 'DOJO' }}
                    </span>
                </p>
            </div>
            <div>
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 rounded-lg text-xs font-medium shadow-sm transition-all">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali</span>
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Floating Action Button (mobile) --}}
    <div class="fixed bottom-6 right-6 z-50 md:hidden" x-data>
        <button type="button" @click="$dispatch('add-member')"
            class="flex items-center justify-center w-12 h-12 bg-red-600 text-white rounded-full shadow-lg shadow-red-600/30 hover:bg-red-700 active:scale-95 transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
            </svg>
        </button>
    </div>

    <div class="py-6 sm:py-8 bg-slate-50 min-h-screen" x-data="{
        members: [],
        showPass: false,
        rawDojoName: '{{ auth()->user()->dojo->name ?? 'DOJO' }}',

        province_id: '{{ $role === 'pb'
            ? old('province_id') ?? (auth()->user()->province_id ?? (auth()->user()->dojo->province_id ?? ''))
            : auth()->user()->province_id ?? (auth()->user()->dojo->province_id ?? '') }}',

        city_id: '{{ auth()->user()->city_id ?? '' }}',
        dojo_id: '{{ auth()->user()->dojo_id ?? '' }}',

        get defaultPass() {
            let nameParts = (this.rawDojoName || 'DOJO').trim().split(' ');
            return (nameParts[0] || 'DOJO').toUpperCase() + '123';
        },

        async checkWa(index) {
            let wa = this.members[index].whatsapp;

            if (!wa || wa.length < 9) {
                this.members[index].errorWa = '';
                return;
            }

            let isDuplicateLocal = this.members.some((m, idx) => m.whatsapp === wa && idx !== index);
            if (isDuplicateLocal) {
                this.members[index].errorWa = 'Nomor ini sudah ditulis di baris lain';
                return;
            }

            try {
                let response = await fetch(`/api/check-whatsapp?number=${encodeURIComponent(wa)}`);
                let data = await response.json();
                this.members[index].errorWa = data.exists ? 'Nomor sudah terdaftar di sistem' : '';
            } catch (e) {
                console.error('WA check failed', e);
            }
        },

        addMember() {
            this.members.push({
                id: Date.now() + Math.random(),
                name: '',
                parent_name: '',
                whatsapp: '',
                email: '',
                belt_level_id: '',
                errorWa: ''
            });

            this.$nextTick(() => window.initBeltSelects && window.initBeltSelects());
        },

        removeMember(id) {
            if (this.members.length > 1) {
                this.members = this.members.filter(m => m.id !== id);
                this.$nextTick(() => window.initBeltSelects && window.initBeltSelects());
            }
        },

        init() {
            const savedData = localStorage.getItem('dojo_collective_2026');
            if (savedData) {
                try { this.members = JSON.parse(savedData); } catch (e) { this.members = []; }
            }

            if (this.members.length === 0) this.addMember();

            this.$watch('members', (val) => {
                localStorage.setItem('dojo_collective_2026', JSON.stringify(val));
            }, { deep: true });

            this.$nextTick(() => {
                window.initBeltSelects && window.initBeltSelects();
                window.initProvinceSelect && window.initProvinceSelect();
            });

            this.$root.addEventListener('add-member', () => this.addMember());
        }
    }">

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Info Login Card --}}
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                <button type="button"
                    class="w-full text-left px-5 sm:px-6 py-4 flex items-center justify-between gap-4 bg-slate-900 text-white hover:bg-slate-800 transition-colors"
                    @click="showPass = !showPass">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-red-400 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-300">
                                Akses Login Akun Murid
                            </p>
                            <p class="text-xs text-slate-300 font-medium mt-0.5 truncate">
                                Username: <span class="text-white font-semibold">No. WhatsApp</span> • Password: <span class="text-red-400 font-semibold" x-text="defaultPass"></span>
                            </p>
                        </div>
                    </div>

                    <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-[11px] font-semibold bg-white/10 text-white border border-white/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                        <span x-text="showPass ? 'Tutup Detail' : 'Lihat Detail'"></span>
                    </span>
                </button>

                <div x-show="showPass" x-transition class="px-5 sm:px-6 py-4 bg-white border-t border-slate-200/80 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200/70">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">
                            Metode Login
                        </span>
                        <p class="text-xs text-slate-700 font-medium leading-relaxed">
                            Murid login ke portal menggunakan nomor WhatsApp aktif yang didaftarkan pada form di bawah ini.
                        </p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200/70">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">
                            Format Password Standar
                        </span>
                        <p class="text-sm font-bold text-red-600 font-mono tracking-wide" x-text="defaultPass"></p>
                        <p class="text-[11px] text-slate-500 mt-1">Dapat diubah oleh murid secara mandiri setelah login.</p>
                    </div>
                </div>
            </div>

            {{-- Form Submission --}}
            <form action="{{ route('admin.members.review') }}" method="POST">
                @csrf

                {{-- Lokasi & Organisasi Card --}}
                <div class="mb-6 bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-6 py-4 border-b border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                    Lokasi & Organisasi
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium">Data organisasi terikat secara otomatis dengan akun yang sedang login</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            {{-- PROVINSI --}}
                            <div class="md:col-span-4">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Provinsi
                                </label>

                                @if ($role === 'pb')
                                    <select class="province-select w-full" name="province_id" x-model="province_id"
                                        x-init="$nextTick(() => window.initProvinceSelect && window.initProvinceSelect())" required>
                                        <option value="">Pilih Provinsi...</option>
                                        @foreach ($provinces as $p)
                                            <option value="{{ $p->id }}">{{ strtoupper($p->name) }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-[11px] text-slate-500">
                                        * Super Admin PB dapat menentukan provinsi untuk penetapan tarif fee.
                                    </p>
                                @else
                                    <input type="hidden" name="province_id" x-bind:value="province_id">
                                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5">
                                        <p class="text-xs font-semibold text-slate-800 uppercase">
                                            {{ auth()->user()->province->name ?? (auth()->user()->dojo->province->name ?? 'PROVINSI') }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Terkunci otomatis</p>
                                    </div>
                                @endif
                            </div>

                            {{-- KAB/KOTA --}}
                            <div class="md:col-span-4">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kabupaten / Kota
                                </label>
                                <input type="hidden" name="city_id" x-bind:value="city_id">
                                <div class="bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5">
                                    <p class="text-xs font-semibold text-slate-800 uppercase">
                                        {{ auth()->user()->city->name ?? 'KAB/KOTA' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Terkunci otomatis</p>
                                </div>
                            </div>

                            {{-- DOJO --}}
                            <div class="md:col-span-4">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Dojo Cabang
                                </label>
                                <input type="hidden" name="dojo_id" x-bind:value="dojo_id">
                                <div class="bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5">
                                    <p class="text-xs font-semibold text-slate-800 uppercase">
                                        {{ auth()->user()->dojo->name ?? 'DOJO' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Terkunci otomatis</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toolbar Input Kolektif --}}
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                Data Calon Anggota
                            </h3>
                            <p class="text-[11px] text-slate-500 font-medium">
                                Masukkan nama murid, nomor WA (sebagai kredensial login), dan tingkatan sabuk.
                            </p>
                        </div>
                    </div>

                    <button type="button" @click="addMember()"
                        class="hidden md:inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-lg text-xs font-semibold shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Baris Murid
                    </button>
                </div>

                {{-- Member Rows --}}
                <div class="space-y-4">
                    <template x-for="(member, index) in members" :key="member.id">
                        <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden relative">

                            <input type="hidden" :name="'members[' + index + '][password]'" :value="defaultPass">

                            {{-- Card Header --}}
                            <div class="px-5 sm:px-6 py-3.5 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-7 h-7 rounded-md bg-white border border-slate-200 text-slate-800 text-xs font-bold flex items-center justify-center shadow-xs shrink-0"
                                        x-text="index + 1"></span>

                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 truncate"
                                            x-text="member.name ? member.name.toUpperCase() : 'CALON ANGGOTA BARU'"></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold border"
                                        :class="member.errorWa ? 'bg-rose-50 text-rose-700 border-rose-200' : (member.whatsapp ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200')">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="member.errorWa ? 'bg-rose-500' : (member.whatsapp ? 'bg-emerald-500' : 'bg-slate-400')"></span>
                                        <span x-text="member.errorWa ? 'Periksa WA' : (member.whatsapp ? 'Valid' : 'Draft')"></span>
                                    </span>

                                    <button type="button" x-show="members.length > 1" @click="removeMember(member.id)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-rose-600 hover:border-rose-300 hover:bg-rose-50 transition-all ml-1"
                                        title="Hapus baris ini">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="p-5 sm:p-6 space-y-4">
                                {{-- WA Error Alert --}}
                                <div x-show="member.errorWa" x-cloak
                                    class="p-3 bg-rose-50 border border-rose-200 rounded-lg flex items-center gap-3">
                                    <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <p class="text-xs font-semibold text-rose-700" x-text="member.errorWa"></p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    {{-- Nama Murid --}}
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Nama Murid <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" x-model="member.name"
                                            :name="'members[' + index + '][name]'"
                                            class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 uppercase focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                            required placeholder="Nama Lengkap">
                                    </div>

                                    {{-- Nama Ortu --}}
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Nama Orang Tua <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" x-model="member.parent_name"
                                            :name="'members[' + index + '][parent_name]'"
                                            class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 uppercase focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                            required placeholder="Ayah / Ibu">
                                    </div>

                                    {{-- WhatsApp --}}
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            WhatsApp (ID Login) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="tel" inputmode="numeric" x-model="member.whatsapp"
                                            @input.debounce.500ms="checkWa(index)"
                                            :name="'members[' + index + '][whatsapp]'"
                                            class="w-full h-10 px-3.5 rounded-lg text-xs font-medium focus:ring-2 shadow-sm transition-all"
                                            :class="member.errorWa ? 'border-rose-400 bg-rose-50/20 text-rose-900 focus:border-rose-500 focus:ring-rose-500/20' : 'border-slate-200 bg-white text-slate-800 focus:border-red-500 focus:ring-red-500/20'"
                                            required placeholder="0812...">
                                        <p class="mt-1 text-[11px] text-slate-500">
                                            Digunakan sebagai nomor login murid.
                                        </p>
                                    </div>

                                    {{-- Email --}}
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Email <span class="text-slate-400 font-normal">(Opsional)</span>
                                        </label>
                                        <input type="email" x-model="member.email"
                                            :name="'members[' + index + '][email]'"
                                            class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                            placeholder="alamat@email.com">
                                    </div>

                                    {{-- Sabuk Saat Ini --}}
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Tingkatan Sabuk <span class="text-red-500">*</span>
                                        </label>

                                        <div wire:ignore>
                                            <select class="belt-select w-full" :id="'belt_select_' + member.id"
                                                :name="'members[' + index + '][belt_level_id]'"
                                                x-init="$nextTick(() => { if (window.initOneBeltSelect) window.initOneBeltSelect($el, member, members); })" required>
                                                <option value="">PILIH TINGKATAN...</option>
                                                @foreach ($beltLevels as $belt)
                                                    <option value="{{ $belt->id }}">
                                                        {{ strtoupper($belt->name) }} ({{ $belt->kyu_dan }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Password Default Info --}}
                                    <div class="md:col-span-8">
                                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5 flex items-center justify-between gap-3 h-full">
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                                    Password Akun Otomatis
                                                </p>
                                                <p class="text-sm font-bold text-slate-900 font-mono mt-0.5" x-text="defaultPass"></p>
                                                <p class="text-[11px] text-slate-500 mt-0.5">
                                                    Akun murid aktif segera setelah verifikasi pembayaran berhasil.
                                                </p>
                                            </div>
                                            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Action Bar Summary --}}
                <div class="mt-8 mb-20 md:mb-10">
                    <div class="bg-slate-900 text-white rounded-xl p-5 sm:p-6 shadow-md border border-slate-800">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ringkasan Pendaftaran</p>
                                <h3 class="text-xl sm:text-2xl font-bold tracking-tight text-white mt-0.5">
                                    <span x-text="members.length"></span> <span class="text-red-500 font-medium">Calon Anggota</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Pastikan seluruh nomor WhatsApp terisi dengan benar dan unik sebelum lanjut ke tahap review biaya.
                                </p>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2.5 shrink-0">
                                <button type="button"
                                    @click="if(confirm('Kosongkan semua data yang telah diinput?')){ members = []; addMember(); localStorage.removeItem('dojo_collective_2026'); }"
                                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white transition-all text-xs font-semibold">
                                    Reset Form
                                </button>

                                <button type="submit"
                                    :disabled="members.some(m => !m.name || !m.parent_name || !m.belt_level_id || m.errorWa || !m.whatsapp) || (!province_id)"
                                    class="inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-500 text-white px-6 py-2.5 rounded-lg font-semibold text-xs tracking-wide transition-all shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                    <span>Lanjut Ke Review</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            border-radius: 0.5rem !important;
            border: 1px solid #e2e8f0 !important;
            height: 40px !important;
            background-color: #ffffff !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-weight: 600 !important;
            font-size: 12px !important;
            color: #1e293b !important;
            text-transform: uppercase !important;
            padding-left: 14px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #94a3b8 !important;
            font-weight: 500 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
            right: 10px !important;
        }

        .custom-dropdown.select2-dropdown {
            border-radius: 0.5rem !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            overflow: hidden !important;
        }

        .custom-dropdown .select2-results__option {
            font-weight: 600 !important;
            font-size: 12px !important;
            padding: 8px 14px !important;
            text-transform: uppercase !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #dc2626 !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>

    <script>
        window.initOneBeltSelect = (el, member, membersRef) => {
            if (!window.jQuery) return;
            const $el = window.jQuery(el);

            if ($el.hasClass('select2-hidden-accessible')) {
                $el.off('change.select2sync');
                $el.select2('destroy');
            }

            $el.select2({
                width: '100%',
                placeholder: 'PILIH TINGKATAN...',
                dropdownCssClass: 'custom-dropdown'
            });

            if (member.belt_level_id) $el.val(member.belt_level_id).trigger('change.select2');
            else $el.val(null).trigger('change.select2');

            $el.on('change.select2sync', (e) => {
                member.belt_level_id = e.target.value;
                membersRef = Array.from(membersRef);
            });
        };

        window.initBeltSelects = () => {
            document.querySelectorAll('select.belt-select').forEach((el) => {
                if (!window.jQuery(el).hasClass('select2-hidden-accessible')) {
                    window.jQuery(el).select2({
                        width: '100%',
                        placeholder: 'PILIH TINGKATAN...',
                        dropdownCssClass: 'custom-dropdown'
                    });
                }
            });
        };

        window.initProvinceSelect = () => {
            const el = document.querySelector('select.province-select');
            if (!el || !window.jQuery) return;

            const $el = window.jQuery(el);
            if ($el.hasClass('select2-hidden-accessible')) return;

            $el.select2({
                width: '100%',
                placeholder: 'PILIH PROVINSI...',
                dropdownCssClass: 'custom-dropdown'
            });
        };
    </script>
</x-app-layout>
