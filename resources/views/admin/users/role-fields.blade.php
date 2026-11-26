@php
    $selectedPositions = old('positions', isset($user) ? ($user->positions ?? array_values(array_intersect($user->assignedRoles(), array_keys(\App\Services\RoleAccess::POSITIONS)))) : ['member']);
    $selectedPermissions = old('roles', isset($user) ? $user->roles : []);
@endphp
<div class="space-y-5">
    <fieldset class="space-y-3"><legend class="font-semibold text-slate-900">Jabatan & pengelola sistem</legend>
        <p class="text-xs text-slate-500">Jabatan menunjukkan kedudukan pengguna. Hak verifikasi dan sertifikat dipilih terpisah.</p>
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">@foreach(array_diff_key($availablePositions, \App\Services\RoleAccess::SERVICE_POSITIONS) as $key => $label)
            <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm"><input type="checkbox" name="positions[]" value="{{ $key }}" @checked(in_array($key, $selectedPositions))>{{ $label }}</label>
        @endforeach</div>
    </fieldset>
    @if(array_intersect_key($availablePositions, \App\Services\RoleAccess::SERVICE_POSITIONS))
    <fieldset class="space-y-3"><legend class="font-semibold text-slate-900">Penanggung Jawab Layanan Bantuan</legend>
        <p class="text-xs text-slate-500">Pilih tanggung jawab layanan sesuai penugasan. Setiap peran memberikan fungsi layanan sesuai tugasnya. Admin Utama memiliki kewenangan pengelolaan sistem; verifikasi DAN dibatasi pada data sabuk hitam.</p>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach(\App\Services\RoleAccess::SERVICE_POSITIONS as $key => $label)
                <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm"><input type="checkbox" name="positions[]" value="{{ $key }}" class="mt-0.5" @checked(in_array($key, $selectedPositions))>{{ $label }}</label>
            @endforeach
        </div>
    </fieldset>
    @endif
    <fieldset class="space-y-3"><legend class="font-semibold text-slate-900">Hak akses tambahan</legend>
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">@foreach($availablePermissions as $key => $label)
            <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm"><input type="checkbox" name="roles[]" value="{{ $key }}" @checked(in_array($key, $selectedPermissions ?? []))>{{ $label }}</label>
        @endforeach</div>
        <p class="text-xs text-slate-500">Verifikator daerah hanya memeriksa wilayah akun. Verifikator pusat dapat menangani seluruh daerah. Verifikator ujian, penguji, dan panitia tetap memerlukan penugasan sesi.</p>
    </fieldset>
</div>
