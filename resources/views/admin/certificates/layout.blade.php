<x-app-layout>
    <x-slot name="header"><div><h1 class="page-title">Editor Koordinat Sertifikat</h1><p class="page-subtitle">{{ $template->name }} · {{ strtoupper($template->exam_type) }}</p></div></x-slot>
    @vite('resources/js/certificate-pdf.js')
    <script type="application/json" id="certificate-editor-config">@json($editorConfig)</script>
    <div id="certificate-editor" class="mx-auto max-w-7xl space-y-4 px-4 py-6">
        <div class="flex flex-wrap items-center gap-2"><a href="{{ route('admin.certificate-templates.index') }}" class="btn btn-secondary">Kembali</a><button type="button" id="save-layout" class="btn btn-primary" disabled>Simpan koordinat</button><button type="button" id="preview-layout" class="btn btn-secondary" disabled>Pratinjau PDF</button><span id="editor-status" role="status" class="text-sm text-slate-500">Memuat desain PDF…</span></div>
        <p class="text-xs text-slate-500">X dan Y dalam milimeter dari sudut kiri atas halaman. Tambahkan isian, lalu seret teks atau ubah koordinatnya. Lebar kotak mengatur perataan dan penyesuaian ukuran teks panjang. Pratinjau menggunakan data contoh dan berlabel DRAF.</p>
        <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
            <aside class="order-2 space-y-4 rounded-xl border border-slate-200 bg-white p-4 lg:sticky lg:top-4 lg:max-h-[calc(100vh-110px)] lg:overflow-y-auto">
                <label class="block text-xs font-semibold">Halaman PDF<select id="pdf-page" class="mt-1 w-full rounded-lg border-slate-200"></select></label>
                <label class="block text-xs font-semibold">Isian yang ditambahkan<select id="field-key" class="mt-1 w-full rounded-lg border-slate-200">@foreach(\App\Services\CertificatePdfLayout::fields() as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                <button type="button" id="add-field" class="btn btn-secondary w-full" disabled>Tambah isian ke halaman</button>
                <label class="block text-xs font-semibold">Isian pada halaman ini<select id="selected-field" class="mt-1 w-full rounded-lg border-slate-200" size="5"></select></label>
                <fieldset id="field-properties" disabled class="space-y-3 border-t pt-3"><legend class="text-sm font-semibold">Posisi dan tampilan teks</legend>
                    <div class="grid grid-cols-2 gap-3">@foreach(['x' => 'X (mm)', 'y' => 'Y (mm)', 'width' => 'Lebar (mm)', 'font_size' => 'Ukuran (pt)'] as $key => $label)<label class="text-xs">{{ $label }}<input id="prop-{{ $key }}" type="number" step="0.1" min="{{ $key === 'font_size' ? 4 : ($key === 'width' ? 1 : 0) }}" max="{{ $key === 'font_size' ? 72 : 1200 }}" class="mt-1 w-full rounded-lg border-slate-200"></label>@endforeach</div>
                    <label class="block text-xs">Tinggi baris (pt)<input id="prop-line_height" type="number" min="4" max="144" step="0.1" class="mt-1 w-full rounded-lg border-slate-200"></label>
                    <label class="block text-xs"><input id="prop-wrap" type="checkbox" class="rounded border-slate-300"> Teks beberapa baris sesuai lebar kotak</label>
                    <label class="block text-xs">Perataan<select id="prop-align" class="mt-1 w-full rounded-lg border-slate-200"><option value="left">Kiri</option><option value="center">Tengah</option><option value="right">Kanan</option></select></label>
                    <label class="block text-xs">Huruf<select id="prop-font" class="mt-1 w-full rounded-lg border-slate-200"><option value="serif">Times</option><option value="sans">Helvetica</option><option value="mono">Courier</option></select></label>
                    <div class="flex items-center gap-3"><label class="text-xs"><input type="checkbox" id="prop-bold" class="rounded border-slate-300"> Tebal</label><label class="flex items-center gap-2 text-xs">Warna<input type="color" id="prop-color" value="#000000"></label></div>
                    <label class="block text-xs" id="custom-text-label" hidden>Teks bebas<textarea id="prop-text" maxlength="1000" rows="3" class="mt-1 w-full rounded-lg border-slate-200"></textarea></label>
                    <button type="button" id="remove-field" class="btn btn-secondary w-full">Hapus isian terpilih</button>
                </fieldset>
            </aside>
            <div class="order-1 min-w-0 overflow-auto rounded-xl border border-slate-200 bg-slate-200 p-3" id="pdf-canvas-container"><div id="pdf-stage" style="position:relative; background:white; margin:auto; touch-action:none"><canvas id="pdf-canvas" style="display:block"></canvas><div id="pdf-overlays" style="position:absolute; inset:0"></div></div></div>
        </div>
    </div>
</x-app-layout>
