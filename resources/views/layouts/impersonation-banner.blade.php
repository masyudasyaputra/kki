@if(session()->has('impersonation'))
    <div class="impersonation-banner" role="status"><div><strong>Login sebagai {{ auth()->user()->name }}</strong><span>{{ auth()->user()->email }}</span></div><form method="post" action="{{ route('impersonation.stop') }}" target="_top">@csrf<button type="submit">← Kembali ke {{ session('impersonation.actor_name', 'akun admin') }}</button></form></div>
@endif
