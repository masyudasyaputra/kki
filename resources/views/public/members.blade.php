@extends('layouts.public')

@section('title', 'Direktori Anggota')

@section('content')
<section class="pp-page-hero">
    <div class="pp-wrap pp-page-hero-inner">
        <p class="pp-eyebrow">Keluarga besar KKI</p>
        <h1 class="pp-display">Direktori Anggota</h1>
        <p>Temukan karateka KKI berdasarkan nama, dojo, wilayah, dan tingkat sabuk.</p>
        <div class="pp-page-stat"><strong>{{ number_format($members->total()) }}</strong><span>profil publik sesuai pencarian</span></div>
    </div>
</section>

<section class="pp-section pp-white">
    <div class="pp-wrap">
        <form method="get" action="{{ route('public.members') }}" class="pp-search pp-filter-panel">
            <div class="pp-filter-grid pp-filter-grid-members">
                <label for="member-search">Cari anggota<input id="member-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama, dojo, kota, atau provinsi"></label>
                <label for="member-province">Provinsi<select id="member-province" name="province_id"><option value="">Semua provinsi</option>@foreach($provinces as $province)<option value="{{ $province->id }}" @selected((string) ($filters['province_id'] ?? '') === (string) $province->id)>{{ $province->name }}</option>@endforeach</select></label>
                <label for="member-belt">Tingkat sabuk<select id="member-belt" name="belt_level_id"><option value="">Semua tingkat</option>@foreach($beltLevels as $belt)<option value="{{ $belt->id }}" @selected((string) ($filters['belt_level_id'] ?? '') === (string) $belt->id)>{{ $belt->name }} ({{ $belt->kyu_dan }})</option>@endforeach</select></label>
                <div class="pp-filter-actions"><button class="pp-btn pp-red" type="submit">Cari anggota</button><a class="pp-btn pp-outline-dark" href="{{ route('public.members') }}">Reset</a></div>
            </div>
            <p class="pp-filter-note">Direktori hanya menampilkan anggota terverifikasi yang menyetujui publikasi profil. Kontak pribadi tidak ditampilkan. <a href="{{ route('privacy.policy') }}">Kebijakan Privasi</a></p>
        </form>

        @if($members->isNotEmpty())
            <div class="pp-member-directory-grid">
                @foreach($members as $member)
                    <article class="pp-member-directory-card">
                        <span class="pp-icon" aria-hidden="true">{{ mb_substr($member->name, 0, 1) }}</span>
                        <div class="pp-member-card-main">
                            <span class="pp-member-number">{{ $member->member_number }}</span>
                            <h2>{{ $member->name }}</h2>
                            <p>{{ $member->dojo?->name ?? 'Dojo belum tercatat' }}</p>
                            <p>{{ collect([$member->city?->name, $member->province?->name])->filter()->implode(' · ') ?: 'Wilayah belum tercatat' }}</p>
                        </div>
                        @if($member->beltLevel)<span class="pp-member-belt">{{ $member->beltLevel->name }}<small>{{ $member->beltLevel->kyu_dan }}</small></span>@endif
                    </article>
                @endforeach
            </div>
            <x-pagination :paginator="$members" label="anggota" />
        @else
            <div class="pp-empty pp-page-empty"><span class="pp-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm7-3h6m-3-3v6"/></svg></span><div><h2>Belum ada profil publik yang sesuai.</h2><p>Coba ubah kata kunci atau filter. Profil hanya tampil setelah anggota terverifikasi dan menyetujui publikasi direktori.</p><p>Anggota dapat mengatur persetujuan melalui menu Privasi &amp; PDP.</p><a class="pp-link" href="{{ route('privacy.index') }}">Atur publikasi profil</a></div></div>
        @endif
    </div>
</section>
@endsection
