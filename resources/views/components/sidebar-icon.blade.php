@props(['name' => 'dashboard'])
@php
    $paths = [
        'dashboard' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'people' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m20 0v-2a4 4 0 00-3-3.87M13 3.13a4 4 0 010 7.75M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        'membership' => 'M3 5h18v14H3zM7 10a2 2 0 100-4 2 2 0 000 4zM5 15c0-2 4-2 4 0m3-5h6m-6 4h6',
        'organization' => 'M9 3h6v5H9zM4 16h6v5H4zM14 16h6v5h-6zM12 8v4M7 12h10M7 12v4m10-4v4',
        'building' => 'M3 21h18M5 21V3h14v18M9 7h1m4 0h1M9 11h1m4 0h1M10 21v-6h4v6',
        'exam' => 'M9 5H5v16h14V5h-4M9 3h6v4H9zM8 12h8M8 16h5',
        'exam-cert' => 'M6 3h12v12H6zM9 7h6M9 10h4M12 15l-2 6 2-1 2 1-2-6',
        'certificate' => 'M7 3h8l4 4v8M15 3v5h5M7 3H5v18h9M8 12h7M8 16h5M18 14l1.2 2.4 2.7.4-1.9 1.9.4 2.7-2.4-1.3-2.4 1.3.4-2.7-1.9-1.9 2.7-.4z',
        'signature' => 'M4 20h16M5 16l8.8-8.8a2.1 2.1 0 013 3L8 19H5zM13 8l3 3M4 13c1.2-1.4 2.4-1.4 3.6 0s2.4 1.4 3.6 0',
        'user' => 'M20 21a8 8 0 00-16 0M16 7a4 4 0 11-8 0 4 4 0 018 0z',
        'phone' => 'M22 16.9v3a2 2 0 01-2.2 2A19.8 19.8 0 013.1 5.2 2 2 0 015.1 3h3a2 2 0 012 1.7c.1 1 .4 2 .7 2.9a2 2 0 01-.5 2.1L9 11a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.9.3 1.9.6 2.9.7a2 2 0 011.7 2z',
        'receipt' => 'M5 3h14v18l-3-2-4 2-4-2-3 2V3zM9 7h6M9 11h6M9 15h3',
        'wallet' => 'M20 8V5H5a2 2 0 010-4h14M3 3v16a2 2 0 002 2h15V8H5a2 2 0 01-2-2m17 6h-5v5h5m-3-2.5h.01',
        'globe' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3 12h18M12 3a18 18 0 010 18 18 18 0 010-18z',
        'map-pin' => 'M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1116 0zM12 10a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        'belt' => 'M3 9h18v6H3zM10 9v6m4-6v6m-4-3h4',
        'audit' => 'M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11zM9 12l2 2 4-4',
        'settings' => 'M12 8a4 4 0 100 8 4 4 0 000-8zM12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42',
        'logout' => 'M9 21H4V3h5M14 7l5 5-5 5M9 12h10',
    ];
@endphp
<svg {{ $attributes->class(['h-4 w-4 shrink-0']) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $paths[$name] ?? $paths['dashboard'] }}" /></svg>
