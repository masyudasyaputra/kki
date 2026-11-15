@php
    $formKey = (string) ($editingTemplate?->id ?? 'new');
    $restore = (string) old('_template_form') === $formKey;
    $templateSettings = array_merge(\App\Services\ExamCertificates::defaults($editingTemplate?->exam_type ?? ($exam?->exam_type ?? 'dan')), $editingTemplate?->settings ?? []);
    $formValue = fn ($key, $default = '') => $restore ? old($key, $default) : $default;
@endphp
<form method="post" enctype="multipart/form-data" action="{{ route('admin.certificate-templates.store') }}" class="cert-template-form" x-data="{ renderMode: @js($formValue('render_mode', $templateSettings['render_mode'] ?? 'pdf')) }">
    @csrf
    @if(!$editingTemplate && \App\Services\RoleAccess::certificates(auth()->user()))<div class="p-5"><label>Provinsi penerbit (wajib untuk template KYU)<select name="province_id"><option value="">Nasional / pilih provinsi</option>@foreach(\App\Models\Province::orderBy('name')->get() as $province)<option value="{{ $province->id }}" @selected(old('province_id') == $province->id)>{{ $province->name }}</option>@endforeach</select></label></div>@endif
    <input type="hidden" name="_template_form" value="{{ $formKey }}">
    @if($editingTemplate)<input type="hidden" name="template_id" value="{{ $editingTemplate->id }}">@endif
    <div class="cert-template-form-body">
        @if($restore && $errors->any())<div class="cert-alert cert-alert-error mb-4" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

        <section class="cert-form-section">
            <h3>1. Identitas template</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="sm:col-span-2">Nama template <span class="text-rose-700">*</span><input name="name" value="{{ $formValue('name', $editingTemplate?->name) }}" required maxlength="255" placeholder="Contoh: Sertifikat KKI 343 × 223 mm"></label>
                <label>Jenis sertifikat<select name="exam_type">@if(\App\Services\RoleAccess::certificates(auth()->user()))<option value="dan" @selected($formValue('exam_type', $editingTemplate?->exam_type ?? ($exam?->exam_type ?? 'dan')) === 'dan')>Sertifikat DAN</option>@endif<option value="kyu" @selected($formValue('exam_type', $editingTemplate?->exam_type ?? ($exam?->exam_type ?? 'dan')) === 'kyu')>Sertifikat Kyu</option></select></label>
                <label>Sumber desain<select name="render_mode" x-model="renderMode"><option value="pdf">PDF template unggahan</option><option value="builtin">Desain bawaan KKI</option></select></label>
            </div>
        </section>

        <section x-show="renderMode === 'pdf'" x-cloak class="cert-upload-box">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">2. Unggah file desain sertifikat</h3>
            <label>File template PDF <span class="text-rose-700">*</span><input type="file" name="template_pdf" accept="application/pdf,.pdf" :required="renderMode === 'pdf' && @js(empty($templateSettings['pdf_path']))"></label>
            <p class="mt-2">PDF maksimal 20 MB dan 10 halaman. Template KKI 343 × 223 mm didukung; halaman pertama dan kedua dapat dipakai untuk sisi depan dan belakang. Sesudah disimpan, Anda mengatur posisi data pada PDF.</p>
            @if(!empty($templateSettings['pdf_path']))
                <a target="_blank" rel="noopener" href="{{ url('storage/'.$templateSettings['pdf_path']) }}" class="cert-back mt-2 inline-block">Lihat PDF tersimpan ↗</a>
                <p class="mt-2">Kosongkan unggahan untuk mempertahankan PDF saat ini. Mengganti file akan menghapus koordinat lama agar dapat diatur ulang.</p>
            @endif
        </section>

        <section class="cert-form-section" x-show="renderMode === 'builtin'" x-cloak>
            <h3>Kop & tampilan sertifikat bawaan</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach(['heading' => 'Kop penerbit', 'organization' => 'Nama organisasi', 'title' => 'Judul sertifikat'] as $key => $label)<label>{{ $label }}<input name="{{ $key }}" value="{{ $formValue($key, $templateSettings[$key] ?? '') }}" :required="renderMode === 'builtin'" maxlength="{{ $key === 'title' ? 100 : 255 }}"></label>@endforeach
                <label>Warna bingkai<input type="color" name="color" value="{{ $formValue('color', $templateSettings['color']) }}"></label>
            </div>
        </section>

        <details class="cert-form-section" open>
            <summary class="cursor-pointer text-sm font-semibold">3. Penandatangan & QR verifikasi</summary>
            <p class="mt-2 text-xs leading-5 text-slate-500">Penandatangan dipilih dari akun user aktif dengan sabuk DAN. Atur nama, tingkat DAN, dan jabatan melalui <a class="font-semibold text-[#762137] underline" href="{{ route('admin.certificate-signers.index') }}">Pengaturan Penandatangan Sertifikat</a>. QR unik untuk setiap sertifikat membuka halaman verifikasi dan menampilkan kedua penandatangan.</p>
            <p class="mt-4 text-xs text-slate-500" x-show="renderMode === 'pdf'">Nomor ID MSH KKI peserta dimasukkan pada data sertifikat peserta. Tambahkan isian “No. ID MSH KKI” di editor jika desain PDF memuat kolom tersebut.</p>
            <div x-show="renderMode === 'builtin'" class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach(['background' => 'Latar depan · maks. 5 MB', 'back_background' => 'Latar lembar nilai · maks. 5 MB', 'signature_left' => 'Tanda tangan kiri · maks. 2 MB', 'signature_center' => 'Tanda tangan tengah · maks. 2 MB', 'signature_right' => 'Tanda tangan kanan · maks. 2 MB'] as $key => $label)
                    <label>{{ $label }}<input type="file" name="{{ $key }}" accept="image/png,image/jpeg">@if(!empty($templateSettings[$key]))<a class="mt-1 inline-block text-xs text-[#762137]" target="_blank" rel="noopener" href="{{ url('storage/'.$templateSettings[$key]) }}">Lihat gambar tersimpan</a>@endif</label>
                @endforeach
            </div>
        </details>
    </div>
    <div class="cert-form-footer"><button type="button" class="btn btn-secondary" onclick="this.closest('dialog').close()">Batal</button><button class="btn btn-primary">{{ $editingTemplate ? 'Simpan perubahan' : 'Buat template' }}</button></div>
</form>
