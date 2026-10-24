@props(['headers' => [], 'title' => '', 'subtitle' => ''])

<div {{ $attributes->class(['flex flex-col gap-4']) }}>
    {{-- Toolbar Area --}}
    @if ($title || $subtitle || isset($action))
        <div class="table-toolbar">
            <div>
                @if ($title)
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            <div @isset($action) data-table-add-actions @endisset class="ml-auto flex shrink-0 justify-end">
                {{ $action ?? '' }}
            </div>
        </div>
    @endif

    {{-- Table Container --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto table-surface">
            <table class="data-table min-w-full divide-y divide-slate-200/80">
                <thead>
                    <tr class="bg-slate-50/90 border-b border-slate-200/80">
                        @foreach ($headers as $header)
                            <th scope="col"
                                class="px-5 py-3.5 {{ $header['align'] ?? 'text-left' }} text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                {{ $header['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    </div>
</div>
