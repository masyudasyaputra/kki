@props(['title' => '', 'subtitle' => ''])
<div {{ $attributes->class(['table-toolbar']) }}>
    <div class="min-w-0 flex-1">
        @if ($title)<h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>@endif
        @if ($subtitle)<p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>@endif
    </div>
    @if ($slot->isNotEmpty())<div data-table-add-actions class="ml-auto flex shrink-0 items-center justify-end gap-2">{{ $slot }}</div>@endif
</div>
