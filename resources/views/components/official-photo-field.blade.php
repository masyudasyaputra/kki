@props(['url' => null])
<div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4" x-data="{ preview: @js($url), objectUrl: null }">
    <label for="official-photo" class="block text-xs font-semibold text-slate-700">Foto pengurus</label>
    <div class="mt-3 flex items-start gap-4">
        <div class="flex h-24 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
            <img x-show="preview" :src="preview" alt="Pratinjau foto pengurus" class="h-full w-full object-cover" @if($url) src="{{ $url }}" @endif>
            <span x-show="!preview" class="px-2 text-center text-xs text-slate-400">Belum ada foto</span>
        </div>
        <div class="min-w-0 flex-1">
            <input id="official-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-slate-600"
                @change="if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null; preview = objectUrl || @js($url)">
            <p class="mt-2 text-xs text-slate-500">JPG, PNG, atau WebP, maksimal 2 MB. Disarankan foto tegak 3:4. Kosongkan untuk mempertahankan foto saat ini.</p>
            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        </div>
    </div>
</div>
