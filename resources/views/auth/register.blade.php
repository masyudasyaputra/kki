<x-guest-layout>
    @php($googleIdentity = $googleIdentity ?? null)
    <div class="auth-heading auth-heading-with-logo"><img src="{{ asset('images/logo-shokaido.webp') }}" alt="Logo KKI" width="64" height="64" class="auth-heading-logo"><div><span class="auth-eyebrow">Bergabung bersama KKI</span><h1>Pendaftaran anggota</h1><p>Lengkapi identitas, pilih dojo, lalu buat akses akun Anda.</p></div></div>
    @if ($googleIdentity)
        <p class="auth-status">Google terverifikasi: {{ $googleIdentity['email'] }}. Lengkapi profil anggota di bawah ini.</p>
    @else
        <x-google-auth-button mode="register" />
        <div class="auth-divider">atau daftar dengan email</div>
    @endif
    <p class="mb-3 text-xs leading-relaxed text-slate-500">Nama dan email wajib diisi. Isian bertanda opsional boleh dikosongkan. Jika wilayah, dojo, atau sabuk belum tersedia, pilih &quot;Belum tersedia / belum tahu&quot;; data dapat dilengkapi kemudian.</p>
    <form method="POST" action="{{ route('register', (request()->boolean('_auth_modal') || request()->header('Sec-Fetch-Dest') === 'iframe') ? ['_auth_modal' => 1] : []) }}" class="auth-form" x-data="{
        province: @js((string) old('province_id', '')), city: @js((string) old('city_id', '')), dojo: @js((string) old('dojo_id', '')),
        cities: [], dojos: [], loadingCities: false, loadingDojos: false, regionError: '', visible: false,
        async loadCities(restore = false) {
            const selected = this.province;
            if (!restore) { this.city = ''; this.dojo = ''; this.dojos = []; }
            this.cities = []; this.regionError = '';
            if (!selected) return;
            this.loadingCities = true;
            try { const response = await fetch(@js(url('/api/cities')) + '/' + selected); if (!response.ok) throw new Error(); const data = await response.json(); if (this.province === selected) this.cities = data; }
            catch { this.regionError = 'Daftar cabang gagal dimuat. Silakan pilih ulang provinsi.'; }
            finally { this.loadingCities = false; }
        },
        async loadDojos(restore = false) {
            const selected = this.city;
            if (!restore) this.dojo = '';
            this.dojos = []; this.regionError = '';
            if (!selected) return;
            this.loadingDojos = true;
            try { const response = await fetch(@js(url('/api/dojos')) + '/' + selected); if (!response.ok) throw new Error(); const data = await response.json(); if (this.city === selected) this.dojos = data; }
            catch { this.regionError = 'Daftar dojo gagal dimuat. Silakan pilih ulang cabang.'; }
            finally { this.loadingDojos = false; }
        },
        async init() { await this.loadCities(true); await this.loadDojos(true); }
    }">
        @csrf
        @if ($errors->any())<div role="alert" class="auth-error-summary"><p class="font-semibold">Periksa kembali data Anda.</p><ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <fieldset class="auth-section"><legend>Identitas anggota</legend>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><x-input-label for="name" value="Nama lengkap anggota *" /><x-text-input id="name" name="name" type="text" :value="old('name', $googleIdentity['name'] ?? '')" required autocomplete="name" placeholder="Nama lengkap" /></div>
                <div><x-input-label for="parent_name" value="Nama orang tua / wali (opsional)" /><x-text-input id="parent_name" name="parent_name" type="text" :value="old('parent_name')" placeholder="Kosongkan jika tidak ada" /></div>
                <div><x-input-label for="whatsapp" value="Nomor WhatsApp (opsional)" /><x-text-input id="whatsapp" name="whatsapp" type="tel" inputmode="tel" :value="old('whatsapp')" autocomplete="tel" placeholder="08xxxxxxxxxx" /></div>
                <div><x-input-label for="email" value="Alamat email *" /><x-text-input id="email" name="email" type="email" :value="old('email', $googleIdentity['email'] ?? '')" :readonly="(bool) $googleIdentity" required autocomplete="email" placeholder="nama@email.com" /></div>
            </div>
        </fieldset>
        <fieldset class="auth-section"><legend>Wilayah & dojo</legend>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><x-input-label for="province_id" value="Provinsi (opsional)" /><select id="province_id" name="province_id" x-model="province" @change="loadCities()"><option value="">Belum tersedia / belum tahu</option>@foreach ($provinces as $province)<option value="{{ $province->id }}" @selected((string) old('province_id') === (string) $province->id)>{{ $province->name }}</option>@endforeach</select></div>
                <div><x-input-label for="city_id" value="Kabupaten / kota (opsional)" /><select id="city_id" name="city_id" x-model="city" @change="loadDojos()" :disabled="!province || loadingCities"><option value="" x-text="loadingCities ? 'Memuat cabang...' : 'Belum tersedia / belum tahu'">Belum tersedia / belum tahu</option><template x-for="item in cities" :key="item.id"><option :value="String(item.id)" :selected="String(item.id) === city" x-text="item.name"></option></template></select></div>
                <div><x-input-label for="dojo_id" value="Dojo (opsional)" /><select id="dojo_id" name="dojo_id" x-model="dojo" :disabled="!city || loadingDojos"><option value="" x-text="loadingDojos ? 'Memuat dojo...' : 'Belum tersedia / belum tahu'">Belum tersedia / belum tahu</option><template x-for="item in dojos" :key="item.id"><option :value="String(item.id)" x-text="item.name"></option></template></select></div>
                <div><x-input-label for="belt_level_id" value="Tingkat sabuk (opsional)" /><select id="belt_level_id" name="belt_level_id"><option value="">Belum tersedia / belum tahu</option>@foreach ($beltLevels as $belt)<option value="{{ $belt->id }}" @selected((string) old('belt_level_id') === (string) $belt->id)>{{ $belt->name }} {{ $belt->kyu_dan }}</option>@endforeach</select></div>
            </div><p x-show="regionError" x-cloak x-text="regionError" role="alert" class="mt-2 text-xs text-red-600"></p>
        </fieldset>
        <fieldset class="auth-section"><legend>Akses akun</legend>
            @if ($googleIdentity)<p class="mb-3 text-xs text-slate-500">Kata sandi opsional karena Anda menggunakan Google. Isi jika juga ingin masuk dengan email dan sandi.</p>@else<p class="mb-3 text-xs text-slate-500">Kata sandi dan konfirmasi wajib diisi, minimal 8 karakter.</p>@endif
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><x-input-label for="password" value="Kata sandi" /><x-text-input id="password" name="password" type="password" x-bind:type="visible ? 'text' : 'password'" :required="!$googleIdentity" autocomplete="new-password" placeholder="Buat kata sandi" /></div>
                <div><x-input-label for="password_confirmation" value="Konfirmasi kata sandi" /><x-text-input id="password_confirmation" name="password_confirmation" type="password" x-bind:type="visible ? 'text' : 'password'" required autocomplete="new-password" placeholder="Ulangi kata sandi" /></div>
            </div><label class="mt-3 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" x-model="visible" class="rounded border-slate-300 text-red-600 focus:ring-red-500">Tampilkan kata sandi</label>
        </fieldset>
        <div class="auth-submit-row"><p class="text-xs text-slate-500">Sudah memiliki akun? <a href="{{ route('login') }}" class="auth-link">Masuk</a></p><button type="submit" class="btn btn-primary">Daftar anggota</button></div>
    </form>
</x-guest-layout>
