@php
    use Illuminate\Support\Facades\Auth;

    $u = Auth::user();

    $extra = is_array($u->roles) ? $u->roles : json_decode($u->roles, true) ?? [];
    $userRoles = collect(array_merge([$u->role], $extra))
        ->filter()
        ->map(fn($r) => strtolower($r))
        ->unique()
        ->values()
        ->all();

    $hasRole = function ($roles) use ($userRoles) {
        $roles = collect((array) $roles)->map(fn($r) => strtolower($r))->all();
        return count(array_intersect($roles, $userRoles)) > 0;
    };

    $isAdmin = $hasRole(['pb', 'pengprov', 'pengcab', 'admin_dojo']);
    $isStruktural = $hasRole(['pb', 'pengprov', 'pengcab']);
    $isSistemAdmin = $hasRole(['pb', 'pengprov']);

    $homeHref = $isAdmin ? route('admin.dashboard') : route('dashboard');

    $activeDashboard = request()->routeIs('admin.dashboard') || request()->routeIs('dashboard');
    $activeOfficials = request()->routeIs('admin.officials.*');
    $activeMembers = request()->routeIs('admin.members.*');
    $canViewMembers = $hasRole(['pb', 'pengprov', 'admin_pengprov', 'pengcab', 'admin_pengcab', 'admin_dojo']);
    $activeDojos = request()->routeIs('admin.dojos.*');
    $activeExams = request()->routeIs('admin.exams.*');

    $activeUsers = request()->routeIs('admin.users.*');
    $activeProv = request()->routeIs('admin.provinces.*');
    $activeFees = request()->routeIs('admin.fees.*');
    $activeExamFees = request()->routeIs('admin.exams.fees.*');
    $activeSystem = $activeUsers || $activeProv || $activeFees || $activeExamFees;

    $roleLabel = str_replace('_', ' ', $u->role ?? 'member');
@endphp

<button type="button" @click="sidebarOpen = true" class="sidebar-toggle lg:hidden" aria-label="Buka navigasi" aria-controls="backend-sidebar" :aria-expanded="sidebarOpen.toString()">&#9776; <span>Menu</span></button>
<div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-950/50 lg:hidden"></div>
<aside id="backend-sidebar" class="backend-sidebar" :class="{ 'is-open': sidebarOpen }" :inert="!sidebarOpen && !desktopSidebar" @keydown.escape.window="sidebarOpen = false">
    <div class="flex h-20 shrink-0 items-center justify-between border-b border-white/10 px-6">
        <a href="{{ $homeHref }}" class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Logo KKI" class="h-10 w-10 object-contain">
            <div><span class="text-lg font-bold tracking-tight text-white">KKI<span class="text-red-400">.OS</span></span><p class="mt-1 text-[10px] text-slate-400">KKI</p></div>
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-400 lg:hidden" aria-label="Tutup navigasi">&times;</button>
    </div>
    <nav aria-label="Navigasi utama" class="min-h-0 flex-1 overflow-y-auto px-3 py-3">
        <p class="sidebar-label">Menu utama</p>
        <a href="{{ $homeHref }}" class="sidebar-link {{ $activeDashboard ? 'is-active' : '' }}" @if($activeDashboard) aria-current="page" @endif><x-sidebar-icon name="dashboard" /><span>Dashboard</span></a>
        @if ($canViewMembers)
            <a href="{{ route('admin.members.index') }}" class="sidebar-link {{ $activeMembers ? 'is-active' : '' }}" @if($activeMembers) aria-current="page" @endif><x-sidebar-icon name="people" /><span>Anggota</span></a>
        @endif
        @if ($isStruktural)
            <a href="{{ route('admin.officials.index') }}" class="sidebar-link {{ $activeOfficials ? 'is-active' : '' }}" @if($activeOfficials) aria-current="page" @endif><x-sidebar-icon name="people" /><span>Pengurus</span></a>
            <a href="{{ route('admin.dojos.index') }}" class="sidebar-link {{ $activeDojos ? 'is-active' : '' }}" @if($activeDojos) aria-current="page" @endif><x-sidebar-icon name="building" /><span>Dojo</span></a>
        @endif
        @if ($isAdmin)
            <a href="{{ route('admin.exams.index') }}" class="sidebar-link {{ $activeExams && !$activeExamFees ? 'is-active' : '' }}" @if($activeExams && !$activeExamFees) aria-current="page" @endif><x-sidebar-icon name="exam" /><span>Ujian</span></a>
        @endif
        @if ($isSistemAdmin)
            <p class="sidebar-label mt-4">Pengaturan master</p>
            <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ $activeUsers ? 'is-active' : '' }}" @if($activeUsers) aria-current="page" @endif><x-sidebar-icon name="user" /><span>Manajemen User</span></a>
            <a href="{{ route('admin.exams.fees.index') }}" class="sidebar-link {{ $activeExamFees ? 'is-active' : '' }}" @if($activeExamFees) aria-current="page" @endif><x-sidebar-icon name="receipt" /><span>Biaya Ujian</span></a>
            <a href="{{ route('admin.fees.index') }}" class="sidebar-link {{ $activeFees ? 'is-active' : '' }}" @if($activeFees) aria-current="page" @endif><x-sidebar-icon name="wallet" /><span>Konfigurasi Iuran</span></a>
            @if ($hasRole('pb'))
                <a href="{{ route('admin.provinces.index') }}" class="sidebar-link {{ $activeProv ? 'is-active' : '' }}" @if($activeProv) aria-current="page" @endif><x-sidebar-icon name="globe" /><span>Wilayah / Pengprov</span></a>
            @endif
        @endif
    </nav>
</aside>
