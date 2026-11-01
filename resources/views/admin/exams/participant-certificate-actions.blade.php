@php
    $issuedCertificate = $examCertificates[$p->id] ?? null;
    $identity = $p->certificate_identity ?? [];
@endphp
@if($canSendCertificates || $issuedCertificate)
<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/50 px-4 py-3 md:px-8" @click.stop>
    <span class="text-xs {{ $issuedCertificate?->sent_at ? 'text-emerald-700' : 'text-slate-400' }}">{{ $issuedCertificate?->sent_at ? 'Sertifikat dikirim · '.$issuedCertificate->sent_at->format('d/m/Y H:i') : 'Sertifikat belum dikirim' }}</span>
    <div class="flex flex-wrap gap-2">
        @if($issuedCertificate)<a class="cert-action" href="{{ route('admin.exams.certificates.print', [$exam, $p]) }}" target="_blank" rel="noopener">Lihat sertifikat</a>@endif
        @if($canSendCertificates)
            @if(!$issuedCertificate)<button type="button" class="cert-action" onclick="document.getElementById('certificate-identity-{{ $p->id }}').showModal()">Data sertifikat</button>@endif
            <form method="post" action="{{ route('admin.exams.certificates.send', [$exam, $p]) }}" onsubmit="this.querySelector('button').disabled = true;">@csrf<button class="cert-action {{ $issuedCertificate?->sent_at ? '' : 'text-red-600' }}" @disabled($exam->status !== 'completed' || $issuedCertificate?->sent_at || (!$issuedCertificate && $scoreData?->result !== 'Lulus'))>{{ $issuedCertificate?->sent_at ? 'Sudah dikirim' : 'Kirim sertifikat' }}</button></form>
        @endif
    </div>
    @if($canSendCertificates && !$issuedCertificate)
        <dialog id="certificate-identity-{{ $p->id }}" class="cert-dialog cert-dialog-small certificate-management" aria-labelledby="certificate-identity-title-{{ $p->id }}"><div class="cert-dialog-heading"><div><h2 id="certificate-identity-title-{{ $p->id }}">Data sertifikat</h2><p>{{ $p->user->name }}</p></div><button type="button" class="cert-dialog-close" onclick="this.closest('dialog').close()" aria-label="Tutup">×</button></div><form method="post" enctype="multipart/form-data" action="{{ route('admin.exams.certificates.identity', [$exam, $p]) }}">@csrf<div class="cert-template-form-body space-y-4"><label>Tempat lahir<input name="birth_place" value="{{ $identity['birth_place'] ?? '' }}" maxlength="150"></label><label>Tanggal lahir<input name="birth_date" type="date" value="{{ $identity['birth_date'] ?? '' }}" max="{{ now()->toDateString() }}"></label><label>Foto peserta (opsional)<input name="photo" type="file" accept="image/png,image/jpeg"></label></div><div class="cert-form-footer"><button type="button" class="btn btn-secondary" onclick="this.closest('dialog').close()">Batal</button><button class="btn btn-primary">Simpan data</button></div></form></dialog>
    @endif
</div>
@endif
