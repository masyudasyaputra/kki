@props(['title', 'items'])
<section class="member-detail-section">
    <h2>{{ $title }}</h2>
    <dl>@foreach($items as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ filled($value) ? $value : 'Belum diisi' }}</dd></div>@endforeach</dl>
</section>
