@props(['mode' => 'login'])
@if (config('services.google.client_id') && config('services.google.client_secret'))
    <a href="{{ route('google.redirect', ['mode' => $mode]) }}" target="_top" class="btn btn-secondary w-full">
        <span aria-hidden="true" class="text-base font-bold text-blue-600">G</span>
        {{ $mode === 'register' ? 'Daftar dengan Google' : 'Masuk dengan Google' }}
    </a>
@else
    <button type="button" class="btn btn-secondary w-full" disabled><span aria-hidden="true" class="text-base font-bold">G</span>{{ $mode === 'register' ? 'Daftar dengan Google' : 'Masuk dengan Google' }}</button>
    <p class="mt-1 text-center text-[11px] text-slate-500">Google belum tersedia. Gunakan email untuk melanjutkan.</p>
@endif
