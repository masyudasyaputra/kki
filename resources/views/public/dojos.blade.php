@extends('layouts.public')

@section('title', 'Direktori Dojo')

@section('content')
<section class="pp-page-hero pp-dojo-hero">
    <div class="pp-wrap pp-page-hero-inner">
        <img class="pp-dojo-hero-watermark" src="{{ asset('images/logo-kki-dark.png') }}" alt="" aria-hidden="true" width="300" height="300">
        <p class="pp-eyebrow">Jaringan perguruan</p>
        <h1 class="pp-display">Direktori Dojo</h1>
        <p>Jelajahi dojo KKI yang aktif berdasarkan nama, kota, dan provinsi. Informasi kontak yang tampil merupakan kontak dojo.</p>
        <div class="pp-page-stat"><strong>{{ number_format($dojos->total()) }}</strong><span>dojo aktif</span></div>
    </div>
</section>

<section class="pp-section pp-directory pp-dojo-directory">
    <div class="pp-wrap">
        <form method="get" action="{{ route('public.dojos') }}" class="pp-search pp-filter-panel">
            <div class="pp-filter-grid pp-filter-grid-dojos">
                <label for="dojo-search">Cari dojo<input id="dojo-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama dojo, pelatih, atau alamat"></label>
                <label for="dojo-province">Provinsi<select id="dojo-province" name="province_id" onchange="this.form.submit()"><option value="">Semua provinsi</option>@foreach($provinces as $province)<option value="{{ $province->id }}" @selected((string) ($filters['province_id'] ?? '') === (string) $province->id)>{{ $province->name }}</option>@endforeach</select></label>
                <label for="dojo-city">Kabupaten / Kota<select id="dojo-city" name="city_id"><option value="">Semua kabupaten / kota</option>@foreach($cities as $city)<option value="{{ $city->id }}" @selected((string) ($filters['city_id'] ?? '') === (string) $city->id)>{{ $city->name }}</option>@endforeach</select></label>
                <div class="pp-filter-actions"><button class="pp-btn pp-red" type="submit">Cari dojo</button><a class="pp-btn pp-outline-dark" href="{{ route('public.dojos') }}">Reset</a></div>
            </div>
        </form>

        @if($dojos->isNotEmpty())
            <div class="pp-dojo-directory-grid">
                @foreach($dojos as $dojo)
                    <article class="pp-dojo-directory-card">
                        <img class="pp-dojo-watermark" src="{{ asset('images/logo-kki.png') }}" alt="" aria-hidden="true" width="180" height="180" loading="lazy">
                        <div class="pp-dojo-card-top"><span class="pp-directory-icon" aria-hidden="true">道</span><span class="pp-directory-status">Aktif</span></div>
                        <h2>{{ $dojo->name }}</h2>
                        <p class="pp-dojo-registration"><span>No. Registrasi</span><strong>{{ $dojo->registration_number }}</strong></p>
                        <p class="pp-dojo-region">{{ $dojo->city?->name ?? 'Kabupaten / kota belum tercatat' }}@if($dojo->city?->province) <span aria-hidden="true">·</span> {{ $dojo->city->province->name }}@endif</p>
                        @if($dojo->sensei_name)<p><strong>Pelatih:</strong> {{ $dojo->sensei_name }}</p>@endif
                        <p>{{ $dojo->address ?: 'Alamat belum dilengkapi.' }}</p>
                        @if($dojo->phone_number)<a class="pp-link" href="tel:{{ preg_replace('/[^+0-9]/', '', $dojo->phone_number) }}">Hubungi dojo <span aria-hidden="true">↗</span></a>@endif
                        @if($dojo->address)<a class="pp-map-link" href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($dojo->name.' '.$dojo->address.' '.$dojo->city?->name) }}" target="_blank" rel="noopener noreferrer">Lihat peta <span aria-hidden="true">↗</span></a>@endif
                    </article>
                @endforeach
            </div>
            <x-pagination :paginator="$dojos" label="dojo" />
        @else
            <div class="pp-empty pp-page-empty"><span class="pp-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-7 8-13a8 8 0 0 0-16 0c0 6 8 13 8 13zM15 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/></svg></span><div><h2>Dojo belum ditemukan.</h2><p>Periksa kembali kata kunci atau filter wilayah. Direktori memuat dojo dengan status aktif dan SK yang masih berlaku.</p></div></div>
        @endif
    </div>
</section>
@endsection
