@php
    $beltName = $user->beltLevel?->name ?? '';
    $normalizedBelt = mb_strtolower($beltName);
    $beltPalette = [
        'putih' => ['#f5f3ee', '#ffffff', '#24211d', '#645d52', '#24211d22'],
        'kuning' => ['#e7b914', '#ffe68a', '#302707', '#5f4a0c', '#30270730'],
        'hijau' => ['#145c36', '#298553', '#ffffff', '#d6eddf', '#ffffff30'],
        'biru' => ['#163d80', '#3869ac', '#ffffff', '#d9e6fb', '#ffffff30'],
        'coklat' => ['#583621', '#966a43', '#ffffff', '#f2dfcd', '#ffffff30'],
        'cokelat' => ['#583621', '#966a43', '#ffffff', '#f2dfcd', '#ffffff30'],
        'hitam' => ['#111214', '#383a40', '#ffffff', '#d1d1d6', '#ffffff30'],
        'orange' => ['#a94512', '#df7931', '#ffffff', '#fff0dd', '#ffffff30'],
        'oranye' => ['#a94512', '#df7931', '#ffffff', '#fff0dd', '#ffffff30'],
        'jingga' => ['#a94512', '#df7931', '#ffffff', '#fff0dd', '#ffffff30'],
        'ungu' => ['#512777', '#8452ab', '#ffffff', '#edddfa', '#ffffff30'],
        'merah' => ['#821f2b', '#bf4653', '#ffffff', '#fbdde1', '#ffffff30'],
    ];
    $theme = ['#35151f', '#762137', '#ffffff', '#e3b6c1', '#ffffff30'];
    foreach ($beltPalette as $colorName => $palette) {
        if (str_contains($normalizedBelt, $colorName)) { $theme = $palette; break; }
    }
    if (str_contains($normalizedBelt, 'biru tua')) $theme[0] = '#10284f';
    $hasBeltStripe = str_contains($normalizedBelt, 'strip');
@endphp
<div class="karateka-dashboard">
    <div class="kd-welcome">
        <div class="kd-identity"><span class="kd-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}@if($user->photo)<img src="{{ url('storage/'.$user->photo) }}" alt="Pasfoto {{ $user->name }}" width="54" height="54" onerror="this.hidden = true">@endif</span><div><p>Selamat datang kembali</p><h2>Oss, {{ $user->name }}!</h2>@if(!empty($qualificationBadges))<div class="kd-qualification-badges">@foreach($qualificationBadges as $badge)<a href="{{ route('qualifications.index') }}" class="profile-chip profile-chip-success"><x-sidebar-icon name="certificate" />{{ $badge }}</a>@endforeach</div>@endif</div></div>
        <span class="kd-member-status"><i></i>{{ $user->is_active ? 'Anggota aktif' : 'Anggota belum aktif' }}</span>
    </div>

    <div class="kd-layout">
        <div class="kd-main">
            <section class="kd-rank-card {{ $hasBeltStripe ? 'kd-rank-striped' : '' }}" style="--rank-base:{{ $theme[0] }};--rank-glow:{{ $theme[1] }};--rank-ink:{{ $theme[2] }};--rank-muted:{{ $theme[3] }};--rank-border:{{ $theme[4] }}">
                @if($hasBeltStripe)<span class="kd-belt-stripe" aria-hidden="true"></span>@endif
                <img src="{{ asset('images/logo-kki-dark.png') }}" class="kd-rank-watermark" alt="" aria-hidden="true" width="1024" height="1024">
                <div class="kd-rank-top"><span class="kd-kicker">PERJALANAN KARATEKA</span><span class="kd-pill">Kushin Ryu M. Karate-Do Indonesia</span></div>
                <div class="kd-rank-content"><div><p>Tingkatan saat ini</p><h2>{{ $beltName ?: 'Belum ditentukan' }}</h2><p>{{ $user->beltLevel?->kyu_dan ?? 'Lengkapi data tingkatan di profil' }}</p>@if($hasBeltStripe)<span class="kd-stripe-label"><i aria-hidden="true"></i>Strip emas</span>@endif</div></div>
                <div class="kd-rank-footer"><span>Setiap latihan, satu langkah lebih maju.</span><a href="{{ route('member.card') }}">Kartu anggota <span aria-hidden="true"><x-solid-arrow direction="up-right" /></span></a></div>
            </section>

            <section class="kd-actions-section"><div class="kd-section-heading"><h2>Akses cepat</h2><span>Ruang karateka Anda</span></div>
                <div class="kd-actions">
                    @foreach ([['exams', 'exam', 'Ujian saya'], ['payments', 'wallet', 'Pembayaran'], ['certificates', 'certificate', 'Sertifikat'], ['card', 'membership', 'Kartu anggota']] as [$target, $icon, $label])
                        <a href="{{ route('member.'.$target) }}"><span class="kd-action-icon"><x-sidebar-icon :name="$icon" /></span><span>{{ $label }}</span><span class="kd-action-arrow" aria-hidden="true"><x-solid-arrow direction="up-right" /></span></a>
                    @endforeach
                </div>
            </section>

            <section class="kd-verification"><div class="kd-verification-icon"><x-sidebar-icon name="audit" /></div><div><p class="kd-kicker">KEANGGOTAAN ANDA</p><h2>{{ $verification }}</h2><p>{{ $user->verification_status === 'approved' ? 'Data Anda sudah disetujui. Teruskan perjalanan dan pantau aktivitas karateka Anda.' : 'Pastikan identitas, dojo, wilayah, dan tingkatan sabuk sudah sesuai untuk pemeriksaan pengurus.' }}</p>@if($user->verification_status === 'revision' && $latestVerification?->notes)<p class="kd-review-note">{{ $latestVerification->notes }}</p>@endif<a href="{{ route('profile.edit') }}">{{ $user->verification_status === 'approved' ? 'Lihat profil' : 'Periksa & lengkapi profil' }} <span aria-hidden="true"><x-solid-arrow /></span></a></div></section>

            <section class="kd-dojo"><div class="kd-section-heading"><h2>Dojo Anda</h2><a href="{{ route('public.dojos') }}">Direktori dojo <x-solid-arrow direction="up-right" /></a></div><div class="kd-dojo-content"><span class="kd-dojo-icon"><x-sidebar-icon name="building" /></span><div><h3>{{ $user->dojo?->name ?? 'Dojo belum dipilih' }}</h3><p>{{ $user->city?->name ?? 'Kabupaten / kota belum diisi' }} · {{ $user->province?->name ?? 'Provinsi belum diisi' }}</p></div></div></section>
        </div>

        <aside class="kd-side" aria-label="Ringkasan karateka">
            <div class="kd-section-heading"><h2>Aktivitas Anda</h2><span>Ringkasan pribadi</span></div>
            <a href="{{ route('member.payments') }}" class="kd-payment-card"><div class="kd-payment-heading"><span>Tagihan menunggu</span><x-sidebar-icon name="wallet" /></div><strong>Rp {{ number_format($summary['amount'], 0, ',', '.') }}</strong><div class="kd-payment-footer"><span>{{ $summary['pending'] ? $summary['pending'].' tagihan perlu diselesaikan' : 'Tidak ada tagihan menunggu' }}</span><span aria-hidden="true"><x-solid-arrow direction="up-right" /></span></div></a>
            <div class="kd-stats">
                @foreach ([['Ujian diikuti', $summary['exams'], 'exam', 'exams', 'kepesertaan'], ['Sertifikat diterima', $summary['certificates'], 'certificate', 'certificates', 'pencapaian']] as [$label, $value, $icon, $target, $unit])
                    <a href="{{ route('member.'.$target) }}" class="kd-stat"><div><x-sidebar-icon :name="$icon" /><span aria-hidden="true"><x-solid-arrow direction="up-right" /></span></div><span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $unit }}</small></a>
                @endforeach
            </div>
            <a href="{{ route('public.agenda') }}" class="kd-agenda"><span class="kd-action-icon"><x-sidebar-icon name="exam" /></span><div><h3>Siap naik tingkat?</h3><p>Jelajahi agenda ujian kenaikan tingkat KKI.</p><span>Lihat agenda UKT <x-solid-arrow /></span></div></a>
            <div class="kd-motto"><span aria-hidden="true">“</span><p>Disiplin dalam latihan.<br>Tangguh dalam perjalanan.</p><small>SEMANGAT KARATEKA KKI</small></div>
        </aside>
    </div>
</div>
