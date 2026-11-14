@props(['direction' => 'right'])
<svg {{ $attributes->class(['kd-solid-arrow']) }} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
    @if ($direction === 'up-right')
        <path d="M5 3a1 1 0 0 0 0 2h8.586L3.293 15.293a1 1 0 1 0 1.414 1.414L15 6.414V15a1 1 0 1 0 2 0V4a1 1 0 0 0-1-1H5Z" />
    @else
        <path d="M11.293 3.293a1 1 0 0 0 0 1.414L15.586 9H3a1 1 0 1 0 0 2h12.586l-4.293 4.293a1 1 0 0 0 1.414 1.414l6-6a1 1 0 0 0 0-1.414l-6-6a1 1 0 0 0-1.414 0Z" />
    @endif
</svg>
