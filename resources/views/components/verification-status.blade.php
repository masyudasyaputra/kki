@props(['status' => 'pending'])
@php
    $label = ['approved' => 'Terverifikasi', 'revision' => 'Perlu perbaikan', 'pending' => 'Menunggu verifikasi'][$status] ?? 'Menunggu verifikasi';
    $color = ['approved' => 'bg-emerald-50 text-emerald-800', 'revision' => 'bg-red-50 text-red-800', 'pending' => 'bg-amber-50 text-amber-900'][$status] ?? 'bg-amber-50 text-amber-900';
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold', $color]) }} aria-label="Status verifikasi data: {{ $label }}">
    @if($status === 'approved')<x-sidebar-icon name="audit" class="h-3.5 w-3.5" />@endif
    {{ $label }}
</span>
