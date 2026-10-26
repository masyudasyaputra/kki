<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#141717">
<title>PP KKI | Pengurus Pusat Kushin Ryu M Karate-Do Indonesia</title>
<meta name="description" content="Portal Pengurus Pusat KKI: keanggotaan, direktori dojo, agenda ujian kenaikan tingkat, dan administrasi perguruan.">
<link rel="canonical" href="{{ url('/') }}">
<link rel="icon" type="image/png" href="{{ asset('images/logo-kki.png') }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Pengurus Pusat KKI">
<meta property="og:title" content="PP KKI | Satu keluarga, satu langkah ke depan">
<meta property="og:image" content="{{ asset('images/logo-kki.png') }}">
<meta property="og:url" content="{{ url('/') }}">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=barlow:400,500,600,700,800|barlow-condensed:600,700,800,900" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'SportsOrganization', 'name' => 'Pengurus Pusat KKI', 'url' => url('/'), 'logo' => asset('images/logo-kki.png'), 'sport' => 'Karate', 'areaServed' => ['@type' => 'Country', 'name' => 'Indonesia']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body class="pp" x-data="{ menuOpen: false }">
@php
$entryUrl = auth()->check() ? route('dashboard') : route('login');
$navigation = ['layanan' => 'Layanan', 'alur' => 'Alur anggota', 'agenda' => 'Agenda UKT', 'dojo' => 'Direktori dojo'];
$services = [
 ['Keanggotaan', 'Lengkapi profil, akses kartu anggota dengan QR, dan lihat informasi sabuk serta dojo Anda.', 'Buka ruang anggota', $entryUrl, 'M8 7h8M8 11h5M4 3h16v18H4zM8 16h2v2H8zM14 16h2v2h-2z'],
 ['Ujian kenaikan tingkat', 'Temukan jadwal UKT. Pengurus mengelola peserta, penguji, hingga hasil kenaikan tingkat.', 'Lihat agenda ujian', '#agenda', 'M4 5h16v16H4zM8 3v4M16 3v4M4 10h16M8 15l3 3 5-5'],
 ['Jaringan dojo', 'Cari tempat berlatih melalui direktori dojo aktif beserta wilayah, pelatih, dan lokasinya.', 'Temukan dojo', '#dojo', 'M12 22s8-7 8-13a8 8 0 0 0-16 0c0 6 8 13 8 13zM15 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0'],
 ['Iuran & pembayaran', 'Akses informasi tagihan anggota. Pengurus mengelola iuran dan pembayaran peserta ujian.', 'Masuk ke administrasi', $entryUrl, 'M3 5h18v15H3zM3 10h18M7 15h4M16 15h1'],
];
$steps = [
 ['Buat akun anggota', 'Daftarkan nama dan email, lalu lengkapi identitas serta informasi wilayah dan dojo.', 'Pendaftaran online'],
 ['Bangun profil karateka', 'Lengkapi data keanggotaan agar kartu anggota, dojo, dan tingkat sabuk tercatat dalam sistem.', 'Profil & kartu anggota'],
 ['Ikuti proses UKT', 'Pantau agenda ujian dan koordinasikan pendaftaran peserta serta administrasinya dengan pengurus dojo.', 'Jadwal & administrasi ujian'],
 ['Lanjutkan pembinaan', 'Penguji mencatat nilai, pengurus menetapkan hasil, dan riwayat kenaikan tingkat menjadi bagian dari data anggota.', 'Penilaian & riwayat sabuk'],
];
$audiences = [
 'anggota' => ['Anggota', 'Ruang pribadi setiap karateka.', ['Profil, wilayah, dan dojo anggota', 'Kartu anggota dengan kode QR', 'Informasi sabuk dan tagihan keanggotaan'], 'Buka ruang anggota'],
 'pengurus' => ['Pengurus & dojo', 'Administrasi yang saling terhubung.', ['Kelola data anggota sesuai kewenangan', 'Atur dojo, wilayah, dan kepengurusan', 'Kelola jadwal, peserta UKT, serta pembayaran'], 'Masuk sebagai pengurus'],
 'penguji' => ['Penguji', 'Fokus pada proses penilaian.', ['Akses agenda dan peserta sesuai kewenangan', 'Catat nilai ujian setiap peserta', 'Finalisasi penilaian untuk proses kelulusan'], 'Masuk sebagai penguji'],
];
$faqs = [
 ['Bagaimana cara menjadi anggota?', 'Pilih Daftar anggota, lalu isi nama, email, dan kata sandi. Lengkapi identitas, wilayah, serta dojo melalui formulir pendaftaran atau profil Anda.'],
 ['Bagaimana jika dojo saya belum tersedia?', 'Anda tetap dapat membuat akun dengan pilihan Belum tersedia / belum tahu. Koordinasikan dengan pengurus agar informasi dojo dapat dilengkapi kemudian.'],
 ['Apakah saya bisa langsung mendaftar ujian di sini?', 'Halaman ini menampilkan jadwal UKT. Pendaftaran peserta dan administrasi ujian dikelola pengurus melalui sistem. Hubungi pengurus dojo untuk proses keikutsertaan.'],
 ['Di mana saya melihat kartu anggota dan tagihan?', 'Masuk ke akun untuk membuka dashboard anggota. Kartu anggota dengan QR, data sabuk, dojo, dan informasi tagihan tersedia sesuai data keanggotaan Anda.'],
 ['Bagaimana pengurus dan penguji mengakses sistem?', 'Gunakan tombol Masuk portal. Dashboard dan akses fitur mengikuti peran serta kewenangan yang telah diberikan pada akun Anda.'],
];
@endphp
<a href="#konten-utama" class="pp-skip">Lewati ke konten</a>
<header class="pp-header">
<div class="pp-wrap pp-header-inner">
<a href="#beranda" class="pp-brand" aria-label="PP KKI - Beranda">
<img src="{{ asset('images/logo-kki.png') }}" alt="Logo KKI" width="46" height="46">
<span>
<strong>PP KKI</strong>
<small>Pengurus Pusat</small>
</span>
</a>
<nav class="pp-nav" aria-label="Navigasi utama">
@foreach($navigation as $id => $label)<a href="#{{ $id }}">{{ $label }}</a>
@endforeach
<a href="{{ $entryUrl }}">{{ auth()->check() ? 'Dashboard' : 'Masuk portal' }}</a>
@guest<a href="{{ route('register') }}" class="pp-btn pp-red">Daftar anggota ↗</a>
@endguest
</nav>
<button class="pp-toggle" type="button" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="pp-mobile" :aria-label="menuOpen ? 'Tutup navigasi' : 'Buka navigasi'">
<span x-text="menuOpen ? '✕' : '☰'" aria-hidden="true">☰</span>
</button>
</div>
<nav id="pp-mobile" class="pp-mobile" x-cloak x-show="menuOpen" @click.outside="menuOpen = false" @keydown.escape.window="menuOpen = false" aria-label="Navigasi seluler">
@foreach($navigation as $id => $label)<a href="#{{ $id }}" @click="menuOpen = false">{{ $label }}</a>
@endforeach
<a href="{{ $entryUrl }}" @click="menuOpen = false">{{ auth()->check() ? 'Dashboard' : 'Masuk portal' }}</a>
@guest<a href="{{ route('register') }}" @click="menuOpen = false">Daftar anggota ↗</a>
@endguest
</nav>
</header>
<main id="konten-utama">
<section id="beranda" class="pp-hero">
<div class="pp-wrap pp-hero-grid">
<div>
<p class="pp-eyebrow">Pengurus Pusat KKI · Indonesia</p>
<h1 class="pp-display">Satu keluarga.<br>
<em>Satu langkah</em>
<br>ke depan.</h1>
<p class="pp-copy">Kushin Ryu M Karate-Do Indonesia. Menghubungkan karateka, dojo, dan pengurus melalui keanggotaan serta pembinaan yang tertata.</p>
<div class="pp-actions">
<a href="{{ $entryUrl }}" class="pp-btn pp-red">{{ auth()->check() ? 'Buka dashboard' : 'Masuk portal KKI' }} ↗</a>
<a href="#layanan" class="pp-btn pp-outline">Jelajahi layanan ↓</a>
</div>
<p class="pp-note">Untuk anggota, pengurus, pengelola dojo, dan penguji.</p>
</div>
<div class="pp-emblem">
<div class="pp-emblem-card">
<img src="{{ asset('images/logo-kki.png') }}" alt="Lambang Kushin Ryu M Karate-Do Indonesia" width="342" height="342" fetchpriority="high">
</div>
<p class="pp-emblem-label">
<span>Kushin Ryu</span>
<span>Karakter · Persaudaraan</span>
</p>
</div>
</div>
<div class="pp-wrap pp-stats">
<span>
<strong>{{ $dojos->count() }}</strong> Dojo aktif dalam direktori</span>
<span>
<strong>{{ $dojoRegions->count() }}</strong> Cabang kabupaten/kota</span>
<a href="#tentang" class="pp-link">Kenali perguruan ↗</a>
</div>
</section>
<section id="layanan" class="pp-section pp-white">
<div class="pp-wrap">
<div class="pp-head">
<div>
<p class="pp-eyebrow">Layanan KKI</p>
<h2 class="pp-display pp-title">Apa yang ingin<br>Anda lakukan hari ini?</h2>
</div>
<p class="pp-copy">Mulai dari kebutuhan Anda. Akses layanan anggota dan administrasi perguruan melalui satu portal.</p>
</div>
<div class="pp-services">
@foreach($services as [$title, $description, $label, $href, $icon])<article class="pp-service">
<span class="pp-icon">
<svg viewBox="0 0 24 24" aria-hidden="true">
<path d="{{ $icon }}"/>
</svg>
</span>
<h3>{{ $title }}</h3>
<p>{{ $description }}</p>
<a href="{{ $href }}" class="pp-link">{{ $label }} ↗</a>
</article>
@endforeach
</div>
</div>
</section>
<section id="alur" class="pp-section">
<div class="pp-wrap pp-split">
<div class="pp-sticky">
<p class="pp-eyebrow">Perjalanan anggota</p>
<h2 class="pp-display pp-title">Dari langkah pertama,<br>sampai tingkat berikutnya.</h2>
<p class="pp-copy">Latihan membentuk kemampuan. Pencatatan yang tertib membantu menjaga perjalanan keanggotaan dan kenaikan tingkat Anda.</p>
@guest<a href="{{ route('register') }}" class="pp-btn pp-red">Mulai pendaftaran ↗</a>
@else<a href="{{ route('dashboard') }}" class="pp-btn pp-red">Lihat data saya ↗</a>
@endguest
</div>
<div>
@foreach($steps as [$title, $description, $tag])<article class="pp-step">
<span class="pp-num">0{{ $loop->iteration }}</span>
<div>
<h3>{{ $title }}</h3>
<p>{{ $description }}</p>
<span class="pp-tag">{{ $tag }}</span>
</div>
</article>
@endforeach
</div>
</div>
</section>
<section id="akses" class="pp-section pp-roles" x-data="{ role: 'anggota' }">
<div class="pp-wrap pp-split">
<div>
<p class="pp-eyebrow">Sesuai peran Anda</p>
<h2 class="pp-display pp-title">Satu sistem.<br>Tanggung jawab berbeda.</h2>
<p class="pp-copy">Anggota, pengurus, dan penguji memiliki ruang kerja masing-masing. Pilih peran untuk mengenali layanan yang tersedia.</p>
<div class="pp-tabs" role="group" aria-label="Layanan berdasarkan peran">
@foreach($audiences as $key => [$label])<button type="button" class="pp-tab" @click="role = '{{ $key }}'" :aria-pressed="(role === '{{ $key }}').toString()" aria-controls="panel-{{ $key }}">{{ $label }}</button>
@endforeach
</div>
</div>
<div>
@foreach($audiences as $key => [$label, $title, $features, $action])<div id="panel-{{ $key }}" class="pp-panel" x-show="role === '{{ $key }}'" @if($key !== 'anggota') x-cloak @endif>
<div>
<p class="pp-eyebrow">{{ $label }}</p>
<h3 class="pp-display">{{ $title }}</h3>
<ul>
@foreach($features as $feature)<li>{{ $feature }}</li>
@endforeach
</ul>
</div>
<a href="{{ $entryUrl }}" class="pp-link">{{ $action }} ↗</a>
</div>
@endforeach
</div>
</div>
</section>
<section id="agenda" class="pp-section pp-white">
<div class="pp-wrap">
<div class="pp-head">
<div>
<p class="pp-eyebrow">Kalender pembinaan</p>
<h2 class="pp-display pp-title">Bersiap untuk<br>tingkat berikutnya.</h2>
</div>
<p class="pp-copy">Agenda UKT yang dibuka atau sedang berlangsung. Koordinasikan pendaftaran peserta dengan pengurus dojo Anda.</p>
</div>
@if($agendas->isNotEmpty())<div class="pp-agenda-list">
@foreach($agendas as $agenda)<article class="pp-agenda-item">
<time class="pp-date" datetime="{{ $agenda->execution_date->toDateString() }}">
<strong>{{ $agenda->execution_date->format('d') }}</strong>
<span>{{ $agenda->execution_date->locale('id')->translatedFormat('M Y') }}</span>
</time>
<div>
<span class="pp-status">{{ $agenda->status === 'ongoing' ? 'Sedang berlangsung' : 'Pendaftaran dibuka' }}</span>
<h3>{{ $agenda->name }}</h3>
<p>{{ $agenda->location }}@if($agenda->province) · {{ $agenda->province->name }}@endif
</p>
</div>
<a href="{{ $entryUrl }}" class="pp-link">Masuk portal ↗</a>
</article>
@endforeach
</div>
@else<div class="pp-empty">
<span class="pp-icon">
<svg viewBox="0 0 24 24" aria-hidden="true">
<path d="M4 5h16v16H4zM8 3v4M16 3v4M4 10h16M9 15h6"/>
</svg>
</span>
<div>
<h3>Belum ada jadwal UKT yang dibuka.</h3>
<p>Jadwal akan tampil setelah diterbitkan pengurus. Sambil menunggu, lengkapi profil anggota dan berkoordinasi dengan dojo Anda.</p>
</div>
</div>
@endif
</div>
</section>
<section id="dojo" class="pp-section pp-directory" x-data="{ query: '', hasMatches: true, filterDojos() { const term = this.query.trim().toLocaleLowerCase('id'); let total = 0; this.$refs.regions.querySelectorAll('[data-region]').forEach(region => { let count = 0; region.querySelectorAll('[data-dojo]').forEach(dojo => { const match = !term || dojo.dataset.search.includes(term); dojo.hidden = !match; if (match) count++; }); region.hidden = count === 0; if (term &amp;&amp; count) region.open = true; total += count; }); this.hasMatches = total > 0; } }">
<div class="pp-wrap">
<div class="pp-split">
<div>
<p class="pp-eyebrow">Jaringan perguruan</p>
<h2 class="pp-display pp-title">Temukan tempat<br>untuk bertumbuh.</h2>
<p class="pp-copy">Direktori dojo aktif dalam jaringan PP KKI. Temukan lokasi berlatih dan informasi pelatih di wilayah Anda.</p>
</div>
@if($dojos->isNotEmpty())<div class="pp-search">
<label for="pp-search">Cari dojo terdekat</label>
<input id="pp-search" type="search" x-model="query" @input="filterDojos()" placeholder="Nama dojo, pelatih, kota, atau provinsi…" aria-describedby="pp-search-hint">
<small id="pp-search-hint">{{ $dojos->count() }} dojo aktif · {{ $dojoRegions->count() }} cabang kabupaten/kota. Buka wilayah untuk melihat detail.</small>
</div>
@endif
</div>
@if($dojos->isNotEmpty())<div class="pp-regions" x-ref="regions">
@foreach($dojoRegions as $region => $regionDojos)<details class="pp-region" data-region @if($loop->first) open @endif>
<summary>
<div>{{ $region }} <span> / {{ $regionDojos->count() }} dojo</span>
</div>
</summary>
<div class="pp-dojos">
@foreach($regionDojos as $dojo)<article class="pp-dojo" data-dojo data-search="{{ mb_strtolower(implode(' ', [$region, $dojo->city?->province?->name, $dojo->name, $dojo->address, $dojo->sensei_name]), 'UTF-8') }}">
<h4>{{ $dojo->name }}</h4>
@if($dojo->city?->province)<p>{{ $dojo->city->province->name }}</p>
@endif @if($dojo->sensei_name)<p>Pelatih: {{ $dojo->sensei_name }}</p>
@endif
<p>{{ $dojo->address ?: 'Alamat belum dilengkapi.' }}</p>
@if($dojo->address)<a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($dojo->name.' '.$dojo->address.' '.$region) }}" target="_blank" rel="noopener noreferrer" class="pp-link">Lihat lokasi ↗</a>
@endif
</article>
@endforeach
</div>
</details>
@endforeach
</div>
<p class="pp-no-match" role="status" aria-live="polite" x-cloak x-show="!hasMatches">Tidak ada dojo yang sesuai. Coba nama kota, provinsi, atau kata kunci lain.</p>
@else<div class="pp-empty">
<span class="pp-icon">
<svg viewBox="0 0 24 24" aria-hidden="true">
<path d="M12 22s8-7 8-13a8 8 0 0 0-16 0c0 6 8 13 8 13zM15 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
</svg>
</span>
<div>
<h3>Direktori dojo sedang disiapkan.</h3>
<p>Dojo akan ditampilkan setelah data dan masa berlaku SK tercatat aktif. Pengurus dapat melengkapi data dojo melalui portal administrasi.</p>
</div>
</div>
@endif
</div>
</section>
<section id="tentang" class="pp-section pp-about">
<div class="pp-wrap pp-about-grid">
<img src="{{ asset('images/logo-kki.png') }}" alt="Lambang KKI dengan bunga melati dan pusat merah putih" width="140" height="140" loading="lazy">
<div>
<p class="pp-eyebrow">Pengurus Pusat KKI</p>
<h2 class="pp-display">Berakar pada karakter.<br>Terikat persaudaraan.</h2>
<p>Kushin Ryu M Karate-Do Indonesia adalah keluarga karate beraliran Kushin Ryu. Pembinaan teknik berjalan bersama disiplin, tanggung jawab, dan penghormatan kepada sesama.</p>
</div>
<div class="pp-about-note">
<p>Melati dan pusat merah putih pada lambang KKI menyatukan semangat Pancasila, keberanian, kesucian, serta persatuan keluarga besar KKI di Indonesia.</p>
<a href="{{ asset('images/makna-lambang-kki.png') }}" class="pp-link" target="_blank" rel="noopener noreferrer">Kenali makna lambang ↗</a>
</div>
</div>
</section>
<section id="bantuan" class="pp-section pp-white pp-faq">
<div class="pp-wrap pp-split">
<div>
<p class="pp-eyebrow">Sebelum memulai</p>
<h2 class="pp-display pp-title">Ada yang ingin<br>Anda ketahui?</h2>
<p class="pp-copy">Panduan singkat untuk mengakses layanan dan memahami alur administrasi KKI.</p>
</div>
<div>
@foreach($faqs as [$question, $answer])<details @if($loop->first) open @endif>
<summary>{{ $question }}</summary>
<p>{{ $answer }}</p>
</details>
@endforeach
</div>
</div>
</section>
<section class="pp-cta">
<div class="pp-wrap pp-cta-inner">
<div>
<h2 class="pp-display">Langkah Anda berikutnya dimulai di sini.</h2>
<p>Lengkapi keanggotaan. Terhubung dengan dojo. Terus bertumbuh bersama KKI.</p>
</div>
<a href="{{ $entryUrl }}" class="pp-btn pp-light">{{ auth()->check() ? 'Buka dashboard' : 'Masuk portal KKI' }} ↗</a>
</div>
</section>
</main>
<footer class="pp-footer">
<div class="pp-wrap">
<div class="pp-footer-top">
<a href="#beranda" class="pp-brand">
<img src="{{ asset('images/logo-kki.png') }}" alt="Logo KKI" width="46" height="46" loading="lazy">
<span>
<strong>PP KKI</strong>
<small>Pengurus Pusat</small>
</span>
</a>
<nav class="pp-footer-links" aria-label="Navigasi footer">
<a href="#tentang">Tentang KKI</a>
<a href="#layanan">Layanan</a>
<a href="#agenda">Agenda UKT</a>
<a href="#dojo">Direktori dojo</a>
<a href="#bantuan">Bantuan</a>
</nav>
</div>
<div class="pp-footer-bottom">
<p>&copy; {{ date('Y') }} Pengurus Pusat KKI. Kushin Ryu M Karate-Do Indonesia.</p>
<p>Karakter. Disiplin. Persaudaraan.</p>
</div>
</div>
</footer>
</body>
</html>
