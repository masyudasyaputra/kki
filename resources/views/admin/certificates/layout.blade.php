<x-app-layout>
    <x-slot name="header">
        <div><h1 class="page-title">Atur Isi Template PDF</h1><p class="page-subtitle">{{ $template->name }} · {{ strtoupper($template->exam_type) }} · atur teks dan QR dengan koordinat.</p></div>
    </x-slot>
    @vite('resources/js/certificate-pdf.js')
    <script type="application/json" id="certificate-editor-config">@json($editorConfig)</script>

    <div id="certificate-editor" class="mx-auto max-w-[1600px] space-y-4 px-4 py-5 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2"><a href="{{ route('admin.certificate-templates.index') }}" class="btn btn-secondary">Kembali ke template</a><span id="editor-status" role="status" class="text-sm text-slate-500">Membuka file PDF...</span></div>
            <div class="flex flex-wrap gap-2"><button type="button" id="preview-layout" class="btn btn-secondary" disabled>Pratinjau</button><button type="button" id="save-layout" class="btn btn-primary" disabled>Simpan pengaturan</button></div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section class="order-2 min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm lg:order-1" aria-label="Pratinjau dan penempatan isian PDF">
                <div id="pdf-canvas-container" class="max-h-[calc(100vh-150px)] min-h-[420px] overflow-auto p-4 sm:p-6"><div id="pdf-stage" style="position:relative;background:white;margin:auto;touch-action:none;box-shadow:0 2px 18px #0f172a22"><canvas id="pdf-canvas" style="display:block"></canvas><div id="pdf-overlays" style="position:absolute;inset:0"></div></div></div>
            </section>

            <aside class="order-1 space-y-4 lg:order-2 lg:sticky lg:top-4 lg:max-h-[calc(100vh-110px)] lg:overflow-y-auto">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div><h2 class="text-sm font-semibold text-slate-900">Tempatkan data sertifikat</h2><p class="mt-1 text-xs leading-5 text-slate-500">Pilih halaman dan jenis data. Isian dapat diseret pada PDF; posisi diukur dari sudut kiri atas dalam milimeter.</p></div>
                    <div class="mt-4 space-y-3">
                        <label class="block text-xs font-medium text-slate-700">Halaman PDF<select id="pdf-page" class="mt-1.5 w-full rounded-lg border-slate-300 text-sm"></select></label>
                        <label class="block text-xs font-medium text-slate-700">Data yang ditambahkan<select id="field-key" class="mt-1.5 w-full rounded-lg border-slate-300 text-sm">
                            <optgroup label="Peserta">@foreach(['name', 'number', 'member_number', 'no_id_msh', 'birth_details', 'birth_place', 'birth_date', 'province', 'city', 'dojo', 'photo'] as $key)@if(isset($editorConfig['fields'][$key]))<option value="{{ $key }}">{{ $editorConfig['fields'][$key] }}</option>@endif @endforeach</optgroup>
                            <optgroup label="Ujian & hasil">@foreach(['rank', 'belt', 'exam_name', 'exam_date', 'location', 'issue_place', 'issue_date', 'decision_number', 'decision_date', 'examiner', 'result'] as $key)<option value="{{ $key }}">{{ $editorConfig['fields'][$key] }}</option>@endforeach</optgroup>
                            <optgroup label="Tanda tangan digital">@foreach(['signer_left_title', 'signer_left_name', 'signer_left_rank', 'signer_right_title', 'signer_right_name', 'signer_right_rank', 'verification_qr'] as $key)<option value="{{ $key }}">{{ $editorConfig['fields'][$key] }}</option>@endforeach</optgroup>
                            <optgroup label="Nilai ujian">@foreach($editorConfig['fields'] as $key => $label)@if(str_starts_with($key, 'score_') || str_starts_with($key, 'words_'))<option value="{{ $key }}">{{ $label }}</option>@endif @endforeach</optgroup>
                            <option value="custom">Teks bebas</option>
                        </select></label>
                        <button type="button" id="add-field" class="btn btn-secondary w-full justify-center">+ Tambah isian ke PDF</button>
                    </div>
                    <div class="mt-4 border-t border-slate-100 pt-4"><label class="block text-xs font-medium text-slate-700">Isian pada halaman ini<select id="selected-field" class="mt-1.5 w-full rounded-lg border-slate-300 text-sm" size="5"></select></label></div>
                </section>

                <fieldset id="field-properties" disabled class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <legend class="px-1 text-sm font-semibold text-slate-900">Posisi dan tampilan</legend>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['x' => 'X · mm', 'y' => 'Y · mm', 'width' => 'Lebar · mm', 'height' => 'Tinggi · mm'] as $key => $label)<label @if($key === 'height') id="qr-height-control" hidden @endif class="text-xs font-medium text-slate-600">{{ $label }}<input id="prop-{{ $key }}" type="number" step="0.1" min="0" max="1200" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>@endforeach
                    </div>
                    <div id="text-field-properties" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">@foreach(['font_size' => 'Ukuran · pt', 'line_height' => 'Tinggi baris · pt'] as $key => $label)<label class="text-xs font-medium text-slate-600">{{ $label }}<input id="prop-{{ $key }}" type="number" step="0.1" min="4" max="144" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>@endforeach</div>
                        <label class="flex items-center gap-2 text-xs text-slate-600"><input id="prop-wrap" type="checkbox" class="rounded border-slate-300"> Bungkus teks panjang</label>
                        <div class="grid grid-cols-2 gap-3"><label class="text-xs font-medium text-slate-600">Perataan<select id="prop-align" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="left">Kiri</option><option value="center">Tengah</option><option value="right">Kanan</option></select></label><label class="text-xs font-medium text-slate-600">Jenis huruf<select id="prop-font" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="serif">Serif</option><option value="sans">Sans serif</option><option value="mono">Monospace</option></select></label></div>
                        <div class="flex items-center gap-4"><label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" id="prop-bold" class="rounded border-slate-300"> Tebal</label><label class="flex items-center gap-2 text-xs text-slate-600">Warna<input type="color" id="prop-color" value="#000000" class="h-8 w-10 rounded border-slate-200 p-0.5"></label></div>
                        <label id="custom-text-label" hidden class="block text-xs font-medium text-slate-600">Teks bebas<textarea id="prop-text" maxlength="1000" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea></label>
                    </div>
                    <div id="qr-field-help" hidden class="rounded-lg bg-rose-50 p-3 text-xs leading-5 text-[#641b2d]">QR TTE/verifikasi dibuat otomatis saat sertifikat diterbitkan. Atur ukuran dan posisinya di sini.</div>
                    <button type="button" id="remove-field" class="btn btn-secondary w-full justify-center">Hapus isian terpilih</button>
                </fieldset>
            </aside>
        </div>
    </div>
</x-app-layout>
