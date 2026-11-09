@props(['variant' => 'admin'])

<div id="kki-page-loader" class="kki-page-loader {{ $variant === 'public' ? 'kki-page-loader-public' : '' }}" role="status" aria-live="polite" aria-label="Memuat halaman" aria-hidden="true" hidden>
    <div class="kki-loader-content">
        <div class="kki-loader-emblem" aria-hidden="true">
            <span class="kki-loader-ring"></span>
            <img src="{{ asset('images/logo-kki.png') }}" alt="" width="72" height="72" fetchpriority="high">
        </div>
        <p class="kki-loader-title">PP KKI</p>
        <p class="kki-loader-message">{{ $variant === 'public' ? 'Menyiapkan halaman KKI...' : 'Memuat halaman...' }}</p>
        <div class="kki-loader-track" aria-hidden="true"><span></span></div>
        <span class="sr-only">Mohon tunggu sebentar</span>
    </div>
</div>
