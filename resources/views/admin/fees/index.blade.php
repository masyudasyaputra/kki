<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">
                    Konfigurasi <span class="text-red-600">Iuran Sabuk</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">
                    Atur tarif iuran registrasi per tingkatan sabuk. Nilai tersimpan secara otomatis (auto-save).
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

    @php
        if (!function_exists('beltBadge')) {
            function beltBadge($beltName)
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
                if (str_contains($name, 'cokelat') || str_contains($name, 'coklat')) {
                    return 'bg-amber-100 text-amber-900 border-amber-300';
                }
                if (str_contains($name, 'hitam')) {
                    return 'bg-slate-900 text-white border-slate-950';
                }

                return 'bg-slate-50 text-slate-600 border-slate-200';
            }
        }
    @endphp

    <div class="py-6 sm:py-8 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Status Bar --}}
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] text-slate-500 font-medium">Cakupan Wilayah Konfigurasi</p>
                        <p class="text-xs font-bold text-slate-800 uppercase tracking-tight truncate">
                            {{ Auth::user()->province->name ?? 'Nasional (Pengurus Besar)' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div id="save-status" class="hidden items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-40"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                        <span class="text-xs font-medium text-slate-500">Menyimpan...</span>
                    </div>

                    <div id="save-ok" class="hidden items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-emerald-700">Tersimpan</span>
                    </div>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $beltLevels->count() }} Tingkatan Sabuk
                    </span>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                            Tabel Tarif Iuran
                        </h3>
                        <p class="text-[11px] text-slate-500 font-medium">Ubah nominal pada kolom biaya, data akan disimpan secara otomatis.</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Auto-Save Aktif
                    </span>
                </div>

                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50/90 border-b border-slate-200/80">
                        <tr class="text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                            <th class="px-6 py-3.5 text-left w-16">Urutan</th>
                            <th class="px-6 py-3.5 text-left">Nama Sabuk</th>
                            <th class="px-6 py-3.5 text-left">Kyu / Dan</th>
                            <th class="px-6 py-3.5 text-right w-64">Biaya Iuran (Rp)</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($beltLevels as $belt)
                            @php
                                $amount = $fees[$belt->id]->amount ?? 0;
                                $badgeClass = beltBadge($belt->name);
                            @endphp

                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-500">
                                    #{{ $belt->order }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold border {{ $badgeClass }}">
                                        {{ $belt->name }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 font-medium text-slate-600">
                                    {{ $belt->kyu_dan }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="relative w-48 ml-auto">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                                            Rp
                                        </span>
                                        <input type="text"
                                            class="currency-input autosave w-full h-10 pl-9 pr-3.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all tabular-nums text-right"
                                            value="{{ number_format($amount, 0, ',', '.') }}"
                                            data-belt-id="{{ $belt->id }}" inputmode="numeric"
                                            autocomplete="off" placeholder="0">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200/80">
                    <p class="text-[11px] text-slate-500 font-medium">
                        * Nilai tarif tersimpan otomatis tanpa perlu menekan tombol konfirmasi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const statusSaving = document.getElementById('save-status');
            const statusOk = document.getElementById('save-ok');

            const showSaving = () => {
                statusOk?.classList.add('hidden');
                statusOk?.classList.remove('flex');
                statusSaving?.classList.remove('hidden');
                statusSaving?.classList.add('flex');
            };

            const showOk = () => {
                statusSaving?.classList.add('hidden');
                statusSaving?.classList.remove('flex');
                statusOk?.classList.remove('hidden');
                statusOk?.classList.add('flex');
                setTimeout(() => {
                    statusOk?.classList.add('hidden');
                    statusOk?.classList.remove('flex');
                }, 1200);
            };

            const formatID = (val) => {
                const digits = (val || '').toString().replace(/\D/g, '');
                return digits ? new Intl.NumberFormat('id-ID').format(digits) : '';
            };

            const debounce = (fn, delay = 450) => {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), delay);
                };
            };

            async function saveFee(beltId, amount) {
                showSaving();

                try {
                    const res = await fetch("{{ route('admin.fees.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            amounts: {
                                [beltId]: Number(amount || 0)
                            }
                        })
                    });

                    if (!res.ok) throw new Error('Save failed');
                    showOk();
                } catch (e) {
                    statusSaving?.classList.add('hidden');
                    statusSaving?.classList.remove('flex');
                    console.error(e);
                    alert('Gagal menyimpan tarif. Periksa koneksi atau coba lagi.');
                }
            }

            const debouncedSave = debounce(saveFee, 500);

            document.querySelectorAll('.currency-input.autosave').forEach((input) => {
                input.value = formatID(input.value);

                input.addEventListener('input', function() {
                    const beltId = this.dataset.beltId;
                    const digits = this.value.replace(/\D/g, '');
                    this.value = formatID(digits);
                    debouncedSave(beltId, digits);
                });

                input.addEventListener('blur', function() {
                    const beltId = this.dataset.beltId;
                    const digits = this.value.replace(/\D/g, '');
                    this.value = formatID(digits);
                    saveFee(beltId, digits);
                });
            });
        })();
    </script>
</x-app-layout>
