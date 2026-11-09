<x-app-layout>
    <x-slot name="header">
        <div><h1 class="page-title">Penandatangan Sertifikat</h1><p class="page-subtitle">Atur user aktif bersabuk DAN dan jabatan yang dicetak pada sertifikat.</p></div>
    </x-slot>
<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <p class="max-w-3xl text-sm leading-6 text-slate-600">Pilih dua user aktif dengan sabuk DAN. Nama dan tingkat DAN pada sertifikat akan mengikuti akun tersebut. QR memuat tautan verifikasi yang menampilkan siapa yang menandatangani sertifikat.</p>

    @if(session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    @if($templates->isEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Belum ada template sertifikat</h2>
            <p class="mt-2 text-sm text-slate-600">Buat template DAN atau Kyu terlebih dahulu untuk mengatur penandatangannya.</p>
            <a href="{{ route('admin.certificate-templates.index') }}" class="mt-5 inline-flex rounded-lg bg-[#762137] px-4 py-2.5 text-sm font-semibold text-white">Buka template sertifikat</a>
        </section>
    @else
        @if($signers->isEmpty())
            <aside class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950"><strong>Belum ada user yang memenuhi syarat.</strong> Aktifkan akun user, lalu tetapkan sabuk DAN di profilnya. <a class="font-semibold underline" href="{{ route('admin.belt-levels.index') }}">Periksa pengaturan sabuk Kyu/DAN</a>.</aside>
        @endif
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach($templates as $template)
                @php($settings = $template->settings ?? [])
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                        <div><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $template->exam_type === 'dan' ? 'bg-slate-100 text-slate-700' : 'bg-amber-50 text-amber-800' }}">{{ strtoupper($template->exam_type) }}</span><h2 class="mt-2 font-semibold text-slate-900">{{ $template->name }}</h2></div>
                        <a class="text-sm font-semibold text-[#762137] hover:underline" href="{{ route('admin.certificate-templates.layout', $template) }}">Atur koordinat PDF</a>
                    </div>
                    <form method="post" action="{{ route('admin.certificate-signers.update', $template) }}" class="space-y-5 p-5">
                        @csrf @method('PUT')
                        @foreach(['left' => 'Penandatangan kiri', 'right' => 'Penandatangan kanan'] as $side => $label)
                            <fieldset class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-2">
                                <legend class="px-1 text-sm font-semibold text-slate-800">{{ $label }}</legend>
                                <label class="text-sm font-medium text-slate-700 sm:col-span-2">User
                                    <select name="signer_{{ $side }}_user_id" required @disabled($signers->isEmpty()) class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#762137] focus:outline-none focus:ring-2 focus:ring-[#762137]/15">
                                        <option value="">Pilih user aktif bersabuk DAN</option>
                                        @foreach($signers as $signer)
                                            <option value="{{ $signer->id }}" @selected((string) old('signer_'.$side.'_user_id', $settings['signer_'.$side.'_user_id'] ?? '') === (string) $signer->id)>{{ $signer->name }} · {{ $signer->beltLevel->kyu_dan }} · {{ str_replace('_', ' ', $signer->role) }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-sm font-medium text-slate-700 sm:col-span-2">Jabatan pada sertifikat
                                    <input name="signer_{{ $side }}_title" required maxlength="150" value="{{ old('signer_'.$side.'_title', $settings['signer_'.$side.'_title'] ?? ($side === 'left' ? 'Ketua Umum' : 'Ketua Dewan Guru')) }}" placeholder="Contoh: Ketua Umum" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#762137] focus:outline-none focus:ring-2 focus:ring-[#762137]/15">
                                </label>
                                <p class="text-xs leading-5 text-slate-500 sm:col-span-2">Nama dan tingkat DAN diambil langsung dari data user saat sertifikat diterbitkan.</p>
                            </fieldset>
                        @endforeach
                        <div class="flex justify-end"><button @disabled($signers->count() < 2) class="rounded-lg bg-[#762137] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#5f182b] disabled:cursor-not-allowed disabled:opacity-50">Simpan penandatangan</button></div>
                    </form>
                </section>
            @endforeach
        </div>
    @endif
</div>
</x-app-layout>
