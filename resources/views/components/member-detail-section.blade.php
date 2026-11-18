@props(['title', 'items', 'section' => null, 'member' => null, 'options' => [], 'tab' => 'personal'])
@php
    $canEdit = $section && $member && auth()->user()->hasRole('super_admin');
    $editing = $canEdit && (old('_section', request('edit')) === $section);
    $labels = ['name' => 'Nama lengkap', 'parent_name' => 'Orang tua / wali', 'dojo_id' => 'Dojo', 'belt_level_id' => 'Tingkat sabuk', 'first_coach' => 'Pelatih awal', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'address' => 'Alamat', 'province_id' => 'Provinsi', 'city_id' => 'Kabupaten / kota', 'district' => 'Kecamatan', 'is_active' => 'Status akun'];
    $fields = ['profile' => ['name','parent_name'], 'training' => ['dojo_id','belt_level_id','first_coach'], 'contact' => ['email','whatsapp','address','province_id','city_id','district'], 'region' => ['province_id','city_id','district'], 'status' => ['is_active']][$section] ?? [];
@endphp
<section class="member-detail-section" id="section-{{ $tab }}-{{ $section }}">
    <div class="member-detail-section-heading"><h2>{{ $title }}</h2>@if($canEdit && !$editing)<x-action-button icon="edit" :label="'Edit '.$title" :href="route('admin.members.show', ['member' => $member, 'edit' => $section, 'tab' => $tab]).'#section-'.$tab.'-'.$section" />@endif</div>
    @if($editing)
    <form method="POST" action="{{ route('admin.members.sections.update', [$member, $section]) }}" class="space-y-3">@csrf @method('PUT')<input type="hidden" name="_section" value="{{ $section }}"><input type="hidden" name="_tab" value="{{ $tab }}">
        @foreach($fields as $field)<label class="block text-xs text-slate-600" for="edit-{{ $tab }}-{{ $section }}-{{ $field }}">{{ $labels[$field] }}
            @if($field === 'is_active')<select id="edit-{{ $tab }}-{{ $section }}-{{ $field }}" name="{{ $field }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="1" @selected(old($field, $member->$field) == 1)>Aktif</option><option value="0" @selected(old($field, $member->$field) == 0)>Nonaktif</option></select>
            @elseif(isset($options[$field]))<select id="edit-{{ $tab }}-{{ $section }}-{{ $field }}" name="{{ $field }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Belum diisi</option>@foreach($options[$field] as $option)<option value="{{ $option->id }}" @selected((string) old($field, $member->$field) === (string) $option->id)>{{ $field === 'belt_level_id' ? $option->display_name : $option->name }}</option>@endforeach</select>
            @else<input id="edit-{{ $tab }}-{{ $section }}-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $member->$field) }}" maxlength="{{ $field === 'address' ? 2000 : 255 }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">@endif
            <x-input-error :messages="$errors->get($field)" class="mt-1" />
        </label>@endforeach
        @if(in_array($section, ['contact','region','training']))<p class="text-xs text-slate-500">Wilayah harus sesuai provinsi dan kota. Perubahan kota mengosongkan dojo; pilih kembali dojo pada Informasi latihan. Perubahan wilayah, dojo, atau sabuk memerlukan verifikasi ulang.</p>@endif
        <div class="flex gap-2"><button class="btn btn-primary">Simpan</button><a class="btn btn-secondary" href="{{ route('admin.members.show', ['member' => $member, 'tab' => $tab]) }}">Batal</a></div>
    </form>
    @else
    <dl>@foreach($items as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ filled($value) ? $value : 'Belum diisi' }}</dd></div>@endforeach</dl>
    @endif
</section>
