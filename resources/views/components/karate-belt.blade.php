@props(['name'])
@php
    $beltName = mb_strtolower(trim((string) $name));
    $colors = [
        'putih' => '#f5f3ee', 'white' => '#f5f3ee',
        'kuning' => '#facc15', 'yellow' => '#facc15',
        'jingga' => '#f97316', 'oranye' => '#f97316', 'orange' => '#f97316',
        'hijau' => '#16a34a', 'green' => '#16a34a',
        'biru' => '#2563eb', 'blue' => '#2563eb',
        'ungu' => '#9333ea', 'purple' => '#9333ea',
        'coklat' => '#924b28', 'cokelat' => '#924b28', 'brown' => '#924b28',
        'merah' => '#dc2626', 'red' => '#dc2626',
        'hitam' => '#151519', 'black' => '#151519',
    ];
    $beltColor = '#a1a1aa';
    foreach ($colors as $word => $color) {
        if (str_contains($beltName, $word)) {
            $beltColor = $color;
            break;
        }
    }
@endphp
<svg {{ $attributes }} viewBox="0 0 180 76" fill="none" role="img" aria-label="Sabuk {{ $name }}">
    <g fill="{{ $beltColor }}" stroke="#ffffff" stroke-opacity=".3" stroke-width="1.2" stroke-linejoin="round">
        <path d="M13 19 Q47 13 86 20 L86 37 Q48 30 13 37 Z" />
        <path d="M94 20 Q133 13 167 19 L167 37 Q130 30 94 37 Z" />
        <path d="M81 30 L96 36 L71 71 L52 61 Z" />
        <path d="M91 31 L104 28 L130 61 L111 71 Z" />
        <path d="M80 17 L99 17 L105 30 L98 42 L81 39 L75 28 Z" />
    </g>
    <g stroke="#fff" stroke-opacity=".18" stroke-width="1.4">
        <path d="M18 24 Q48 19 72 25 M111 24 Q135 20 162 24 M62 60 L81 36 M115 59 L101 38 M81 23 L98 35 M83 35 L97 23" />
    </g>
</svg>
