{{-- Tombol Edit --}}
<x-action-button href="{{ route('admin.dojos.edit', $dojo->id) }}" icon="edit" label="Edit" />

{{-- Tombol Hapus --}}
<form action="{{ route('admin.dojos.destroy', $dojo->id) }}" method="POST" class="inline"
    onsubmit="return confirm('Hapus dojo {{ $dojo->name }}?')">
    @csrf
    @method('DELETE')
    <x-action-button type="submit" icon="delete" label="Hapus" />
</form>
