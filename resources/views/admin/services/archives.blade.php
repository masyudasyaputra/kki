<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Arsip Dokumen SK</h1><p class="page-subtitle">Dokumen administrasi pengurus, wilayah, dojo, dan ujian.</p></x-slot>
    <div class="mx-auto max-w-5xl space-y-5 px-4 py-6">
        <a href="{{ route('admin.services.index') }}" class="btn btn-secondary">Kembali ke layanan</a>
        <form method="get" class="flex flex-wrap gap-3"><select name="type" class="rounded-lg border-slate-300">@foreach(['official' => 'Pengurus', 'province' => 'Provinsi', 'dojo' => 'Dojo', 'exam' => 'Ujian'] as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach</select><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama" aria-label="Cari arsip berdasarkan nama" class="rounded-lg border-slate-300"><button class="btn btn-primary">Cari arsip</button></form>
        <div class="divide-y divide-slate-100 rounded-xl border bg-white">@forelse($records as $record)<article class="flex items-center justify-between gap-4 p-4"><div><h2 class="font-semibold">{{ $record->name }}</h2><p class="mt-1 text-xs text-slate-500">{{ $type === 'exam' ? ($record->certificate_settings['decision_number'] ?? 'Nomor SK belum diisi') : ($record->sk_number ?: 'Nomor SK belum diisi') }}</p></div><a href="{{ route('admin.sk-documents.show', ['entity' => $type, 'id' => $record->id]) }}" target="_blank" rel="noopener" class="btn btn-secondary">Buka SK</a></article>@empty<p class="p-5 text-sm text-slate-500">Belum ada dokumen yang sesuai.</p>@endforelse</div>
        {{ $records->links() }}
    </div>
</x-app-layout>
