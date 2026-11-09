<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="page-title">Pengajuan Hasil Ujian ke Pusat</h1>
            <p class="page-subtitle">Pantau status pengajuan dan tindak lanjuti hasil ujian dari wilayah Anda.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#4a1421] via-[#6e1d31] to-[#87283c] text-white shadow-sm">
            <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-100/80">Verifikasi ujian</p>
                    <h2 class="mt-2 text-xl font-bold sm:text-2xl">Pengajuan hasil ujian</h2>
                    <p class="mt-2 text-sm leading-6 text-rose-50/85">Tinjau hasil yang dikirim panitia, lihat riwayat keputusan, dan pantau pengajuan yang perlu ditindaklanjuti.</p>
                </div>
                <a href="{{ route('admin.exams.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#6e1d31]">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Jadwal Ujian
                </a>
            </div>
        </section>

        <section aria-label="Ringkasan pengajuan" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $reviewStats = [
                    ['label' => 'Semua pengajuan', 'value' => $totalCount, 'status' => null, 'tone' => 'slate'],
                    ['label' => 'Menunggu verifikasi', 'value' => $statusCounts->get('submitted', 0), 'status' => 'submitted', 'tone' => 'amber'],
                    ['label' => 'Perlu perbaikan', 'value' => $statusCounts->get('revision', 0), 'status' => 'revision', 'tone' => 'rose'],
                    ['label' => 'Disetujui pusat', 'value' => $statusCounts->get('approved', 0), 'status' => 'approved', 'tone' => 'emerald'],
                ];
            @endphp
            @foreach($reviewStats as $stat)
                @php
                    $toneClasses = match($stat['tone']) {
                        'amber' => 'bg-amber-50 text-amber-700',
                        'rose' => 'bg-rose-50 text-rose-700',
                        'emerald' => 'bg-emerald-50 text-emerald-700',
                        default => 'bg-slate-100 text-slate-700',
                    };
                    $selected = request('status') === $stat['status'] || ($stat['status'] === null && !request()->filled('status'));
                    $statUrl = route('admin.exam-reviews.index', array_filter(['status' => $stat['status'], 'search' => request('search')], fn ($value) => $value !== null && $value !== ''));
                @endphp
                <a href="{{ $statUrl }}" class="rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $selected ? 'border-[#762137] ring-1 ring-[#762137]/15' : 'border-slate-200' }}">
                    <div class="flex items-center justify-between gap-3"><p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p><span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $toneClasses }}">{{ number_format($stat['value']) }}</span></div>
                    <p class="mt-3 text-xs text-slate-500">{{ $selected ? 'Sedang ditampilkan' : 'Lihat daftar' }}</p>
                </a>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Daftar pengajuan</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ number_format($exams->total()) }} ujian sesuai pencarian dan filter</p>
                </div>
                <form method="GET" action="{{ route('admin.exam-reviews.index') }}" role="search" class="grid w-full gap-2 sm:max-w-xl sm:grid-cols-[minmax(0,1fr)_190px_auto]">
                    <label for="review-search" class="sr-only">Cari nama ujian atau provinsi</label>
                    <input id="review-search" type="search" name="search" value="{{ request('search') }}" placeholder="Cari ujian atau provinsi..." class="min-w-0 rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-[#762137] focus:ring-[#762137]">
                    <label for="review-status" class="sr-only">Filter status pengajuan</label>
                    <select id="review-status" name="status" class="rounded-lg border-slate-300 text-sm text-slate-700 shadow-sm focus:border-[#762137] focus:ring-[#762137]">
                        <option value="">Semua status</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>Menunggu verifikasi</option>
                        <option value="revision" @selected(request('status') === 'revision')>Perlu perbaikan</option>
                        <option value="approved" @selected(request('status') === 'approved')>Disetujui pusat</option>
                        <option value="draft" @selected(request('status') === 'draft')>Belum dikirim</option>
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#641b2d] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4a1421] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4" stroke-linecap="round"/></svg><span>Terapkan</span>
                    </button>
                </form>
            </div>

            @if($exams->count())
                <div class="divide-y divide-slate-100">
                    @foreach($exams as $exam)
                        @php
                            $statusLabel = match($exam->review_status) {
                                'submitted' => 'Menunggu verifikasi',
                                'revision' => 'Perlu perbaikan',
                                'approved' => 'Disetujui pusat',
                                default => 'Belum dikirim',
                            };
                            $statusClass = match($exam->review_status) {
                                'submitted' => 'bg-amber-50 text-amber-800 ring-amber-200',
                                'revision' => 'bg-rose-50 text-rose-800 ring-rose-200',
                                'approved' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                                default => 'bg-slate-100 text-slate-600 ring-slate-200',
                            };
                        @endphp
                        <article class="flex flex-col gap-4 px-5 py-5 transition hover:bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="flex min-w-0 gap-4">
                                <span class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-[#762137] sm:flex" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold text-slate-900">{{ $exam->name }}</h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                        <span>{{ $exam->province?->name ?? 'Nasional' }}</span>
                                        <span>{{ $exam->execution_date?->translatedFormat('d M Y') ?? 'Tanggal belum ditentukan' }}</span>
                                        <span>{{ number_format($exam->participants_count) }} peserta</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center justify-between gap-3 sm:justify-end">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">{{ $statusLabel }}</span>
                                <a href="{{ route('admin.exam-reviews.show', $exam) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-[#762137] hover:text-[#641b2d] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2">Tinjau<span class="sr-only"> {{ $exam->name }}</span><svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10h12m-5-5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6"><x-pagination :paginator="$exams" label="pengajuan" /></div>
            @else
                <div class="px-5 py-14 text-center sm:px-6">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    @if(request()->filled('search') || request()->filled('status'))
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Tidak ada pengajuan yang cocok</h3>
                        <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">Coba ubah kata kunci atau pilih status lain untuk melihat pengajuan.</p>
                        <a href="{{ route('admin.exam-reviews.index') }}" class="mt-4 inline-flex text-sm font-semibold text-[#641b2d] hover:text-[#4a1421]">Hapus pencarian dan filter</a>
                    @else
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum ada pengajuan hasil ujian</h3>
                        <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">Pengajuan akan muncul di sini setelah panitia mengirim hasil ujian ke pusat. Anda tetap dapat melihat jadwal dan peserta melalui halaman jadwal ujian.</p>
                        <a href="{{ route('admin.exams.index') }}" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-[#641b2d] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#4a1421]">Buka Jadwal Ujian<svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10h12m-5-5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
