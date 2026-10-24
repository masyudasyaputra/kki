<x-app-layout>
    <x-slot name="header">
        <div><h1 class="page-title">Data Anggota</h1><p class="page-subtitle">Keanggotaan dalam cakupan wilayah akun</p></div>
    </x-slot>
    <div class="py-4">
        <div class="mx-auto max-w-7xl space-y-3 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-3 text-xs text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <div class="table-filter-host rounded-xl border border-slate-200 bg-white p-3">
                <form data-table-filters method="GET" action="{{ route('admin.members.index') }}" class="table-filter-fields">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Nama, nomor anggota, WA..." aria-label="Cari anggota">
                    <select name="status" aria-label="Status anggota"><option value="">Semua status</option><option value="1" @selected(request('status') === '1')>Aktif</option><option value="0" @selected(request('status') === '0')>Nonaktif</option></select>
                    <select name="belt_level_id" aria-label="Tingkat sabuk"><option value="">Semua sabuk</option>@foreach ($beltLevels as $belt)<option value="{{ $belt->id }}" @selected((string) request('belt_level_id') === (string) $belt->id)>{{ $belt->name }}</option>@endforeach</select>
                    <select name="province_id" aria-label="Provinsi"><option value="">Semua provinsi</option>@foreach ($provinces as $province)<option value="{{ $province->id }}" @selected((string) request('province_id') === (string) $province->id)>{{ $province->name }}</option>@endforeach</select>
                    <select name="city_id" aria-label="Cabang"><option value="">Semua cabang</option>@foreach ($cities as $city)<option value="{{ $city->id }}" @selected((string) request('city_id') === (string) $city->id)>{{ $city->name }}</option>@endforeach</select>
                    <select name="dojo_id" aria-label="Dojo"><option value="">Semua dojo</option>@foreach ($dojos as $dojo)<option value="{{ $dojo->id }}" @selected((string) request('dojo_id') === (string) $dojo->id)>{{ $dojo->name }}</option>@endforeach</select>
                    <button type="submit" class="btn btn-primary">Terapkan</button><a href="{{ route('admin.members.index') }}" class="btn btn-secondary">Reset</a>
                </form>
            </div>
            <x-table-toolbar title="Daftar Anggota" :subtitle="$members->total() . ' anggota ditemukan'">
                <x-button-add href="{{ route('admin.members.create') }}">Tambah Anggota</x-button-add>
            </x-table-toolbar>
            <div class="table-surface hidden md:block">
                <table class="data-table" data-server-sort="true">
                    <thead><tr>
                        <th data-sort-key="name" class="text-left">Anggota</th><th data-sort-key="belt" class="text-left">Sabuk</th><th data-sort-key="dojo" class="text-left">Dojo</th><th data-sort-key="city" class="text-left">Cabang / Provinsi</th><th data-sort-key="status" class="text-center">Status</th><th class="text-right">Aksi</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td><p class="font-semibold text-slate-800">{{ $member->name }}</p><p class="mt-0.5 text-[11px] text-slate-500">#{{ str_pad($member->id, 5, '0', STR_PAD_LEFT) }} / {{ $member->whatsapp ?: '-' }}</p></td>
                                <td>{{ $member->beltLevel?->name ?? 'Belum ditetapkan' }}</td><td>{{ $member->dojo?->name ?? '-' }}</td>
                                <td>{{ $member->city?->name ?? '-' }}<p class="text-[11px] text-slate-500">{{ $member->province?->name ?? '-' }}</p></td>
                                <td class="text-center"><span class="rounded-full px-2 py-1 text-[11px] {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="text-right"><x-action-button icon="view" label="Detail anggota" onclick="document.getElementById('member-detail-{{ $member->id }}').showModal()" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-slate-500">Tidak ada anggota yang sesuai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="grid grid-cols-2 gap-2 md:hidden">
                @forelse ($members as $member)
                    <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-3">
                        <p class="break-words text-xs font-semibold text-slate-900">{{ $member->name }}</p><p class="mt-1 text-[10px] text-slate-400">#{{ str_pad($member->id, 5, '0', STR_PAD_LEFT) }}</p>
                        <p class="mt-2 break-words text-[11px] text-slate-600">{{ $member->dojo?->name ?? '-' }}</p><p class="text-[11px] text-slate-500">{{ $member->beltLevel?->name ?? 'Belum ditetapkan' }}</p>
                        <div class="mt-2 flex items-center justify-between gap-1"><span class="text-[10px] {{ $member->is_active ? 'text-emerald-700' : 'text-slate-400' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span><x-action-button icon="view" label="Detail anggota" onclick="document.getElementById('member-detail-{{ $member->id }}').showModal()" /></div>
                    </div>
                @empty
                    <p class="col-span-2 py-6 text-center text-xs text-slate-500">Tidak ada anggota yang sesuai.</p>
                @endforelse
            </div>
            {{ $members->links() }}
            @foreach ($members as $member)
                <dialog id="member-detail-{{ $member->id }}" class="create-fee-dialog" aria-label="Detail anggota {{ $member->name }}">
                    <div class="create-form-heading"><h2>Detail Anggota</h2><button type="button" class="btn btn-secondary" onclick="this.closest('dialog').close()">Tutup</button></div>
                    <dl class="grid grid-cols-[110px_1fr] gap-3 p-4 text-xs">
                        @foreach (['Nama' => $member->name, 'Nomor anggota' => '#' . str_pad($member->id, 5, '0', STR_PAD_LEFT), 'Status' => $member->is_active ? 'Aktif' : 'Nonaktif', 'WhatsApp' => $member->whatsapp, 'Email' => $member->email, 'Orang tua' => $member->parent_name, 'Sabuk' => $member->beltLevel?->name, 'Dojo' => $member->dojo?->name, 'Cabang' => $member->city?->name, 'Provinsi' => $member->province?->name] as $label => $value)
                            <dt class="text-slate-500">{{ $label }}</dt><dd class="min-w-0 break-words font-medium text-slate-800">{{ $value ?: '-' }}</dd>
                        @endforeach
                    </dl>
                </dialog>
            @endforeach
        </div>
    </div>
</x-app-layout>
