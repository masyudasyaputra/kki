<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                    {{ $title }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Ringkasan performa dan data keanggotaan • {{ now()->format('d M Y') }}
                </p>
            </div>
        </div>
    </x-slot>

    @php
        // ==========================================================
        // Helpers
        // ==========================================================
        if (!function_exists('beltBadgeClass')) {
            function beltBadgeClass($beltName)
            {
                $n = strtolower($beltName ?? '');
                if (str_contains($n, 'putih')) {
                    return 'bg-slate-100 text-slate-700 border-slate-200';
                }
                if (str_contains($n, 'kuning muda')) {
                    return 'bg-amber-50 text-amber-800 border-amber-200';
                }
                if (str_contains($n, 'kuning')) {
                    return 'bg-amber-100 text-amber-900 border-amber-300';
                }
                if (str_contains($n, 'orange')) {
                    return 'bg-orange-50 text-orange-700 border-orange-200';
                }
                if (str_contains($n, 'hijau')) {
                    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                }
                if (str_contains($n, 'biru')) {
                    return 'bg-blue-50 text-blue-700 border-blue-200';
                }
                if (str_contains($n, 'ungu')) {
                    return 'bg-purple-50 text-purple-700 border-purple-200';
                }
                if (str_contains($n, 'cokelat') || str_contains($n, 'coklat')) {
                    return 'bg-amber-900/10 text-amber-900 border-amber-900/20';
                }
                if (str_contains($n, 'hitam')) {
                    return 'bg-slate-900 text-white border-slate-900';
                }
                return 'bg-slate-100 text-slate-700 border-slate-200';
            }
        }

        if (!function_exists('beltHexColor')) {
            function beltHexColor($beltName)
            {
                $n = strtolower($beltName ?? '');
                if (str_contains($n, 'putih')) {
                    return '#E2E8F0';
                }
                if (str_contains($n, 'kuning muda')) {
                    return '#FDE68A';
                }
                if (str_contains($n, 'kuning')) {
                    return '#F59E0B';
                }
                if (str_contains($n, 'orange')) {
                    return '#F97316';
                }
                if (str_contains($n, 'hijau')) {
                    return '#10B981';
                }
                if (str_contains($n, 'biru')) {
                    return '#3B82F6';
                }
                if (str_contains($n, 'ungu')) {
                    return '#8B5CF6';
                }
                if (str_contains($n, 'cokelat') || str_contains($n, 'coklat')) {
                    return '#92400E';
                }
                if (str_contains($n, 'hitam')) {
                    return '#0F172A';
                }
                return '#94A3B8';
            }
        }

        // ==========================================================
        // Role & Access
        // ==========================================================
        $currentRole = $role ?? auth()->user()->role;
        $isPB = $currentRole === 'pb';
        $isPengprov = $currentRole === 'pengprov';
        $isPengcab = $currentRole === 'pengcab';
        $isAdminDojo = $currentRole === 'admin_dojo';

        $beltOptions = $beltLevels ?? collect();
        $dojoOptions = $dojos ?? collect();
        $provinceOptions = $provinces ?? collect();
        $cityOptions = $cities ?? collect();

        // Donut Data
        $beltCounts =
            $beltCounts ??
            $members
                ->getCollection()
                ->groupBy(fn($m) => $m->beltLevel->name ?? 'Putih')
                ->map(fn($g) => $g->count())
                ->toArray();

        if ($beltOptions && $beltOptions->count()) {
            $orderMap = $beltOptions->pluck('order', 'name')->toArray();
            uksort($beltCounts, function ($a, $b) use ($orderMap) {
                $oa = $orderMap[$a] ?? 999;
                $ob = $orderMap[$b] ?? 999;
                return $oa <=> $ob;
            });
        }

        $beltLabels = array_keys($beltCounts);
        $beltValues = array_values($beltCounts);
        $beltColors = array_map(fn($name) => beltHexColor($name), $beltLabels);
        $totalBeltSlice = array_sum($beltValues) ?: 0;

        // Stats cards definition
        $statCards = [
            [
                'label' => 'Total Anggota',
                'value' => number_format($stats['total_members'] ?? 0),
                'icon_bg' => 'bg-blue-50 text-blue-600',
                'dot' => 'bg-blue-500',
                'footer' => 'Database Terdaftar',
                'icon' =>
                    'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            ],
            [
                'label' => 'Anggota Aktif',
                'value' => number_format($stats['active_members'] ?? 0),
                'icon_bg' => 'bg-emerald-50 text-emerald-600',
                'dot' => 'bg-emerald-500',
                'footer' => 'Terverifikasi Aktif',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Perlu Konfirmasi',
                'value' => number_format($stats['pending_payments'] ?? 0),
                'icon_bg' => 'bg-amber-50 text-amber-600',
                'dot' => 'bg-amber-500',
                'footer' => 'Menunggu Pembayaran',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Total Kas Masuk',
                'value' => 'Rp ' . number_format($stats['total_revenue'] ?? 0, 0, ',', '.'),
                'icon_bg' => 'bg-red-50 text-red-600',
                'dot' => 'bg-red-500',
                'footer' => 'Total Pembayaran',
                'icon' =>
                    'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ];

        $rqProvince = request('province_id');
        $rqCity = request('city_id');
        $rqDojo = request('dojo_id');
        $rqBelt = request('belt_level_id');
        $rqStatus = request('status');
        $rqSearch = request('search');
    @endphp

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- NOTIFIKASI STATUS DOJO (Khusus Admin Dojo) --}}
            @if ($isAdminDojo)
                @if (!Auth::user()->dojo_id)
                    <div class="bg-white rounded-xl p-5 border border-red-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="bg-red-100 text-red-700 p-2.5 rounded-lg shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Akun Belum Terhubung ke Dojo</h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Akun Anda belum memiliki penugasan Dojo resmi. Silakan hubungi Administrator Pengurus Besar atau Pengcab.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="h-10 w-10 bg-slate-100 text-slate-700 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Dojo Yang Dikelola</p>
                                <h4 class="text-sm font-bold text-slate-900 truncate">
                                    {{ Auth::user()->dojo->name ?? 'Dojo Terdaftar' }}
                                </h4>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200/60 shrink-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif
                        </span>
                    </div>
                @endif
            @endif

            {{-- TOP GRID: STATS & DONUT --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                {{-- Stat Cards (7 Cols) --}}
                <div class="lg:col-span-7">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($statCards as $card)
                            <div class="bg-white rounded-xl p-5 border border-slate-200/80 shadow-sm hover:border-slate-300 transition-all flex flex-col justify-between">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-medium text-slate-500">{{ $card['label'] }}</p>
                                        <h3 class="mt-1.5 text-2xl font-bold tracking-tight text-slate-900">
                                            {{ $card['value'] }}
                                        </h3>
                                    </div>
                                    <div class="p-2.5 rounded-lg {{ $card['icon_bg'] }} shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs text-slate-500">
                                    <span class="h-2 w-2 rounded-full {{ $card['dot'] }} mr-2"></span>
                                    {{ $card['footer'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Donut Chart Card (5 Cols) --}}
                <div class="lg:col-span-5">
                    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-5 sm:p-6 h-full flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Distribusi Tingkat Sabuk</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Komposisi anggota per level</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    Total: {{ number_format($totalBeltSlice) }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                                <div class="sm:col-span-5">
                                    <div class="relative w-full max-w-[200px] mx-auto aspect-square flex items-center justify-center">
                                        <canvas id="beltDonut" class="w-full h-full"></canvas>
                                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-xl font-bold text-slate-900 leading-tight">
                                                {{ number_format($totalBeltSlice) }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 uppercase font-medium">Anggota</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="sm:col-span-7">
                                    <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
                                        @forelse ($beltCounts as $beltName => $count)
                                            <div class="bg-slate-50/80 border border-slate-100 rounded-lg p-2 flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ beltHexColor($beltName) }}"></span>
                                                    <span class="truncate font-medium text-slate-700 text-[11px]">{{ $beltName }}</span>
                                                </div>
                                                <span class="font-semibold text-slate-900 tabular-nums ml-1">{{ $count }}</span>
                                            </div>
                                        @empty
                                            <div class="col-span-2 py-6 text-center text-xs text-slate-400">
                                                Belum ada data sabuk
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="mt-3 text-[11px] text-slate-400 italic">
                            * Grafik menampilkan distribusi sabuk dari data yang tampil.
                        </p>
                    </div>
                </div>
            </div>

            {{-- MAIN SECTION: TABLE & FILTERS --}}
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                {{-- Card Header & Filter Bar --}}
                <div class="p-5 sm:p-6 border-b border-slate-200/80 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Daftar Anggota</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Menampilkan {{ $members->total() }} anggota terdaftar dalam sistem
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.members.create') }}"
                                class="inline-flex items-center gap-2 px-3.5 py-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white rounded-lg text-xs font-semibold shadow-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Anggota
                            </a>
                        </div>
                    </div>

                    {{-- Filter Toolbar --}}
                    <form id="filterForm" method="GET" action="{{ route('admin.dashboard') }}"
                        class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3">

                        {{-- Search Input --}}
                        <div class="md:col-span-3">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input id="searchInput" type="text" name="search" value="{{ $rqSearch }}"
                                    class="w-full h-10 pl-9 pr-3 bg-slate-50/70 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all"
                                    placeholder="Cari nama anggota...">
                            </div>
                        </div>

                        {{-- Status Filter --}}
                        <div class="md:col-span-2">
                            <select id="statusSelect" name="status"
                                class="w-full h-10 px-3 bg-slate-50/70 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                                <option value="">Semua Status</option>
                                <option value="active" {{ $rqStatus === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ $rqStatus === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                            </select>
                        </div>

                        {{-- Sabuk Filter --}}
                        <div class="md:col-span-2">
                            <select id="beltSelect" name="belt_level_id"
                                class="w-full h-10 px-3 bg-slate-50/70 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                                <option value="">Semua Sabuk</option>
                                @foreach ($beltOptions as $belt)
                                    <option value="{{ $belt->id }}" {{ (string) $rqBelt === (string) $belt->id ? 'selected' : '' }}>
                                        {{ $belt->name }} {{ $belt->kyu_dan ? "({$belt->kyu_dan})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($isPB)
                            {{-- Provinsi (PB) --}}
                            <div class="md:col-span-2">
                                <select id="provinceSelect" name="province_id"
                                    class="w-full h-10 px-3 bg-slate-50/70 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                                    <option value="">Semua Provinsi</option>
                                    @foreach ($provinceOptions as $p)
                                        <option value="{{ $p->id }}" {{ (string) $rqProvince === (string) $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Kota (PB) --}}
                            <div class="md:col-span-2">
                                <select id="citySelect" name="city_id"
                                    class="w-full h-10 px-3 bg-slate-50/70 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all"
                                    {{ empty($rqProvince) ? 'disabled' : '' }}>
                                    <option value="">Semua Kota</option>
                                    @foreach ($cityOptions as $c)
                                        <option value="{{ $c->id }}" {{ (string) $rqCity === (string) $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Reset Button --}}
                        <div class="md:col-span-1">
                            <a href="{{ route('admin.dashboard') }}"
                                class="w-full h-10 inline-flex items-center justify-center bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                {{-- DESKTOP TABLE --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80">
                                <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Anggota</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Sabuk</th>
                                @if (!$isAdminDojo)
                                    <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Dojo / Wilayah</th>
                                @endif
                                <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($members as $member)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($member->name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-semibold text-slate-900 text-sm truncate">
                                                    {{ $member->name }}
                                                </div>
                                                <div class="text-[11px] text-slate-400 font-normal">
                                                    ID: #{{ str_pad($member->id, 5, '0', STR_PAD_LEFT) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ beltBadgeClass($member->beltLevel->name ?? 'Putih') }}">
                                                {{ $member->beltLevel->name ?? 'Putih' }}
                                            </span>
                                            @if (!empty($member->beltLevel->kyu_dan))
                                                <span class="text-xs text-slate-400">
                                                    {{ $member->beltLevel->kyu_dan }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    @if (!$isAdminDojo)
                                        <td class="px-6 py-4">
                                            <div class="text-xs font-medium text-slate-800 truncate">
                                                {{ $member->dojo->name ?? '-' }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                {{ $member->province->name ?? '' }}{{ $member->city?->name ? ' • ' . $member->city->name : '' }}
                                            </div>
                                        </td>
                                    @endif

                                    <td class="px-6 py-4 text-right">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border
                                            {{ $member->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-red-50 text-red-700 border-red-200/60' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $member->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                            {{ $member->is_active ? 'Aktif' : 'Non-Aktif' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        Tidak ada data anggota yang ditemukan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARDS VIEW --}}
                <div class="md:hidden p-4 space-y-3">
                    @forelse ($members as $member)
                        <div class="bg-white rounded-lg border border-slate-200/80 p-4 shadow-sm space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-xs flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-semibold text-slate-900 text-sm truncate">{{ $member->name }}</h4>
                                        <p class="text-[11px] text-slate-400">#{{ str_pad($member->id, 5, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium border
                                    {{ $member->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-red-50 text-red-700 border-red-200/60' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $member->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    {{ $member->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100">
                                <div>
                                    <span class="text-slate-400 text-[10px] block">Sabuk</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border mt-0.5 {{ beltBadgeClass($member->beltLevel->name ?? 'Putih') }}">
                                        {{ $member->beltLevel->name ?? 'Putih' }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-400 text-[10px] block">Dojo</span>
                                    <span class="font-medium text-slate-800 text-[11px] truncate block mt-0.5">{{ $member->dojo->name ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-slate-400">
                            Tidak ada data anggota
                        </div>
                    @endforelse
                </div>

                {{-- Pagination Footer --}}
                <div class="p-4 sm:p-5 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $members->appends(request()->query())->links() }}
                </div>
            </div>

        </div>
    </div>

    {{-- Chart.js Script --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            const labels = @json($beltLabels);
            const values = @json($beltValues);
            const colors = @json($beltColors);

            const el = document.getElementById('beltDonut');
            if (!el || !labels.length) return;

            new Chart(el, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '74%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const v = context.parsed ?? 0;
                                    const name = context.label || '';
                                    return `${name}: ${v.toLocaleString('id-ID')} anggota`;
                                }
                            }
                        }
                    }
                }
            });
        })();

        // Auto submit filter logic
        (function() {
            const form = document.getElementById('filterForm');
            if (!form) return;

            const search = document.getElementById('searchInput');
            const status = document.getElementById('statusSelect');
            const belt = document.getElementById('beltSelect');
            const province = document.getElementById('provinceSelect');
            const city = document.getElementById('citySelect');

            const debounce = (fn, delay = 500) => {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), delay);
                };
            };

            const submitNow = () => form.submit();
            const submitDebounced = debounce(submitNow, 500);

            if (search) {
                search.addEventListener('input', submitDebounced);
                search.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        submitNow();
                    }
                });
            }

            status?.addEventListener('change', submitNow);
            belt?.addEventListener('change', submitNow);

            province?.addEventListener('change', () => {
                if (city) city.value = '';
                submitNow();
            });

            city?.addEventListener('change', submitNow);
        })();
    </script>
</x-app-layout>
