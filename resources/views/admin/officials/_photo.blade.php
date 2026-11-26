@if($official->photo_url)
    <img src="{{ $official->photo_url }}" alt="Foto {{ $official->name }}" class="h-16 w-12 shrink-0 rounded-lg border border-slate-200 object-cover" loading="lazy" width="48" height="64">
@else
    <span class="flex h-16 w-12 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-lg font-bold text-slate-500" aria-label="Foto belum tersedia">{{ mb_strtoupper(mb_substr($official->name, 0, 1)) }}</span>
@endif
