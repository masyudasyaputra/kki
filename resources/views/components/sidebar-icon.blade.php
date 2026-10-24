@props(['name' => 'dashboard'])
@php
    $paths = [
        'dashboard' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'people' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m20 0v-2a4 4 0 00-3-3.87M13 3.13a4 4 0 010 7.75M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        'building' => 'M3 21h18M5 21V3h14v18M9 7h1m4 0h1M9 11h1m4 0h1M10 21v-6h4v6',
        'exam' => 'M9 5H5v16h14V5h-4M9 3h6v4H9zM8 12h8M8 16h5',
        'user' => 'M20 21a8 8 0 00-16 0M16 7a4 4 0 11-8 0 4 4 0 018 0z',
        'receipt' => 'M5 3h14v18l-3-2-4 2-4-2-3 2V3zM9 7h6M9 11h6M9 15h3',
        'wallet' => 'M20 8V5H5a2 2 0 010-4h14M3 3v16a2 2 0 002 2h15V8H5a2 2 0 01-2-2m17 6h-5v5h5m-3-2.5h.01',
        'globe' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3 12h18M12 3a18 18 0 010 18 18 18 0 010-18z',
        'logout' => 'M9 21H4V3h5M14 7l5 5-5 5M9 12h10',
    ];
@endphp
<svg {{ $attributes->class(['h-4 w-4 shrink-0']) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $paths[$name] ?? $paths['dashboard'] }}" /></svg>
