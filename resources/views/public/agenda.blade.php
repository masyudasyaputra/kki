@extends('layouts.public')

@section('title', 'Agenda UKT')

@section('content')
<section class="pp-page-hero">
    <div class="pp-wrap pp-page-hero-inner">
        <p class="pp-eyebrow">Kalender pembinaan KKI</p>
        <h1 class="pp-display">Agenda UKT</h1>
        <p>Temukan jadwal ujian kenaikan tingkat yang akan datang dan informasi wilayah penyelenggara.</p>
        <div class="pp-page-stat"><strong>{{ number_format($agendas->total()) }}</strong><span>agenda tersedia</span></div>
    </div>
</section>

<section class="pp-section pp-white">
    <div class="pp-wrap">
        <form method="get" action="{{ route('public.agenda') }}" class="pp-search pp-filter-panel">
            <div class="pp-filter-grid pp-filter-grid-agenda">
                <label for="agenda-search">Cari agenda<input id="agenda-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama ujian, lokasi, atau provinsi"></label>
                <label for="agenda-province">Provinsi<select id="agenda-province" name="province_id"><option value="">Semua provinsi</option>@foreach($provinces as $province)<option value="{{ $province->id }}" @selected((string) ($filters['province_id'] ?? '') === (string) $province->id)>{{ $province->name }}</option>@endforeach</select></label>
                <div class="pp-filter-actions"><button class="pp-btn pp-red" type="submit">Cari agenda</button><a class="pp-btn pp-outline-dark" href="{{ route('public.agenda') }}">Reset</a></div>
            </div>
            <p class="pp-filter-note">Menampilkan agenda yang pendaftarannya dibuka atau sedang berlangsung.</p>
        </form>

        @if($agendas->isNotEmpty())
            <div class="pp-agenda-list pp-agenda-page-list">
                @foreach($agendas as $agenda)
                    <article class="pp-agenda-item">
                        <time class="pp-date" datetime="{{ $agenda->execution_date->toDateString() }}"><strong>{{ $agenda->execution_date->format('d') }}</strong><span>{{ $agenda->execution_date->locale('id')->translatedFormat('M Y') }}</span></time>
                        <div class="pp-agenda-details">
                            <span class="pp-status">{{ $agenda->status === 'ongoing' ? 'Sedang berlangsung' : 'Pendaftaran dibuka' }}</span>
                            <h2>{{ $agenda->name }}</h2>
                            <p>{{ $agenda->execution_date->locale('id')->translatedFormat('l, d F Y') }}</p>
                            <p>{{ $agenda->location }}@if($agenda->province) <span aria-hidden="true">·</span> {{ $agenda->province->name }}@endif</p>
                            <p>{{ number_format($agenda->participants_count) }} peserta terdaftar</p>
                        </div>
                        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="pp-link">{{ auth()->check() ? 'Buka portal' : 'Masuk portal' }} <span aria-hidden="true">↗</span></a>
                    </article>
                @endforeach
            </div>
            <x-pagination :paginator="$agendas" label="agenda UKT" />
        @else
            <div class="pp-empty pp-page-empty"><span class="pp-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v16H4zM8 3v4M16 3v4M4 10h16M9 15h6"/></svg></span><div><h2>Belum ada agenda yang sesuai.</h2><p>Coba ubah kata kunci atau pilih provinsi lain. Agenda akan tampil setelah diterbitkan pengurus.</p></div></div>
        @endif
    </div>
</section>
@endsection
