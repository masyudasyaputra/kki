@php
    $assignment = $candidate?->official;
    $selectedLevel = old('level', $assignment?->level ?? ($national ? ($candidate?->city_id ? 'pengcab' : 'provinsi') : 'pengcab'));
    $selectedProvince = old('province_id', $candidate?->province_id);
    $selectedCity = old('city_id', $candidate?->city_id);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="page-title">
                    Tambah Data Pengurus
                </h1>
                <p class="page-subtitle">
                    Tingkat {{ $national ? 'Wilayah / Pusat' : 'Cabang (Pengcab)' }}
                </p>
            </div>
            <a href="{{ route('admin.officials.index') }}"
                class="btn btn-secondary hidden sm:inline-flex">
                Kembali
            </a>
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
                        <h2 class="text-sm font-bold text-slate-900">Formulir Data Pengurus</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Lengkapi identitas pengurus, jabatan, dan nomor SK organisasi</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                        {{ $national ? 'PENGURUS PUSAT' : 'PENGCAB' }}
                    </span>
                </div>

                <form action="{{ $candidate ? route('admin.users.appoint', $candidate) : route('admin.officials.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-8">
                    @csrf
                    @if($assignment?->official_number)<p class="font-mono text-sm">ID Pengurus: {{ $assignment->official_number }}</p>@endif
                    @if($candidate)
                        <input type="hidden" name="return_to" value="officials">
                        <p class="rounded-lg bg-slate-50 p-3 text-sm">Terhubung dengan akun <strong>{{ $candidate->email }}</strong>. Simpan untuk menetapkan jabatan, mengaktifkan akun, dan memberikan akses wilayah sekaligus.</p>
                    @endif
                    @if($canAppoint)
                        <div><x-input-label for="proposed-user" value="Ambil dari usulan pengurus" />
                            <select id="proposed-user" class="w-full rounded-lg border-slate-300" onchange="const target = new URL(window.location.href); this.value ? target.searchParams.set('user_id', this.value) : target.searchParams.delete('user_id'); window.location.assign(target.href)">
                                <option value="">Input pengurus secara manual</option>
                                @if($candidate)<option value="{{ $candidate->id }}" selected>{{ $candidate->name }} — {{ $candidate->email }}</option>@endif
                                @foreach($candidates as $proposed)
                                    @if($candidate?->id !== $proposed->id)<option value="{{ $proposed->id }}">{{ $proposed->name }} — {{ $proposed->requested_position }} — {{ $proposed->email }}</option>@endif
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Section 1: Profil Pengurus --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Profil & Jabatan</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama Lengkap & Gelar <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $candidate?->name) }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required placeholder="Contoh: Dr. H. Ahmad Fauzi, M.Pd">
                            </div>

                            <x-official-position-field :value="$assignment?->position ?? $candidate?->requested_position" />

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nomor WhatsApp
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">+62</span>
                                    <input type="text" name="phone_number" value="{{ old('phone_number', $assignment?->phone_number ?? $candidate?->whatsapp) }}"
                                        class="w-full h-10 pl-11 pr-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                        placeholder="812xxxxxxxx">
                                </div>
                                <p class="mt-1 text-[11px] text-slate-400 italic">
                                    * Contoh pengisian: 81234567890 (tanpa awalan angka 0)
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Cakupan Wilayah Tugas --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Cakupan Wilayah Tugas</h3>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">
                                Tingkat Kepengurusan <span class="text-red-500">*</span>
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @if ($national)
                                    <label class="relative flex flex-col p-3.5 rounded-lg border border-slate-200 cursor-pointer hover:border-slate-300 transition-all">
                                        <div class="flex items-center gap-2"><input type="radio" name="level" value="pusat" class="text-red-600 focus:ring-red-500/20" {{ $selectedLevel === 'pusat' ? 'checked' : '' }} onchange="toggleWilayah(this.value)"><span class="text-xs font-semibold text-slate-800">Pengurus Pusat</span></div>
                                        <span class="text-[11px] text-slate-400 ml-5 mt-0.5">Yurisdiksi seluruh Indonesia</span>
                                    </label>
                                    <label class="relative flex flex-col p-3.5 rounded-lg border border-slate-200 cursor-pointer hover:border-slate-300 transition-all">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" name="level" value="provinsi"
                                                class="text-red-600 focus:ring-red-500/20"
                                                {{ $selectedLevel === 'provinsi' ? 'checked' : '' }}
                                                onchange="toggleWilayah(this.value)">
                                            <span class="text-xs font-semibold text-slate-800">Provinsi (Pengprov)</span>
                                        </div>
                                        <span class="text-[11px] text-slate-400 ml-5 mt-0.5">Yurisdiksi tingkat daerah/provinsi</span>
                                    </label>
                                @endif

                                <label class="relative flex flex-col p-3.5 rounded-lg border border-slate-200 cursor-pointer hover:border-slate-300 transition-all">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="level" value="pengcab"
                                            class="text-red-600 focus:ring-red-500/20"
                                            {{ $selectedLevel === 'pengcab' ? 'checked' : '' }}
                                            onchange="toggleWilayah(this.value)">
                                        <span class="text-xs font-semibold text-slate-800">Kota / Kabupaten (Pengcab)</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400 ml-5 mt-0.5">Yurisdiksi cabang kota atau kabupaten</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div id="province_container">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Provinsi <span class="text-red-500">*</span>
                                </label>

                                @if (!$national)
                                    <input type="hidden" name="province_id" value="{{ auth()->user()->province_id }}">
                                    <div class="w-full h-10 px-3.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 flex items-center">
                                        {{ auth()->user()->province->name }}
                                    </div>
                                @else
                                    <select name="province_id" id="province_id" required
                                        class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                        <option value="">Pilih Provinsi</option>
                                        @foreach ($provinces as $p)
                                            <option value="{{ $p->id }}" {{ (string) $selectedProvince === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div id="city_container" class="{{ $selectedLevel === 'pengcab' ? '' : 'hidden' }}">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Kota / Kabupaten <span class="text-red-500">*</span>
                                </label>
                                <select name="city_id" id="city_id" data-selected="{{ $selectedCity }}"
                                    class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    {{ !$national ? 'required' : '' }}>
                                    <option value="">Pilih Kota</option>
                                </select>
                            </div>
                        </div>
                        <x-sk-document-upload entity="official" label="Unggah dokumen SK Pengurus" />
                        @if($assignment?->sk_document_path)
                            <p class="text-xs text-slate-500"><a class="underline" href="{{ route('admin.sk-documents.show', ['entity' => 'official', 'id' => $assignment->id]) }}">Lihat SK tersimpan</a>. Kosongkan unggahan untuk mempertahankan dokumen.</p>
                        @endif
                    </div>

                    {{-- Section 3: Legalitas SK --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Legalitas & Masa Bakti</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nomor SK Pengurus
                                </label>
                                <input type="text" name="sk_number" value="{{ old('sk_number', $assignment?->sk_number) }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    placeholder="SK/001/WIL/2026">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Masa Bakti Hingga <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="sk_expiry_date" value="{{ old('sk_expiry_date', $assignment?->sk_expiry_date) }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-5 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.officials.index') }}"
                            class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit"
                            class="btn btn-primary inline-flex">
                            <span>{{ $candidate ? 'Simpan & Tetapkan Pengurus' : 'Simpan Pengurus' }}</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    {{-- Script AJAX Wilayah --}}
    <script>
        const provinceSelect = document.getElementById('province_id');
        const citySelect = document.getElementById('city_id');
        const cityContainer = document.getElementById('city_container');
        const userRole = "{{ auth()->user()->role }}";
        const userProvinceId = "{{ auth()->user()->province_id }}";

        const initialLevel = document.querySelector('input[name="level"]:checked');
        if (initialLevel) toggleWilayah(initialLevel.value);

        function toggleWilayah(level) {
            const isNational = level === 'pusat';
            document.getElementById('province_container').classList.toggle('hidden', isNational);
            if (provinceSelect) { provinceSelect.required = !isNational; provinceSelect.disabled = isNational; }
            citySelect.disabled = level !== 'pengcab';
            if (level === 'pengcab') {
                cityContainer.classList.remove('hidden');
                citySelect.required = true;

                if (provinceSelect && provinceSelect.value) fetchCities(provinceSelect.value);
                else if (userProvinceId) fetchCities(userProvinceId);
            } else {
                cityContainer.classList.add('hidden');
                citySelect.required = false;
                citySelect.value = "";
            }
        }

        function fetchCities(provinceId) {
            if (!provinceId) return;

            citySelect.innerHTML = '<option value="">Memuat...</option>';

            fetch(`/api/cities/${provinceId}`)
                .then(res => res.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Pilih Kota</option>';
                    data.forEach(city => {
                        const option = new Option(city.name, city.id); option.selected = String(city.id) === citySelect.dataset.selected; citySelect.add(option);
                    });
                })
                .catch(() => {
                    citySelect.innerHTML = '<option value="">Gagal memuat data</option>';
                });
        }

        if (provinceSelect) {
            provinceSelect.addEventListener('change', function() {
                citySelect.dataset.selected = '';
                const checkedLevel = document.querySelector('input[name="level"]:checked');
                if (checkedLevel && checkedLevel.value === 'pengcab' && this.value) {
                    fetchCities(this.value);
                }
            });
        }
    </script>
</x-app-layout>
