@props(['label' => 'Pilih semua data'])
<label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
    <input type="checkbox" data-verification-select-all :checked="allChecked" :disabled="keys.length === 0"
        x-effect="$el.indeterminate = someChecked" @change="toggleAll($event.target.checked)"
        class="rounded border-slate-300 text-[#762137] focus:ring-[#762137]">
    <span>{{ $label }}</span>
</label>
