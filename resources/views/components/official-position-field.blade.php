@props(['value' => null, 'options' => ['Ketua Umum', 'Sekretaris', 'Bendahara']])
@php
    $position = old('position', $value) ?? '';
    $custom = filled($position) && !in_array($position, $options, true);
@endphp
<div x-data="{ choice: @js($custom ? '__custom__' : $position) }">
    <label for="official-position" class="mb-1.5 block text-xs font-semibold text-slate-700">Jabatan Organisasi <span class="text-red-500">*</span></label>
    <select id="official-position" name="position" x-model="choice" x-bind:name="choice === '__custom__' ? null : 'position'" :required="choice !== '__custom__'"
        class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
        <option value="">Pilih Jabatan</option>
        @foreach($options as $option)<option value="{{ $option }}" @selected($position === $option)>{{ $option }}</option>@endforeach
        <option value="__custom__" @selected($custom)>Lainnya — ketik sendiri</option>
    </select>
    <div x-show="choice === '__custom__'" @if(!$custom) x-cloak @endif class="mt-2">
        <label for="official-position-custom" class="mb-1 block text-xs text-slate-600">Nama jabatan lainnya *</label>
        <input id="official-position-custom" name="position" type="text" value="{{ $custom ? $position : '' }}" maxlength="255" :disabled="choice !== '__custom__'" :required="choice === '__custom__'"
            placeholder="Ketik jabatan organisasi" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20">
    </div>
    <x-input-error :messages="$errors->get('position')" class="mt-1" />
</div>
