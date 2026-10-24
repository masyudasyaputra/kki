@props(['href' => null, 'label', 'icon' => 'edit'])

@php
    $paths = [
        'edit' => 'M12 20h9M16.5 3.5a2.12 2.12 0 013 3L9 17l-4 1 1-4L16.5 3.5z',
        'delete' => 'M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v6M14 10v6',
        'view' => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'score' => 'M9 5H5v16h14V5h-4M9 3h6v4H9zM8 12h8M8 16h5',
        'people' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m20 0v-2a4 4 0 00-3-3.87M13 3.13a4 4 0 010 7.75M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        'lock' => 'M5 11h14v10H5zM8 11V7a4 4 0 018 0v4',
    ];
    $classes = ['action-button', 'action-button-danger' => $icon === 'delete'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes)->merge(['title' => $label, 'aria-label' => $label]) }}>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $paths[$icon] ?? $paths['edit'] }}" /></svg>
    </a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => 'button', 'title' => $label, 'aria-label' => $label]) }}>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $paths[$icon] ?? $paths['edit'] }}" /></svg>
    </button>
@endif
