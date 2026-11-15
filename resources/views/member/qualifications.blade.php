<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Kompetensi Wasit/Juri</h1><p class="page-subtitle">Ajukan lisensi dan pantau hasil pemeriksaan tanpa membuat akun baru.</p></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6">
        <a href="{{ route('profile.edit') }}" class="btn btn-secondary">Kembali ke profil</a>
        @if(session('success'))<p role="status" class="text-sm text-emerald-800">{{ session('success') }}</p>@endif
        @if($errors->any())<div role="alert" class="text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="post" action="{{ route('qualifications.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            <div><h2 class="text-lg font-bold">Ajukan kompetensi Wasit/Juri</h2><p class="mt-2 text-sm text-slate-500">Lengkapi wilayah profil terlebih dahulu. Dokumen hanya dapat dilihat oleh Anda dan administrator yang berwenang.</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><x-input-label for="kind" value="Kompetensi *" /><select id="kind" name="kind" required class="w-full rounded-lg border-slate-300">@foreach(\App\Models\OfficialQualification::KINDS as $key => $label)<option value="{{ $key }}" @selected(old('kind') === $key)>{{ $label }}</option>@endforeach</select></div>
                <div><x-input-label for="license_level" value="Tingkat lisensi *" /><x-text-input id="license_level" name="license_level" :value="old('license_level')" required maxlength="150" placeholder="Sesuai yang tertera pada sertifikat" /></div>
                <div><x-input-label for="certificate_number" value="Nomor sertifikat / lisensi *" /><x-text-input id="certificate_number" name="certificate_number" :value="old('certificate_number')" required maxlength="150" /></div>
                <div><x-input-label for="issuer" value="Lembaga penerbit *" /><x-text-input id="issuer" name="issuer" :value="old('issuer')" required maxlength="255" /></div>
                <div><x-input-label for="issued_at" value="Tanggal terbit *" /><x-text-input id="issued_at" name="issued_at" type="date" :value="old('issued_at')" required /></div>
                <div><x-input-label for="expires_at" value="Berlaku sampai (jika ada)" /><x-text-input id="expires_at" name="expires_at" type="date" :value="old('expires_at')" /></div>
                <div class="sm:col-span-2"><x-input-label for="document" value="Dokumen sertifikat *" /><input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="mt-2 block w-full text-sm"><p class="mt-2 text-xs text-slate-500">PDF, JPG, atau PNG. Maksimal 10 MB.</p></div>
            </div>
            <button class="btn btn-primary">Kirim pengajuan</button>
        </form>
        <section class="space-y-4"><h2 class="text-lg font-bold">Lisensi & riwayat pengajuan</h2>
            @forelse($qualifications as $qualification)
                <article class="rounded-2xl border border-slate-200 bg-white p-5"><div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-bold">{{ \App\Models\OfficialQualification::KINDS[$qualification->kind] }} · {{ $qualification->license_level }}</h3><span class="profile-chip {{ $qualification->is_valid ? 'profile-chip-success' : '' }}">{{ $qualification->is_valid ? 'Wasit/Juri Terverifikasi' : ($qualification->status === 'approved' ? 'Lisensi kedaluwarsa' : ($qualification->status === 'revision' ? 'Perlu perbaikan' : 'Menunggu pemeriksaan')) }}</span></div><p class="mt-3 text-sm">{{ $qualification->certificate_number }} · {{ $qualification->issuer }}</p><p class="mt-2 text-xs text-slate-500">Terbit {{ $qualification->issued_at->format('d/m/Y') }} · {{ $qualification->expires_at ? 'Berlaku hingga '.$qualification->expires_at->format('d/m/Y') : 'Tanpa tanggal kedaluwarsa pada dokumen' }}</p>
                    @if($qualification->notes)<p class="mt-3 whitespace-pre-wrap text-sm text-amber-900">Catatan pemeriksa: {{ $qualification->notes }}</p>@endif
                    @if($qualification->status === 'revision')<p class="mt-2 text-xs text-slate-500">Perbaiki data dan kirim pengajuan baru melalui formulir di atas. Riwayat ini tetap tersimpan.</p>@endif
                    <a class="mt-3 inline-block text-sm underline" href="{{ route('qualifications.document', $qualification) }}" target="_blank" rel="noopener">Lihat dokumen</a>
                </article>
            @empty<p class="rounded-2xl border bg-white p-5 text-sm text-slate-500">Belum ada kompetensi Wasit/Juri yang diajukan.</p>@endforelse
            <x-pagination :paginator="$qualifications" label="lisensi" />
        </section>
    </div>
</x-app-layout>
