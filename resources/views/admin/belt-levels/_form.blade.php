<form method="POST" action="{{ $beltLevel->exists ? route('admin.belt-levels.update', $beltLevel) : route('admin.belt-levels.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    @csrf
    @if ($beltLevel->exists) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama sabuk</label>
            <input id="name" name="name" type="text" maxlength="100" required value="{{ old('name', $beltLevel->name) }}" placeholder="Contoh: Putih atau Hitam" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#762137] focus:ring-[#762137]">
            @error('name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="kyu_dan" class="mb-1.5 block text-sm font-medium text-slate-700">Tingkatan Kyu/DAN</label>
            <input id="kyu_dan" name="kyu_dan" type="text" maxlength="20" required value="{{ old('kyu_dan', $beltLevel->kyu_dan) }}" placeholder="Contoh: Kyu 7 atau DAN I" aria-describedby="kyu-dan-help" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#762137] focus:ring-[#762137]">
            <p id="kyu-dan-help" class="mt-1 text-xs text-slate-500">Gunakan format “Kyu 7” atau “DAN I”.</p>
            @error('kyu_dan')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="order" class="mb-1.5 block text-sm font-medium text-slate-700">Urutan kenaikan</label>
            <input id="order" name="order" type="number" min="1" max="999" required value="{{ old('order', $beltLevel->order) }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#762137] focus:ring-[#762137]">
            @error('order')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="membership_fee" class="mb-1.5 block text-sm font-medium text-slate-700">Biaya iuran (Rp)</label>
            <input id="membership_fee" name="membership_fee" type="number" min="0" max="999999999" step="1000" required value="{{ old('membership_fee', $beltLevel->membership_fee ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#762137] focus:ring-[#762137]">
            @error('membership_fee')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <input type="hidden" name="is_active" value="0">
        <label for="is_active" class="flex cursor-pointer items-start gap-3">
            <input id="is_active" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $beltLevel->is_active ?? true)) class="mt-0.5 rounded border-slate-300 text-[#641b2d] focus:ring-[#762137]">
            <span><span class="block text-sm font-medium text-slate-800">Tingkatan aktif</span><span class="mt-0.5 block text-xs leading-5 text-slate-500">Tingkatan aktif tersedia untuk pendaftaran dan penilaian ujian.</span></span>
        </label>
    </div>

    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.belt-levels.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#641b2d] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#4a1421] focus:outline-none focus:ring-2 focus:ring-[#762137] focus:ring-offset-2">{{ $beltLevel->exists ? 'Simpan Perubahan' : 'Tambah Tingkatan' }}</button>
    </div>
</form>
