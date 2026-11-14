<x-app-layout>
    @php
        $titles = ['overview' => 'Beranda Karateka', 'card' => 'Kartu Anggota', 'exams' => 'Ujian Saya', 'payments' => 'Pembayaran', 'certificates' => 'Sertifikat Saya'];
        $verification = ['pending' => 'Menunggu pemeriksaan', 'approved' => 'Terverifikasi', 'revision' => 'Perlu perbaikan'][$user->verification_status ?? 'pending'] ?? 'Menunggu pemeriksaan';
    @endphp
    <x-slot name="header"><h1 class="page-title">{{ $titles[$section] }}</h1><p class="page-subtitle">Ruang karateka KKI</p></x-slot>
    <div class="karateka-space mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-3"><span class="text-sm text-slate-500">Verifikasi data anggota</span><x-verification-status :status="$user->verification_status" /></div>
        @if ($section === 'overview')
            @include('member.overview')
        @elseif ($section === 'card')
            <div class="karateka-panel flex flex-wrap items-center justify-between gap-3"><div><h2>Identitas karateka</h2><p class="mt-1 text-sm text-slate-500">Verifikasi: {{ $verification }} · Status anggota: {{ $user->is_active ? 'Aktif' : 'Belum aktif' }}</p></div><a href="{{ route('profile.edit') }}" class="btn btn-secondary">Perbarui data</a></div>
            @include('member.card')
        @elseif ($section === 'exams')
            <section class="karateka-panel"><div class="flex flex-wrap items-center justify-between gap-4"><div><h2>Perjalanan ujian Anda</h2><p class="mt-2 text-sm text-slate-500">Kepesertaan dan hasil yang dicatat oleh pengurus. Hubungi pengurus dojo untuk pendaftaran ujian.</p></div><a href="{{ route('public.agenda') }}" class="btn btn-secondary">Lihat agenda UKT</a></div>
            @forelse($myExams as $participant)<article class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-3"><div><h3 class="font-bold">{{ $participant->exam?->name ?? 'Ujian' }}</h3><p class="mt-1 text-sm text-slate-500">{{ $participant->exam?->execution_date?->format('d/m/Y') ?? 'Jadwal belum tersedia' }} · {{ $participant->exam?->location ?? 'Lokasi belum tersedia' }}</p></div><div class="text-sm"><p class="text-slate-500">Target tingkatan</p><p class="mt-1 font-semibold">{{ $participant->targetBelt?->display_name ?? 'Belum ditentukan' }}</p></div><div class="text-sm"><span class="karateka-badge">{{ ['pass' => 'Lulus', 'fail' => 'Belum lulus', 'pending' => 'Menunggu hasil'][$participant->result ?? 'pending'] ?? 'Menunggu hasil' }}</span><p class="mt-2 text-slate-500">Biaya ujian: {{ $participant->payment_status === 'paid' ? 'Lunas' : 'Belum lunas' }}</p></div></article>@empty<div class="karateka-empty"><x-sidebar-icon name="exam" class="h-8 w-8" /><h3>Belum ada ujian yang diikuti</h3><p>Kepesertaan akan tampil setelah pengurus mendaftarkan Anda.</p></div>@endforelse
            <x-pagination :paginator="$myExams" label="ujian" /></section>
        @elseif ($section === 'payments')
            <section class="karateka-panel"><h2>Tagihan & riwayat pembayaran</h2><p class="mt-2 text-sm text-slate-500">Pembayaran iuran dan ujian yang tercatat atas nama Anda.</p>
            @forelse($myPayments as $payment)
                @php($canPay = $payment->status === 'pending' && $payment->payment_url && (!$payment->expires_at || $payment->expires_at->isFuture()) && in_array(parse_url($payment->payment_url, PHP_URL_SCHEME), ['https', 'http'], true))
                <article class="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-5"><div><h3 class="font-bold">{{ $payment->type === 'ujian' ? 'Biaya ujian' : 'Iuran anggota' }}</h3><p class="mt-1 break-all text-sm text-slate-500">{{ $payment->invoice_number }}</p><p class="mt-1 text-xs text-slate-500">{{ $payment->created_at?->format('d/m/Y') }}@if($payment->paid_at) · Dibayar {{ $payment->paid_at->format('d/m/Y') }}@endif</p></div><div class="flex flex-wrap items-center gap-4"><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong><span class="karateka-badge">{{ ['pending' => 'Menunggu pembayaran', 'paid' => 'Lunas', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'canceled' => 'Dibatalkan'][$payment->status] ?? $payment->status }}</span>@if($canPay)<a href="{{ $payment->payment_url }}" class="btn btn-primary">Lanjutkan pembayaran</a>@elseif($payment->status === 'pending')<span class="text-xs text-slate-500">Hubungi pengurus untuk tautan pembayaran.</span>@endif</div></article>
            @empty<div class="karateka-empty"><x-sidebar-icon name="wallet" class="h-8 w-8" /><h3>Belum ada transaksi</h3><p>Tagihan dan pembayaran Anda akan ditampilkan di sini.</p></div>@endforelse
            <x-pagination :paginator="$myPayments" label="transaksi" /></section>
        @elseif ($section === 'certificates')
            <section class="karateka-panel"><h2>Pencapaian karateka</h2><p class="mt-2 text-sm text-slate-500">Sertifikat resmi yang sudah dikirim oleh pengurus kepada Anda.</p>
            @forelse($myCertificates as $certificate)<article class="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-5"><div class="flex items-start gap-3"><x-sidebar-icon name="certificate" class="h-7 w-7 text-red-700" /><div><h3 class="font-bold">{{ $certificate->snapshot['exam_name'] ?? 'Sertifikat ujian' }}</h3><p class="mt-1 text-sm text-slate-500">{{ $certificate->snapshot['rank'] ?? '' }} · {{ $certificate->number }}</p><p class="mt-1 text-xs text-slate-500">Diterima {{ $certificate->sent_at->format('d/m/Y') }}</p></div></div><a class="btn btn-secondary" href="{{ route('member.certificates.show', $certificate) }}" target="_blank" rel="noopener">Buka / Unduh sertifikat</a></article>@empty<div class="karateka-empty"><x-sidebar-icon name="certificate" class="h-8 w-8" /><h3>Sertifikat pertama Anda menanti</h3><p>Sertifikat akan tersedia setelah diterbitkan dan dikirim oleh pengurus.</p></div>@endforelse
            <x-pagination :paginator="$myCertificates" label="sertifikat" /></section>
        @endif
    </div>
</x-app-layout>
