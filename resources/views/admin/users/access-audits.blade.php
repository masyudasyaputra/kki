<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Riwayat Hak Akses & Penugasan</h1></x-slot>
    <div class="mx-auto max-w-7xl space-y-4 p-6">@foreach($audits as $audit)
        <article class="rounded-xl bg-white p-5"><h2 class="font-semibold">{{ $audit->event }} · {{ $audit->target_name ?? $audit->before['name'] ?? 'Penugasan sesi' }}</h2>
            <p class="text-sm">{{ $audit->actor_name ?? 'Migrasi sistem' }} · {{ $audit->created_at->format('d/m/Y H:i') }} @if($audit->exam_id)· Sesi #{{ $audit->exam_id }}@endif</p>
            <details class="mt-3 text-xs"><summary>Perubahan</summary><p>Sebelum</p><pre class="overflow-auto">{{ json_encode($audit->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><p>Sesudah</p><pre class="overflow-auto">{{ json_encode($audit->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>
        </article>
    @endforeach<x-pagination :paginator="$audits" label="riwayat" /></div>
</x-app-layout>
