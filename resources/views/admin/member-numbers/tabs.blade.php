<nav aria-label="Jenis Nomor ID" class="flex gap-2">
    <a href="{{ route('admin.member-numbers.index') }}" class="btn {{ request()->routeIs('admin.member-numbers.*') ? 'btn-primary' : 'btn-secondary' }}">Anggota</a>
    <a href="{{ route('admin.official-numbers.index') }}" class="btn {{ request()->routeIs('admin.official-numbers.*') ? 'btn-primary' : 'btn-secondary' }}">Pengurus</a>
</nav>
