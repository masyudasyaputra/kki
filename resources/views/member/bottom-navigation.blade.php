<nav class="member-bottom-nav" aria-label="Navigasi karateka">
    @foreach ([['member.dashboard', 'dashboard', 'Beranda'], ['member.exams', 'exam', 'Ujian'], ['member.card', 'membership', 'Kartu'], ['member.payments', 'wallet', 'Tagihan'], ['profile.edit', 'user', 'Profil']] as [$navRoute, $navIcon, $navLabel])
        <a href="{{ route($navRoute) }}" class="{{ request()->routeIs($navRoute) ? 'is-active' : '' }}" @if(request()->routeIs($navRoute)) aria-current="page" @endif>
            <span><x-sidebar-icon :name="$navIcon" /></span>
            <span>{{ $navLabel }}</span>
        </a>
    @endforeach
</nav>
