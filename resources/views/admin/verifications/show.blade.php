<x-app-layout>
    <x-slot name="header"><div><h1 class="page-title">Pemeriksaan Pendaftaran Anggota</h1><p class="page-subtitle">{{ $member->member_number }} · {{ $member->name }}</p></div></x-slot>
    @php
        $pending = $member->verification_status === 'pending';
        $statusLabel = ['pending' => 'Menunggu verifikasi', 'approved' => 'Disetujui', 'revision' => 'Perlu perbaikan'][$member->verification_status] ?? 'Menunggu verifikasi';
        $latestReview = $history->first();
    @endphp
    <div class="verification-workspace" x-data="verificationChecklist(@js(array_keys($fields)), @js(old('checked', [])))">
        <div class="vr-toolbar"><a class="vr-back" href="{{ route('admin.verifications.index') }}"><span aria-hidden="true">&larr;</span> Kembali ke daftar</a><span>Pemeriksaan keanggotaan</span></div>
        @if(session('success'))<p role="status" class="vr-feedback vr-feedback-success"><x-sidebar-icon name="audit" />{{ session('success') }}</p>@endif
        @if($errors->any())<div role="alert" class="vr-feedback vr-feedback-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="vr-member-summary" aria-label="Ringkasan anggota">
            <x-member-identity :member="$member"><h2>{{ $member->name }}</h2><p>{{ $member->member_number ?: 'Nomor belum diterbitkan' }}</p></x-member-identity>
            <div class="vr-member-meta"><span class="vr-status vr-status-{{ $member->verification_status }}">{{ $statusLabel }}</span><span>{{ $member->is_active ? 'Anggota aktif' : 'Anggota nonaktif' }}</span><span>Daftar {{ $member->created_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
        </section>
        <form method="post" action="{{ route('admin.verifications.store', $member) }}" class="vr-review-layout">
            @csrf
            <input type="hidden" name="fingerprint" value="{{ $fingerprint }}">
            <section class="vr-data-panel">
                <div class="vr-panel-heading"><div><h2>Data pendaftaran</h2><p>{{ $pending ? 'Periksa dan centang setiap data sebelum menyetujui.' : 'Data anggota saat ini. Keputusan sebelumnya tersimpan di riwayat.' }}</p></div>@if($pending)<span class="vr-check-count" aria-live="polite"><strong x-text="checked.length">{{ count(old('checked', [])) }}</strong>/{{ count($fields) }} diperiksa</span>@else<span class="vr-field-count">{{ count($fields) }} data</span>@endif</div>
                @if($pending)<div class="px-4 py-3"><x-verification-select-all /></div>@endif
                <div class="vr-data-grid">
                    @if($member->requested_belt_level_id && $member->belt_diploma_path)
                        <div class="p-4"><p class="text-sm">Sabuk tercatat: {{ $member->beltLevel?->display_name ?? 'Belum diisi' }}</p><a href="{{ route('profile.belt-diploma', $member) }}" target="_blank" rel="noopener" class="btn btn-secondary mt-2">Buka ijazah sabuk</a></div>
                    @endif
                    @foreach($fields as $key => [$label, $value])
                        <label class="vr-field {{ $key === 'address' ? 'vr-field-address' : '' }} {{ !$value ? 'vr-field-empty' : '' }}" @if($pending) :class="{ 'is-checked': checked.includes('{{ $key }}') }" @endif>
                            @if($pending)<input type="checkbox" name="checked[]" value="{{ $key }}" x-model="checked" @checked(in_array($key, old('checked', [])))>@endif
                            <span><span class="vr-field-label">{{ $label }}</span><span class="vr-field-value">{{ $value ?: '—' }}</span></span>
                        </label>
                    @endforeach
                </div>
                <p class="vr-data-note">Tanda — berarti data belum diisi. Persetujuan tidak otomatis mengaktifkan keanggotaan.</p>
            </section>
            <aside class="vr-decision-panel" aria-label="Keputusan pemeriksaan">
                @if($pending)
                    <div class="vr-panel-heading"><div><h2>Keputusan pemeriksaan</h2><p>Selesaikan pengecekan, lalu simpan keputusan.</p></div></div>
                    <div class="vr-decision-body"><label for="notes">Catatan pemeriksaan</label><textarea id="notes" name="notes" maxlength="5000" rows="4" placeholder="Tuliskan data yang perlu diperbaiki…">{{ old('notes') }}</textarea><p>Wajib diisi untuk meminta perbaikan.</p>
                    <div class="vr-decision-actions"><button name="status" value="approved" class="btn btn-primary" :disabled="checked.length !== {{ count($fields) }}"><x-sidebar-icon name="audit" />Setujui pendaftaran</button><button name="status" value="revision" class="btn btn-secondary">Minta perbaikan</button></div>
                    <p class="vr-check-help" x-show="checked.length !== {{ count($fields) }}">Centang seluruh {{ count($fields) }} data untuk menyetujui.</p></div>
                @else
                    <div class="vr-panel-heading"><div><h2>Pemeriksaan selesai</h2><p>Pendaftaran ini sudah diperiksa.</p></div></div>
                    <div class="vr-decision-body"><span class="vr-status vr-status-{{ $member->verification_status }}">{{ $statusLabel }}</span>@if($latestReview)<dl class="vr-review-facts"><div><dt>Pemeriksa terakhir</dt><dd>{{ $latestReview->reviewer?->name ?? 'Akun pemeriksa dihapus' }}</dd></div><div><dt>Waktu pemeriksaan</dt><dd>{{ $latestReview->created_at->format('d/m/Y H:i') }}</dd></div></dl><details class="vr-latest-notes"><summary>Catatan pemeriksa</summary><p>{{ $latestReview->notes ?: 'Tanpa catatan' }}</p></details>@endif<a href="{{ route('admin.verifications.index') }}" class="btn btn-secondary vr-next">Kembali ke daftar pemeriksaan <x-solid-arrow /></a></div>
                @endif
            </aside>
        </form>
        <details class="vr-history">
            <summary><span>Riwayat verifikasi <span class="vr-history-count">{{ $history->count() }}</span></span><span class="vr-history-toggle">Lihat riwayat <span aria-hidden="true">⌄</span></span></summary>
            <div class="vr-history-list">@forelse($history as $entry)
                <article><div class="vr-history-heading"><strong>{{ $entry->status === 'approved' ? 'Disetujui' : 'Perlu perbaikan' }} · {{ $entry->reviewer?->name ?? 'Akun pemeriksa dihapus' }}</strong><time>{{ $entry->created_at->format('d/m/Y H:i') }}</time></div><p class="vr-history-level">{{ $entry->reviewer_level === 'pb' ? 'Pengurus Pusat' : strtoupper(str_replace('_', ' ', $entry->reviewer_level)) }}</p><p class="vr-history-note">{{ $entry->notes ?: 'Tanpa catatan' }}</p><details class="vr-snapshot"><summary>Data saat diperiksa</summary><dl>@foreach($entry->snapshot['fields'] ?? [] as [$label, $value])<div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>@endforeach</dl></details></article>
            @empty<p class="vr-history-empty">Belum ada pemeriksaan.</p>@endforelse</div>
        </details>
    </div>
</x-app-layout>
