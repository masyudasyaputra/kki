<x-app-layout>
    <x-slot name="header"><div><h1 class="page-title">Verifikasi Keanggotaan</h1><p class="page-subtitle">Tim Verifikasi {{ strtoupper(str_replace('_', ' ', $level)) }} · Pemeriksaan seluruh data pendaftaran anggota</p></div></x-slot>
    <div class="mx-auto max-w-7xl space-y-4 px-4 py-6 sm:px-6">
        <div class="grid grid-cols-3 gap-3">
            @foreach (['pending' => 'Menunggu verifikasi', 'approved' => 'Disetujui', 'revision' => 'Perlu perbaikan'] as $key => $label)
                <a class="rounded-xl border bg-white p-4 {{ $status === $key ? 'border-red-500' : 'border-slate-200' }}" href="{{ route('admin.verifications.index', ['status' => $key]) }}"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold">{{ $counts[$key] ?? 0 }}</p></a>
            @endforeach
        </div>
        <form method="get" class="flex gap-2"><input type="hidden" name="status" value="{{ $status }}"><input type="search" name="search" value="{{ request('search') }}" placeholder="Nama, ID anggota, email, WhatsApp" aria-label="Cari pendaftaran" class="min-w-0 flex-1 rounded-lg border-slate-200"><button class="btn btn-primary">Cari</button></form>
        <div class="table-surface overflow-x-auto"><table class="data-table w-full"><thead><tr><th>Anggota</th><th>Wilayah</th><th>Dojo / Sabuk</th><th>Pendaftaran</th><th></th></tr></thead><tbody>
            @forelse ($members as $member)
                <tr><td><p class="font-semibold">{{ $member->name }}</p><p class="text-xs text-slate-500">{{ $member->member_number }} · {{ $member->whatsapp ?: '-' }}</p></td><td>{{ $member->province?->name ?? '-' }}<p class="text-xs text-slate-500">{{ $member->city?->name ?? '-' }} / {{ $member->district ?: '-' }}</p></td><td>{{ $member->dojo?->name ?? '-' }}<p class="text-xs text-slate-500">{{ $member->beltLevel?->name ?? '-' }}</p></td><td>{{ $member->created_at?->format('d/m/Y') }}</td><td><a class="btn btn-secondary" href="{{ route('admin.verifications.show', $member) }}">Periksa data</a></td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-slate-500">Tidak ada pendaftaran pada status ini.</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $members->links() }}
    </div>
</x-app-layout>
