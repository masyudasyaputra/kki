<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Penandatangan Sertifikat</h1><p class="page-subtitle">Tiga posisi penandatangan: kiri, tengah, dan kanan.</p></x-slot>
    <div class="certificate-management mx-auto max-w-7xl space-y-4 px-4 py-5 sm:px-6">
        <p class="text-xs leading-6 text-slate-500">Pilih tiga user berbeda yang aktif dan bersabuk DAN. Nama dan tingkat DAN mengikuti data akun saat sertifikat diterbitkan. Sertifikat lama tetap memakai data penerbitannya.</p>
        @if(session('success'))<div role="status" class="cert-alert cert-alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div role="alert" class="cert-alert cert-alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if($templates->isEmpty())
            <section class="rounded-xl border border-slate-200 bg-white p-8 text-center"><h2 class="font-semibold">Belum ada template sertifikat</h2><p class="mt-2 text-xs text-slate-500">Buat template DAN atau Kyu terlebih dahulu.</p><a href="{{ route('admin.certificate-templates.index') }}" class="btn btn-primary mt-4">Buka template sertifikat</a></section>
        @else
            @if($signers->count() < 3)<aside class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-6 text-amber-900">Tersedia {{ $signers->count() }} user yang memenuhi syarat. Diperlukan minimal tiga user aktif bersabuk DAN untuk mengisi ketiga posisi. <a href="{{ route('admin.belt-levels.index') }}" class="font-semibold underline">Periksa data sabuk.</a></aside>@endif
            @foreach($templates as $template)
                @php($settings = $template->settings ?? [])
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div class="flex items-center gap-3"><span class="rounded-md bg-[#f4e9ea] px-2 py-1 text-xs font-bold text-[#762137]">{{ strtoupper($template->exam_type) }}</span><h2 class="text-sm font-semibold">{{ $template->name }}</h2></div><a href="{{ route('admin.certificate-templates.layout', $template) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-[#762137]">Atur koordinat PDF <x-solid-arrow /></a></div>
                    <form method="post" action="{{ route('admin.certificate-signers.update', $template) }}" class="p-4 sm:p-5">
                        @csrf @method('PUT')
                        <div class="grid gap-4 lg:grid-cols-3">
                            @foreach(['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan'] as $side => $label)
                                <fieldset class="min-w-0 rounded-xl border border-slate-200 bg-slate-50/50 p-4"><legend class="px-2 text-xs font-semibold text-[#762137]">Penandatangan {{ strtolower($label) }}</legend>
                                    <label for="signer-{{ $template->id }}-{{ $side }}">User aktif bersabuk DAN</label>
                                    <select id="signer-{{ $template->id }}-{{ $side }}" name="signer_{{ $side }}_user_id" required @disabled($signers->isEmpty())><option value="">Pilih penandatangan {{ strtolower($label) }}</option>@foreach($signers as $signer)<option value="{{ $signer->id }}" @selected((string) old('signer_'.$side.'_user_id', $settings['signer_'.$side.'_user_id'] ?? '') === (string) $signer->id)>{{ $signer->name }} · {{ $signer->beltLevel->kyu_dan }}</option>@endforeach</select>
                                    <label for="title-{{ $template->id }}-{{ $side }}" class="mt-4">Jabatan pada sertifikat</label><input id="title-{{ $template->id }}-{{ $side }}" name="signer_{{ $side }}_title" required maxlength="150" value="{{ old('signer_'.$side.'_title', $settings['signer_'.$side.'_title'] ?? ($side === 'left' ? 'Ketua Umum' : ($side === 'right' ? 'Ketua Dewan Guru' : ''))) }}" placeholder="Isi jabatan penandatangan">
                                    <p class="mt-3 text-[11px] leading-5 text-slate-500">Nama dan tingkat DAN diambil dari akun yang dipilih.</p>
                                </fieldset>
                            @endforeach
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-slate-500">Ketiga posisi wajib memakai user yang berbeda.</p><button type="submit" @disabled($signers->count() < 3) class="btn btn-primary">Simpan penandatangan</button></div>
                    </form>
                </section>
            @endforeach
        @endif
    </div>
</x-app-layout>
