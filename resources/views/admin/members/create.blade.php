<x-app-layout>
    {{-- Dependencies --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="page-title">
                    Pendaftaran <span class="text-red-600">Kolektif</span>
                </h1>
                <p class="page-subtitle">
                    <span>Dojo:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                        {{ $role === 'admin_dojo' ? (auth()->user()->dojo?->name ?? 'Belum diatur') : 'Pilih pada setiap murid' }}
                    </span>
                </p>
            </div>
            <div>
                <a href="{{ route('admin.dashboard') }}"
                    class="btn btn-secondary inline-flex">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="member-registration py-4 bg-slate-50 min-h-screen" x-data="{
        members: [],
        showPass: false,
        availableCities: @js($cities->map->only(['id', 'name', 'province_id'])->values()),
        availableDojos: @js($dojos),
        lockedCity: @js(in_array($role, ['pengcab', 'admin_dojo'])),
        lockedDojo: @js($role === 'admin_dojo'),
        draftKey: @js('collective_members_' . auth()->id()),
        passwordFor(member) {
            const dojo = this.availableDojos.find(d => String(d.id) === String(member.dojo_id));
            return ((dojo?.name || 'DOJO').trim().split(/\s+/)[0]).toUpperCase() + '123';
        },

        province_id: '{{ $role === 'pb'
            ? old('province_id') ?? (auth()->user()->province_id ?? (auth()->user()->dojo->province_id ?? ''))
            : auth()->user()->province_id ?? (auth()->user()->dojo->province_id ?? '') }}',

        city_id: @js(in_array($role, ['pengcab', 'admin_dojo']) ? (string) (auth()->user()->city_id ?? auth()->user()->dojo?->city_id ?? '') : ''),
        dojo_id: '{{ auth()->user()->dojo_id ?? '' }}',
        memberCities: [],
        regionError: '',
        loadMemberCities(reset = false) {
            this.memberCities = this.availableCities.filter(c => String(c.province_id) === String(this.province_id));
            this.members.forEach(member => {
                if (reset || !this.memberCities.some(c => String(c.id) === String(member.city_id))) {
                    member.city_id = this.lockedCity ? this.city_id : '';
                    member.district = ''; member.dojo_id = this.lockedDojo ? this.dojo_id : '';
                }
            });
        },

        async checkWa(index) {
            const member = this.members[index];
            if (!member) return;
            const wa = member.whatsapp.replace(/[+\s()-]/g, '');
            member.errorWa = '';
            if (!/^[0-9]{8,16}$/.test(wa)) {
                member.errorWa = 'Gunakan 8?16 digit nomor WhatsApp.';
                return;
            }
            if (this.members.some(m => m !== member && m.whatsapp.replace(/[+\s()-]/g, '') === wa)) {
                member.errorWa = 'Nomor ini sudah ditulis di baris lain';
                return;
            }
            try {
                const response = await fetch(@js(url('/api/check-whatsapp')) + '?number=' + encodeURIComponent(wa));
                if (!response.ok) throw new Error();
                const data = await response.json();
                if (member.whatsapp.replace(/[+\s()-]/g, '') === wa) member.errorWa = data.exists ? 'Nomor sudah terdaftar di sistem' : '';
            } catch { member.errorWa = 'Pemeriksaan WhatsApp gagal. Coba isi kembali.'; }
        },

        addMember() {
            this.members.push({
                id: Date.now() + Math.random(),
                name: '',
                parent_name: '',
                whatsapp: '',
                email: '',
                city_id: this.city_id,
                district: '', address: '', first_coach: '', dojo_id: this.lockedDojo ? this.dojo_id : '',
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
            const oldMembers = @js(old('members'));
            let savedData = null;
            try { savedData = localStorage.getItem(this.draftKey); } catch {}
            if (Array.isArray(oldMembers)) this.members = oldMembers.map((m, i) => ({ ...m, id: Date.now() + i, errorWa: '' }));
            if (!Array.isArray(oldMembers) && savedData) {
                try { const parsed = JSON.parse(savedData); this.members = Array.isArray(parsed) ? parsed : []; } catch (e) { this.members = []; }
            }

            if (this.members.length === 0) this.addMember();
            this.members.forEach(member => { member.city_id ??= this.city_id; member.district ??= ''; member.address ??= ''; });
            this.loadMemberCities();
            this.$watch('province_id', () => this.loadMemberCities(true));

            this.$watch('members', (val) => {
                try { localStorage.setItem(this.draftKey, JSON.stringify(val)); } catch {}
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
                    class="btn btn-primary w-full flex"
                    @click="showPass = !showPass">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-[#e1b9bc] shrink-0">
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
                                Username: <span class="text-white font-semibold">No. WhatsApp</span> • Password: <span class="text-red-400 font-semibold">Sesuai dojo murid</span>
                            </p>
                        </div>
                    </div>

                    <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-[11px] font-semibold bg-white/10 text-white border border-white/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#a85863]"></span>
                        <span x-text="showPass ? 'Tutup Detail' : 'Lihat Detail'"></span>
                    </span>
                </button>

                <div x-show="showPass" x-transition class="px-3 sm:px-4 py-4 bg-white border-t border-slate-200/80 grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        <p class="text-sm font-bold text-red-600 font-mono tracking-wide" >Kata pertama nama dojo + 123 (tanpa dojo: DOJO123)</p>
                        <p class="text-[11px] text-slate-500 mt-1">Dapat diubah oleh murid secara mandiri setelah login.</p>
                    </div>
                </div>
            </div>

            {{-- Form Submission --}}
            @if ($errors->any())
                <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form action="{{ route('admin.members.review') }}" method="POST">
                @csrf

                {{-- Lokasi & Organisasi Card --}}
                <div class="mb-6 bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-3 sm:px-4 py-4 border-b border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                    Lokasi & Organisasi
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium">Pilihan wilayah dan dojo mengikuti cakupan akses akun.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 sm:p-4">
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
                                @else
                                    <input type="hidden" name="province_id" x-bind:value="province_id">
                                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5">
                                        <p class="text-xs font-semibold text-slate-800 uppercase">
                                            {{ auth()->user()->province->name ?? (auth()->user()->dojo->province->name ?? 'PROVINSI') }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Sesuai cakupan akses akun</p>
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
                                        {{ in_array($role, ['pengcab', 'admin_dojo']) ? (auth()->user()->city?->name ?? auth()->user()->dojo?->city?->name ?? 'Belum diatur') : 'Pilih pada setiap murid' }}
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
                                        {{ $role === 'admin_dojo' ? (auth()->user()->dojo?->name ?? 'Belum diatur') : 'Pilih pada setiap murid' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Terkunci otomatis</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toolbar Input Kolektif --}}
                <div class="table-toolbar member-form-toolbar flex-col items-stretch sm:flex-row sm:items-center">
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
                        class="btn btn-primary ml-0 w-full min-h-11 justify-center shrink-0 sm:ml-auto sm:w-auto">
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

                            {{-- Card Header --}}
                            <div class="px-4 sm:px-5 py-3.5 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between gap-3">
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
                                        <span x-text="member.errorWa ? 'Periksa WA' : (member.whatsapp ? 'Terisi' : 'Draft')"></span>
                                    </span>

                                    <x-action-button type="button" x-show="members.length > 1" @click="removeMember(member.id)" icon="delete" label="Hapus" />
                                </div>
                            </div>

                            <div class="p-4 sm:p-5 lg:p-6 space-y-5">
                                {{-- WA Error Alert --}}
                                <div x-show="member.errorWa" x-cloak
                                    class="p-3 bg-rose-50 border border-rose-200 rounded-lg flex items-center gap-3">
                                    <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <p class="text-xs font-semibold text-rose-700" x-text="member.errorWa"></p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-x-4 gap-y-5">
                                    {{-- Nama Murid --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Nama <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" x-model="member.name"
                                            :name="'members[' + index + '][name]'"
                                            class="member-form-control w-full h-11 px-3.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 uppercase focus:ring-2 focus:ring-[#762137]/15 focus:border-[#762137] shadow-sm transition-all"
                                            required placeholder="Nama Lengkap">
                                    </div>

                                    {{-- Nama Ortu --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Nama Orang Tua <span class="text-slate-400 font-normal">(Opsional)</span>
                                        </label>
                                        <input type="text" x-model="member.parent_name"
                                            :name="'members[' + index + '][parent_name]'"
                                            class="member-form-control w-full h-11 px-3.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 uppercase focus:ring-2 focus:ring-[#762137]/15 focus:border-[#762137] shadow-sm transition-all"
                                            placeholder="Ayah / Ibu">
                                    </div>

                                    {{-- WhatsApp --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            WhatsApp (ID Login) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="tel" inputmode="numeric" x-model="member.whatsapp"
                                            @input.debounce.500ms="checkWa(index)"
                                            :name="'members[' + index + '][whatsapp]'"
                                            class="member-form-control w-full h-11 px-3.5 rounded-xl text-sm font-medium focus:ring-2 shadow-sm transition-all"
                                            :class="member.errorWa ? 'border-rose-400 bg-rose-50/20 text-rose-900 focus:border-rose-500 focus:ring-rose-500/20' : 'border-slate-200 bg-white text-slate-800 focus:border-[#762137] focus:ring-[#762137]/15'"
                                            required placeholder="0812...">
                                        <p class="mt-1 text-[11px] text-slate-500">
                                            Digunakan sebagai nomor login murid.
                                        </p>
                                    </div>

                                    {{-- Email --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Email <span class="text-slate-400 font-normal">(Opsional)</span>
                                        </label>
                                        <input type="email" x-model="member.email"
                                            :name="'members[' + index + '][email]'"
                                            class="member-form-control w-full h-11 px-3.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-[#762137]/15 focus:border-[#762137] shadow-sm transition-all"
                                            placeholder="alamat@email.com">
                                    </div>

                                    <div class="sm:col-span-1 xl:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" :for="'member-city-' + member.id">Kabupaten / Kota *</label>
                                        <select :id="'member-city-' + member.id" :name="'members[' + index + '][city_id]'" x-model="member.city_id" :disabled="lockedCity" @change="member.district = ''; member.dojo_id = ''" required class="member-form-control w-full h-11 rounded-xl border-slate-200 px-3.5 text-sm focus:border-[#762137] focus:ring-[#762137]/15">
                                            <option value="">Pilih Kabupaten / Kota</option>
                                            <template x-for="city in memberCities" :key="city.id"><option :value="String(city.id)" :selected="String(city.id) === String(member.city_id)" x-text="city.name"></option></template>
                                        </select>
                                        <input type="hidden" :disabled="!lockedCity" :name="'members[' + index + '][city_id]'" :value="member.city_id">
                                        <p x-show="regionError" x-text="regionError" class="text-xs text-red-600" role="alert"></p>
                                    </div>
                                    <div class="sm:col-span-1 xl:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" :for="'member-district-' + member.id">Kecamatan</label>
                                        <datalist :id="'district-options-' + member.id"><template x-for="district in @js($districts).filter(d => String(d.city_id) === String(member.city_id))" :key="district.id"><option :value="district.name"></option></template></datalist><input :list="'district-options-' + member.id" :id="'member-district-' + member.id" type="text" :name="'members[' + index + '][district]'" x-model="member.district" maxlength="150" placeholder="Nama Kecamatan" class="member-form-control w-full h-11 rounded-xl border-slate-200 px-3.5 text-sm focus:border-[#762137] focus:ring-[#762137]/15">
                                    </div>

                                    <div class="sm:col-span-1 xl:col-span-2"><label class="member-form-label">Pelatih awal<input type="text" :name="'members[' + index + '][first_coach]'" x-model="member.first_coach" maxlength="255" placeholder="Nama pelatih" class="member-form-control mt-1.5 w-full h-11 rounded-xl border-slate-200 px-3.5 text-sm focus:border-[#762137] focus:ring-[#762137]/15"></label></div>
                                    <div class="sm:col-span-2 xl:col-span-4"><label class="member-form-label">Alamat (opsional)<textarea :name="'members[' + index + '][address]'" x-model="member.address" maxlength="2000" rows="3" autocomplete="street-address" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan" class="member-form-control mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-3 text-sm focus:border-[#762137] focus:ring-[#762137]/15"></textarea></label></div>
                                    <div class="sm:col-span-1 xl:col-span-2"><label class="member-form-label">Nama dojo<select :name="'members[' + index + '][dojo_id]'" x-model="member.dojo_id" :disabled="lockedDojo" class="member-form-control mt-1.5 w-full h-11 rounded-xl border-slate-200 px-3.5 text-sm focus:border-[#762137] focus:ring-[#762137]/15"><option value="">Pilih dojo</option><template x-for="dojo in availableDojos.filter(d => String(d.city_id) === String(member.city_id))" :key="dojo.id"><option :value="String(dojo.id)" x-text="dojo.name"></option></template></select></label></div>
                                    <input type="hidden" :disabled="!lockedDojo" :name="'members[' + index + '][dojo_id]'" :value="member.dojo_id">
                                    {{-- Sabuk Saat Ini --}}
                                    <div>
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
                                    <div class="sm:col-span-2 xl:col-span-3">
                                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5 flex items-center justify-between gap-3 h-full">
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                                    Password Akun Otomatis
                                                </p>
                                                <p class="text-sm font-bold text-slate-900 font-mono mt-0.5" x-text="passwordFor(member)"></p>
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
                <div class="member-form-actions mt-8 mb-4 md:mb-10">
                    <div class="bg-slate-900 text-white rounded-xl p-3 sm:p-4 shadow-md border border-slate-800">
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

                            <div class="member-form-buttons flex flex-col sm:flex-row gap-2.5 shrink-0">
                                <button type="button"
                                    @click="if(confirm('Kosongkan semua data yang telah diinput?')){ members = []; addMember(); localStorage.removeItem(draftKey); }"
                                    class="btn btn-secondary inline-flex">
                                    Reset Form
                                </button>

                                <button type="submit"
                                    :disabled="members.some(m => !m.name || !m.belt_level_id || m.errorWa || !m.whatsapp || !m.city_id) || (!province_id)"
                                    class="btn btn-primary inline-flex">
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
            border-radius: 0.75rem !important;
            border: 1px solid #e2e8f0 !important;
            height: 44px !important;
            background-color: #ffffff !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-weight: 600 !important;
            font-size: 14px !important;
            color: #1e293b !important;
            text-transform: none !important;
            line-height: 42px !important;
            padding-left: 14px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #94a3b8 !important;
            font-weight: 500 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            right: 10px !important;
        }

        .custom-dropdown.select2-dropdown {
            border-radius: 0.75rem !important;
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
            background-color: #762137 !important;
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

        .member-form-label { display: block; color: #334155; font-size: 0.75rem; font-weight: 600; }
        .member-form-control:focus { --tw-ring-color: rgb(118 33 55 / 0.15); }
        @media (max-width: 639px) {
            .member-registration { padding-top: 0.75rem; }
            .member-registration .table-toolbar { gap: 0.875rem; }
            .member-registration .member-form-actions { position: sticky; bottom: 0; z-index: 20; margin: 1rem -1rem 0; padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom)); background: rgb(248 250 252 / 0.96); backdrop-filter: blur(12px); }
            .member-registration .member-form-actions > div { padding: 1rem; }
            .member-registration .member-form-buttons { flex-direction: column-reverse; }
            .member-registration .member-form-actions button { width: 100%; justify-content: center; min-height: 44px; }
            .member-registration .member-form-actions h3 { font-size: 1.125rem; }
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
            $el.on('change.provinceSync', () => el.dispatchEvent(new Event('change', { bubbles: true })));
        };
    </script>
</x-app-layout>
