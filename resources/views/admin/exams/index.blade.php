<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="page-title">
                    Jadwal <span class="text-red-600">Ujian Sabuk</span>
                </h1>
                <p class="page-subtitle">
                    Manajemen sesi pelaksanaan ujian, pendaftaran peserta, dan penilaian kenaikan tingkat.
                </p>
            </div>
        </div>
    </x-slot>

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

            <x-table-toolbar title="Daftar Jadwal Ujian">
                @if (auth()->user()->hasRole(['pb', 'pengprov']))
                    <button type="button" onclick="toggleModal('modalAddExam')" class="btn btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14" /></svg>
                        Tambah Ujian
                    </button>
                @endif
            </x-table-toolbar>

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

                <table class="data-table min-w-full text-xs">
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
                            <tr data-record-key="{{ $exam->id }}" class="hover:bg-slate-50/60 transition-colors">
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
                                        <x-action-button href="{{ route('admin.exams.show', $exam->id) }}" icon="view" label="Kelola" />

                                        {{-- Input Nilai --}}
                                        @if (auth()->user()->hasRole(['pb', 'pengprov', 'penguji']))
                                            <x-action-button href="{{ route('admin.exams.scoring', $exam->id) }}" icon="score" label="Input nilai" />
                                        @endif

                                        {{-- PB / Pengprov specific actions --}}
                                        @if (auth()->user()->hasRole(['pb', 'pengprov']))
                                            {{-- Atur Penguji --}}
                                            <x-action-button href="{{ route('admin.exams.examiners.edit', $exam->id) }}" icon="people" label="Atur penguji" />

                                            {{-- Edit --}}
                                            <x-action-button type="button" onclick="openEditModal({{ json_encode($exam) }})" icon="edit" label="Edit" />

                                            {{-- Delete --}}
                                            <form action="{{ route('admin.exams.destroy', $exam->id) }}"
                                                method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ujian ini?')">
                                                @csrf @method('DELETE')
                                                <x-action-button type="submit" icon="delete" label="Hapus" />
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
                    <div data-card-key="{{ $exam->id }}" class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
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
                                <x-action-button href="{{ route('admin.exams.show', $exam->id) }}" icon="view" label="Kelola" />

                                @if (auth()->user()->hasRole(['pb', 'pengprov', 'penguji']))
                                    <x-action-button href="{{ route('admin.exams.scoring', $exam->id) }}" icon="score" label="Input nilai" />
                                @endif
                            </div>

                            @if (auth()->user()->hasRole(['pb', 'pengprov']))
                                <div class="flex items-center gap-1">
                                    <x-action-button href="{{ route('admin.exams.examiners.edit', $exam->id) }}" icon="people" label="Atur penguji" />

                                    <x-action-button type="button" onclick="openEditModal({{ json_encode($exam) }})" icon="edit" label="Edit" />
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
                        class="btn btn-secondary">
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
                        class="btn btn-secondary">
                        Batal
                    </button>
                    <button type="submit"
                        class="btn btn-primary">
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
                        class="btn btn-secondary">
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
                        class="btn btn-secondary">
                        Batal
                    </button>
                    <button type="submit"
                        class="btn btn-primary">
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
