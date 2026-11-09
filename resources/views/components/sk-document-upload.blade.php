@props(['entity', 'record' => null, 'path' => null, 'label' => 'Dokumen SK'])
@php($existingPath = $path ?? $record?->sk_document_path)
<div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
    <label class="block text-xs font-semibold text-slate-700">{{ $label }}
        <input type="file" name="sk_document" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg" class="mt-2 block w-full cursor-pointer rounded-lg border border-slate-200 bg-white text-xs text-slate-600 file:mr-3 file:cursor-pointer file:border-0 file:bg-[#f4e9ea] file:px-3 file:py-2 file:text-xs file:font-semibold file:text-[#641b2d]">
    </label>
    <p class="mt-1.5 text-[11px] text-slate-500">PDF, JPG, atau PNG · maksimal 10 MB. Berkas hanya dapat dibuka oleh pengguna yang berwenang.</p>
    @if($existingPath)
        <a href="{{ route('admin.sk-documents.show', ['entity' => $entity, 'id' => $record->id]) }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-[#762137] hover:underline">
            <x-sidebar-icon name="certificate" class="h-4 w-4" /> Lihat dokumen SK tersimpan
        </a>
    @endif
</div>
