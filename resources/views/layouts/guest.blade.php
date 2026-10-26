@php($authEmbedded = request()->boolean('_auth_modal') || request()->header('Sec-Fetch-Dest') === 'iframe')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'KKI OS') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-kki.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-authenticated="false" class="{{ $authEmbedded ? 'auth-embedded' : '' }} auth-page font-sans antialiased">
    <main class="auth-shell {{ request()->routeIs('register') ? 'auth-shell-wide' : '' }}">
        @unless($authEmbedded)
        <a href="{{ url('/') }}" class="auth-brand" aria-label="Beranda KKI">
            <img src="{{ asset('images/logo-kki.png') }}" alt="Logo KKI" width="48" height="48">
            <span><strong>KKI<span class="text-red-600">.OS</span></strong><small>Kushin Ryu M Karate-Do Indonesia</small></span>
        </a>
        @endunless
        <div class="auth-card">{{ $slot }}</div>
        @unless($authEmbedded)<footer class="auth-footer">&copy; {{ date('Y') }} KKI Indonesia <span aria-hidden="true">/</span> <a href="{{ url('/') }}">Kembali ke beranda</a></footer>@endunless
    </main>
</body>
</html>
