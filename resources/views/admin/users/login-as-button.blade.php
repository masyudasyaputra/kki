@if(!session()->has('impersonation') && app(\App\Services\UserImpersonation::class)->canStart(auth()->user(), $user))
    <form method="post" action="{{ route('admin.users.login-as', $user) }}">
        @csrf
        <button type="submit" class="login-as-button" title="Masuk sebagai {{ $user->name }}" aria-label="Login sebagai {{ $user->name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 4h5v16h-5M3 12h12m-4-4 4 4-4 4" /></svg><span>Login as</span></button>
    </form>
@endif
