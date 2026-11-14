<x-app-layout>
    @php
        $isMember = $user->role === 'member';
        $profileFields = ['name' => 'Nama lengkap', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'province_id' => 'Provinsi', 'city_id' => 'Kabupaten / kota', 'district' => 'Kecamatan', 'address' => 'Alamat', 'dojo_id' => 'Dojo', 'belt_level_id' => 'Tingkat sabuk', 'first_coach' => 'Pelatih awal'];
        $missingFields = collect($profileFields)->filter(fn ($label, $field) => blank($user->$field));
        $completion = (int) round((count($profileFields) - $missingFields->count()) / count($profileFields) * 100);
        $verification = ['pending' => 'Menunggu pemeriksaan', 'approved' => 'Terverifikasi', 'revision' => 'Perlu perbaikan'][$user->verification_status ?? 'pending'] ?? 'Menunggu pemeriksaan';
    @endphp
    <x-slot name="header"><h1 class="page-title">{{ $isMember ? 'Profil Saya' : 'Profil Akun' }}</h1><p class="page-subtitle">Kelola identitas, kontak, dan keamanan akun Anda.</p></x-slot>
    <div class="profile-workspace mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <section class="profile-summary">
            <div class="profile-avatar overflow-hidden">@if($user->photo)<img src="{{ url('storage/'.$user->photo) }}" alt="Pasfoto {{ $user->name }}" class="h-full w-full object-cover">@else{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}@endif</div>
            <div class="min-w-0 flex-1"><p class="profile-kicker">{{ $isMember ? 'Karateka KKI' : 'Akun KKI' }}</p><h2 class="break-words text-2xl font-bold">{{ $user->name }}</h2><p class="mt-1 break-all text-sm text-slate-500">{{ $user->email }}</p><div class="mt-3 flex flex-wrap gap-2">@foreach($user->allRoles() as $role)<span class="profile-chip">{{ \App\Services\RoleAccess::POSITIONS[$role] ?? \App\Services\RoleAccess::PERMISSIONS[$role] ?? str_replace('_', ' ', $role) }}</span>@endforeach @if($isMember)<x-verification-status :status="$user->verification_status" />@endif<span class="profile-chip {{ $user->is_active ? 'profile-chip-success' : '' }}">{{ $user->is_active ? 'Anggota aktif' : 'Belum aktif' }}</span></div></div>
            @if($isMember)<a href="{{ route('member.card') }}" class="btn btn-secondary"><x-sidebar-icon name="membership" />Kartu anggota</a>@endif
        </section>
        @if(session('status') === 'profile-updated')<div class="profile-feedback" role="status">Profil berhasil disimpan. @if($isMember && $user->verification_status === 'pending')Data sabuk, wilayah, atau dojo yang berubah menunggu verifikasi pengurus provinsi atau Pusat.@endif</div>@endif
        <nav class="profile-section-nav" aria-label="Bagian profil"><a href="#identitas">Identitas & kontak</a><a href="#wilayah">Wilayah & dojo</a><a href="#keamanan">Keamanan akun</a></nav>
        <div class="grid items-start gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <div class="profile-panel">@include('profile.partials.update-profile-information-form')</div>
                <section id="keamanan" class="profile-panel profile-anchor">@include('profile.partials.update-password-form')</section>
                <section class="profile-panel"><div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="profile-section-title">Akun Google</h2><p class="mt-2 text-sm text-slate-500">{{ $user->google_id ? 'Akun Google sudah terhubung untuk masuk ke KKI.' : 'Hubungkan akun Google dengan email yang sama untuk pilihan masuk tambahan.' }}</p></div>@if($user->google_id)<span class="profile-chip profile-chip-success">Terhubung</span>@elseif(config('services.google.client_id') && config('services.google.client_secret'))<a href="{{ route('google.connect') }}" class="btn btn-secondary">Hubungkan Google</a>@else<span class="profile-chip">Belum tersedia</span>@endif</div><x-input-error :messages="$errors->get('google')" class="mt-2" />@if(session('status') === 'google-connected')<p role="status" class="mt-3 text-sm text-emerald-700">Google berhasil dihubungkan.</p>@endif</section>
                <details class="profile-panel profile-danger" @if($errors->userDeletion->isNotEmpty()) open @endif><summary class="cursor-pointer font-semibold text-red-800">Penghapusan akun</summary><div class="mt-5">@include('profile.partials.delete-user-form')</div></details>
            </div>
            <aside class="space-y-6" aria-label="Ringkasan profil">
                <section class="profile-panel"><h2 class="profile-section-title">Kelengkapan profil</h2><div class="my-4 flex items-center justify-between text-sm"><span class="text-slate-500">Data terisi</span><strong>{{ $completion }}%</strong></div><progress class="profile-progress" max="100" value="{{ $completion }}" aria-label="Kelengkapan profil">{{ $completion }}%</progress>@if($missingFields->isNotEmpty())<p class="mt-4 text-sm leading-6 text-slate-500">Lengkapi {{ $missingFields->values()->implode(', ') }}.</p>@else<p class="mt-4 text-sm text-emerald-700">Data profil sudah lengkap.</p>@endif<p class="mt-3 text-xs leading-5 text-slate-400">Indikator kelengkapan membantu Anda meninjau data; bukan status verifikasi.</p></section>
                <section class="profile-panel"><h2 class="profile-section-title">{{ $isMember ? 'Keanggotaan karateka' : 'Data organisasi' }}</h2><dl class="profile-facts">@foreach(['Nomor anggota' => $user->member_number, 'Tingkat sabuk' => $user->beltLevel?->display_name, 'Dojo' => $user->dojo?->name, 'Provinsi' => $user->province?->name, 'Kabupaten / kota' => $user->city?->name, 'Kecamatan' => $user->district] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ filled($value) ? $value : 'Belum diisi' }}</dd></div>@endforeach @if($isMember)<div><dt>Verifikasi pendaftaran</dt><dd>{{ $verification }}</dd></div>@endif</dl>@if($isMember)<p class="mt-5 rounded-xl bg-amber-50 p-3 text-xs leading-5 text-amber-900">Perubahan sabuk/KYU/DAN, provinsi, kabupaten/kota, kecamatan, atau dojo memerlukan verifikasi pengurus provinsi atau Pusat. Data pribadi dan kontak dapat diubah tanpa verifikasi ulang.</p>@endif</section>
                <p class="px-1 text-xs text-slate-400">Terakhir disimpan {{ $user->updated_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB.</p>
            </aside>
        </div>
    </div>
</x-app-layout>
