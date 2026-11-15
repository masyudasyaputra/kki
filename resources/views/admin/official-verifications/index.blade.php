<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Verifikasi Pengurus</h1><p class="page-subtitle">Periksa pengajuan pengurus non-karateka dan tetapkan wilayah tugasnya.</p></x-slot>
    <div class="mx-auto max-w-7xl space-y-4 px-4 py-6 sm:px-6">
        <div class="grid grid-cols-2 gap-3">
            @foreach(['pending' => 'Menunggu verifikasi', 'appointed' => 'Sudah ditetapkan'] as $key => $label)
                <a href="{{ route('admin.official-verifications.index', ['status' => $key]) }}" class="rounded-xl border bg-white p-4 {{ $status === $key ? 'border-red-500' : 'border-slate-200' }}"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold">{{ $counts[$key] }}</p></a>
            @endforeach
        </div>
        <form method="get" class="flex gap-2"><input type="hidden" name="status" value="{{ $status }}"><input type="search" name="search" value="{{ request('search') }}" aria-label="Cari pengurus" placeholder="Nama, email, atau WhatsApp" class="min-w-0 flex-1 rounded-lg border-slate-200"><button class="btn btn-primary">Cari</button></form>
        <div class="table-surface overflow-x-auto"><table class="data-table w-full"><thead><tr><th>Pendaftar</th><th>Jabatan</th><th>Wilayah</th><th>Tanggal daftar</th><th>Tindakan</th></tr></thead><tbody>
            @forelse($users as $user)
                <tr><td><p class="font-semibold">{{ $user->name }}</p><p class="text-xs text-slate-500">{{ $user->email }}</p><p class="text-xs text-slate-500">{{ $user->whatsapp }}</p></td><td>{{ $user->official?->position ?? $user->requested_position }}</td><td>{{ $user->province?->name ?? 'Belum diisi' }}<p class="text-xs text-slate-500">{{ $user->city?->name ?? '-' }}</p></td><td>{{ $user->created_at?->format('d/m/Y') }}</td><td><a data-no-loading data-modal-title="Verifikasi &amp; Penetapan Pengurus" aria-haspopup="dialog" href="{{ route('admin.officials.create', ['user_id' => $user->id]) }}" class="btn btn-secondary">{{ $status === 'pending' ? 'Periksa & tetapkan' : 'Lihat penetapan' }}</a></td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-slate-500">Tidak ada pengajuan pengurus pada status ini.</td></tr>
            @endforelse
        </tbody></table></div>
        <x-pagination :paginator="$users" label="pengurus" />
    </div>
</x-app-layout>
