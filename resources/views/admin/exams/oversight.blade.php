<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="page-title">Pemantauan Dewan Guru Pusat</h1>
            <p class="page-subtitle">Ringkasan keanggotaan nasional dan jadwal ujian</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#4a1421] via-[#6e1d31] to-[#87283c] text-white shadow-sm">
            <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-100/80">Pusat pemantauan</p>
                    <h2 class="mt-2 text-xl font-bold sm:text-2xl">Pantau kegiatan KKI secara nasional</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-rose-50/85">Lihat ringkasan anggota, pengajuan ujian yang menunggu verifikasi pusat, dan jadwal ujian dari seluruh wilayah.</p>
                </div>
                <div class="flex shrink-0 items-center gap-3 rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 3v18h18M7 14l4-4 4 3 6-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span><span class="block text-xs text-rose-100/75">Terakhir diperbarui</span><span class="mt-0.5 block font-semibold">{{ now()->translatedFormat('d F Y') }}</span></span>
                </div>
            </div>
        </section>

        <section aria-label="Ringkasan nasional" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-sm font-medium text-slate-500">Anggota nasional</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($memberCount) }}</p></div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m16 0v-2a4 4 0 0 0-3-3.87M14 3.13a4 4 0 0 1 0 7.75M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <p class="mt-3 text-xs text-slate-500">{{ number_format($activeMemberCount) }} anggota aktif</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-sm font-medium text-slate-500">Menunggu verifikasi pusat</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($pendingCount) }}</p></div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Pengajuan ujian berstatus menunggu</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 xl:col-span-1">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-sm font-medium text-slate-500">Jadwal ujian</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($examCount) }}</p></div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-[#762137]" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Total jadwal ujian terdaftar</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Jadwal ujian nasional</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ number_format($exams->total()) }} jadwal ditemukan</p>
                </div>
                <form method="GET" action="{{ route('admin.oversight') }}" role="search" class="flex w-full gap-2 sm:max-w-md">
                    <label for="exam-search" class="sr-only">Cari nama ujian atau provinsi</label>
                    <input id="exam-search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari ujian atau provinsi..." class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-[#762137] focus:ring-[#762137]">
                    <button type="submit" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-[#641b2d] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4a1421] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4" stroke-linecap="round"/></svg><span>Cari</span>
                    </button>
                    @if(request()->filled('search'))<a href="{{ route('admin.oversight') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</a>@endif
                </form>
            </div>

            @if($exams->count())
                <div class="divide-y divide-slate-100">
                    @foreach($exams as $exam)
                        @php
                            $reviewLabel = match($exam->review_status) {
                                'submitted' => 'Menunggu verifikasi',
                                'approved' => 'Disetujui',
                                'revision' => 'Perlu revisi',
                                default => 'Draft',
                            };
                            $reviewClass = match($exam->review_status) {
                                'submitted' => 'bg-amber-50 text-amber-800 ring-amber-200',
                                'approved' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                                'revision' => 'bg-rose-50 text-rose-800 ring-rose-200',
                                default => 'bg-slate-100 text-slate-600 ring-slate-200',
                            };
                        @endphp
                        <article class="flex flex-col gap-4 px-5 py-5 transition hover:bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="flex min-w-0 gap-4">
                                <span class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-[#762137] sm:flex" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
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
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $reviewClass }}">{{ $reviewLabel }}</span>
                                <a href="{{ route('admin.exams.show', $exam) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-[#762137] hover:text-[#641b2d] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2">
                                    Detail<span class="sr-only"> {{ $exam->name }}</span><svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10h12m-5-5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6"><x-pagination :paginator="$exams" label="ujian" /></div>
            @else
                <div class="px-5 py-14 text-center sm:px-6">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    @if(request()->filled('search'))
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Jadwal tidak ditemukan</h3>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Coba kata kunci nama ujian atau provinsi yang berbeda.</p>
                        <a href="{{ route('admin.oversight') }}" class="mt-4 inline-flex text-sm font-semibold text-[#641b2d] hover:text-[#4a1421]">Hapus pencarian</a>
                    @else
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum ada jadwal ujian</h3>
                        <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">Jadwal ujian akan tampil di sini setelah didaftarkan oleh pengelola wilayah. Ringkasan akan diperbarui otomatis saat data tersedia.</p>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
