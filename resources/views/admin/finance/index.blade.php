<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#762137]">Laporan & transaksi</p><h1 class="page-title">Keuangan</h1><p class="page-subtitle">Pantau penerimaan iuran anggota dan biaya ujian dalam satu laporan.</p></div>
    </x-slot>

    @php
        $maxTrend = max(1, collect($trend)->max(fn ($item) => $item['iuran'] + $item['ujian']));
        $statusStyles = ['paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'pending' => 'bg-amber-50 text-amber-800 ring-amber-200', 'unpaid' => 'bg-slate-100 text-slate-700 ring-slate-200', 'failed' => 'bg-rose-50 text-rose-700 ring-rose-200', 'expired' => 'bg-rose-50 text-rose-700 ring-rose-200', 'canceled' => 'bg-rose-50 text-rose-700 ring-rose-200'];
        $statusText = fn ($value) => \App\Http\Controllers\Admin\FinanceController::statusLabel($value);
        $money = fn ($amount) => 'Rp '.number_format((float) $amount, 0, ',', '.');
    @endphp

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="text-sm font-semibold text-slate-900">Ringkasan penerimaan</h2><p class="mt-1 text-xs text-slate-500">{{ $from->locale('id')->translatedFormat('d M Y') }} – {{ $to->locale('id')->translatedFormat('d M Y') }} · Penerimaan bruto dari transaksi berstatus lunas.</p></div>
                    <a href="{{ route('admin.finance.export', request()->except('page')) }}" class="btn btn-secondary"><x-sidebar-icon name="receipt" />Unduh CSV untuk Excel</a>
                </div>
            </div>
            <div class="grid gap-px bg-slate-100 sm:grid-cols-2 xl:grid-cols-4">
                <article class="bg-white p-5"><p class="text-xs font-medium text-slate-500">Total penerimaan</p><p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $money($summary['total']) }}</p><p class="mt-1 text-xs text-emerald-700">{{ number_format($summary['paid_count']) }} transaksi lunas</p></article>
                <article class="bg-white p-5"><div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-violet-500"></span><p class="text-xs font-medium text-slate-500">Penerimaan iuran</p></div><p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $money($summary['iuran']['paid']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($summary['iuran']['paid_count']) }} transaksi lunas</p></article>
                <article class="bg-white p-5"><div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-[#762137]"></span><p class="text-xs font-medium text-slate-500">Penerimaan ujian</p></div><p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $money($summary['ujian']['paid']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($summary['ujian']['paid_count']) }} transaksi lunas</p></article>
                <article class="bg-white p-5"><p class="text-xs font-medium text-slate-500">Tagihan belum lunas</p><p class="mt-2 text-2xl font-bold tracking-tight text-amber-700">{{ $money($summary['due']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($summary['due_count']) }} tagihan aktif / belum dibayar</p></article>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="finance-trend-title">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 id="finance-trend-title" class="text-sm font-semibold text-slate-900">Tren penerimaan 6 bulan</h2><p class="mt-1 text-xs text-slate-500">Perbandingan transaksi lunas iuran dan biaya ujian.</p></div><div class="flex flex-wrap gap-3 text-xs"><span class="inline-flex items-center gap-1.5 text-slate-600"><i class="h-2.5 w-2.5 rounded-sm bg-violet-500"></i>Iuran</span><span class="inline-flex items-center gap-1.5 text-slate-600"><i class="h-2.5 w-2.5 rounded-sm bg-[#762137]"></i>Ujian</span></div></div>
            <div class="mt-5 grid grid-cols-6 gap-3 sm:gap-5">
                @foreach($trend as $month)
                    @php($total = $month['iuran'] + $month['ujian'])
                    <div class="flex min-w-0 flex-col items-center gap-2">
                        <div class="flex h-28 w-full max-w-12 flex-col justify-end overflow-hidden rounded-t-lg bg-slate-100" title="Iuran {{ $money($month['iuran']) }} · Ujian {{ $money($month['ujian']) }}">
                            @if($total > 0)
                                <div class="w-full bg-[#762137]" style="height: {{ max(2, 100 * $month['ujian'] / $maxTrend) }}%"></div>
                                <div class="w-full bg-violet-500" style="height: {{ max(2, 100 * $month['iuran'] / $maxTrend) }}%"></div>
                            @endif
                        </div>
                        <span class="text-[11px] font-medium text-slate-500">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] leading-5 text-slate-500">Angka iuran dan ujian dihitung dari catatan pembayaran. Pembayaran ujian yang diverifikasi manual juga masuk sebagai transaksi; status peserta yang belum lunas ditampilkan sebagai tagihan.</p>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Transaksi & tagihan</h2><p class="mt-1 text-xs text-slate-500">Telusuri invoice, anggota, ujian, dojo, dan status pembayaran.</p></div>
            <form method="get" action="{{ route('admin.finance.index') }}" class="grid gap-3 border-b border-slate-100 bg-slate-50/70 p-4 sm:grid-cols-2 lg:grid-cols-6 lg:px-5">
                <label class="text-[11px] font-semibold text-slate-600">Dari tanggal<input type="date" name="date_from" value="{{ $from->toDateString() }}" class="mt-1 block min-h-10 w-full rounded-lg border-slate-200 bg-white text-xs"></label>
                <label class="text-[11px] font-semibold text-slate-600">Sampai tanggal<input type="date" name="date_to" value="{{ $to->toDateString() }}" class="mt-1 block min-h-10 w-full rounded-lg border-slate-200 bg-white text-xs"></label>
                <label class="text-[11px] font-semibold text-slate-600">Jenis pemasukan<select name="source" class="mt-1 block min-h-10 w-full rounded-lg border-slate-200 bg-white text-xs"><option value="all" @selected(($filters['source'] ?? 'all') === 'all')>Semua jenis</option><option value="iuran" @selected(($filters['source'] ?? '') === 'iuran')>Iuran anggota</option><option value="ujian" @selected(($filters['source'] ?? '') === 'ujian')>Biaya ujian</option></select></label>
                <label class="text-[11px] font-semibold text-slate-600">Status<select name="status" class="mt-1 block min-h-10 w-full rounded-lg border-slate-200 bg-white text-xs"><option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua status</option><option value="paid" @selected(($filters['status'] ?? '') === 'paid')>Lunas</option><option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Menunggu pembayaran</option><option value="unpaid" @selected(($filters['status'] ?? '') === 'unpaid')>Belum dibayar</option><option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Gagal / kedaluwarsa</option></select></label>
                <label class="text-[11px] font-semibold text-slate-600 sm:col-span-2">Cari transaksi<input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Invoice, anggota, dojo, atau ujian" class="mt-1 block min-h-10 w-full rounded-lg border-slate-200 bg-white text-xs"></label>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6"><button class="btn btn-primary">Terapkan filter</button><a href="{{ route('admin.finance.index') }}" class="btn btn-secondary">Reset</a></div>
            </form>

            <div class="divide-y divide-slate-100 md:hidden">
                @forelse($transactions as $transaction)
                    @php($isManualExam = $transaction->source === 'ujian' && is_numeric($transaction->reference))
                    <article class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><x-member-identity :name="$transaction->member_name ?: $transaction->payer_name" :photo="$transaction->member_photo"><p class="truncate text-xs font-semibold text-slate-900">{{ $transaction->member_name ?: $transaction->payer_name }}</p><p class="mt-1 truncate text-[11px] text-slate-500">{{ $isManualExam ? 'UKT-'.$transaction->reference : $transaction->reference }}</p></x-member-identity></div><span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold ring-1 ring-inset {{ $statusStyles[$transaction->status] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $statusText($transaction->status) }}</span></div>
                        <div class="flex items-end justify-between gap-3"><div><span class="inline-flex rounded-md px-2 py-1 text-[10px] font-semibold {{ $transaction->source === 'iuran' ? 'bg-violet-50 text-violet-700' : 'bg-[#f7ecef] text-[#762137]' }}">{{ $transaction->source === 'iuran' ? 'Iuran' : 'Ujian' }}</span><p class="mt-1 text-[11px] text-slate-500">{{ $transaction->exam_name ?: ($transaction->dojo_name ?: $transaction->city_name ?: '—') }} · {{ $transaction->occurred_at ? \Illuminate\Support\Carbon::parse($transaction->occurred_at)->format('d/m/Y') : '—' }}</p></div><p class="text-sm font-bold text-slate-900">{{ $money($transaction->amount) }}</p></div>
                    </article>
                @empty
                    <div class="px-5 py-14 text-center"><h3 class="text-sm font-semibold text-slate-900">Belum ada transaksi sesuai filter</h3><p class="mt-1 text-xs text-slate-500">Ubah rentang tanggal, jenis pemasukan, atau status untuk melihat data lain.</p></div>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[900px] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Tanggal / Referensi</th><th class="px-5 py-3">Anggota / Pembayar</th><th class="px-5 py-3">Sumber</th><th class="px-5 py-3">Wilayah / Ujian</th><th class="px-5 py-3">Nominal</th><th class="px-5 py-3">Kanal / Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($transactions as $transaction)
                            @php($isManualExam = $transaction->source === 'ujian' && is_numeric($transaction->reference))
                            <tr class="hover:bg-slate-50/70"><td class="px-5 py-3.5"><p class="font-semibold text-slate-800">{{ $isManualExam ? 'UKT-'.$transaction->reference : $transaction->reference }}</p><p class="mt-1 text-[11px] text-slate-500">{{ $transaction->occurred_at ? \Illuminate\Support\Carbon::parse($transaction->occurred_at)->format('d M Y H:i') : '—' }}</p></td><td class="px-5 py-3.5"><x-member-identity :name="$transaction->member_name ?: $transaction->payer_name" :photo="$transaction->member_photo"><p class="font-medium text-slate-800">{{ $transaction->member_name ?: $transaction->payer_name }}</p>@if($transaction->payer_name && $transaction->member_name !== $transaction->payer_name)<p class="mt-1 text-[11px] text-slate-500">Pembayar: {{ $transaction->payer_name }}</p>@endif</x-member-identity></td><td class="px-5 py-3.5"><span class="inline-flex rounded-md px-2 py-1 text-[10px] font-semibold {{ $transaction->source === 'iuran' ? 'bg-violet-50 text-violet-700' : 'bg-[#f7ecef] text-[#762137]' }}">{{ $transaction->source === 'iuran' ? 'Iuran anggota' : 'Biaya ujian' }}</span></td><td class="px-5 py-3.5"><p class="font-medium text-slate-700">{{ $transaction->exam_name ?: ($transaction->dojo_name ?: '—') }}</p><p class="mt-1 text-[11px] text-slate-500">{{ collect([$transaction->city_name, $transaction->province_name])->filter()->unique()->implode(' · ') ?: 'Wilayah belum tercatat' }}</p></td><td class="px-5 py-3.5 font-bold text-slate-900">{{ $money($transaction->amount) }}</td><td class="px-5 py-3.5"><p class="text-slate-600">{{ $transaction->channel }}</p><span class="mt-1 inline-flex rounded-full px-2 py-1 text-[10px] font-semibold ring-1 ring-inset {{ $statusStyles[$transaction->status] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $statusText($transaction->status) }}</span></td></tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-14 text-center"><h3 class="text-sm font-semibold text-slate-900">Belum ada transaksi sesuai filter</h3><p class="mt-1 text-xs text-slate-500">Ubah rentang tanggal, jenis pemasukan, atau status untuk melihat data lain.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 sm:px-5"><x-pagination :paginator="$transactions" label="transaksi / tagihan" /></div>
        </section>
        <p class="text-[11px] leading-5 text-slate-500">Laporan ini menampilkan penerimaan bruto dan tagihan, belum mencakup pengeluaran. Baris ujian yang diverifikasi manual berasal dari status pembayaran peserta; transaksi DOKU dicatat dari invoice pembayaran.</p>
    </main>
</x-app-layout>
