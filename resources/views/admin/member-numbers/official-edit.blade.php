<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Kelola ID Pengurus</h1><p class="page-subtitle">{{ $official->name }} · {{ $official->official_number }}</p></x-slot>
    <div class="mx-auto max-w-4xl space-y-4 px-4 py-6">
        <a class="btn btn-secondary" href="{{ route('admin.official-numbers.index') }}">Kembali</a>
        @if(session('success'))<p role="status" class="text-emerald-800">{{ session('success') }}</p>@endif
        @if($errors->any())<div role="alert" class="text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="post" action="{{ route('admin.official-numbers.update', $official) }}" class="space-y-4 rounded-xl border bg-white p-6" x-data="{ mode: @js(old('mode', 'manual')) }">
            @csrf @method('put')
            <input type="hidden" name="expected_number" value="{{ $official->official_number }}">
            <div><x-input-label for="number-mode" value="Tindakan" /><select id="number-mode" name="mode" x-model="mode" class="w-full rounded-lg border-slate-300"><option value="manual">Ubah nomor ID</option><option value="auto">Hitung ulang nomor otomatis</option></select></div>
            <div x-show="mode === 'manual'"><x-input-label for="official-number" value="Nomor ID pengurus" /><x-text-input id="official-number" name="number" :value="old('number', $official->official_number)" maxlength="40" x-bind:required="mode === 'manual'" x-bind:disabled="mode !== 'manual'" /><p class="text-xs text-slate-500">Gunakan awalan PGR-. Nomor harus unik.</p></div>
            <p x-show="mode === 'auto'" x-cloak>Nomor otomatis: <strong>{{ $automatic }}</strong></p>
            <div><x-input-label for="number-reason" value="Alasan perubahan *" /><textarea id="number-reason" name="reason" required maxlength="2000" class="w-full rounded-lg border-slate-300">{{ old('reason') }}</textarea></div>
            <button class="btn btn-primary">Simpan perubahan nomor</button>
        </form>
        <section class="rounded-xl border bg-white p-6"><h2 class="font-bold">Riwayat perubahan nomor</h2>@forelse($changes as $change)<article class="mt-4 border-t pt-3 text-sm"><p>{{ $change->before['official_number'] }} → {{ $change->after['official_number'] }}</p><p>{{ $change->after['reason'] }}</p><time>{{ $change->created_at->format('d/m/Y H:i') }}</time></article>@empty<p class="mt-3 text-sm text-slate-500">Belum ada perubahan nomor.</p>@endforelse<x-pagination :paginator="$changes" label="perubahan" /></section>
    </div>
</x-app-layout>
