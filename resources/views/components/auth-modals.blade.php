@guest
    @php
        $provinces = \App\Models\Province::orderBy('name')->get();
        $beltLevels = \App\Models\BeltLevel::active()->orderBy('order')->get();
        $googleIdentity = session('google_signup');
        if (!is_array($googleIdentity) || ($googleIdentity['expires'] ?? 0) < time()) $googleIdentity = null;
    @endphp
    @foreach(['login', 'register', 'forgot-password'] as $authMode)
        <template data-auth-form="{{ $authMode }}"><div class="auth-card auth-modal-card">@include('auth.partials.'.$authMode)</div></template>
    @endforeach
@endguest
