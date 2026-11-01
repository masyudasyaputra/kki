@php
    $formKey = (string) ($editingTemplate?->id ?? 'new');
    $restore = (string) old('_template_form') === $formKey;
    $templateSettings = array_merge(\App\Services\ExamCertificates::defaults($editingTemplate?->exam_type ?? ($exam?->exam_type ?? 'dan')), $editingTemplate?->settings ?? []);
    $formValue = fn ($key, $default = '') => $restore ? old($key, $default) : $default;
@endphp
<form method="post" enctype="multipart/form-data" action="{{ route('admin.certificate-templates.store') }}" class="cert-template-form" x-data="{ renderMode: @js($formValue('render_mode', $templateSettings['render_mode'] ?? 'builtin')) }">
    @csrf
    <input type="hidden" name="_template_form" value="{{ $formKey }}">
    @if($editingTemplate)<input type="hidden" name="template_id" value="{{ $editingTemplate->id }}">@endif
    <div class="cert-template-form-body">
        @if($restore && $errors->any())<div class="cert-alert cert-alert-error mb-4" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="cert-form-section"><h3>Informasi template</h3><div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2">Nama template <span class="text-red-600">*</span><input name="name" value="{{ $formValue('name', $editingTemplate?->name) }}" required maxlength="255" placeholder="Contoh: Ijazah DAN Pengurus Pusat"></label>
            <label>Jenis ujian<select name="exam_type"><option value="dan" @selected($formValue('exam_type', $editingTemplate?->exam_type ?? ($exam?->exam_type ?? 'dan')) === 'dan')>Ujian DAN</option><option value="kyu" @selected($formValue('exam_type', $editingTemplate?->exam_type ?? ($exam?->exam_type ?? 'dan')) === 'kyu')>Ujian KYU</option></select></label>
            <label>Sumber desain<select name="render_mode" x-model="renderMode"><option value="builtin">Desain bawaan KKI</option><option value="pdf">Unggah PDF + koordinat teks</option></select></label>
        </div></div>
        <div x-show="renderMode === 'pdf'" x-cloak class="cert-upload-box"><label>Desain sertifikat PDF<input type="file" name="template_pdf" accept="application/pdf,.pdf"></label><p>PDF maksimal 20 MB, hingga 10 halaman. Setelah disimpan, tempatkan isian melalui editor koordinat.</p>@if(!empty($templateSettings['pdf_path']))<a target="_blank" rel="noopener" href="{{ asset('storage/'.$templateSettings['pdf_path']) }}" class="cert-back mt-2 inline-block">Lihat PDF tersimpan ↗</a><p class="mt-2">Kosongkan unggahan untuk mempertahankan PDF saat ini. Mengganti PDF akan mereset koordinat.</p>@endif</div>
        <div class="cert-form-section" x-show="renderMode === 'builtin'"><h3>Kop & tampilan sertifikat</h3><div class="grid gap-4 sm:grid-cols-2">
            @foreach(['heading' => 'Kop penerbit', 'organization' => 'Nama organisasi', 'title' => 'Judul sertifikat'] as $key => $label)<label>{{ $label }}<input name="{{ $key }}" value="{{ $formValue($key, $templateSettings[$key] ?? '') }}" :required="renderMode === 'builtin'" maxlength="{{ $key === 'title' ? 100 : 255 }}"></label>@endforeach
            <label>Warna bingkai<input type="color" name="color" value="{{ $formValue('color', $templateSettings['color']) }}"></label>
        </div></div>
        <details class="cert-form-section" :open="renderMode === 'builtin'"><summary class="cursor-pointer text-sm font-semibold">Penandatangan & gambar pendukung</summary><p class="mt-2 text-xs text-slate-400" x-show="renderMode === 'pdf'">Opsional untuk PDF. Isi nama jika ingin menempatkannya sebagai teks melalui editor koordinat.</p><div class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach(['signer_left_title' => 'Jabatan penandatangan kiri', 'signer_left_name' => 'Nama penandatangan kiri', 'signer_left_rank' => 'Tingkat / gelar kiri', 'signer_right_title' => 'Jabatan penandatangan kanan', 'signer_right_name' => 'Nama penandatangan kanan', 'signer_right_rank' => 'Tingkat / gelar kanan'] as $key => $label)<label>{{ $label }}<input name="{{ $key }}" value="{{ $formValue($key, $templateSettings[$key] ?? '') }}" :required="renderMode === 'builtin' && @js(!str_ends_with($key, '_rank'))" maxlength="{{ str_ends_with($key, '_name') ? 255 : 150 }}"></label>@endforeach
            @foreach(['background' => 'Latar depan · maks. 5 MB', 'back_background' => 'Latar lembar nilai · maks. 5 MB', 'signature_left' => 'Tanda tangan kiri · maks. 2 MB', 'signature_right' => 'Tanda tangan kanan · maks. 2 MB'] as $key => $label)<label x-show="renderMode === 'builtin'">{{ $label }}<input type="file" name="{{ $key }}" accept="image/png,image/jpeg">@if(!empty($templateSettings[$key]))<a class="mt-1 inline-block text-xs text-red-600" target="_blank" rel="noopener" href="{{ asset('storage/'.$templateSettings[$key]) }}">Lihat gambar tersimpan</a>@endif</label>@endforeach
        </div></details>
    </div>
    <div class="cert-form-footer"><button type="button" class="btn btn-secondary" onclick="this.closest('dialog').close()">Batal</button><button class="btn btn-primary">{{ $editingTemplate ? 'Simpan perubahan' : 'Buat template' }}</button></div>
</form>
