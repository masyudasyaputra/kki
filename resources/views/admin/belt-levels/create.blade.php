<x-app-layout>
    <x-slot name="header">
        <div><h1 class="page-title">Tambah Tingkatan Sabuk</h1><p class="page-subtitle">Tambahkan tingkat Kyu atau DAN pada katalog sabuk.</p></div>
    </x-slot>
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if ($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">Data belum dapat disimpan. Periksa isian yang ditandai.</div>@endif
        @include('admin.belt-levels._form', ['beltLevel' => $beltLevel])
    </div>
</x-app-layout>
