<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Verifikasi Wasit/Juri</h1><p class="page-subtitle">Pengajuan dan daftar kompetensi sesuai wilayah kewenangan Anda.</p></x-slot>
    <div class="mx-auto max-w-7xl space-y-4 px-4 py-6">
        @if(session('success'))<p role="status" class="text-emerald-800">{{ session('success') }}</p>@endif
        <nav class="flex flex-wrap gap-2" aria-label="Status kompetensi">@foreach(['pending' => 'Menunggu verifikasi', 'approved' => 'Wasit/Juri terverifikasi', 'revision' => 'Perlu perbaikan', 'expired' => 'Kedaluwarsa'] as $key => $label)<a href="{{ route('admin.qualifications.index', ['status' => $key]) }}" class="btn {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }}</a>@endforeach</nav>
        <form method="get" class="flex gap-2"><input name="status" type="hidden" value="{{ $status }}"><input type="search" name="search" value="{{ request('search') }}" placeholder="Nama atau nomor sertifikat" aria-label="Cari kompetensi" class="min-w-0 flex-1 rounded-lg border-slate-300"><button class="btn btn-primary">Cari</button></form>
        <div class="table-surface overflow-x-auto"><table class="data-table w-full"><thead><tr><th>Karateka</th><th>Kompetensi</th><th>Lisensi</th><th>Wilayah pengajuan</th><th></th></tr></thead><tbody>
            @forelse($qualifications as $qualification)<tr><td>{{ $qualification->user?->name }}</td><td>{{ \App\Models\OfficialQualification::KINDS[$qualification->kind] }}</td><td>{{ $qualification->license_level }}<p class="text-xs text-slate-500">{{ $qualification->certificate_number }}</p></td><td>{{ $qualification->province?->name }}<p class="text-xs text-slate-500">{{ $qualification->city?->name }}</p></td><td><a href="{{ route('admin.qualifications.show', $qualification) }}" class="btn btn-secondary" data-no-loading data-modal-title="Verifikasi Wasit/Juri">{{ $status === 'pending' ? 'Periksa pengajuan' : 'Lihat lisensi' }}</a></td></tr>@empty<tr><td colspan="5">Tidak ada kompetensi pada status ini.</td></tr>@endforelse
        </tbody></table></div><x-pagination :paginator="$qualifications" label="kompetensi" />
    </div>
</x-app-layout>
