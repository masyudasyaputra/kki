<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#141717">
    <title>@yield('title') | PP KKI</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-kki.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=barlow:400,500,600,700,800|barlow-condensed:600,700,800,900" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pp" x-data="{ menuOpen: false }">
<x-page-loader variant="public" />
<a href="#konten-utama" class="pp-skip">Lewati ke konten</a>
<header class="pp-header">
    <div class="pp-wrap pp-header-inner">
        <a href="{{ url('/') }}" class="pp-brand" aria-label="PP KKI - Beranda">
            <img src="{{ asset('images/logo-kki-dark.png') }}" alt="Logo KKI" width="46" height="46">
            <span><strong>PP KKI</strong><small>Pengurus Pusat</small></span>
        </a>
        <nav class="pp-nav" aria-label="Navigasi utama">
            <a href="{{ route('public.agenda') }}" @if(request()->routeIs('public.agenda')) aria-current="page" @endif>Agenda UKT</a>
            <a href="{{ route('public.dojos') }}" @if(request()->routeIs('public.dojos')) aria-current="page" @endif>Direktori dojo</a>
            <a href="{{ route('public.members') }}" @if(request()->routeIs('public.members')) aria-current="page" @endif>Direktori anggota</a>
            <a href="{{ url('/') }}#layanan">Layanan</a>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? 'Dashboard' : 'Masuk portal' }}</a>
            @guest<a href="{{ route('register') }}" class="pp-btn pp-red">Daftar anggota <span aria-hidden="true">↗</span></a>@endguest
        </nav>
        <button class="pp-toggle" type="button" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="pp-mobile" :aria-label="menuOpen ? 'Tutup navigasi' : 'Buka navigasi'">
            <span x-text="menuOpen ? '×' : '☰'" aria-hidden="true">☰</span>
        </button>
    </div>
    <nav id="pp-mobile" class="pp-mobile" x-cloak x-show="menuOpen" @click.outside="menuOpen = false" @keydown.escape.window="menuOpen = false" aria-label="Navigasi seluler">
        <a href="{{ route('public.agenda') }}" @click="menuOpen = false">Agenda UKT</a>
        <a href="{{ route('public.dojos') }}" @click="menuOpen = false">Direktori dojo</a>
        <a href="{{ route('public.members') }}" @click="menuOpen = false">Direktori anggota</a>
        <a href="{{ url('/') }}#layanan" @click="menuOpen = false">Layanan</a>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" @click="menuOpen = false">{{ auth()->check() ? 'Dashboard' : 'Masuk portal' }}</a>
        @guest<a href="{{ route('register') }}" @click="menuOpen = false">Daftar anggota ↗</a>@endguest
    </nav>
</header>
<main id="konten-utama">@yield('content')</main>
<footer class="pp-footer">
    <div class="pp-wrap">
        <div class="pp-footer-top">
            <a href="{{ url('/') }}" class="pp-brand"><img src="{{ asset('images/logo-kki-dark.png') }}" alt="Logo KKI" width="46" height="46" loading="lazy"><span><strong>PP KKI</strong><small>Pengurus Pusat</small></span></a>
            <nav class="pp-footer-links" aria-label="Navigasi footer">
                <a href="{{ route('public.agenda') }}">Agenda UKT</a>
                <a href="{{ route('public.dojos') }}">Direktori dojo</a>
                <a href="{{ route('public.members') }}">Direktori anggota</a>
                <a href="{{ url('/') }}#tentang">Tentang KKI</a>
            </nav>
        </div>
        <div class="pp-footer-bottom"><p>&copy; {{ date('Y') }} Pengurus Pusat KKI. Kushin Ryu M Karate-Do Indonesia.</p><p>Karakter. Disiplin. Persaudaraan.</p></div>
    </div>
</footer>
<x-auth-modals />
</body>
</html>
