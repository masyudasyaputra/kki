{{-- Tombol Edit --}}
<x-action-button href="{{ route('admin.officials.edit', $official->id) }}" icon="edit" label="Edit" />

{{-- Tombol Hapus --}}
<form action="{{ route('admin.officials.destroy', $official->id) }}" method="POST" class="inline"
    onsubmit="return confirm('Hapus Pengurus {{ $official->name }}?')">
    @csrf @method('DELETE')
    <x-action-button type="submit" icon="delete" label="Hapus" />
</form>
