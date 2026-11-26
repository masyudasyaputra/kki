@php
    use Illuminate\Support\Facades\Auth;

    $u = Auth::user();

    $userRoles = $u->allRoles();

    $hasRole = function ($roles) use ($userRoles) {
        $roles = collect((array) $roles)->map(fn($r) => strtolower($r))->all();
        return count(array_intersect($roles, $userRoles)) > 0;
    };

    $isSuperAdmin = $hasRole('super_admin');
    $isAdmin = $isSuperAdmin || $hasRole(['pb', 'pengprov', 'pengcab', 'admin_dojo', 'admin_pengprov', 'admin_pengcab']);
    $isStruktural = $isSuperAdmin || $hasRole(['pb', 'pengprov', 'pengcab']);
    $isSistemAdmin = $hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov']);
    $canVerifyOfficials = \App\Services\RoleAccess::reviewsOfficials($u);
    $pendingOfficialVerifications = $canVerifyOfficials ? app(\App\Services\OfficialReview::class)->pendingCount($u) : 0;
    $canManageTemplates = \App\Services\RoleAccess::certificateTemplates($u);

    $homeHref = route('dashboard');

    $activeDashboard = request()->routeIs('admin.dashboard', 'dashboard', 'member.dashboard');
    $activeFinance = request()->routeIs('admin.finance.*');
    $canViewFinance = $hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo']);
    $activeOfficials = request()->routeIs('admin.officials.*');
    $activeMembers = request()->routeIs('admin.members.*');
    $canViewMembers = $isSuperAdmin || $hasRole(['pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo']);
    $canVerifyMembers = $hasRole(['pb', 'super_admin']) || $hasRole(array_keys(\App\Services\MembershipReview::ROLES));
    $pendingMemberVerifications = $canVerifyMembers
        ? app(\App\Services\MembershipReview::class)->members($u)->where('verification_status', 'pending')->count()
        : 0;
    $activeDojos = request()->routeIs('admin.dojos.*');
    $activeExams = request()->routeIs('admin.exams.*');

    $activeUsers = request()->routeIs('admin.users.*');
    $activeProv = request()->routeIs('admin.provinces.*');
    $activeFees = request()->routeIs('admin.fees.*');
    $activeExamFees = request()->routeIs('admin.exams.fees.*');
    $activeBeltLevels = request()->routeIs('admin.belt-levels.*');
    $activeCertificateSigners = request()->routeIs('admin.certificate-signers.*');
    $activeSystem = $activeUsers || $activeProv || $activeFees || $activeExamFees
        || $activeBeltLevels || $activeCertificateSigners
        || request()->routeIs('admin.regions.*', 'admin.access-audits.*', 'admin.certificate-templates.*', 'admin.member-numbers.*', 'admin.official-numbers.*');

    $roleLabel = \App\Services\RoleAccess::POSITIONS[$u->role] ?? str_replace('_', ' ', $u->role ?? 'member');
    $canManageOrganization = $isStruktural || $hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov']);
    $canOpenExams = $isAdmin || $hasRole(['dewan_guru_pusat', 'pengelola_sertifikat_pb', 'verifikator_ujian_pb', 'panitia_ujian', 'penguji']);
    $canOpenExamReviews = $hasRole(['pb', 'super_admin', 'dewan_guru_pusat', 'verifikator_ujian_pb', 'pengelola_sertifikat_pb', 'panitia_ujian', 'penguji', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab']);
@endphp

<button type="button" @click="sidebarOpen = true" class="sidebar-toggle lg:hidden" aria-label="Buka navigasi" aria-controls="backend-sidebar" :aria-expanded="sidebarOpen.toString()">&#9776; <span>Menu</span></button>
<div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-950/50 lg:hidden"></div>
<aside id="backend-sidebar" class="backend-sidebar" :class="{ 'is-open': sidebarOpen }" :inert="!sidebarOpen && !desktopSidebar" @keydown.escape.window="sidebarOpen = false">
    <div class="flex h-20 shrink-0 items-center justify-between border-b border-white/10 px-6">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/logo-kki-dark.png') }}" alt="Logo KKI" class="h-10 w-10 object-contain">
            <div><span class="text-lg font-bold tracking-tight text-white">PP KKI</span><p class="mt-1 text-[10px] text-slate-400">Pengurus Pusat KKI</p></div>
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-400 lg:hidden" aria-label="Tutup navigasi">&times;</button>
    </div>
    <nav aria-label="Navigasi utama" class="sidebar-groups min-h-0 flex-1 overflow-y-auto px-3 py-3">
        <a href="{{ $homeHref }}" class="sidebar-link sidebar-standalone-link {{ $activeDashboard ? 'is-active' : '' }}" @if($activeDashboard) aria-current="page" @endif><x-sidebar-icon name="dashboard" /><span>{{ $u->role === 'member' ? 'Beranda Karateka' : 'Dashboard' }}</span></a>

        @if ($hasRole('member'))
            <p class="sidebar-section-label">Ruang Karateka</p>
            @foreach ([['member.card', 'membership', 'Kartu Anggota'], ['member.exams', 'exam', 'Ujian Saya'], ['member.payments', 'wallet', 'Pembayaran'], ['member.certificates', 'certificate', 'Sertifikat Saya'], ['profile.edit', 'user', 'Profil Saya']] as [$memberRoute, $memberIcon, $memberLabel])
                <a href="{{ route($memberRoute) }}" class="sidebar-link {{ request()->routeIs($memberRoute) ? 'is-active' : '' }}" @if(request()->routeIs($memberRoute)) aria-current="page" @endif><x-sidebar-icon :name="$memberIcon" /><span>{{ $memberLabel }}</span></a>
            @endforeach
            @if($u->registration_type !== 'official')<a href="{{ route('qualifications.index') }}" class="sidebar-link {{ request()->routeIs('qualifications.*') ? 'is-active' : '' }}"><x-sidebar-icon name="certificate" /><span>Kompetensi Wasit/Juri</span></a>@endif
            <p class="sidebar-section-label">Informasi KKI</p>
            <a href="{{ route('public.agenda') }}" class="sidebar-link"><x-sidebar-icon name="exam" /><span>Agenda UKT</span></a>
            <a href="{{ route('public.dojos') }}" class="sidebar-link"><x-sidebar-icon name="building" /><span>Direktori Dojo</span></a>
        @endif

        @if ($isStruktural || $isSistemAdmin || $canVerifyOfficials || $hasRole(\App\Services\QualificationReview::REVIEWERS))
            <details name="kki-sidebar-menu" class="sidebar-group" @if($activeOfficials || $activeDojos || request()->routeIs('admin.official-verifications.*', 'admin.qualifications.*')) open @endif>
                <summary><x-sidebar-icon name="organization" /><span>Organisasi</span>@if($pendingOfficialVerifications > 0)<span class="sidebar-notification-badge" aria-label="{{ $pendingOfficialVerifications }} pengajuan pengurus menunggu verifikasi">{{ $pendingOfficialVerifications }}</span>@endif<span class="sidebar-chevron" aria-hidden="true">⌄</span></summary>
                <div class="sidebar-group-items">
                    <a href="{{ route('admin.officials.index') }}" class="sidebar-link {{ $activeOfficials ? 'is-active' : '' }}" @if($activeOfficials) aria-current="page" @endif><x-sidebar-icon name="people" /><span>Pengurus</span></a>
                    @if ($canVerifyOfficials)<a href="{{ route('admin.official-verifications.index') }}" class="sidebar-link {{ request()->routeIs('admin.official-verifications.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.official-verifications.*')) aria-current="page" @endif><x-sidebar-icon name="people" /><span class="min-w-0 flex-1">Verifikasi Pengurus</span>@if($pendingOfficialVerifications > 0)<span class="sidebar-notification-badge" aria-label="{{ $pendingOfficialVerifications }} pengajuan pengurus menunggu verifikasi">{{ $pendingOfficialVerifications }}</span>@endif</a>@endif
                    @if($isAdmin)<a href="{{ route('admin.dojos.index') }}" class="sidebar-link {{ $activeDojos ? 'is-active' : '' }}" @if($activeDojos) aria-current="page" @endif><x-sidebar-icon name="building" /><span>Dojo</span></a>@endif
                    @if($hasRole(\App\Services\QualificationReview::REVIEWERS))<a href="{{ route('admin.qualifications.index') }}" class="sidebar-link {{ request()->routeIs('admin.qualifications.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.qualifications.*')) aria-current="page" @endif><x-sidebar-icon name="certificate" /><span>Verifikasi Wasit/Juri</span></a>@endif
                </div>
            </details>
        @endif

        @if ($canViewMembers || $canVerifyMembers)
            <details name="kki-sidebar-menu" class="sidebar-group" @if($activeMembers || request()->routeIs('admin.verifications.*')) open @endif>
                <summary><x-sidebar-icon name="membership" /><span>Keanggotaan</span>@if($pendingMemberVerifications > 0)<span class="sidebar-notification-badge" aria-label="{{ $pendingMemberVerifications }} pengajuan keanggotaan menunggu verifikasi">{{ $pendingMemberVerifications }}</span>@endif<span class="sidebar-chevron" aria-hidden="true">⌄</span></summary>
                <div class="sidebar-group-items">
                    @if ($canViewMembers)<a href="{{ route('admin.members.index') }}" class="sidebar-link {{ $activeMembers ? 'is-active' : '' }}" @if($activeMembers) aria-current="page" @endif><x-sidebar-icon name="people" /><span>Anggota</span></a>@endif
                    @if ($canVerifyMembers)<a href="{{ route('admin.verifications.index') }}" class="sidebar-link {{ request()->routeIs('admin.verifications.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.verifications.*')) aria-current="page" @endif><x-sidebar-icon name="people" /><span class="min-w-0 flex-1">Verifikasi Keanggotaan</span>@if($pendingMemberVerifications > 0)<span class="sidebar-notification-badge" aria-label="{{ $pendingMemberVerifications }} pengajuan menunggu verifikasi">{{ $pendingMemberVerifications }}</span>@endif</a>@endif
                </div>
            </details>
        @endif

        @if ($canOpenExams || $canOpenExamReviews || $hasRole(['pb', 'dewan_guru_pusat']))
            <details name="kki-sidebar-menu" class="sidebar-group" @if(($activeExams && !$activeExamFees) || request()->routeIs('admin.exam-reviews.*', 'admin.oversight')) open @endif>
                <summary><x-sidebar-icon name="exam-cert" /><span>Ujian & Sertifikasi</span><span class="sidebar-chevron" aria-hidden="true">⌄</span></summary>
                <div class="sidebar-group-items">
                    @if ($canOpenExams)<a href="{{ route('admin.exams.index') }}" class="sidebar-link {{ $activeExams && !$activeExamFees ? 'is-active' : '' }}" @if($activeExams && !$activeExamFees) aria-current="page" @endif><x-sidebar-icon name="exam" /><span>Jadwal & Peserta Ujian</span></a>@endif
            @if($hasRole(['pb', 'super_admin', 'dewan_guru_pusat']))<a class="sidebar-link {{ request()->routeIs('admin.oversight') ? 'is-active' : '' }}" href="{{ route('admin.oversight') }}" @if(request()->routeIs('admin.oversight')) aria-current="page" @endif><x-sidebar-icon name="exam" /><span>Pemantauan Nasional</span></a>@endif
                    @if ($canOpenExamReviews)<a class="sidebar-link {{ request()->routeIs('admin.exam-reviews.*') ? 'is-active' : '' }}" href="{{ route('admin.exam-reviews.index') }}" @if(request()->routeIs('admin.exam-reviews.*')) aria-current="page" @endif><x-sidebar-icon name="exam" /><span>Hasil & Verifikasi Ujian</span></a>@endif
                </div>
            </details>
        @endif

        <a class="sidebar-link {{ request()->routeIs('privacy.index') ? 'is-active' : '' }}" href="{{ route('privacy.index') }}"><x-sidebar-icon name="settings" /><span>Privasi & PDP</span></a>
        @if($isSuperAdmin)<a class="sidebar-link {{ request()->routeIs('privacy.admin') ? 'is-active' : '' }}" href="{{ route('privacy.admin') }}"><x-sidebar-icon name="settings" /><span>Permintaan PDP</span></a>@endif
        @if ($canViewFinance)
            <details name="kki-sidebar-menu" class="sidebar-group" @if($activeFinance) open @endif>
                <summary><x-sidebar-icon name="wallet" /><span>Keuangan</span><span class="sidebar-chevron" aria-hidden="true">⌄</span></summary>
                <div class="sidebar-group-items">
                    <a href="{{ route('admin.finance.index') }}" class="sidebar-link {{ $activeFinance ? 'is-active' : '' }}" @if($activeFinance) aria-current="page" @endif><x-sidebar-icon name="receipt" /><span>Ringkasan & Transaksi</span></a>
                </div>
            </details>
        @endif

        @if ($isSistemAdmin || $canManageTemplates)
            <details name="kki-sidebar-menu" class="sidebar-group" @if($activeSystem) open @endif>
                <summary><x-sidebar-icon name="settings" /><span>Pengaturan Sistem</span><span class="sidebar-chevron" aria-hidden="true">⌄</span></summary>
                <div class="sidebar-group-items">
                    @if($hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov']))<p class="sidebar-section-label">Wilayah</p>@endif
                    @if ($hasRole(['pb', 'super_admin']))
                        <a href="{{ route('admin.provinces.index') }}" class="sidebar-link {{ $activeProv ? 'is-active' : '' }}" @if($activeProv) aria-current="page" @endif><x-sidebar-icon name="globe" /><span>Wilayah / Pengprov</span></a>
                    @endif
                    @if($hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov']))<a href="{{ route('admin.regions.index') }}" class="sidebar-link {{ request()->routeIs('admin.regions.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.regions.*')) aria-current="page" @endif><x-sidebar-icon name="map-pin" /><span>Kabupaten & Kecamatan</span></a>@endif
                    @if($canManageTemplates)<p class="sidebar-section-label">Sertifikat</p>@endif
                    @if($canManageTemplates)<a href="{{ route('admin.certificate-templates.index') }}" class="sidebar-link {{ request()->routeIs('admin.certificate-templates.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.certificate-templates.*')) aria-current="page" @endif><x-sidebar-icon name="certificate" /><span>Pengaturan Template Sertifikat</span></a>@endif
                    @if($canManageTemplates)<a href="{{ route('admin.certificate-signers.index') }}" class="sidebar-link {{ $activeCertificateSigners ? 'is-active' : '' }}" @if($activeCertificateSigners) aria-current="page" @endif><x-sidebar-icon name="signature" /><span>Penandatangan Sertifikat</span></a>@endif
                    @if($hasRole(['pb', 'super_admin', 'pengprov', 'admin_pengprov']))
                        <p class="sidebar-section-label">Pengguna & Keuangan</p>
                        <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ $activeUsers ? 'is-active' : '' }}" @if($activeUsers) aria-current="page" @endif><x-sidebar-icon name="user" /><span>Manajemen User</span></a>
                        <a href="{{ route('admin.exams.fees.index') }}" class="sidebar-link {{ $activeExamFees ? 'is-active' : '' }}" @if($activeExamFees) aria-current="page" @endif><x-sidebar-icon name="receipt" /><span>Biaya Ujian</span></a>
                        <a href="{{ route('admin.fees.index') }}" class="sidebar-link {{ $activeFees ? 'is-active' : '' }}" @if($activeFees) aria-current="page" @endif><x-sidebar-icon name="wallet" /><span>Konfigurasi Iuran</span></a>
                    @endif
                    @if ($hasRole(['pb', 'super_admin']))
                        <p class="sidebar-section-label">Master Data & Audit</p>
                        @if($isSuperAdmin)<a href="{{ route('admin.member-numbers.index') }}" class="sidebar-link {{ request()->routeIs('admin.member-numbers.*', 'admin.official-numbers.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.member-numbers.*', 'admin.official-numbers.*')) aria-current="page" @endif><x-sidebar-icon name="membership" /><span>Manajemen Nomor ID</span></a>@endif
                        <a href="{{ route('admin.belt-levels.index') }}" class="sidebar-link {{ $activeBeltLevels ? 'is-active' : '' }}" @if($activeBeltLevels) aria-current="page" @endif><x-sidebar-icon name="belt" /><span>Pengaturan Sabuk Kyu/DAN</span></a>
                        <a class="sidebar-link {{ request()->routeIs('admin.access-audits.*') ? 'is-active' : '' }}" href="{{ route('admin.access-audits.index') }}" @if(request()->routeIs('admin.access-audits.*')) aria-current="page" @endif><x-sidebar-icon name="audit" /><span>Riwayat Hak Akses</span></a>
                    @endif
                </div>
            </details>
        @endif
        @if(\App\Services\RoleAccess::serviceDesk($u))<a href="{{ route('admin.services.index') }}" class="sidebar-link sidebar-standalone-link {{ request()->routeIs('admin.services.*') ? 'is-active' : '' }}"><x-sidebar-icon name="organization" /><span>Layanan Bantuan SIM-KKI</span></a>@endif
        <a href="{{ route('guide') }}" class="sidebar-link sidebar-standalone-link {{ request()->routeIs('guide') ? 'is-active' : '' }}" @if(request()->routeIs('guide')) aria-current="page" @endif><x-sidebar-icon name="exam-cert" /><span>Panduan Sistem</span></a>
    </nav>
</aside>
