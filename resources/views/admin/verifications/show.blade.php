<x-app-layout>
    <x-slot name="header"><div><h1 class="page-title">Pemeriksaan Pendaftaran Anggota</h1><p class="page-subtitle">{{ $member->member_number }} · {{ $member->name }}</p></div></x-slot>
    <div class="mx-auto max-w-5xl space-y-4 px-4 py-6 sm:px-6">
        <a class="btn btn-secondary" href="{{ route('admin.verifications.index') }}">Kembali ke daftar</a>
        <x-member-identity :member="$member" class="rounded-xl border border-slate-200 bg-white p-4"><p class="font-semibold">{{ $member->name }}</p><p class="text-xs text-slate-500">{{ $member->member_number }}</p></x-member-identity>
        @if (session('success'))<p role="status" class="rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('success') }}</p>@endif
        @if ($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-3 text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p>Status verifikasi: <strong>{{ ['pending' => 'Menunggu verifikasi', 'approved' => 'Disetujui', 'revision' => 'Perlu perbaikan'][$member->verification_status] }}</strong></p><p class="mt-1 text-xs text-slate-500">Status keanggotaan: {{ $member->is_active ? 'Aktif' : 'Nonaktif' }}. Pendaftaran: {{ $member->created_at?->format('d/m/Y H:i') }}.</p></div>
        <form method="post" action="{{ route('admin.verifications.store', $member) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="fingerprint" value="{{ $fingerprint }}">
            <div class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-semibold">Data pendaftaran</h2><p class="mt-1 text-xs text-slate-500">Periksa setiap isian. Seluruh checklist wajib dicentang untuk menyetujui pendaftaran. Data kosong ditampilkan dengan tanda —.</p>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach ($fields as $key => [$label, $value])
                        <label class="flex items-start gap-3 py-3"><input type="checkbox" name="checked[]" value="{{ $key }}" @checked(in_array($key, old('checked', []))) @disabled($member->verification_status !== 'pending') class="mt-1 rounded border-slate-300 text-red-600"><span class="min-w-0"><span class="block text-xs text-slate-500">{{ $label }}</span><span class="block break-words font-medium">{{ $value ?: '—' }}</span></span></label>
                    @endforeach
                </div>
            </div>
            @if ($member->verification_status === 'pending')
                <div class="space-y-3 rounded-xl border border-slate-200 bg-white p-5"><label for="notes" class="block font-semibold">Catatan pemeriksaan</label><textarea id="notes" name="notes" maxlength="5000" rows="4" class="w-full rounded-lg border-slate-200" placeholder="Jelaskan data yang perlu diperbaiki">{{ old('notes') }}</textarea><p class="text-xs text-slate-500">Catatan wajib diisi jika pendaftaran perlu perbaikan.</p><div class="flex flex-wrap gap-3"><button name="status" value="approved" class="btn btn-primary">Setujui pendaftaran</button><button name="status" value="revision" class="btn btn-secondary">Minta perbaikan</button></div></div>
            @endif
        </form>
        <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-semibold">Riwayat verifikasi</h2>
            @forelse ($history as $entry)
                <div class="border-t border-slate-100 pt-3"><p class="text-sm font-semibold">{{ $entry->status === 'approved' ? 'Disetujui' : 'Perlu perbaikan' }} · {{ $entry->reviewer?->name ?? 'Akun pemeriksa dihapus' }} ({{ $entry->reviewer_level === 'pb' ? 'Pengurus Pusat' : strtoupper(str_replace('_', ' ', $entry->reviewer_level)) }})</p><p class="text-xs text-slate-500">{{ $entry->created_at->format('d/m/Y H:i') }}</p><p class="mt-2 whitespace-pre-wrap text-sm">{{ $entry->notes ?: 'Tanpa catatan' }}</p><details class="mt-2 text-xs"><summary class="cursor-pointer">Data saat diperiksa</summary><dl class="mt-2 grid gap-2 sm:grid-cols-2">@foreach ($entry->snapshot['fields'] as [$label, $value])<div><dt class="text-slate-500">{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>@endforeach</dl></details></div>
            @empty<p class="text-sm text-slate-500">Belum ada pemeriksaan.</p>@endforelse
        </section>
    </div>
</x-app-layout>
