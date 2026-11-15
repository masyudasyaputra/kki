@if($user->registration_type !== 'official' && ($user->hasRole('member') || $user->belt_level_id))
    <section class="profile-panel">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="profile-section-title">Kompetensi Wasit/Juri</h2><p class="mt-2 text-sm text-slate-500">Lisensi terhubung dengan akun dan keanggotaan karateka Anda.</p></div><a href="{{ route('qualifications.index') }}" class="btn btn-secondary">Ajukan / kelola kompetensi</a></div>
        @if($user->getConnection()->getSchemaBuilder()->hasTable('official_qualifications'))
            @php($validQualifications = $user->qualifications()->where('status', 'approved')->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))->get())
            <div class="mt-4 flex flex-wrap gap-2">@foreach($validQualifications as $license)<span class="profile-chip profile-chip-success">{{ \App\Models\OfficialQualification::KINDS[$license->kind] }} Terverifikasi · {{ $license->license_level }}</span>@endforeach</div>
        @endif
    </section>
@endif
