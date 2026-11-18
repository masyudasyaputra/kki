@props(['member'])
@php
    $approved = $member->verification_status === 'approved';
    $label = match ($member->verification_status) {
        'approved' => 'Terverifikasi',
        'revision' => 'Perlu perbaikan',
        default => 'Menunggu verifikasi',
    };
@endphp
<span class="inline-block rounded-full px-2 py-1 text-[11px] {{ $approved ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $label }}</span>
