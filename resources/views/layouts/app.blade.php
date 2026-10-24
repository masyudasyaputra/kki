<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SHOKAIDO OS') }}</title>

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

<body class="font-sans antialiased text-slate-800 bg-[#f8fafc] min-h-full flex flex-col">
    <div class="min-h-screen flex flex-col">

        @include('layouts.navigation')

        {{-- Header Section --}}
        @isset($header)
            <header class="bg-white border-b border-slate-200/80 sticky top-16 z-30 transition-all duration-200">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                        {{-- Sisi Kiri: Judul Halaman --}}
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-1.5 h-7 bg-red-600 rounded-full flex-shrink-0"></div>
                            <div class="min-w-0">
                                <div class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 leading-snug">
                                    {{ $header }}
                                </div>
                            </div>
                        </div>

                        {{-- Sisi Kanan: Otoritas / Badge Role --}}
                        <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                            <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 bg-slate-50 border border-slate-200/90 rounded-full text-xs text-slate-700 shadow-sm">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-30"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span class="text-[11px] font-semibold text-slate-700 uppercase tracking-wide">
                                    {{ str_replace('_', ' ', $role ?? Auth::user()->role) }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </header>
        @endisset

        {{-- Main Content --}}
        <main class="flex-grow">
            {{ $slot }}
        </main>

        {{-- Clean Admin Footer --}}
        <footer class="bg-white border-t border-slate-200/80 py-4 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 tracking-tight">SHOKAIDO<span class="text-red-600">.OS</span></span>
                        <span class="text-slate-300">•</span>
                        <span>Sistem Administrasi Shotokan Kandaga Indonesia</span>
                    </div>
                    <div class="flex items-center gap-4 text-[11px]">
                        <span>&copy; {{ date('Y') }} Shokaido. All rights reserved.</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>
