@php
    $cardData = \App\Support\MemberCardData::forUser($user);
    $cardData['qualification'] = $cardQualificationLabel ?? null;
    $cardData['qr'] = 'data:image/svg+xml;base64,'.$qrPngBase64;
@endphp
<div class="membership-studio" data-card="{{ json_encode($cardData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}" x-data="{
    isDownloading: false,
    async downloadCard(side) {
        if (this.isDownloading) return;
        this.isDownloading = true;
        try {
            await window.downloadKkiMemberCard(JSON.parse(this.$root.dataset.card), side);
        } catch (error) {
            alert(error.message);
        } finally {
            this.isDownloading = false;
        }
    }
}">
    <div class="membership-heading"><div><p>KUSHIN RYU M. KARATE-DO INDONESIA</p><h2>Kartu Anggota <span>KKI</span></h2><p>Identitas karateka. Satu keluarga, satu semangat.</p></div><span class="membership-edition">{{ $user->beltLevel?->display_name ?? 'KARATEKA KKI' }}</span></div>
    <div class="membership-cards">
        @foreach (['front' => 'Sisi depan', 'back' => 'Sisi belakang'] as $side => $sideLabel)
            <section class="membership-side" aria-label="{{ $sideLabel }} kartu anggota">
                <div class="membership-side-heading"><span>{{ $sideLabel }}</span><span>{{ $side === 'front' ? '01' : '02' }}</span></div>
                @include('member.card-face', ['side' => $side])
                <button type="button" class="membership-download" @click="downloadCard('{{ $side }}')" :disabled="isDownloading"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9 2a1 1 0 0 1 2 0v8.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L9 10.586V2ZM3 13a1 1 0 0 1 1 1v3h12v-3a1 1 0 1 1 2 0v4a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1Z" /></svg><span x-text="isDownloading ? 'Menyiapkan unduhan…' : 'Unduh {{ $sideLabel }} (PNG)'">Unduh {{ $sideLabel }} (PNG)</span></button>
            </section>
        @endforeach
    </div>
    <p class="membership-download-note">Unduh kedua sisi untuk menyimpan kartu digital Anda. Pindai QR untuk membuka halaman verifikasi anggota dan melihat status terbaru dari sistem KKI.</p>
    @if($showMemberDetails ?? true)
    <section class="membership-details"><div class="membership-details-heading"><h2>Data anggota</h2><a href="{{ route('profile.edit') }}">Perbarui profil <x-solid-arrow /></a></div><dl>
        @foreach (['Nama karateka' => $user->name, 'Nomor anggota' => $cardData['number'], 'ID akun' => $cardData['accountId'], 'Provinsi' => $cardData['province'], 'Status keanggotaan' => $cardData['status'], 'Verifikasi data' => $cardData['verification'], 'Berlaku hingga' => $cardData['expiry'], 'Tingkatan' => $user->beltLevel?->display_name ?? 'Belum ditentukan', 'Dojo' => $user->dojo?->name ?? 'Belum dipilih', 'Kabupaten / kota' => $user->city?->name ?? 'Belum diisi', 'Kecamatan' => $user->district ?: 'Belum diisi', 'WhatsApp' => $user->whatsapp ?: 'Belum diisi', 'Nama orang tua' => $user->parent_name ?: 'Belum diisi'] as $label => $value)
            <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl></section>
    @endif
</div>
