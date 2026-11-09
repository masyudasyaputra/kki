@props(['member' => null, 'name' => null, 'photo' => null])
<div {{ $attributes->class(['flex items-center gap-3']) }}>
    <x-member-avatar :member="$member" :name="$name" :photo="$photo" />
    <div class="min-w-0">{{ $slot }}</div>
</div>
