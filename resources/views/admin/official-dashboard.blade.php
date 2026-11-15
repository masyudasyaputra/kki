@php
    $user = auth()->user();
    $national = in_array($role, ['pb', 'super_admin'], true);
    $roleLabel = ['pb' => 'Pengurus Pusat', 'super_admin' => 'Super Admin', 'pengprov' => 'Pengurus Provinsi', 'pengcab' => 'Pengurus Kabupaten / Kota', 'admin_dojo' => 'Pengelola Dojo'][$role] ?? 'Pengurus';
    $scope = $national ? 'Seluruh Indonesia' : ($role === 'admin_dojo' ? $user->dojo?->name : ($role === 'pengcab' ? $user->city?->name : $user->province?->name));
    $canAppoint = $user->hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov']);
    $canVerify = $user->hasRole(['super_admin', ...array_keys(\App\Services\MembershipReview::ROLES)]);
    $pendingOfficials = app(\App\Services\OfficialReview::class)->pendingCount($user);
    $pendingMembers = $canVerify ? app(\App\Services\MembershipReview::class)->members($user)->where('verification_status', 'pending')->count() : 0;
    $assignment = $user->getConnection()->getSchemaBuilder()->hasColumn('officials', 'user_id') ? $user->official : null;
    $skAttention = $organization['dojo_expiring'] + $organization['dojo_expired'] + $organization['official_expiring'] + $organization['official_expired'];
    $memberRoute = $role === 'super_admin' ? 'admin.users.index' : 'admin.members.index';
    $actions = [[$memberRoute, 'people', 'Data anggota'], ['admin.exams.index', 'exam', 'Kelola ujian'], ['admin.finance.index', 'wallet', 'Keuangan']];
    if ($canAppoint) $actions[] = ['admin.official-verifications.index', 'organization', 'Verifikasi pengurus'];
    else $actions[] = ['admin.dojos.index', 'building', 'Data dojo'];
@endphp
<x-app-layout>
    <x-slot name="header"><h1 class="page-title">{{ $title }}</h1><p class="page-subtitle">Ruang pengurus KKI · {{ $scope ?: 'Wilayah belum ditetapkan' }}</p></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="karateka-dashboard official-dashboard">
            <div class="kd-welcome">
                <div class="kd-identity"><span class="kd-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}@if($user->photo)<img src="{{ url('storage/'.$user->photo) }}" alt="Pasfoto {{ $user->name }}" width="54" height="54" onerror="this.hidden = true">@endif</span><div><p>Selamat datang kembali</p><h2>Oss, {{ $user->name }}!</h2></div></div>
                <span class="kd-member-status"><i></i>{{ $roleLabel }}</span>
            </div>
            <div class="kd-layout">
                <div class="kd-main">
                    <section class="kd-rank-card" style="--rank-base:#35151f;--rank-glow:#762137;--rank-ink:#fff;--rank-muted:#e3b6c1;--rank-border:#ffffff30">
                        <img src="{{ asset('images/logo-kki-dark.png') }}" class="kd-rank-watermark" alt="" aria-hidden="true" width="1024" height="1024">
                        <div class="kd-rank-top"><span class="kd-kicker">PENGABDIAN UNTUK KKI</span><span class="kd-pill">{{ $roleLabel }}</span></div>
                        <div class="kd-rank-content"><div><p>Wilayah tanggung jawab</p><h2>{{ $scope ?: 'Wilayah belum ditetapkan' }}</h2><p>{{ $assignment?->position ?? 'Administrasi dan pembinaan organisasi' }}</p>@if($assignment?->official_number)<p class="mt-3 font-mono">{{ $assignment->official_number }}</p>@endif</div></div>
                        <div class="kd-rank-footer"><span>{{ $assignment?->sk_expiry_date ? 'Masa bakti hingga '.\Carbon\Carbon::parse($assignment->sk_expiry_date)->format('d/m/Y') : 'Bersama membangun organisasi yang tertata.' }}</span><a href="{{ route('profile.edit') }}">Profil pengurus <x-solid-arrow direction="up-right" /></a></div>
                    </section>
                    <section class="kd-actions-section"><div class="kd-section-heading"><h2>Akses cepat</h2><span>Ruang kerja pengurus</span></div><div class="kd-actions">
                        @foreach($actions as [$target, $icon, $label])<a href="{{ route($target) }}"><span class="kd-action-icon"><x-sidebar-icon :name="$icon" /></span><span>{{ $label }}</span><span class="kd-action-arrow" aria-hidden="true"><x-solid-arrow direction="up-right" /></span></a>@endforeach
                    </div></section>
                    <section class="kd-dojo"><div class="kd-section-heading"><h2>Organisasi dalam cakupan Anda</h2><span>{{ $scope ?: 'Wilayah belum ditetapkan' }}</span></div><div class="od-metrics">
                        @foreach([['Anggota terdaftar', $organization['members'], $organization['active_members'].' anggota aktif'], ['Dojo terdaftar', $organization['dojos'], $organization['active_dojos'].' SK masih berlaku'], ['Pengurus terdaftar', $organization['officials'], $organization['active_officials'].' SK masih berlaku'], ['Cabang dengan dojo', $organization['cities'], 'Kabupaten / kota']] as [$label, $value, $note])<div><span>{{ $label }}</span><strong>{{ number_format($value, 0, ',', '.') }}</strong><small>{{ $note }}</small></div>@endforeach
                    </div></section>
                    <section class="kd-verification"><div class="kd-verification-icon"><x-sidebar-icon name="audit" /></div><div><p class="kd-kicker">PERLU PERHATIAN</p><h2>{{ $skAttention ? $skAttention.' dokumen SK perlu ditinjau' : 'Masa berlaku SK terpantau' }}</h2><p>{{ $organization['official_expiring'] }} SK pengurus dan {{ $organization['dojo_expiring'] }} SK dojo berakhir dalam 30 hari. {{ $organization['official_expired'] }} SK pengurus dan {{ $organization['dojo_expired'] }} SK dojo sudah kedaluwarsa.</p><a href="{{ route('admin.dojos.index') }}">Periksa data dojo <x-solid-arrow /></a>@if($role !== 'admin_dojo')<a class="ml-4" href="{{ route('admin.officials.index') }}">Periksa pengurus <x-solid-arrow /></a>@endif</div></section>
                </div>
                <aside class="kd-side" aria-label="Ringkasan pengurus">
                    <div class="kd-section-heading"><h2>Aktivitas wilayah</h2><span>{{ $month->locale('id')->translatedFormat('F Y') }}</span></div>
                    <a href="{{ route('admin.finance.index') }}" class="kd-payment-card"><div class="kd-payment-heading"><span>Penerimaan iuran & ujian</span><x-sidebar-icon name="wallet" /></div><strong>Rp {{ number_format($finance['total'], 0, ',', '.') }}</strong><div class="kd-payment-footer"><span>Tertunda Rp {{ number_format($finance['pending'], 0, ',', '.') }}</span><x-solid-arrow direction="up-right" /></div></a>
                    <div class="kd-stats">
                        @foreach([['Sesi ujian', $examSummary['sessions'], 'exam', 'sesi bulan ini'], ['Peserta ujian', $examSummary['participants'], 'people', $examSummary['paid_participants'].' peserta lunas']] as [$label, $value, $icon, $note])<a href="{{ route('admin.exams.index') }}" class="kd-stat"><div><x-sidebar-icon :name="$icon" /><x-solid-arrow direction="up-right" /></div><span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $note }}</small></a>@endforeach
                    </div>
                    @if($canAppoint || $canVerify)<section class="kd-dojo"><div class="kd-section-heading"><h2>Antrean verifikasi</h2><span>Menunggu pemeriksaan</span></div>
                        @if($canAppoint)<a class="od-task" href="{{ route('admin.official-verifications.index') }}"><span>Pengajuan pengurus</span><strong>{{ $pendingOfficials }}</strong><x-solid-arrow /></a>@endif
                        @if($canVerify)<a class="od-task" href="{{ route('admin.verifications.index') }}"><span>Keanggotaan karateka</span><strong>{{ $pendingMembers }}</strong><x-solid-arrow /></a>@endif
                    </section>@endif
                    <section class="kd-dojo"><div class="kd-section-heading"><h2>Agenda UKT mendatang</h2><a href="{{ route('admin.exams.index') }}">Semua <x-solid-arrow direction="up-right" /></a></div>
                        @forelse($upcomingExams as $exam)<a class="od-exam" href="{{ route('admin.exams.show', $exam) }}"><span>{{ \Carbon\Carbon::parse($exam->execution_date)->locale('id')->translatedFormat('d M Y') }}</span><h3>{{ $exam->name }}</h3><p>{{ $exam->location ?: 'Lokasi belum ditentukan' }}</p></a>@empty<p class="text-sm text-slate-500">Belum ada agenda ujian yang dibuka dalam cakupan Anda.</p>@endforelse
                    </section>
                    <div class="kd-motto"><span aria-hidden="true">“</span><p>Melayani dengan tertib.<br>Membina dengan tanggung jawab.</p><small>SEMANGAT PENGURUS KKI</small></div>
                </aside>
            </div>
        </div>
        <div class="mt-8 space-y-6">@include('admin.partials.dashboard-finance')
            <section class="kd-dojo"><div class="kd-section-heading"><h2>Data Anggota</h2><a href="{{ route($memberRoute) }}">Kelola anggota <x-solid-arrow direction="up-right" /></a></div>
                <div class="table-surface overflow-x-auto"><table class="data-table w-full"><thead><tr><th>Anggota</th><th>Dojo</th><th>Status</th></tr></thead><tbody>@forelse($members as $member)<tr><td><x-member-identity :member="$member"><p class="font-semibold">{{ $member->name }}</p><p class="text-xs text-slate-500">{{ $member->member_number }}</p></x-member-identity></td><td>{{ $member->dojo?->name ?? '-' }}</td><td>{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</td></tr>@empty<tr><td colspan="3">Tidak ada anggota yang sesuai.</td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$members" label="anggota" />
            </section>
        </div>
    </div>
</x-app-layout>
