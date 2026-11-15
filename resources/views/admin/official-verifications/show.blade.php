<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Verifikasi Pengurus</h1><p class="page-subtitle">{{ $user->name }} · {{ $user->email }} · {{ $user->whatsapp }}</p></x-slot>
    <div class="mx-auto max-w-4xl px-6 pt-6"><a href="{{ route('admin.official-verifications.index') }}" class="btn btn-secondary">Kembali ke verifikasi pengurus</a>
        @if(session('success'))<p role="status" class="mt-4 text-sm text-emerald-800">{{ session('success') }}</p>@endif
    </div>
    @include('admin.users.appointment')
</x-app-layout>
