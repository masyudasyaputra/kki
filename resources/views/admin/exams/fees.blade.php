<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="page-title">
                    Tarif <span class="text-red-600">Ujian Sabuk</span>
                </h1>
                <p class="page-subtitle">
                    Konfigurasi biaya pendaftaran ujian berdasarkan kenaikan tingkatan sabuk.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.exams.index') }}"
                    class="btn btn-secondary inline-flex">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Jadwal</span>
                </a>
            </div>
        </div>
    </x-slot>

    @php
        function getBeltColor($beltName)
        {
            $name = strtolower($beltName);
            if (str_contains($name, 'putih')) {
                return 'bg-white text-slate-600 border-slate-200';
            }
            if (str_contains($name, 'kuning muda')) {
                return 'bg-yellow-50 text-yellow-700 border-yellow-200';
            }
            if (str_contains($name, 'kuning')) {
                return 'bg-yellow-100 text-yellow-800 border-yellow-300';
            }
            if (str_contains($name, 'orange')) {
                return 'bg-orange-50 text-orange-700 border-orange-200';
            }
            if (str_contains($name, 'hijau')) {
                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            }
            if (str_contains($name, 'biru')) {
                return 'bg-blue-50 text-blue-700 border-blue-200';
            }
            if (str_contains($name, 'ungu')) {
                return 'bg-purple-50 text-purple-700 border-purple-200';
            }
            if (str_contains($name, 'coklat') || str_contains($name, 'cokelat')) {
                return 'bg-amber-100 text-amber-900 border-amber-300';
            }
            if (str_contains($name, 'hitam')) {
                return 'bg-slate-900 text-white border-slate-950';
            }
            return 'bg-slate-50 text-slate-600 border-slate-200';
        }
    @endphp

    <div class="py-6 sm:py-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-emerald-900">{{ session('success') }}</span>
                </div>
            @endif

            <div class="space-y-3">

                <dialog id="createFeeDialog" class="create-fee-dialog" aria-labelledby="fee-modal-title">
                    <div class="create-form-heading">
                        <h2 id="fee-modal-title">Tambah / ubah tarif ujian</h2>
                        <button type="button" onclick="document.getElementById('createFeeDialog').close()" class="btn btn-secondary">Tutup</button>
                    </div>
                    @if ($errors->any())<div role="alert" class="m-3 rounded-lg bg-red-50 p-3 text-xs text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                {{-- FORM INPUT --}}
                <div class="p-3">
                    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="px-5 sm:px-6 py-4 border-b border-slate-200/80">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                Form Penetapan Tarif
                            </h3>
                            <p class="text-[11px] text-slate-500 font-medium">Tentukan biaya kenaikan tingkatan</p>
                        </div>

                        <form id="feeForm" action="{{ route('admin.exams.fees.store') }}" method="POST" class="p-5 sm:p-6 space-y-4">
                            @csrf

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Target Kenaikan Sabuk <span class="text-red-500">*</span>
                                </label>
                                <select name="belt_level_id" id="belt_level_id" required
                                    class="w-full h-10 px-3 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all uppercase">
                                    <option value="">Pilih Sabuk Target...</option>
                                    @foreach ($belts as $belt)
                                        @php $prev = $belts->where('order', '<', $belt->order)->sortByDesc('order')->first(); @endphp
                                        <option value="{{ $belt->id }}">
                                            {{ $prev ? $prev->name . ' → ' : '' }}{{ $belt->name }}
                                            ({{ $belt->kyu_dan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Tarif Biaya Ujian (Rp) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                                        Rp
                                    </span>
                                    <input type="text" id="amount_mask" required placeholder="0"
                                        class="w-full h-10 pl-10 pr-3.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all tabular-nums">
                                </div>
                                <input type="hidden" name="amount" id="amount_real">
                                <p class="text-[11px] text-slate-500 mt-1">
                                    * Format angka otomatis. Contoh: 150.000
                                </p>
                            </div>

                            <button type="submit"
                                class="btn btn-primary w-full">
                                Simpan Tarif Ujian
                            </button>
                        </form>
                    </div>
                </div>

                </dialog>

                <x-table-toolbar title="Daftar tarif ujian">
                    <button type="button" onclick="document.getElementById('createFeeDialog').showModal()" class="btn btn-primary">+ Tambah Tarif</button>
                </x-table-toolbar>
                {{-- LIST MASTER BIAYA --}}
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="px-5 sm:px-6 py-4 border-b border-slate-200/80 flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                    Daftar Tarif Ujian Aktif
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium">Tarif ujian yang berlaku saat ini per tingkatan sabuk</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $fees->count() }} Tingkatan
                            </span>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @forelse($fees as $fee)
                                @php
                                    $prevBelt = $belts
                                        ->where('order', '<', $fee->beltLevel->order)
                                        ->sortByDesc('order')
                                        ->first();
                                @endphp

                                <div class="p-4 sm:px-6 flex items-center justify-between gap-4 hover:bg-slate-50/60 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex items-center gap-2">
                                            @if ($prevBelt)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ getBeltColor($prevBelt->name) }}">
                                                    {{ $prevBelt->name }}
                                                </span>
                                                <span class="text-slate-400 text-xs">→</span>
                                            @endif
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border {{ getBeltColor($fee->beltLevel->name) }}">
                                                {{ $fee->beltLevel->name }}
                                            </span>
                                            <span class="text-[11px] text-slate-400 font-medium">({{ $fee->beltLevel->kyu_dan }})</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4 shrink-0">
                                        <span class="text-sm font-bold text-slate-900 tabular-nums">
                                            Rp {{ number_format($fee->amount, 0, ',', '.') }}
                                        </span>

                                        <div class="flex items-center gap-1.5">
                                            <x-action-button type="button"
                                                onclick="quickEdit('{{ $fee->belt_level_id }}', '{{ $fee->amount }}')" icon="edit" label="Edit" />

                                            <form action="{{ route('admin.exams.fees.destroy', $fee->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus pengaturan tarif ini?')">
                                                @csrf @method('DELETE')
                                                <x-action-button type="submit" icon="delete" label="Hapus" />
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-12 text-center text-slate-400 text-xs font-medium">
                                    Belum ada tarif ujian yang ditetapkan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const maskInput = document.getElementById('amount_mask');
        const realInput = document.getElementById('amount_real');

        maskInput?.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            realInput.value = value;
            this.value = value ? new Intl.NumberFormat('id-ID').format(value) : '';
        });

        @if ($errors->any())
            document.getElementById('createFeeDialog').showModal();
        @endif

        function quickEdit(beltId, amount) {
            document.getElementById('createFeeDialog').showModal();
            document.getElementById('belt_level_id').value = beltId;
            realInput.value = amount;
            maskInput.value = new Intl.NumberFormat('id-ID').format(amount);

            document.getElementById('feeForm').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            maskInput.focus();
        }
    </script>
</x-app-layout>
