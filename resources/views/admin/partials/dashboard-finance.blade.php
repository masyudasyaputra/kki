<section class="space-y-3" aria-labelledby="finance-heading">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 id="finance-heading" class="text-sm font-semibold text-slate-900">Keuangan iuran & ujian</h3>
            <p class="mt-1 text-xs text-slate-500">Periode {{ $month->locale('id')->translatedFormat('F Y') }}</p>
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            @foreach (request()->except(['month', 'page']) as $key => $value)
                @if (is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" aria-label="Bulan laporan keuangan" class="rounded-lg border-slate-200 py-1.5 text-xs text-slate-700">
            <button type="submit" class="btn btn-secondary">Tampilkan</button>
        </form>
    </div>
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3">
        @foreach (['iuran' => 'Penerimaan iuran', 'ujian' => 'Penerimaan ujian'] as $type => $label)
            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <p class="mt-1 break-words text-base font-bold tracking-tight sm:text-lg text-slate-900">Rp {{ number_format($finance[$type]['paid'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-emerald-700">{{ $finance[$type]['paid_count'] }} transaksi lunas</p>
                <div class="mt-2 flex flex-wrap justify-between gap-1 border-t border-slate-100 pt-2 text-xs text-slate-500">
                    <span>{{ $finance[$type]['pending_count'] }} tagihan tertunda</span>
                    <span class="font-medium text-amber-700">Rp {{ number_format($finance[$type]['pending'], 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach
        <div class="col-span-2 rounded-xl border border-slate-200 bg-slate-900 p-3 shadow-sm sm:col-span-1">
            <p class="text-xs text-slate-300">Total penerimaan tercatat</p>
            <p class="mt-1 text-lg font-bold tracking-tight text-white">Rp {{ number_format($finance['total'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-400">Iuran + ujian berstatus lunas</p>
            <p class="mt-2 border-t border-slate-700 pt-2 text-xs text-slate-300">Tertunda: Rp {{ number_format($finance['pending'], 0, ',', '.') }}</p>
        </div>
    </div>
    <details class="text-[11px] text-slate-500"><summary class="cursor-pointer">Dasar perhitungan</summary><p class="mt-1 leading-relaxed">Penerimaan mengikuti tanggal lunas (data lama memakai tanggal pembaruan). Tagihan tertunda dibuat pada bulan terpilih dan belum kedaluwarsa. Cakupan transaksi mengikuti akun pembayar, termasuk pembayaran kolektif. Angka ini belum dikurangi pengeluaran.</p></details>
</section>

<div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm" aria-labelledby="exam-summary-heading">
        <div class="flex items-center justify-between gap-2">
            <h3 id="exam-summary-heading" class="text-sm font-semibold text-slate-900">Administrasi ujian</h3>
            <span class="text-xs text-slate-500">{{ $examSummary['sessions'] }} sesi / {{ $examSummary['participants'] }} peserta</span>
        </div>
        <p class="mt-1 text-xs text-slate-500">Jadwal pelaksanaan {{ $month->locale('id')->translatedFormat('F Y') }}</p>
        @php($paidPercent = $examSummary['participants'] ? round(100 * $examSummary['paid_participants'] / $examSummary['participants']) : 0)
        <div class="mt-3 flex h-2 overflow-hidden rounded-full bg-amber-100" role="img" aria-label="{{ $paidPercent }} persen peserta sudah lunas">
            <span class="bg-emerald-500" style="width: {{ $paidPercent }}%"></span>
        </div>
        <dl class="mt-2 grid grid-cols-2 gap-2">
            <div class="rounded-lg bg-emerald-50 p-3">
                <dt class="text-xs text-emerald-700">{{ $examSummary['paid_participants'] }} peserta lunas</dt>
                <dd class="mt-1 text-sm font-semibold text-emerald-900">Rp {{ number_format($examSummary['paid_amount'], 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-lg bg-amber-50 p-3">
                <dt class="text-xs text-amber-700">{{ $examSummary['participants'] - $examSummary['paid_participants'] }} peserta belum lunas</dt>
                <dd class="mt-1 text-sm font-semibold text-amber-900">Rp {{ number_format($examSummary['unpaid_amount'], 0, ',', '.') }}</dd>
            </div>
        </dl>
        <p class="mt-3 text-[11px] leading-relaxed text-slate-500">Berdasarkan tarif dan status bayar peserta. Rekap ini terpisah dari transaksi penerimaan di atas.</p>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm" aria-labelledby="organization-attention-heading">
        <h3 id="organization-attention-heading" class="text-sm font-semibold text-slate-900">Perlu perhatian</h3>
        <dl class="mt-3 divide-y divide-slate-100 text-xs">
            <div class="flex justify-between gap-3 py-1.5"><dt class="text-slate-500">SK dojo berakhir dalam 30 hari</dt><dd class="font-semibold text-amber-700">{{ $organization['dojo_expiring'] }}</dd></div>
            <div class="flex justify-between gap-3 py-1.5"><dt class="text-slate-500">SK dojo sudah kedaluwarsa</dt><dd class="font-semibold text-red-700">{{ $organization['dojo_expired'] }}</dd></div>
            <div class="flex justify-between gap-3 py-1.5"><dt class="text-slate-500">SK pengurus berakhir dalam 30 hari</dt><dd class="font-semibold text-amber-700">{{ $organization['official_expiring'] }}</dd></div>
            <div class="flex justify-between gap-3 py-1.5"><dt class="text-slate-500">SK pengurus sudah kedaluwarsa</dt><dd class="font-semibold text-red-700">{{ $organization['official_expired'] }}</dd></div>
        </dl>
    </section>
</div>

@if ($upcomingExams->isNotEmpty())
    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm" aria-labelledby="upcoming-exams-heading">
        <h3 id="upcoming-exams-heading" class="text-sm font-semibold text-slate-900">Ujian mendatang</h3>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach ($upcomingExams as $upcoming)
                <a href="{{ route('admin.exams.show', $upcoming) }}" class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 p-3 hover:bg-slate-50">
                    <div class="min-w-0"><p class="truncate text-xs font-semibold text-slate-800">{{ $upcoming->name }}</p><p class="mt-1 text-[11px] text-slate-500">{{ $upcoming->execution_date->format('d/m/Y') }} / {{ $upcoming->location }}</p></div>
                    <span class="shrink-0 text-xs text-slate-500">&rarr;</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
