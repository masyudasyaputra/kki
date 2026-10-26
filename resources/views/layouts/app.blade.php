@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $embeddedForm = request()->boolean('_form_modal') || request()->header('Sec-Fetch-Dest') === 'iframe';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-kki.png') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PP KKI') }}</title>

    {{-- Font: Inter --}}
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            font-feature-settings: "cv02" 1, "cv03" 1, "cv04" 1, "cv11" 1;
        }

        /* Modern subtle scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body data-authenticated="{{ auth()->check() ? 'true' : 'false' }}" data-modal-status="{{ $errors->any() || session('error') ? 'error' : (session('success') ? 'saved' : 'ready') }}" data-modal-message="{{ session('success', 'Data berhasil disimpan.') }}" class="{{ $embeddedForm ? 'form-modal-body' : '' }} font-sans antialiased text-slate-800 bg-[#f8fafc] min-h-full flex flex-col backend" x-data="{ sidebarOpen: false, desktopSidebar: window.innerWidth >= 1024 }" @resize.window="desktopSidebar = window.innerWidth >= 1024" :class="{ 'sidebar-open': sidebarOpen }">
    @if ($embeddedForm)
        <main class="min-w-0">
            @if ($errors->any() || session('error'))
                <div role="alert" class="m-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    @if (session('error'))<p>{{ session('error') }}</p>@endif
                </div>
            @endif
            {{ $slot }}
        </main>
    @else
    <div class="min-h-screen flex flex-col lg:pl-[272px]">

        @include('layouts.navigation')

        <header class="page-header">
            <div class="mx-auto flex min-h-[72px] max-w-7xl items-center justify-between gap-3 py-3 pl-16 pr-4 sm:gap-5 sm:pr-6 lg:px-8">
                <div class="page-header-content min-w-0 flex-1">
                    @isset($header)
                        {{ $header }}
                    @endisset
                </div>
                <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                <a href="{{ route('profile.edit') }}" class="flex min-w-0 items-center gap-2 rounded-xl p-1 text-slate-700 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Profil akun {{ auth()->user()->name }}">
                    <span class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-sm font-semibold text-red-700 sm:flex">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div class="min-w-0 text-right sm:text-left">
                        <p class="max-w-[64px] truncate text-xs font-semibold text-slate-900 sm:max-w-[220px] sm:text-sm">{{ auth()->user()->name }}</p>
                        <p class="mt-0.5 text-[10px] uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', auth()->user()->role ?? 'member') }}</p>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0 border-l border-slate-200 pl-2 sm:pl-4">
                    @csrf
                    <button type="submit" class="btn btn-danger" aria-label="Logout">
                        <x-sidebar-icon name="logout" /><span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
                </div>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="flex-grow">
            {{ $slot }}
        </main>

        {{-- Clean Admin Footer --}}
        <footer class="bg-white border-t border-slate-200/80 py-4 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 tracking-tight">PP KKI</span>
                        <span class="text-slate-300">•</span>
                        <span>Sistem Administrasi PP KKI</span>
                    </div>
                    <div class="flex items-center gap-4 text-[11px]">
                        <span>&copy; {{ date('Y') }} PP KKI. All rights reserved.</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    @endif
</body>

</html>
