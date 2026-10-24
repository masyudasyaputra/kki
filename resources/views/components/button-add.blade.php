@props(['href'])

<div class="hidden sm:inline-block">
    <a href="{{ $href }}"
        {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 active:bg-slate-950 text-white px-4 py-2.5 rounded-lg text-xs font-semibold shadow-sm hover:shadow transition-all']) }}>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>{{ $slot }}</span>
    </a>
</div>

{{-- Mobile Floating Action Button --}}
<div class="fixed bottom-6 right-6 z-40 sm:hidden">
    <a href="{{ $href }}"
        class="flex items-center justify-center w-12 h-12 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-lg shadow-red-600/30 active:scale-95 transition-transform"
        aria-label="Tambah data">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
    </a>
</div>
