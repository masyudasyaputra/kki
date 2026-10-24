<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">
                    Jadwal <span class="text-red-600">Ujian Sabuk</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">
                    Manajemen sesi pelaksanaan ujian, pendaftaran peserta, dan penilaian kenaikan tingkat.
                </p>
            </div>
            @if (auth()->user()->hasRole(['pb', 'pengprov']))
                <div class="hidden sm:block">
                    <button onclick="toggleModal('modalAddExam')"
                        class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Tambah Sesi Ujian</span>
                    </button>
                </div>
            @endif
        </div>
    </x-slot>

    {{-- Floating Action Button for Mobile (PB/PENGPROV) --}}
    @if (auth()->user()->hasRole(['pb', 'pengprov']))
        <div class="fixed bottom-6 right-6 z-50 sm:hidden">
            <button onclick="toggleModal('modalAddExam')"
                class="flex items-center justify-center w-12 h-12 bg-red-600 text-white rounded-full shadow-lg shadow-red-600/30 hover:bg-red-700 active:scale-95 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
        </div>
    @endif

    <div class="py-6 sm:py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Alert sukses --}}
            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 alert-notif">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-emerald-900 uppercase tracking-wide">Berhasil</h4>
                        <p class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            {{-- Desktop Table --}}
            <div class="hidden md:block bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                            Daftar Jadwal Ujian
                        </h3>
                        <p class="text-[11px] text-slate-500 font-medium">Seluruh sesi ujian kenaikan sabuk yang telah dijadwalkan</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        Total: {{ $exams->count() }} Sesi
                    </span>
                </div>

                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50/90 border-b border-slate-200/80">
                        <tr class="text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                            <th class="px-6 py-3.5 text-left">Nama Sesi</th>
                            <th class="px-6 py-3.5 text-left">Tanggal</th>
                            <th class="px-6 py-3.5 text-left">Lokasi</th>
                            <th class="px-6 py-3.5 text-center">Peserta</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($exams as $exam)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-900 text-xs uppercase hover:text-red-600 transition-colors">
                                            {{ $exam->name }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 mt-0.5">
                                            ID Sesi: #{{ $exam->id }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-700">
                                        {{ is_string($exam->execution_date) ? \Carbon\Carbon::parse($exam->execution_date)->format('d M Y') : $exam->execution_date->format('d M Y') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-slate-600 font-medium">
                                        {{ $exam->location }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-800">
                                        {{ $exam->participants->count() }} Orang
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold border
                                        {{ $exam->status === 'open'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                        {{ ucfirst($exam->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-1.5">
                                        {{-- Kelola --}}
                                        <a href="{{ route('admin.exams.show', $exam->id) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition-all shadow-xs">
                                            <span>Kelola</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>

                                        {{-- Input Nilai --}}
                                        @if (auth()->user()->hasRole(['pb', 'pengprov', 'penguji']))
                                            <a href="{{ route('admin.exams.scoring', $exam->id) }}"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-all shadow-xs">
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6M9 8h6m4 14H5a2 2 0 01-2-2V4a2 2 0 012-2h10l6 6v14a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>Nilai</span>
                                            </a>
                                        @endif

                                        {{-- PB / Pengprov specific actions --}}
                                        @if (auth()->user()->hasRole(['pb', 'pengprov']))
                                            {{-- Atur Penguji --}}
                                            <a href="{{ route('admin.exams.examiners.edit', $exam->id) }}"
                                                title="Tugaskan Penguji"
                                                class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all shadow-xs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </a>

                                            {{-- Edit --}}
                                            <button type="button" onclick="openEditModal({{ json_encode($exam) }})"
                                                title="Edit Jadwal"
                                                class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all shadow-xs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            {{-- Delete --}}
                                            <form action="{{ route('admin.exams.destroy', $exam->id) }}"
                                                method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ujian ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Hapus Jadwal"
                                                    class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 transition-all shadow-xs">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-medium">
                                    Belum ada data jadwal ujian yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden space-y-4">
                @forelse ($exams as $exam)
                    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-slate-100">
                            <div class="flex justify-between items-start gap-3">
                                <div class="min-w-0">
                                    <h4 class="font-bold text-slate-900 text-sm uppercase">
                                        {{ $exam->name }}
                                    </h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $exam->location }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border shrink-0
                                    {{ $exam->status === 'open'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ ucfirst($exam->status) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-slate-100 text-xs">
                                <div>
                                    <span class="text-[11px] text-slate-400 block font-medium">Tanggal Sesi</span>
                                    <span class="font-semibold text-slate-700 mt-0.5 block">
                                        {{ is_string($exam->execution_date) ? \Carbon\Carbon::parse($exam->execution_date)->format('d M Y') : $exam->execution_date->format('d M Y') }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-400 block font-medium">Total Peserta</span>
                                    <span class="font-semibold text-slate-900 mt-0.5 block">
                                        {{ $exam->participants->count() }} Orang
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Mobile Footer Actions --}}
                        <div class="p-3 bg-slate-50 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-1">
                                <a href="{{ route('admin.exams.show', $exam->id) }}"
                                    class="flex-1 inline-flex items-center justify-center py-2 px-3 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition-all">
                                    Kelola
                                </a>

                                @if (auth()->user()->hasRole(['pb', 'pengprov', 'penguji']))
                                    <a href="{{ route('admin.exams.scoring', $exam->id) }}"
                                        class="flex-1 inline-flex items-center justify-center py-2 px-3 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-100 transition-all">
                                        Nilai
                                    </a>
                                @endif
                            </div>

                            @if (auth()->user()->hasRole(['pb', 'pengprov']))
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('admin.exams.examiners.edit', $exam->id) }}"
                                        class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </a>

                                    <button type="button" onclick="openEditModal({{ json_encode($exam) }})"
                                        class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-6 rounded-xl border border-slate-200 text-center text-slate-400 text-xs font-medium">
                        Belum ada data jadwal ujian yang terdaftar.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH JADWAL --}}
    <div id="modalAddExam"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div class="bg-white rounded-xl w-full max-w-md shadow-xl border border-slate-200/80 overflow-hidden">
            <form action="{{ route('admin.exams.store') }}" method="POST">
                @csrf

                <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Tambah Jadwal Ujian</h3>
                        <p class="text-[11px] text-slate-500 font-medium">Buat sesi ujian kenaikan sabuk baru</p>
                    </div>
                    <button type="button" onclick="toggleModal('modalAddExam')"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Nama Sesi Ujian <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" placeholder="Contoh: Ujian Sabuk Semester Ganjil 2026" required
                            class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Tanggal Pelaksanaan <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="execution_date" required
                                class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Status Sesi <span class="text-red-500">*</span>
                            </label>
                            <select name="status"
                                class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                <option value="open">Open (Terbuka)</option>
                                <option value="draft">Draft (Tertutup)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Lokasi Pelaksanaan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="location" placeholder="Contoh: GOR Dojo Pusat" required
                            class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-200/80 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="toggleModal('modalAddExam')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-50 transition-all">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition-all">
                        Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT JADWAL --}}
    <div id="modalEditExam"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div class="bg-white rounded-xl w-full max-w-md shadow-xl border border-slate-200/80 overflow-hidden">
            <form id="editForm" method="POST">
                @csrf @method('PUT')

                <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Edit Jadwal Ujian</h3>
                        <p class="text-[11px] text-slate-500 font-medium">Perbarui rincian sesi ujian</p>
                    </div>
                    <button type="button" onclick="toggleModal('modalEditExam')"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Nama Sesi Ujian <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="edit_name" required
                            class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Tanggal Pelaksanaan <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="execution_date" id="edit_date" required
                                class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Status Sesi <span class="text-red-500">*</span>
                            </label>
                            <select name="status" id="edit_status"
                                class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                                <option value="open">Open (Terbuka)</option>
                                <option value="draft">Draft (Tertutup)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Lokasi Pelaksanaan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="location" id="edit_location" required
                            class="w-full h-10 px-3.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 shadow-sm transition-all">
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-200/80 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="toggleModal('modalEditExam')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-50 transition-all">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition-all">
                        Update Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.toggle('hidden');
        }

        function openEditModal(exam) {
            document.getElementById('editForm').action = `/admin/exams/${exam.id}`;
            document.getElementById('edit_name').value = exam.name;
            document.getElementById('edit_location').value = exam.location;
            document.getElementById('edit_status').value = exam.status;

            const date = new Date(exam.execution_date);
            document.getElementById('edit_date').value = date.toISOString().split('T')[0];

            toggleModal('modalEditExam');
        }

        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-notif');
            alerts.forEach(a => a.remove());
        }, 4000);
    </script>
</x-app-layout>
