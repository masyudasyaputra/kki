<x-app-layout>
    <x-slot name="header"><div><h1 class="page-title">Detail Anggota</h1><p class="page-subtitle">Profil dan informasi keanggotaan KKI</p></div></x-slot>
    <div class="member-detail-page" x-data="{ tab: 'personal' }">
        <nav class="member-detail-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('admin.members.index') }}">Data Anggota</a><span aria-hidden="true">/</span><span aria-current="page">Detail anggota</span></nav>
        <section class="member-detail-hero" aria-label="Ringkasan anggota">
            <x-member-avatar :member="$member" />
            <div class="member-detail-identity"><p class="member-detail-eyebrow">Kushin Ryu M Karate-Do Indonesia</p><h2>{{ $member->name }}</h2><span class="member-detail-number">{{ $member->member_number }}</span>
                <div class="member-detail-badges"><span class="{{ $member->is_active ? 'is-active' : '' }}">Akun {{ $member->is_active ? 'aktif' : 'nonaktif' }}</span><x-member-verification-status :member="$member" /></div>
            </div>
            <div class="member-detail-actions"><a href="{{ route('admin.members.index') }}" class="btn btn-secondary">Kembali ke daftar</a>@if(auth()->user()->hasRole('super_admin'))<a href="{{ route('admin.users.edit', $member) }}" class="btn btn-primary">Edit anggota</a>@endif</div>
        </section>
        <div class="member-detail-tabs" role="tablist" aria-label="Informasi anggota">
            @foreach(['personal' => 'Data pribadi', 'membership' => 'Keanggotaan', 'card' => 'Kartu anggota', 'privacy' => 'Status & privasi'] as $key => $label)
                <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" :aria-selected="(tab === '{{ $key }}').toString()" @click="tab = '{{ $key }}'">{{ $label }}</button>
            @endforeach
        </div>
        <div id="panel-personal" role="tabpanel" aria-labelledby="tab-personal" x-show="tab === 'personal'" class="member-detail-grid">
            <div class="member-detail-column"><x-member-detail-section title="Profil anggota" :items="['Nama lengkap' => $member->name, 'Nomor anggota' => $member->member_number, 'Orang tua / wali' => $member->parent_name]" />
            <x-member-detail-section title="Informasi latihan" :items="['Dojo' => $member->dojo?->name, 'Tingkat sabuk' => $member->beltLevel?->display_name, 'Pelatih awal' => $member->first_coach]" /></div>
            <div class="member-detail-column"><x-member-detail-section title="Alamat dan kontak" :items="['WhatsApp' => $member->whatsapp, 'Email' => $member->email, 'Alamat' => $member->address, 'Kecamatan' => $member->district, 'Kabupaten / kota' => $member->city?->name, 'Provinsi' => $member->province?->name]" /></div>
        </div>
        <div id="panel-membership" role="tabpanel" aria-labelledby="tab-membership" x-show="tab === 'membership'" x-cloak class="member-detail-grid">
            <x-member-detail-section title="Data keanggotaan" :items="['Nomor anggota' => $member->member_number, 'Dojo' => $member->dojo?->name, 'Tingkat sabuk' => $member->beltLevel?->display_name, 'Pelatih awal' => $member->first_coach, 'Tanggal terdaftar' => $member->created_at?->format('d/m/Y')]" />
            <x-member-detail-section title="Wilayah keanggotaan" :items="['Provinsi' => $member->province?->name, 'Kabupaten / kota' => $member->city?->name, 'Kecamatan' => $member->district]" />
        </div>
        <div id="panel-card" role="tabpanel" aria-labelledby="tab-card" x-show="tab === 'card'" x-cloak class="member-detail-card-panel">
            @include('member.card', ['user' => $member, 'showMemberDetails' => false])
        </div>
        <div id="panel-privacy" role="tabpanel" aria-labelledby="tab-privacy" x-show="tab === 'privacy'" x-cloak class="member-detail-grid">
            <x-member-detail-section title="Status akun dan verifikasi" :items="['Status akun' => $member->is_active ? 'Aktif' : 'Nonaktif', 'Verifikasi anggota' => ['approved' => 'Terverifikasi', 'revision' => 'Perlu perbaikan', 'pending' => 'Menunggu verifikasi'][$member->verification_status ?? 'pending'] ?? 'Menunggu verifikasi', 'Email terverifikasi' => $member->email_verified_at ? 'Sudah' : 'Belum']" />
            <section class="member-detail-section"><h2>Publikasi direktori</h2><dl><div><dt>Persetujuan publikasi</dt><dd>{{ $member->public_directory_consent ? 'Diaktifkan' : 'Tidak diaktifkan' }}</dd></div><div><dt>Tampil di direktori</dt><dd>{{ $member->verification_status === 'approved' && $member->public_directory_consent ? 'Ya' : 'Belum memenuhi syarat' }}</dd></div></dl><p class="member-detail-note">Profil publik tampil jika anggota terverifikasi dan publikasi diaktifkan. Kontak dan alamat rinci hanya untuk pengurus sesuai kewenangan.</p></section>
        </div>
    </div>
</x-app-layout>
