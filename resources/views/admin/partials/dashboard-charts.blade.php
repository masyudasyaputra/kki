@php
    $topBranches = $branchDojos->sortByDesc('dojo_count')->take(6);
    $maxDojos = max(1, (int) $branchDojos->max('dojo_count'));
    $maxFinance = max(1, $finance['iuran']['paid'], $finance['ujian']['paid'], $finance['iuran']['pending'], $finance['ujian']['pending']);
@endphp
<div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm" aria-labelledby="branch-chart-title">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 id="branch-chart-title" class="text-sm font-semibold text-slate-900">Sebaran dojo per cabang</h3>
            <div class="flex items-center gap-3 text-[10px] text-slate-500"><span><i class="mr-1 inline-block h-2 w-2 rounded-sm bg-red-500"></i>SK berlaku</span><span><i class="mr-1 inline-block h-2 w-2 rounded-sm bg-slate-200"></i>Lainnya</span></div>
        </div>
        <p class="mt-1 text-[11px] text-slate-500">{{ $organization['cities'] }} cabang / {{ $organization['dojos'] }} dojo. Maksimal 6 cabang terbesar.</p>
        <div class="mt-3 space-y-2">
            @forelse ($topBranches as $branch)
                <div class="grid grid-cols-[minmax(0,110px)_1fr_32px] items-center gap-2 text-xs" title="{{ $branch->city?->name ?? 'Belum ditetapkan' }}: {{ $branch->dojo_count }} dojo, {{ $branch->active_dojo_count }} SK berlaku">
                    <span class="truncate text-slate-600">{{ $branch->city?->name ?? 'Belum ditetapkan' }}</span>
                    <div role="img" aria-label="{{ $branch->dojo_count }} dojo, {{ $branch->active_dojo_count }} SK berlaku" class="h-3">
                        <div class="h-full overflow-hidden rounded-sm bg-slate-200" style="width: {{ 100 * $branch->dojo_count / $maxDojos }}%">
                            <div class="h-full rounded-sm bg-red-500" style="width: {{ $branch->dojo_count ? 100 * $branch->active_dojo_count / $branch->dojo_count : 0 }}%"></div>
                        </div>
                    </div>
                    <span class="text-right font-semibold tabular-nums text-slate-800">{{ $branch->dojo_count }}</span>
                </div>
            @empty
                <p class="py-4 text-xs text-slate-500">Belum ada data dojo.</p>
            @endforelse
        </div>
        <details class="mt-3 border-t border-slate-100 pt-2">
            <summary class="cursor-pointer text-xs font-medium text-slate-600">Lihat rincian semua cabang</summary>
            <div class="mt-3">@include('admin.partials.dashboard-branch-details')</div>
        </details>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm" aria-labelledby="finance-chart-title">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 id="finance-chart-title" class="text-sm font-semibold text-slate-900">Perbandingan iuran & ujian</h3>
            <span class="text-[11px] text-slate-500">{{ $month->locale('id')->translatedFormat('F Y') }}</span>
        </div>
        <div class="mt-2 flex items-center gap-3 text-[10px] text-slate-500"><span><i class="mr-1 inline-block h-2 w-2 rounded-sm bg-emerald-500"></i>Lunas</span><span><i class="mr-1 inline-block h-2 w-2 rounded-sm bg-amber-400"></i>Tertunda</span></div>
        <div class="mt-3 space-y-3">
            @foreach (['iuran' => 'Iuran', 'ujian' => 'Ujian'] as $type => $label)
                <div>
                    <p class="mb-1 text-xs font-medium text-slate-700">{{ $label }}</p>
                    @foreach (['paid' => ['Lunas', 'bg-emerald-500'], 'pending' => ['Tertunda', 'bg-amber-400']] as $status => [$statusLabel, $color])
                        <div class="mb-1 grid grid-cols-[1fr_112px] items-center gap-2" title="{{ $label }} {{ strtolower($statusLabel) }}: Rp {{ number_format($finance[$type][$status], 0, ',', '.') }}">
                            <div class="h-3 rounded-sm bg-slate-50" role="img" aria-label="{{ $label }} {{ strtolower($statusLabel) }}: Rp {{ number_format($finance[$type][$status], 0, ',', '.') }}">
                                <div class="h-full rounded-sm {{ $color }}" style="width: {{ 100 * $finance[$type][$status] / $maxFinance }}%"></div>
                            </div>
                            <span class="text-right text-[11px] tabular-nums text-slate-600">Rp {{ number_format($finance[$type][$status], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-[10px] text-slate-500">Skala rupiah sama untuk seluruh batang. Ubah bulan pada ringkasan keuangan.</p>
    </section>
</div>
