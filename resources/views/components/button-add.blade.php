@props(['href'])
<a data-create-modal href="{{ $href }}" {{ $attributes->class(['btn btn-primary']) }}>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14" /></svg>
    <span>{{ $slot }}</span>
</a>
