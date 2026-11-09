<x-app-layout>
    <x-slot name="header">
        <div><h1 class="page-title">Template Sertifikat</h1><p class="page-subtitle">Unggah PDF, atur data dengan koordinat, dan kelola QR verifikasi untuk sertifikat Kyu maupun DAN.</p></div>
    </x-slot>

    @php
        $exam = null;
        $selectedTemplate = null;
        $canEditTemplate = fn ($template) => \App\Services\RoleAccess::certificates(auth()->user());
    @endphp
    <div class="certificate-management mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8" x-data="{ search: '', type: 'all' }">
        @if(session('success'))<div class="cert-alert cert-alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="cert-alert cert-alert-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

        <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#4a1421] via-[#6e1d31] to-[#87283c] text-white shadow-sm">
            <div class="p-6 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-100/80">Pengaturan sertifikat</p>
                <h2 class="mt-2 text-xl font-bold sm:text-2xl">Gunakan desain PDF KKI Anda</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-rose-50/85">Unggah file PDF sebagai latar sertifikat, lalu tempatkan nama, nomor anggota, No. ID MSH KKI, tingkat sabuk, tanda tangan, dan QR verifikasi sesuai koordinat pada desain.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    @foreach([['01', 'Unggah PDF', 'Pilih desain sertifikat untuk DAN atau Kyu.'], ['02', 'Atur koordinat', 'Seret data ke posisi yang tepat pada tiap halaman.'], ['03', 'Pratinjau & simpan', 'Cek hasil sebelum dipakai pada sesi ujian.']] as [$number, $title, $description])
                        <div class="flex gap-3 rounded-xl border border-white/15 bg-white/10 p-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15 text-xs font-bold">{{ $number }}</span><div><p class="text-sm font-semibold">{{ $title }}</p><p class="mt-1 text-xs leading-5 text-rose-50/75">{{ $description }}</p></div></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2" aria-label="Jenis template sertifikat">
            @foreach(['dan' => ['DAN', 'Sertifikat DAN', 'Template untuk ujian tingkat DAN'], 'kyu' => ['KYU', 'Sertifikat Kyu', 'Template untuk ujian kenaikan tingkat Kyu']] as $kind => [$badge, $title, $description])
                @php $count = $templates->where('exam_type', $kind)->count(); @endphp
                <button type="button" @click="type = type === @js($kind) ? 'all' : @js($kind)" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-[#a85863]" :class="type === @js($kind) ? 'ring-2 ring-[#762137]/20 border-[#762137]' : ''">
                    <span><span class="cert-badge {{ $kind === 'dan' ? 'cert-badge-dark' : 'cert-badge-amber' }}">{{ $badge }}</span><strong class="mt-3 block text-sm text-slate-900">{{ $title }}</strong><span class="mt-1 block text-xs text-slate-500">{{ $description }}</span></span>
                    <span class="text-right"><strong class="block text-2xl text-slate-900">{{ $count }}</strong><span class="text-xs text-slate-500">template</span></span>
                </button>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div><h2 class="text-base font-semibold text-slate-900">Daftar template sertifikat</h2><p class="mt-1 text-sm text-slate-500">Setiap jenis ujian menggunakan konfigurasi template tersendiri.</p></div>
                <button type="button" class="btn btn-primary shrink-0" onclick="document.getElementById('template-new').showModal()">+ Tambah template PDF</button>
            </div>
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:px-6">
                <label for="template-search" class="sr-only">Cari template sertifikat</label><input id="template-search" type="search" x-model="search" placeholder="Cari nama template..." class="w-full rounded-lg border-slate-300 text-sm shadow-sm sm:max-w-sm">
                <label for="template-type" class="sr-only">Filter jenis sertifikat</label><select id="template-type" x-model="type" class="w-full rounded-lg border-slate-300 text-sm shadow-sm sm:w-52"><option value="all">Semua jenis</option><option value="dan">Sertifikat DAN</option><option value="kyu">Sertifikat Kyu</option></select>
                <span class="text-xs text-slate-500 sm:ml-auto">{{ $templates->count() }} template terdaftar</span>
            </div>

            @if($templates->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 sm:px-6">Template</th><th class="px-5 py-3">Jenis</th><th class="px-5 py-3">Desain & koordinat</th><th class="px-5 py-3">Penggunaan</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($templates as $template)
                                @php $pdf = ($template->settings['render_mode'] ?? 'builtin') === 'pdf'; $usage = $templateUsage[(string) $template->id] ?? 0; $editable = $canEditTemplate($template); $fieldCount = count($template->settings['pdf_layout'] ?? []); @endphp
                                <tr x-show="(type === 'all' || type === @js($template->exam_type)) && @js(mb_strtolower($template->name)).includes(search.toLowerCase())" class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4 sm:px-6"><div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $pdf ? 'bg-rose-50 text-[#762137]' : 'bg-slate-100 text-slate-600' }} text-xs font-bold">{{ $pdf ? 'PDF' : 'KKI' }}</span><div><p class="font-semibold text-slate-900">{{ $template->name }}</p><p class="mt-1 text-xs text-slate-500">Diubah {{ $template->updated_at?->format('d M Y') ?? '—' }}</p></div></div></td>
                                    <td class="whitespace-nowrap px-5 py-4"><span class="cert-badge {{ $template->exam_type === 'dan' ? 'cert-badge-dark' : 'cert-badge-amber' }}">{{ strtoupper($template->exam_type) }}</span></td>
                                    <td class="px-5 py-4"><p class="font-medium text-slate-700">{{ $pdf ? 'PDF unggahan' : 'Desain bawaan' }}</p><p class="mt-1 text-xs {{ $pdf && $fieldCount === 0 ? 'font-medium text-amber-700' : 'text-slate-500' }}">{{ $pdf ? $fieldCount.' isian koordinat' : 'Sertifikat & lembar nilai' }}{{ $pdf && $fieldCount === 0 ? ' · perlu diatur' : '' }}</p></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $usage ? $usage.' sesi ujian' : 'Belum digunakan' }}</td>
                                    <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2">@if($editable)<button type="button" class="cert-action" onclick="document.getElementById('template-edit-{{ $template->id }}').showModal()">Edit</button>@if($pdf && !empty($template->settings['pdf_path']))<a class="cert-action" href="{{ route('admin.certificate-templates.layout', $template) }}">Atur PDF</a>@endif<button type="button" class="cert-action cert-action-danger" onclick="document.getElementById('template-delete-{{ $template->id }}').showModal()">Hapus</button>@else<span class="cert-badge cert-badge-muted">Template bersama</span>@endif</div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="px-5 py-3 text-xs text-slate-500" x-show="!@js($templates->map(fn($item) => ['name' => mb_strtolower($item->name), 'type' => $item->exam_type])->values()).some(item => (type === 'all' || item.type === type) && item.name.includes(search.toLowerCase()))" x-cloak>Tidak ada template yang cocok dengan pencarian.</p>
            @else
                <div class="px-5 py-12 text-center sm:px-6">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-[#762137]" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 3h8l4 4v14H6zM14 3v5h5M9 13h6M9 17h6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum ada template sertifikat</h3>
                    <p class="mx-auto mt-1 max-w-lg text-sm leading-6 text-slate-500">Mulai dengan PDF sertifikat KKI yang diunggah. Sesudah itu, atur posisi nama, No. ID MSH KKI, tingkat DAN/Kyu, informasi penandatangan, dan QR verifikasi.</p>
                    <button type="button" class="btn btn-primary mt-5" onclick="document.getElementById('template-new').showModal()">Unggah template PDF</button>
                </div>
            @endif
        </section>

        <dialog id="template-new" class="cert-dialog" aria-labelledby="template-new-title" @if($errors->any() && old('_template_form') === 'new') x-init="$nextTick(() => $el.showModal())" @endif>
            <div class="cert-dialog-heading"><div><h2 id="template-new-title">Tambah template sertifikat</h2><p>Unggah PDF lalu atur koordinat data dan QR verifikasi.</p></div><button type="button" class="cert-dialog-close" onclick="this.closest('dialog').close()" aria-label="Tutup">×</button></div>
            @include('admin.certificates.template-form', ['editingTemplate' => null])
        </dialog>

        @foreach($templates as $template)
            @if($canEditTemplate($template))
                @php $dialogUsage = $templateUsage[(string) $template->id] ?? 0; @endphp
                <dialog id="template-edit-{{ $template->id }}" class="cert-dialog" aria-labelledby="template-edit-title-{{ $template->id }}" @if($errors->any() && (string) old('_template_form') === (string) $template->id) x-init="$nextTick(() => $el.showModal())" @endif>
                    <div class="cert-dialog-heading"><div><h2 id="template-edit-title-{{ $template->id }}">Edit template sertifikat</h2><p>{{ $template->name }}</p></div><button type="button" class="cert-dialog-close" onclick="this.closest('dialog').close()" aria-label="Tutup">×</button></div>
                    @include('admin.certificates.template-form', ['editingTemplate' => $template])
                </dialog>
                <dialog id="template-delete-{{ $template->id }}" class="cert-dialog cert-dialog-small" aria-labelledby="template-delete-title-{{ $template->id }}">
                    <div class="cert-dialog-heading"><h2 id="template-delete-title-{{ $template->id }}">Hapus template?</h2><button type="button" class="cert-dialog-close" onclick="this.closest('dialog').close()" aria-label="Tutup">×</button></div>
                    <div class="p-5"><p class="text-sm text-slate-600">Template <strong class="text-slate-900">{{ $template->name }}</strong> akan dihapus dari daftar.</p>@if($dialogUsage)<p class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Masih digunakan oleh {{ $dialogUsage }} sesi ujian. Ganti template di pengaturan sesi sebelum menghapus.</p>@else<p class="mt-2 text-xs text-slate-400">Sertifikat yang sudah diterbitkan tetap tersimpan.</p>@endif</div>
                    <form method="post" action="{{ route('admin.certificate-templates.destroy', $template) }}" class="cert-form-footer">@csrf @method('DELETE')<button type="button" class="btn btn-secondary" onclick="this.closest('dialog').close()">Batal</button><button class="btn btn-danger" @disabled($dialogUsage > 0)>Hapus template</button></form>
                </dialog>
            @endif
        @endforeach
    </div>
</x-app-layout>
