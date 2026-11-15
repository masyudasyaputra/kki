<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Manajemen Nomor ID</h1><p class="page-subtitle">ID pengurus · khusus Super Admin</p></x-slot>
    <div class="mx-auto max-w-7xl space-y-4 px-4 py-6">
        @include('admin.member-numbers.tabs')
        <form method="get" class="flex gap-2"><input type="search" name="search" value="{{ request('search') }}" placeholder="Nama atau ID pengurus" aria-label="Cari pengurus" class="min-w-0 flex-1 rounded-lg border-slate-200"><button class="btn btn-primary">Cari</button></form>
        <div class="table-surface overflow-x-auto"><table class="data-table w-full"><thead><tr><th>Pengurus</th><th>ID pengurus</th><th>Provinsi</th><th>Mode</th><th></th></tr></thead><tbody>
            @forelse($officials as $official)<tr><td>{{ $official->name }}</td><td class="font-mono">{{ $official->official_number }}</td><td>{{ $official->province?->name }}</td><td>{{ $official->official_number_manual ? 'Manual' : 'Otomatis' }}</td><td><a class="btn btn-secondary" href="{{ route('admin.official-numbers.edit', $official) }}">Kelola ID</a></td></tr>@empty<tr><td colspan="5">Tidak ada pengurus yang cocok.</td></tr>@endforelse
        </tbody></table></div>
        <x-pagination :paginator="$officials" label="pengurus" />
    </div>
</x-app-layout>
