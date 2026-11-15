<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Pemeriksaan Wasit/Juri</h1><p class="page-subtitle">{{ $qualification->user?->name }}</p></x-slot>
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-6">
        <a href="{{ route('admin.qualifications.index') }}" class="btn btn-secondary">Kembali</a>
        @if($errors->any())<div role="alert" class="text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="rounded-2xl border bg-white p-6"><h2 class="text-lg font-bold">{{ \App\Models\OfficialQualification::KINDS[$qualification->kind] }} · {{ $qualification->license_level }}</h2><dl class="mt-4 space-y-3 text-sm">
            @foreach(['Pemohon' => $qualification->user?->name, 'Nomor sertifikat' => $qualification->certificate_number, 'Penerbit' => $qualification->issuer, 'Tanggal terbit' => $qualification->issued_at->format('d/m/Y'), 'Berlaku hingga' => $qualification->expires_at?->format('d/m/Y') ?? 'Tidak dicantumkan', 'Provinsi pengajuan' => $qualification->province?->name, 'Kabupaten / kota' => $qualification->city?->name] as $label => $value)<div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
        </dl><a class="mt-5 inline-block underline" href="{{ route('qualifications.document', $qualification) }}" target="_blank" rel="noopener">Lihat dokumen sertifikat</a></section>
        @if($qualification->status === 'pending')<form method="post" action="{{ route('admin.qualifications.store', $qualification) }}" class="space-y-4 rounded-2xl border bg-white p-6">@csrf
            <x-input-label for="notes" value="Catatan pemeriksaan (wajib jika meminta perbaikan)" /><textarea id="notes" name="notes" maxlength="2000" rows="3" class="w-full rounded-lg border-slate-300">{{ old('notes') }}</textarea>
            <div class="flex flex-wrap gap-3"><button name="status" value="approved" class="btn btn-primary">Setujui kompetensi</button><button name="status" value="revision" class="btn btn-secondary">Minta perbaikan</button></div>
        </form>@else<section class="rounded-2xl border bg-white p-6 text-sm"><p>Keputusan: {{ $qualification->is_valid ? 'Terverifikasi' : ($qualification->status === 'approved' ? 'Lisensi kedaluwarsa' : 'Perlu perbaikan') }}</p><p class="mt-2 whitespace-pre-wrap">{{ $qualification->notes }}</p><p class="mt-2 text-xs text-slate-500">Diperiksa {{ $qualification->reviewed_at?->format('d/m/Y H:i') }} oleh {{ $qualification->reviewer?->name ?? 'Administrator' }}</p></section>@endif
    </div>
</x-app-layout>
