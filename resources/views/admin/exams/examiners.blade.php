<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="page-title">
                    Penugasan <span class="text-red-600">Penguji Ujian</span>
                </h1>
                <p class="page-subtitle">
                    {{ $exam->name }} • {{ \Carbon\Carbon::parse($exam->execution_date)->format('d M Y') }}
                </p>
            </div>
            <div>
                <a href="{{ route('admin.exams.show', $exam) }}"
                    class="btn btn-secondary inline-flex">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- NOTIFIKASI BERHASIL --}}
            @if (session('success'))
                <div id="toast-success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-emerald-900">{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="btn btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- Tim Penguji Saat Ini --}}
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                Tim Penguji Terpilih
                            </h3>
                            <p class="text-[11px] text-slate-500 font-medium">Penguji yang saat ini memiliki akses memberikan penilaian ujian</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ count($selected) }} Penguji
                    </span>
                </div>

                <div class="flex gap-2.5 overflow-x-auto pb-1 pt-1 no-scrollbar">
                    @forelse($exam->examiners as $assigned)
                        <div class="flex-none flex items-center gap-2.5 bg-slate-50 px-3 py-2 rounded-lg border border-slate-200/80 shadow-xs">
                            <div class="w-7 h-7 rounded-md bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase">
                                {{ substr($assigned->name, 0, 1) }}
                            </div>
                            <div class="whitespace-nowrap">
                                <p class="text-xs font-semibold text-slate-800 uppercase leading-tight">{{ $assigned->name }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ $assigned->dojo->name ?? 'Independen' }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="w-full py-4 text-center border border-dashed border-slate-200 rounded-lg">
                            <p class="text-xs text-slate-400 font-medium">Belum ada penguji yang ditugaskan pada sesi ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Form Database Penguji --}}
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-200/80">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                    Daftar Master Penguji
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium">Centang penguji yang akan ditugaskan ke sesi ini</p>
                            </div>
                        </div>

                        <div class="relative w-full sm:w-64">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" id="searchPenguji" placeholder="Cari nama / dojo..."
                                class="w-full h-10 pl-9 pr-3 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.exams.examiners.update', $exam) }}">
                    @csrf
                    @method('PUT')

                    <div class="p-5 sm:p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="containerPenguji">
                            @foreach ($examiners as $u)
                                <label class="card-penguji relative flex items-center p-3.5 rounded-lg border border-slate-200 bg-white cursor-pointer hover:border-slate-300 hover:bg-slate-50/60 transition-all group has-[:checked]:border-red-500 has-[:checked]:bg-red-50/20">
                                    <input type="checkbox" name="examiner_ids[]" value="{{ $u->id }}"
                                        @checked(in_array($u->id, $selected))
                                        class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500" />

                                    <div class="ml-3.5 flex-1 min-w-0">
                                        <p class="nama-penguji font-semibold text-slate-800 uppercase text-xs truncate group-hover:text-red-600 transition-colors">
                                            {{ $u->name }}
                                        </p>

                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[11px] text-slate-500 font-medium">
                                                {{ $u->province->name ?? 'Wilayah N/A' }}
                                            </span>
                                            <span class="text-slate-300">•</span>
                                            <span class="dojo-penguji text-[11px] font-semibold text-slate-600 truncate">
                                                {{ $u->dojo->name ?? 'Independen' }}
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="px-5 sm:px-6 py-4 bg-slate-50/80 border-t border-slate-200/80 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.exams.show', $exam) }}"
                            class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit"
                            class="btn btn-primary inline-flex">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Simpan Penugasan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <script>
        document.getElementById('searchPenguji')?.addEventListener('input', function(e) {
            const keyword = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.card-penguji');

            cards.forEach(card => {
                const nama = card.querySelector('.nama-penguji')?.textContent.toLowerCase() || '';
                const dojo = card.querySelector('.dojo-penguji')?.textContent.toLowerCase() || '';

                if (nama.includes(keyword) || dojo.includes(keyword)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        setTimeout(() => {
            const toast = document.getElementById('toast-success');
            if(toast) {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }
        }, 4000);
    </script>
</x-app-layout>
