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
    $activeDojos = request()->routeIs('admin.dojos.*');
    $activeExams = request()->routeIs('admin.exams.*');

    $activeUsers = request()->routeIs('admin.users.*');
    $activeProv = request()->routeIs('admin.provinces.*');
    $activeFees = request()->routeIs('admin.fees.*');
    $activeExamFees = request()->routeIs('admin.exams.fees.*');
    $activeSystem = $activeUsers || $activeProv || $activeFees || $activeExamFees;

    $roleLabel = str_replace('_', ' ', $u->role ?? 'member');
@endphp

<nav x-data="{ openMenu: false, openSystem: false, openUser: false }"
    class="bg-slate-900 text-white sticky top-0 z-50 border-b border-slate-800 shadow-sm">

    {{-- TOP BAR --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            {{-- Brand Logo --}}
            <div class="flex items-center gap-6 min-w-0">
                <a href="{{ $homeHref }}" class="flex items-center gap-3 group transition-opacity hover:opacity-95">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-9 w-auto object-contain">
                    <div class="flex flex-col min-w-0">
                        <span class="font-bold tracking-tight text-base leading-none text-white flex items-center gap-1">
                            SHOKAIDO<span class="text-red-500 font-extrabold">.OS</span>
                        </span>
                        <span class="text-[10px] font-medium text-slate-400 tracking-wide mt-1">
                            Shotokan Kandaga Indonesia
                        </span>
                    </div>
                </a>

                {{-- DESKTOP MENU --}}
                <div class="hidden md:flex items-center gap-1 ml-4 pl-4 border-l border-slate-800">
                    <a href="{{ $homeHref }}"
                        class="px-3.5 py-2 rounded-lg text-xs font-medium transition-all inline-flex items-center gap-2
                        {{ $activeDashboard ? 'bg-slate-800 text-white font-semibold shadow-sm border border-slate-700/70' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>

                    @if ($isStruktural)
                        <a href="{{ route('admin.officials.index') }}"
                            class="px-3.5 py-2 rounded-lg text-xs font-medium transition-all inline-flex items-center gap-2
                            {{ $activeOfficials ? 'bg-slate-800 text-white font-semibold shadow-sm border border-slate-700/70' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Pengurus
                        </a>

                        <a href="{{ route('admin.dojos.index') }}"
                            class="px-3.5 py-2 rounded-lg text-xs font-medium transition-all inline-flex items-center gap-2
                            {{ $activeDojos ? 'bg-slate-800 text-white font-semibold shadow-sm border border-slate-700/70' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Dojo
                        </a>
                    @endif

                    @if ($isAdmin)
                        <a href="{{ route('admin.exams.index') }}"
                            class="px-3.5 py-2 rounded-lg text-xs font-medium transition-all inline-flex items-center gap-2
                            {{ $activeExams ? 'bg-slate-800 text-white font-semibold shadow-sm border border-slate-700/70' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M2,11L12,16L22,11L12,6L2,11M12,18L2,13V15L12,20L22,15V13L12,18Z" />
                            </svg>
                            Ujian
                        </a>
                    @endif

                    @if ($isSistemAdmin)
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                class="px-3.5 py-2 rounded-lg text-xs font-medium transition-all inline-flex items-center gap-1.5
                                {{ $activeSystem ? 'bg-slate-800 text-white font-semibold shadow-sm border border-slate-700/70' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Sistem
                                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak @click.outside="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute left-0 mt-2 w-60 bg-white text-slate-800 rounded-xl shadow-lg border border-slate-200/90 py-1.5 z-50">
                                
                                <div class="px-3.5 py-1.5 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                    Pengaturan Master
                                </div>

                                <a href="{{ route('admin.users.index') }}"
                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium transition-colors
                                    {{ $activeUsers ? 'bg-red-50 text-red-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Manajemen User
                                </a>

                                <a href="{{ route('admin.exams.fees.index') }}"
                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium transition-colors
                                    {{ $activeExamFees ? 'bg-red-50 text-red-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                    </svg>
                                    Biaya Ujian
                                </a>

                                <a href="{{ route('admin.fees.index') }}"
                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium transition-colors
                                    {{ $activeFees ? 'bg-red-50 text-red-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Konfigurasi Iuran
                                </a>

                                @if ($hasRole('pb'))
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <a href="{{ route('admin.provinces.index') }}"
                                        class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium transition-colors
                                        {{ $activeProv ? 'bg-red-50 text-red-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Wilayah / Pengprov
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Section: User Profile --}}
            <div class="hidden md:flex items-center">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="flex items-center gap-3 px-3 py-1.5 rounded-lg border border-slate-800 hover:bg-slate-800/80 transition-all text-left">
                        <div class="h-8 w-8 rounded-full bg-slate-800 border border-slate-700 text-slate-200 flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr($u->name, 0, 1)) }}
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs font-semibold text-white leading-tight">{{ $u->name }}</span>
                            <span class="text-[10px] text-slate-400 font-normal uppercase mt-0.5">
                                {{ $roleLabel }}
                            </span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-64 bg-white text-slate-800 rounded-xl shadow-lg border border-slate-200/90 py-1.5 z-50">
                        
                        <div class="px-4 py-2.5 border-b border-slate-100">
                            <p class="text-[11px] font-semibold text-slate-900 truncate">{{ $u->name }}</p>
                            <p class="text-xs text-slate-500 truncate mt-0.5">{{ $u->email }}</p>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}"
                                class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profil Saya
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors text-left">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Keluar Sistem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MOBILE MENU BUTTON --}}
            <div class="flex md:hidden items-center">
                <button type="button" @click="openMenu = !openMenu"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition-colors"
                    aria-label="Toggle menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!openMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="openMenu" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    {{-- MOBILE DROPDOWN MENU --}}
    <div x-show="openMenu" x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="md:hidden border-t border-slate-800 bg-slate-900 px-4 pt-3 pb-5 space-y-1">
        
        <div class="px-3 py-2 border-b border-slate-800 mb-2">
            <p class="text-xs font-semibold text-white">{{ $u->name }}</p>
            <p class="text-[11px] text-slate-400 uppercase tracking-wide mt-0.5">{{ $roleLabel }}</p>
        </div>

        <a href="{{ $homeHref }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-medium
            {{ $activeDashboard ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60' }}">
            Dashboard
        </a>

        @if ($isStruktural)
            <a href="{{ route('admin.officials.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-medium
                {{ $activeOfficials ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60' }}">
                Pengurus
            </a>

            <a href="{{ route('admin.dojos.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-medium
                {{ $activeDojos ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60' }}">
                Dojo
            </a>
        @endif

        @if ($isAdmin)
            <a href="{{ route('admin.exams.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-medium
                {{ $activeExams ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800/60' }}">
                Ujian
            </a>
        @endif

        @if ($isSistemAdmin)
            <div class="pt-2 border-t border-slate-800 mt-2">
                <div class="px-3 py-1.5 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                    Sistem
                </div>
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium
                    {{ $activeUsers ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60' }}">
                    Manajemen User
                </a>
                <a href="{{ route('admin.exams.fees.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium
                    {{ $activeExamFees ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60' }}">
                    Biaya Ujian
                </a>
                <a href="{{ route('admin.fees.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium
                    {{ $activeFees ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60' }}">
                    Konfigurasi Iuran
                </a>
                @if ($hasRole('pb'))
                    <a href="{{ route('admin.provinces.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium
                        {{ $activeProv ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60' }}">
                        Wilayah / Pengprov
                    </a>
                @endif
            </div>
        @endif

        <div class="pt-3 border-t border-slate-800 mt-2 flex flex-col gap-1">
            <a href="{{ route('profile.edit') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:bg-slate-800/60">
                Profil Saya
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full text-left flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-red-400 hover:bg-red-500/10">
                    Keluar Sistem
                </button>
            </form>
        </div>
    </div>
</nav>
