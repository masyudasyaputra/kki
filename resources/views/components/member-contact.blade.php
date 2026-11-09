@props(['member'])
<div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
    <span class="inline-flex items-center gap-1.5" aria-label="ID anggota: {{ $member->member_number }}"><x-sidebar-icon name="membership" class="h-3.5 w-3.5" /><span>{{ $member->member_number }}</span></span>
    @if(filled($member->whatsapp))<span class="inline-flex items-center gap-1.5" aria-label="Nomor WhatsApp: {{ $member->whatsapp }}"><x-sidebar-icon name="phone" class="h-3.5 w-3.5" /><span>{{ $member->whatsapp }}</span></span>@endif
</div>
