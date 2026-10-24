            <x-table title="Sebaran dojo per cabang" subtitle="Jumlah dojo terdaftar dan yang SK-nya masih berlaku dalam cakupan akun."
                :headers="[
                    ['label' => 'Cabang'], ['label' => 'Provinsi'],
                    ['label' => 'Jumlah dojo', 'align' => 'text-right'],
                    ['label' => 'SK berlaku', 'align' => 'text-right'],
                ]">
                @forelse ($branchDojos as $branch)
                    <tr>
                        <td class="font-semibold text-slate-800">{{ $branch->city?->name ?? 'Cabang belum ditetapkan' }}</td>
                        <td class="text-slate-500">{{ $branch->city?->province?->name ?? '-' }}</td>
                        <td class="text-right font-semibold tabular-nums">{{ number_format($branch->dojo_count) }}</td>
                        <td class="text-right tabular-nums text-emerald-700">{{ number_format($branch->active_dojo_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-slate-500">Belum ada dojo dalam cakupan akun.</td></tr>
                @endforelse
            </x-table>

