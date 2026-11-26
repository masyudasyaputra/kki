<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Layanan Bantuan SIM-KKI</h1><p class="page-subtitle">Ruang kerja sesuai kewenangan layanan Anda.</p></x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6">
        @if(session('status'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('status') }}</p>@endif
        @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-900">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($actions as [$title, $description, $route])
                <a href="{{ route($route) }}" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-red-300"><h2 class="font-bold text-slate-900">{{ $title }}</h2><p class="mt-2 text-sm text-slate-500">{{ $description }}</p><span class="mt-4 inline-block text-sm font-semibold text-red-900">Buka layanan →</span></a>
            @endforeach
        </div>
        @if($metrics)
            <section class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-bold">Kelengkapan data & antrean layanan</h2><p class="mt-1 text-sm text-slate-500">Jumlah dihitung dari data terbaru. Data yang belum lengkap dapat ditindaklanjuti bersama pengurus terkait.</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach($metrics as $label => $count)<div class="rounded-lg bg-slate-50 p-4"><dt class="text-sm text-slate-500">{{ $label }}</dt><dd class="mt-1 text-2xl font-bold">{{ $count === null ? '—' : number_format($count) }}</dd></div>@endforeach</dl>
            </section>
        @endif
        @if(\App\Services\RoleAccess::managesRegionData($actor))
            <section class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-bold">Pengelolaan database wilayah</h2><p class="mt-2 text-sm text-slate-500">Unduh master wilayah untuk pemeriksaan atau isi kode Kemendagri yang masih kosong berdasarkan master. Sinkronisasi mempertahankan ID dan relasi wilayah.</p><div class="mt-4 flex flex-wrap gap-3"><a href="{{ route('admin.services.export-regions') }}" class="btn btn-secondary">Ekspor master wilayah (CSV)</a><form method="post" action="{{ route('admin.services.sync-regions') }}">@csrf<button class="btn btn-primary">Sinkronkan kode wilayah</button></form></div></section>
        @endif
    </div>
</x-app-layout>
