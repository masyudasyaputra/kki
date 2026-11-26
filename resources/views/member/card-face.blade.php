@php($cardTheme = $cardData['theme'])
<div id="member-card-{{ $side }}" class="kki-id-card kki-id-{{ $side }} {{ $cardTheme['stripe'] ? 'kki-id-striped' : '' }}" style="--card-start:{{ $cardTheme['start'] }};--card-base:{{ $cardTheme['base'] }};--card-end:{{ $cardTheme['end'] }};--card-ink:{{ $cardTheme['ink'] }};--card-muted:{{ $cardTheme['muted'] }};--card-gold:{{ $cardTheme['accent'] }}">
    <img class="kki-id-watermark" src="{{ asset('images/logo-kki-dark.png') }}" alt="" aria-hidden="true" width="1024" height="1024">
    @if ($side === 'front')
        <div class="kki-id-brand"><img src="{{ asset('images/logo-kki-dark.png') }}" alt="Logo KKI" width="64" height="64"><div><strong>KKI</strong><span>KUSHIN RYU M. KARATE-DO INDONESIA</span><small>KARAKTER · KEHORMATAN · INTEGRITAS</small></div><span class="kki-id-type">KARTU<br>ANGGOTA</span></div>
        <div class="kki-id-front-body"><div class="kki-id-fields"><div><span>NOMOR ANGGOTA</span><p class="kki-id-number">{{ $cardData['number'] }}</p></div><div><span>NAMA KARATEKA</span><h3>{{ $cardData['name'] }}</h3>@if(!empty($cardQualificationLabel))<p class="kki-id-qualification">{{ $cardQualificationLabel }}</p>@endif</div><div class="kki-id-bottom-fields"><div><span>DOJO</span><p>{{ $cardData['dojo'] }}</p></div><div><span>TINGKATAN</span><p>{{ $cardData['rank'] }}</p></div></div></div><div class="kki-id-photo"><span>{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>@if($user->photo)<img src="{{ url('storage/'.$user->photo) }}" alt="Pasfoto {{ $cardData['name'] }}" width="90" height="120">@endif</div></div>
        <div class="kki-id-footer"><span>OFFICIAL MEMBERSHIP CARD</span><span>PP KKI • INDONESIA</span></div>
    @else
        <div class="kki-id-magnetic" aria-hidden="true"></div>
        <div class="kki-id-back-body"><div class="kki-id-qr-column"><span>IDENTITAS DIGITAL</span><div class="kki-id-qr"><img src="data:image/svg+xml;base64,{{ $qrPngBase64 }}" alt="QR identitas anggota" width="120" height="120"></div><small>PINDAI UNTUK VERIFIKASI</small></div><dl class="kki-id-record">
            @foreach (['ID AKUN' => $cardData['accountId'], 'PROVINSI' => $cardData['province'], 'KABUPATEN / KOTA' => $cardData['city'], 'STATUS / VERIFIKASI' => $cardData['status'].' / '.$cardData['verification'], 'BERLAKU HINGGA' => $cardData['expiry']] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl></div>
        <div class="kki-id-back-footer"><p>Kartu ini adalah identitas pribadi anggota KKI dan tidak dapat dipindahtangankan.</p><strong>KUSHIN RYU M. KARATE-DO INDONESIA</strong></div>
    @endif
    @if($cardTheme['stripe'])<div class="kki-id-belt-stripe" aria-hidden="true"></div>@endif
    <div class="kki-id-gold-line" aria-hidden="true"></div>
</div>
