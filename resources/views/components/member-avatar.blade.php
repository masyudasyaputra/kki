@props(['member' => null, 'name' => null, 'photo' => null])
@php
    $displayName = $name ?? $member?->name ?? 'Anggota';
    $photoPath = $photo ?? $member?->photo;
@endphp
<span {{ $attributes->class(['member-avatar']) }}>
    <span aria-hidden="true">{{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}</span>
    @if($photoPath)
        <img src="{{ asset('storage/'.$photoPath) }}" alt="Pasfoto {{ $displayName }}" width="40" height="48" loading="lazy" decoding="async" onerror="this.hidden = true">
    @endif
</span>
