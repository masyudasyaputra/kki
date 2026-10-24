<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                    Registrasi Dojo Baru
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Lengkapi identitas, legalitas SK, dan lokasi latihan dojo
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
                        <h2 class="text-sm font-bold text-slate-900">Formulir Data Dojo</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Isi data secara lengkap sesuai dokumen resmi</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                        {{ strtoupper(str_replace('_', ' ', auth()->user()->role)) }}
                    </span>
                </div>

                <form action="{{ route('admin.dojos.store') }}" method="POST" class="p-6 sm:p-8 space-y-8">
                    @csrf

                    {{-- Section 1: Informasi Dasar --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Identitas Dojo</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama Dojo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required placeholder="Contoh: Dojo Garuda Medan">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nama Sensei / Kepala Pelatih
                                </label>
                                <input type="text" name="sensei_name" value="{{ old('sensei_name') }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    placeholder="Nama pelatih kepala">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nomor Kontak / Telepon
                                </label>
                                <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    placeholder="0812xxxxxxxx">
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Legalitas SK --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Legalitas & Masa Berlaku</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nomor Surat Keputusan (SK)
                                </label>
                                <input type="text" name="sk_number" value="{{ old('sk_number') }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    placeholder="Nomor SK resmi dojo">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Masa Berlaku SK Sampai <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="sk_expiry_date" value="{{ old('sk_expiry_date') }}"
                                    class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                    required>
                                <p class="mt-1.5 text-[11px] text-slate-400 italic">
                                    * Dojo otomatis non-aktif jika melewati tanggal berakhir ini.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Wilayah & Lokasi --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Wilayah & Alamat Latihan</h3>
                        </div>

                        @if (auth()->user()->role === 'pengcab')
                            <input type="hidden" name="province_id" value="{{ auth()->user()->province_id }}">
                            <input type="hidden" name="city_id" value="{{ auth()->user()->city_id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-2">
                                <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200/80">
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase">Provinsi</span>
                                    <p class="text-xs font-bold text-slate-800 mt-0.5">
                                        {{ auth()->user()->province->name }}
                                    </p>
                                </div>
                                <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200/80">
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase">Kota/Cabang</span>
                                    <p class="text-xs font-bold text-slate-800 mt-0.5">
                                        {{ auth()->user()->city->name }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Provinsi <span class="text-red-500">*</span>
                                    </label>
                                    <select name="province_id" id="province_id"
                                        class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                        required>
                                        <option value="">Pilih Provinsi</option>
                                        @foreach ($provinces as $p)
                                            <option value="{{ $p->id }}" {{ (string) old('province_id') === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Kota / Cabang <span class="text-red-500">*</span>
                                    </label>
                                    <select name="city_id" id="city_id"
                                        class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                        required>
                                        <option value="">Pilih Kota</option>
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Alamat Lengkap Latihan <span class="text-red-500">*</span>
                            </label>
                            <textarea name="address" rows="3"
                                class="w-full p-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all"
                                required placeholder="Contoh: GOR Serbaguna Lantai 2, Jl. Merdeka...">{{ old('address') }}</textarea>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-5 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.dojos.index') }}"
                            class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                            Batal
                        </a>
                        <button type="submit"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 active:bg-slate-950 text-white rounded-lg text-xs font-semibold shadow-sm transition-all inline-flex items-center gap-2">
                            <span>Daftarkan Dojo</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Script AJAX Wilayah --}}
    <script>
        const provinceSelect = document.getElementById('province_id');
        if (provinceSelect) {
            provinceSelect.addEventListener('change', function() {
                const provinceId = this.value;
                const citySelect = document.getElementById('city_id');
                citySelect.innerHTML = '<option value="">Memuat data...</option>';

                if (provinceId) {
                    fetch(`/api/cities/${provinceId}`)
                        .then(res => res.json())
                        .then(data => {
                            citySelect.innerHTML = '<option value="">Pilih Kota</option>';
                            data.forEach(city => {
                                citySelect.innerHTML += `<option value="${city.id}">${city.name}</option>`;
                            });
                        })
                        .catch(() => {
                            citySelect.innerHTML = '<option value="">Gagal memuat data</option>';
                        });
                } else {
                    citySelect.innerHTML = '<option value="">Pilih Kota</option>';
                }
            });
        }
    </script>
</x-app-layout>
