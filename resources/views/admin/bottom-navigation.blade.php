@php
    $isSystemAdmin = auth()->user()->role === 'super_admin';
    $items = [
        ['dashboard', 'dashboard', 'Beranda', ['dashboard', 'admin.dashboard']],
        [$isSystemAdmin ? 'admin.users.index' : 'admin.members.index', 'people', 'Anggota', $isSystemAdmin ? ['admin.users.*'] : ['admin.members.*']],
        ['admin.exams.index', 'exam', 'Ujian', ['admin.exams.*', 'admin.exam-reviews.*']],
        ['admin.finance.index', 'wallet', 'Keuangan', ['admin.finance.*', 'admin.payments.*']],
    ];
@endphp
<nav class="member-bottom-nav official-bottom-nav" aria-label="Navigasi pengurus">
    @foreach($items as [$navRoute, $navIcon, $navLabel, $patterns])
        <a href="{{ route($navRoute) }}" class="{{ request()->routeIs(...$patterns) ? 'is-active' : '' }}" @if(request()->routeIs(...$patterns)) aria-current="page" @endif>
            <span><x-sidebar-icon :name="$navIcon" /></span><span>{{ $navLabel }}</span>
        </a>
    @endforeach
    <button type="button" @click="sidebarOpen = true" :aria-expanded="sidebarOpen.toString()" aria-controls="backend-sidebar" aria-label="Buka semua menu pengurus">
        <span><x-sidebar-icon name="organization" /></span><span>Menu</span>
    </button>
</nav>
