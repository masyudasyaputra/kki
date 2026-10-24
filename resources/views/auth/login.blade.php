<x-guest-layout>
    <div class="auth-heading auth-heading-with-logo"><img src="{{ asset('images/logo-shokaido.webp') }}" alt="Logo Shokaido" width="64" height="64" class="auth-heading-logo"><div><span class="auth-eyebrow">Selamat datang kembali</span><h1>Masuk ke akun Anda</h1><p>Kelola keanggotaan dan administrasi Shokaido.</p></div></div>
    <x-auth-session-status class="auth-status" :status="session('status')" />
    @if ($errors->has('google'))<p role="alert" class="auth-error-summary mb-3">{{ $errors->first('google') }}</p>@endif
    <x-google-auth-button />
    <div class="auth-divider">atau masuk dengan akun</div>
    <form method="POST" action="{{ route('login', (request()->boolean('_auth_modal') || request()->header('Sec-Fetch-Dest') === 'iframe') ? ['_auth_modal' => 1] : []) }}" class="auth-form">
        @csrf
        <div><x-input-label for="login" value="Email atau WhatsApp" /><x-text-input id="login" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" placeholder="Email atau nomor WhatsApp" /><x-input-error :messages="$errors->get('login')" class="mt-1" /></div>
        <div x-data="{ visible: false }">
            <div class="mb-1.5 flex items-center justify-between gap-2"><label for="password" class="auth-label">Kata sandi</label>@if (Route::has('password.request'))<a href="{{ route('password.request') }}" class="auth-link text-xs">Lupa kata sandi?</a>@endif</div>
            <div class="auth-password"><x-text-input id="password" type="password" x-bind:type="visible ? 'text' : 'password'" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" /><button type="button" @click="visible = !visible" :aria-pressed="visible.toString()" aria-controls="password" x-text="visible ? 'Sembunyikan' : 'Lihat'">Lihat</button></div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <label for="remember_me" class="flex items-center gap-2 text-xs text-slate-600"><input id="remember_me" type="checkbox" name="remember" @checked(old('remember')) class="rounded border-slate-300 text-red-600 focus:ring-red-500">Ingat saya</label>
        <button type="submit" class="btn btn-primary w-full">Masuk</button>
    </form>
    <p class="auth-switch">Belum menjadi anggota? <a href="{{ route('register') }}" class="auth-link">Daftar anggota</a></p>
</x-guest-layout>
