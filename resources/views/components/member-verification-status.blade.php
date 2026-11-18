@props(['member'])
@php
    $approved = $member->verification_status === 'approved';
    $label = match ($member->verification_status) {
        'approved' => 'Terverifikasi',
        'revision' => 'Perlu perbaikan',
        default => 'Menunggu verifikasi',
    };
@endphp
<span class="mt-2 block text-[11px] {{ $approved ? 'text-emerald-700' : 'text-amber-700' }}">{{ $label }}</span>
