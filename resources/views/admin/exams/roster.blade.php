<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="page-title">Rekap <span class="text-red-600">Peserta UKT</span></h1><p class="page-subtitle">{{ $exam->name }} · {{ $exam->execution_date->format('d/m/Y') }} · {{ $exam->location }}</p></div>
            <div class="flex gap-2"><a class="btn btn-secondary" href="{{ route('admin.exams.show', $exam) }}">Kembali ke ujian</a><a class="btn btn-primary" href="{{ route('admin.exams.roster', [$exam, 'format' => 'csv']) }}">Unduh Excel (CSV)</a><button class="btn btn-secondary" onclick="window.print()">Cetak</button></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-7xl space-y-4 p-6">
        <div class="grid gap-3 sm:grid-cols-3"><div class="rounded-2xl bg-white p-4 shadow-sm">Jumlah peserta <strong class="float-right">{{ $participants->count() }}</strong></div><div class="rounded-2xl bg-white p-4 shadow-sm">UKT lunas <strong class="float-right">{{ $participants->where('payment_status', 'paid')->count() }}</strong></div><div class="rounded-2xl bg-white p-4 shadow-sm">Total biaya <strong class="float-right">Rp{{ number_format($participants->sum('fee_amount'), 0, ',', '.') }}</strong></div></div>
        <div class="overflow-x-auto rounded-2xl bg-white p-4 shadow-sm"><table class="w-full min-w-[1100px] border-collapse text-left text-sm"><thead><tr class="border-b bg-slate-50 text-xs uppercase"><th class="p-3">No</th><th class="p-3">No. Anggota / Nama</th><th class="p-3">Tempat / Tgl. Lahir</th><th class="p-3">Dojo / Cabang</th><th class="p-3">Tingkat</th><th class="p-3">No. Ujian</th><th class="p-3">Nilai / Hasil</th><th class="p-3">UKT</th><th class="p-3">Blanko</th></tr></thead><tbody>
        @forelse($participants as $i => $p) @php($score = $scores[$p->user_id] ?? null)
            <tr class="border-b"><td class="p-3">{{ $i + 1 }}</td><td class="p-3"><x-member-identity :member="$p->user"><strong>{{ $p->user->name }}</strong><div class="text-xs text-slate-500">{{ $p->user->member_number }}</div></x-member-identity></td><td class="p-3">{{ $p->certificate_identity['birth_place'] ?? '-' }}<br>{{ $p->certificate_identity['birth_date'] ?? '-' }}</td><td class="p-3">{{ $p->dojo?->name ?? '-' }}<br>{{ $p->user->city?->name ?? '-' }}</td><td class="p-3">{{ $p->currentBelt?->display_name ?? '-' }} → {{ $score?->newBeltLevel?->display_name ?? $p->targetBelt?->display_name ?? '-' }}</td><td class="p-3">{{ $p->id }}</td><td class="p-3">{{ $score?->result ?? 'Belum dinilai' }}</td><td class="p-3"><span class="font-semibold {{ $p->payment_status === 'paid' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $p->payment_status === 'paid' ? 'Lunas' : 'Belum lunas' }}</span><div class="text-xs">Rp{{ number_format($p->fee_amount, 0, ',', '.') }}</div></td><td class="p-3"><a class="text-red-700 underline" href="{{ route('admin.exams.blanko', [$exam, $p]) }}">Cetak blanko</a></td></tr>
        @empty <tr><td colspan="9" class="p-8 text-center text-slate-500">Belum ada peserta terdaftar.</td></tr> @endforelse
        </tbody></table></div>
    </div>
    <style>@media print { aside, nav, header a, header button { display:none!important } body { background:white!important } .shadow-sm { box-shadow:none!important } }</style>
</x-app-layout>
