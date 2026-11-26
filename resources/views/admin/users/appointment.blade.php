@php($appointment = $user->official)
<section class="mx-auto mt-6 max-w-4xl px-4 sm:px-6">
    <form action="{{ route('admin.users.appoint', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6"
        x-data="{ level: @js(old('level', $appointment?->level ?? (\App\Services\RoleAccess::nationalOfficials(auth()->user()) ? 'provinsi' : 'pengcab'))), province: @js((string) old('province_id', $user->province_id)), city: @js((string) old('city_id', $user->city_id)), cities: @js($appointmentCities) }">
        @csrf
        @if(request()->routeIs('admin.official-verifications.show'))<input type="hidden" name="return_to" value="official-verifications">@endif
        <h2 class="text-base font-bold">{{ $appointment ? 'Perbarui penetapan pengurus' : 'Tetapkan sebagai pengurus' }}</h2>
        <p class="text-sm text-slate-500">Simpan jabatan dan SK sekaligus mengaktifkan akun serta akses pengurus pusat, provinsi, atau kabupaten/kota sesuai wilayah. Hak akses tambahan diatur melalui formulir akun di bawah.</p>
        @if($errors->any())<div role="alert" class="text-sm text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><x-input-label for="appointment-position" value="Jabatan organisasi *" /><x-text-input id="appointment-position" name="position" :value="old('position', $appointment?->position ?? $user->requested_position)" required maxlength="255" placeholder="Contoh: Sekretaris" /></div>
            <div><x-input-label for="appointment-level" value="Tingkat kepengurusan *" /><select id="appointment-level" name="level" x-model="level" class="w-full rounded-lg border-slate-300" required>
                @if(\App\Services\RoleAccess::nationalOfficials(auth()->user()))<option value="pusat">Pengurus Pusat</option>@endif
                @if(in_array('provinsi', \App\Services\RoleAccess::officialLevels(auth()->user()), true))<option value="provinsi">Pengprov</option>@endif
                <option value="pengcab">Pengcab</option>
            </select></div>
            <div x-show="level !== 'pusat'"><x-input-label for="appointment-province" value="Provinsi *" /><select id="appointment-province" name="province_id" x-model="province" @change="city = ''" class="w-full rounded-lg border-slate-300" :required="level !== 'pusat'" :disabled="level === 'pusat'"><option value="">Pilih provinsi</option>@foreach($provinces as $province)<option value="{{ $province->id }}">{{ $province->name }}</option>@endforeach</select></div>
            <div x-show="level === 'pengcab'"><x-input-label for="appointment-city" value="Kabupaten / kota *" /><select id="appointment-city" name="city_id" x-model="city" :required="level === 'pengcab'" :disabled="level !== 'pengcab'" class="w-full rounded-lg border-slate-300"><option value="">Pilih kabupaten / kota</option><template x-for="item in cities.filter(item => String(item.province_id) === province)" :key="item.id"><option :value="String(item.id)" :selected="String(item.id) === city" x-text="item.name"></option></template></select></div>
            <div><x-input-label for="appointment-sk" value="Nomor SK *" /><x-text-input id="appointment-sk" name="sk_number" :value="old('sk_number', $appointment?->sk_number)" required maxlength="100" /></div>
            <div><x-input-label for="appointment-expiry" value="Masa bakti hingga *" /><x-text-input id="appointment-expiry" name="sk_expiry_date" type="date" :value="old('sk_expiry_date', $appointment?->sk_expiry_date)" required /></div>
            <div class="sm:col-span-2"><x-input-label for="appointment-document" value="Dokumen SK (PDF/JPG/PNG, maksimal 10 MB)" /><input id="appointment-document" name="sk_document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm" />
                @if($appointment?->sk_document_path)<a class="text-sm underline" href="{{ route('admin.sk-documents.show', ['entity' => 'official', 'id' => $appointment->id]) }}">Lihat SK tersimpan</a><p class="text-xs text-slate-500">Kosongkan unggahan untuk mempertahankan dokumen.</p>@endif
            </div>
        </div>
        <button class="btn btn-primary" type="submit">{{ $appointment ? 'Simpan penetapan pengurus' : 'Tetapkan sebagai pengurus' }}</button>
    </form>
</section>
