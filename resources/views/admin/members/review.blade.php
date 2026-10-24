<x-app-layout>
    @php
        /**
         * Breakdown biaya per sabuk (grouped)
         */
        $adminFee = (int) ($admin_fee ?? 0);

        $beltGroups = collect($member_data ?? [])
            ->groupBy(fn($m) => (string) ($m['belt_level_id'] ?? '0'))
            ->map(function ($items, $beltId) {
                $first = $items->first();

                $beltName = $first['belt_name'] ?? 'SABUK';
                $unit = (int) ($first['unit_fee'] ?? 0);
                $qty = $items->count();
                $subtotal = $unit * $qty;

                return [
                    'belt_level_id' => (int) $beltId,
                    'belt_name' => $beltName,
                    'unit' => $unit,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                    'missing_fee' => $unit <= 0,
                ];
            })
            ->values();

        $subtotalMembers = (int) $beltGroups->sum('subtotal');
        $grandTotal = $subtotalMembers + $adminFee;

        $totalMembers = count($member_data ?? []);
        $missingAnyFee = $beltGroups->contains(fn($g) => $g['missing_fee'] === true);
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">
                    Konfirmasi <span class="text-red-600">Pendaftaran</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">
                    Periksa kembali data calon anggota dan rincian tagihan sebelum melanjutkan proses pembayaran.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.members.create') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 rounded-lg text-xs font-medium shadow-sm transition-all">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali Edit</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($missingAnyFee)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                    <div class="p-1.5 rounded-lg bg-amber-500 text-white shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs font-bold text-amber-800 uppercase tracking-wide">
                            Konfigurasi Biaya Belum Lengkap
                        </h4>
                        <p class="text-xs text-amber-700 mt-0.5 leading-relaxed">
                            Terdapat tingkatan sabuk yang belum ditentukan tarifnya (Rp 0). Harap lengkapi pengaturan biaya pada menu Master Tarif Iuran terlebih dahulu sebelum melakukan checkout.
                        </p>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                
                {{-- Daftar Calon Anggota Section --}}
                <div class="p-5 sm:p-6 border-b border-slate-200/80">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                    Daftar Calon Anggota
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium">Verifikasi data identitas dan tingkatan sabuk</p>
                            </div>
                        </div>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            Total: {{ number_format($totalMembers) }} Orang
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100 border border-slate-100 rounded-lg overflow-hidden">
                        @foreach ($member_data as $index => $item)
                            <div class="p-3.5 sm:px-4 flex items-center justify-between gap-4 hover:bg-slate-50/60 transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center shrink-0">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 text-xs uppercase truncate">
                                            {{ $item['name'] }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[11px] text-slate-500 font-medium">
                                                WA: <span class="font-semibold text-slate-700">{{ $item['whatsapp'] }}</span>
                                            </span>
                                            @if (!empty($item['parent_name']))
                                                <span class="text-slate-300">•</span>
                                                <span class="text-[11px] text-slate-500">Ortu: {{ $item['parent_name'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $item['belt_name'] ?? 'SABUK' }}
                                    </span>
                                    <div class="text-[11px] font-semibold text-slate-600 mt-1 tabular-nums">
                                        Rp {{ number_format((int) ($item['unit_fee'] ?? 0), 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Rincian Invoice Section --}}
                <div class="p-5 sm:p-6 bg-slate-50/50">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                    Rincian Tagihan Biaya
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium">Rincian akumulasi biaya pendaftaran per tingkatan sabuk</p>
                            </div>
                        </div>
                    </div>

                    {{-- Breakdown Table --}}
                    <div class="bg-white rounded-lg border border-slate-200/80 overflow-hidden shadow-xs">
                        <table class="min-w-full text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200/80">
                                <tr class="text-slate-600 font-semibold uppercase text-[11px]">
                                    <th class="py-2.5 px-4 text-left">Tingkatan Sabuk</th>
                                    <th class="py-2.5 px-4 text-right">Tarif Satuan</th>
                                    <th class="py-2.5 px-4 text-center">Jumlah</th>
                                    <th class="py-2.5 px-4 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($beltGroups as $g)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3 px-4 font-semibold text-slate-800 uppercase">
                                            {{ $g['belt_name'] }}
                                        </td>
                                        <td class="py-3 px-4 text-right tabular-nums text-slate-700">
                                            Rp {{ number_format($g['unit'], 0, ',', '.') }}
                                            @if ($g['missing_fee'])
                                                <span class="block text-[10px] text-amber-600 font-bold">Belum ditentukan</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                                {{ $g['qty'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right font-semibold text-slate-800 tabular-nums">
                                            Rp {{ number_format($g['subtotal'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Ringkasan Biaya & Total --}}
                    <div class="mt-4 bg-white rounded-lg border border-slate-200/80 p-4 space-y-2.5 shadow-xs">
                        <div class="flex justify-between items-center text-xs text-slate-600 font-medium">
                            <span>Subtotal Pendaftaran</span>
                            <span class="font-semibold text-slate-800 tabular-nums">Rp {{ number_format($subtotalMembers, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-slate-600 font-medium">
                            <span>Biaya Layanan Admin</span>
                            <span class="font-semibold text-slate-800 tabular-nums">Rp {{ number_format($adminFee, 0, ',', '.') }}</span>
                        </div>
                        <div class="pt-2.5 border-t border-slate-100 flex justify-between items-center">
                            <span class="text-sm font-bold text-slate-900">Total Pembayaran</span>
                            <span class="text-base font-bold text-emerald-600 tabular-nums">
                                Rp {{ number_format($grandTotal, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500 mt-3 leading-relaxed">
                        * Invoice resmi akan diterbitkan setelah menekan tombol konfirmasi pembayaran.
                    </p>
                </div>

                {{-- Action Submission --}}
                <div class="p-5 sm:p-6 bg-white border-t border-slate-200/80">
                    <form action="{{ route('admin.members.store') }}" method="POST">
                        @csrf

                        @if (!empty($provinceId))
                            <input type="hidden" name="province_id" value="{{ $provinceId }}">
                        @endif

                        @foreach ($member_data as $index => $item)
                            <input type="hidden" name="members[{{ $index }}][name]" value="{{ $item['name'] }}">
                            <input type="hidden" name="members[{{ $index }}][whatsapp]" value="{{ $item['whatsapp'] }}">
                            <input type="hidden" name="members[{{ $index }}][belt_level_id]" value="{{ $item['belt_level_id'] }}">
                            <input type="hidden" name="members[{{ $index }}][parent_name]" value="{{ $item['parent_name'] ?? '' }}">

                            @if (!empty($item['email']))
                                <input type="hidden" name="members[{{ $index }}][email]" value="{{ $item['email'] }}">
                            @endif
                        @endforeach

                        <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                            <a href="{{ route('admin.members.create') }}"
                                class="w-full sm:w-auto px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold text-center transition-all">
                                Kembali Edit Data
                            </a>

                            <button type="submit" @if ($missingAnyFee) disabled @endif
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg text-xs font-semibold text-white transition-all shadow-sm
                                {{ $missingAnyFee ? 'bg-slate-300 text-slate-500 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                <span>Konfirmasi & Bayar Sekarang</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <p class="text-center text-[11px] text-slate-400">
                Data anggota yang telah terkonfirmasi akan otomatis tersimpan ke dalam database dan diterbitkan nomor kartu anggota.
            </p>
        </div>
    </div>
</x-app-layout>
