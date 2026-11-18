@php($googleIdentity = $googleIdentity ?? null)
    <div class="auth-heading auth-heading-with-logo registration-heading"><img src="{{ asset('images/logo-kki.png') }}" alt="Logo KKI" width="64" height="64" class="auth-heading-logo"><div><span class="auth-eyebrow">Bergabung bersama KKI</span><h1>Pendaftaran KKI</h1><p>Pilih jenis pendaftaran Anda untuk bergabung bersama KKI.</p></div></div>
    @if ($googleIdentity)
        <p class="auth-status">Google terverifikasi: {{ $googleIdentity['email'] }}. Lengkapi profil anggota di bawah ini.</p>
    @else
        <x-google-auth-button mode="register" />
        <div class="auth-divider">atau daftar dengan email</div>
    @endif
    <p class="registration-hint"><span aria-hidden="true">*</span> Isian wajib. Data latihan dan wilayah boleh dilengkapi nanti.</p>
    <form method="POST" action="{{ route('register', request()->boolean('_auth_modal') ? ['_auth_modal' => 1] : [], false) }}" data-auth-redirect="{{ route('dashboard', [], false) }}" class="auth-form registration-form" x-data="{
        registrationType: @js(old('registration_type', 'karateka')),
        province: @js((string) old('province_id', '')), city: @js((string) old('city_id', '')), dojo: @js((string) old('dojo_id', '')),
        district: @js((string) old('district', '')), districtOpen: false, districtActiveIndex: -1,
        districts: [], cities: [], dojos: [], loadingCities: false, loadingDojos: false, regionError: '', visible: false,
        districtOptions() {
            const term = this.district.trim().toLocaleLowerCase('id');
            const seen = new Set();
            return this.districts.filter(item => {
                const name = (item.name || '').trim().replace(/\s+/g, ' ');
                const key = name.toLocaleLowerCase('id');
                if (!name || seen.has(key)) return false;
                seen.add(key);
                return !term || key.includes(term);
            });
        },
        selectDistrict(name) { this.district = name; this.districtOpen = false; this.districtActiveIndex = -1; },
        async loadCities(restore = false) {
            const selected = this.province;
            if (!restore) { this.city = ''; this.dojo = ''; this.dojos = []; this.districts = []; this.district = ''; this.districtOpen = false; this.districtActiveIndex = -1; }
            this.cities = []; this.regionError = '';
            if (!selected) return;
            this.loadingCities = true;
            try { const response = await fetch(@js(url('/api/cities')) + '/' + selected); if (!response.ok) throw new Error(); const data = await response.json(); if (this.province === selected) this.cities = data; }
            catch { this.regionError = 'Daftar cabang gagal dimuat. Silakan pilih ulang provinsi.'; }
            finally { this.loadingCities = false; }
        },
        async loadDojos(restore = false) {
            const selected = this.city;
            if (!restore) { this.dojo = ''; this.district = ''; this.districtOpen = false; }
            this.dojos = []; this.districts = []; this.regionError = '';
            if (!selected) return;
            this.loadingDojos = true;
            try { const response = await fetch(@js(url('/api/dojos')) + '/' + selected); if (!response.ok) throw new Error(); const data = await response.json(); if (this.city === selected) this.dojos = data; const districts = await fetch(@js(url('/api/districts')) + '/' + selected); if (!districts.ok) throw new Error(); const items = await districts.json(); if (this.city === selected) { const seen = new Set(); this.districts = items.filter(item => { const key = (item.name || '').trim().replace(/\s+/g, ' ').toLocaleLowerCase('id'); if (!key || seen.has(key)) return false; seen.add(key); return true; }); } }
            catch { this.regionError = 'Daftar dojo gagal dimuat. Silakan pilih ulang cabang.'; }
            finally { this.loadingDojos = false; }
        },
        async init() { await this.loadCities(true); await this.loadDojos(true); }
    }">
        @csrf
        <input type="hidden" name="registration_type" :value="registrationType">
        <div class="registration-type-tabs" role="group" aria-label="Jenis pendaftaran">
            <button type="button" :aria-pressed="registrationType === 'karateka'" @click="registrationType = 'karateka'">Karateka</button>
            <button type="button" :aria-pressed="registrationType === 'official'" @click="registrationType = 'official'">Pengurus Non-Karateka</button>
        </div>
        <p x-show="registrationType === 'official'" x-cloak class="text-sm text-slate-500">Pendaftaran terbuka. Hak akses pengurus diberikan setelah pemeriksaan administrator.</p>
        <div x-show="registrationType === 'official'" x-cloak>
            <x-input-label for="requested_position" value="Jabatan yang diajukan *" />
            <x-text-input id="requested_position" name="requested_position" type="text" :value="old('requested_position')" maxlength="255" x-bind:required="registrationType === 'official'" x-bind:disabled="registrationType !== 'official'" placeholder="Contoh: Sekretaris Pengcab" />
        </div>
        @if ($errors->any())<div role="alert" class="auth-error-summary"><p class="font-semibold">Periksa kembali data Anda.</p><ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <fieldset class="auth-section registration-section"><legend><span class="registration-step">01</span> Identitas pendaftar</legend>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><x-input-label for="name" value="Nama lengkap *" /><x-text-input id="name" name="name" type="text" :value="old('name', $googleIdentity['name'] ?? '')" required autocomplete="name" placeholder="Nama lengkap" /></div>
                <div x-show="registrationType === 'karateka'"><x-input-label for="parent_name" value="Nama orang tua / wali (opsional)" /><x-text-input x-bind:disabled="registrationType !== 'karateka'" id="parent_name" name="parent_name" type="text" :value="old('parent_name')" placeholder="Kosongkan jika tidak ada" /></div>
                <div><x-input-label for="whatsapp" value="Nomor WhatsApp (opsional)" /><x-text-input id="whatsapp" name="whatsapp" type="tel" inputmode="tel" :value="old('whatsapp')" autocomplete="tel" placeholder="08xxxxxxxxxx" /></div>
                <div><x-input-label for="email" value="Alamat email *" /><x-text-input id="email" name="email" type="email" :value="old('email', $googleIdentity['email'] ?? '')" :readonly="(bool) $googleIdentity" required autocomplete="email" placeholder="nama@email.com" /></div>
            </div>
        </fieldset>
        @php($expandTraining = collect(['province_id','city_id','district','dojo_id','belt_level_id','first_coach','address'])->contains(fn($key) => filled(old($key)) || $errors->has($key)))
        <details class="registration-optional" @if($expandTraining) open @endif>
            <summary><span><span class="registration-step">02</span><span><strong x-text="registrationType === 'official' ? 'Wilayah tugas & alamat' : 'Wilayah & latihan'">Wilayah & latihan</strong><small x-text="registrationType === 'official' ? 'Provinsi, kabupaten / kota, dan alamat' : 'Provinsi, dojo, sabuk, dan alamat'">Provinsi, dojo, sabuk, dan alamat</small></span></span><span class="registration-optional-label">Opsional <span class="registration-chevron" aria-hidden="true">⌄</span></span></summary>
            <fieldset class="registration-training"><legend class="sr-only">Wilayah & dojo</legend>
            <p class="registration-section-help">Lengkapi sekarang jika sudah tahu. Data ini juga dapat diperbarui setelah mendaftar.</p>
            <div class="registration-training-grid">
                <div><x-input-label for="province_id" value="Provinsi" /><select id="province_id" name="province_id" x-model="province" @change="loadCities()"><option value="">Pilih provinsi</option>@foreach($provinces as $province)<option value="{{ $province->id }}" @selected((string) old('province_id') === (string) $province->id)>{{ $province->name }}</option>@endforeach</select></div>
                <div><x-input-label for="city_id" value="Kabupaten / kota" /><select id="city_id" name="city_id" x-model="city" @change="loadDojos()" :disabled="!province || loadingCities"><option value="" x-text="loadingCities ? 'Memuat kota…' : (!province ? 'Pilih provinsi dahulu' : 'Pilih kabupaten / kota')">Pilih provinsi dahulu</option><template x-for="item in cities" :key="item.id"><option :value="String(item.id)" :selected="String(item.id) === city" x-text="item.name"></option></template></select></div>
                <div class="relative" @click.outside="districtOpen = false">
                    <x-input-label for="district" value="Kecamatan (opsional)" />
                    <div class="relative auth-combobox">
                        <input id="district" name="district" type="text" x-model="district" maxlength="150" autocomplete="off" placeholder="Ketik atau pilih kecamatan" :placeholder="!city ? 'Pilih kota dahulu' : 'Ketik atau pilih kecamatan'" :disabled="!city || loadingDojos"
                            @focus="districtOpen = true" @input="districtOpen = true; districtActiveIndex = -1"
                            @keydown.escape.prevent="districtOpen = false"
                            @keydown.arrow-down.prevent="districtOpen = true; districtActiveIndex = Math.min(districtActiveIndex + 1, districtOptions().length - 1)"
                            @keydown.arrow-up.prevent="districtActiveIndex = Math.max(districtActiveIndex - 1, 0)"
                            @keydown.enter="if (districtOpen && districtActiveIndex >= 0 && districtOptions()[districtActiveIndex]) { $event.preventDefault(); selectDistrict(districtOptions()[districtActiveIndex].name) }"
                            :aria-expanded="districtOpen.toString()" aria-autocomplete="list" aria-controls="district-options" role="combobox"
                            :aria-activedescendant="districtOpen && districtActiveIndex >= 0 && districtOptions()[districtActiveIndex] ? 'district-option-' + districtOptions()[districtActiveIndex].id : null"
                            class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-3 pr-11 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-[#762137] focus:outline-none focus:ring-2 focus:ring-[#762137]/15">
                        <button type="button" tabindex="-1" :disabled="!city || loadingDojos" @click="districtOpen = !districtOpen; districtActiveIndex = -1" aria-label="Tampilkan pilihan kecamatan" :aria-expanded="districtOpen.toString()" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-xl text-slate-500 hover:text-[#762137] disabled:opacity-40">
                            <svg class="h-4 w-4 transition-transform" :class="districtOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                    <div id="district-options" x-show="districtOpen" x-cloak role="listbox" aria-label="Pilihan kecamatan" class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-xl ring-1 ring-black/5">
                        <template x-for="(item, index) in districtOptions()" :key="item.id">
                            <button type="button" role="option" :id="'district-option-' + item.id" :aria-selected="district === item.name" @mouseenter="districtActiveIndex = index" @mousedown.prevent @click="selectDistrict(item.name)" :class="districtActiveIndex === index ? 'bg-[#f7ecef] text-[#762137]' : 'text-slate-700 hover:bg-slate-50'" class="block w-full px-3.5 py-2.5 text-left text-sm font-medium">
                                <span x-text="item.name"></span>
                            </button>
                        </template>
                        <p x-show="districtOptions().length === 0" class="px-3.5 py-3 text-sm text-slate-500">Tidak ada pilihan yang cocok. Nama yang diketik tetap dapat digunakan.</p>
                    </div>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Tidak ada dalam daftar? Ketik nama kecamatan Anda.</p>
                    <x-input-error :messages="$errors->get('district')" class="mt-1" />
                </div>

                <div x-show="registrationType === 'karateka'"><x-input-label for="dojo_id" value="Dojo" /><select id="dojo_id" name="dojo_id" x-model="dojo" :disabled="registrationType !== 'karateka' || !city || loadingDojos"><option value="" x-text="loadingDojos ? 'Memuat dojo…' : (!city ? 'Pilih kota dahulu' : 'Pilih dojo / isi nanti')">Pilih kota dahulu</option><template x-for="item in dojos" :key="item.id"><option :value="String(item.id)" x-text="item.name"></option></template></select></div>
                <div x-show="registrationType === 'karateka'"><x-input-label for="belt_level_id" value="Tingkat sabuk" /><select x-bind:disabled="registrationType !== 'karateka'" id="belt_level_id" name="belt_level_id"><option value="">Pilih tingkatan / isi nanti</option>@foreach($beltLevels as $belt)<option value="{{ $belt->id }}" @selected((string) old('belt_level_id') === (string) $belt->id)>{{ $belt->name }} {{ $belt->kyu_dan }}</option>@endforeach</select></div>
                <div x-show="registrationType === 'karateka'"><x-input-label for="first_coach" value="Pelatih awal" /><x-text-input x-bind:disabled="registrationType !== 'karateka'" id="first_coach" name="first_coach" type="text" :value="old('first_coach')" maxlength="255" placeholder="Nama pelatih / isi nanti" /><x-input-error :messages="$errors->get('first_coach')" class="mt-1" /></div>
                <div class="registration-address"><x-input-label for="address" value="Alamat" /><textarea id="address" name="address" rows="2" maxlength="2000" autocomplete="street-address" placeholder="Jalan, nomor rumah, RT/RW, dan kelurahan">{{ old('address') }}</textarea><x-input-error :messages="$errors->get('address')" class="mt-1" /></div>
            </div><p x-show="regionError" x-cloak x-text="regionError" role="alert" class="mt-2 text-xs text-red-600"></p>
            </fieldset>
        </details>
        <p class="text-xs text-slate-500">Data digunakan untuk layanan keanggotaan sesuai <a href="{{ route('privacy.policy') }}" target="_blank" rel="noopener" class="underline">Kebijakan Privasi</a>. Publikasi direktori bersifat opsional dan diatur terpisah di Pusat Privasi setelah masuk.</p>
        <fieldset class="auth-section registration-section"><legend><span class="registration-step">03</span> Keamanan akun</legend>
            @if ($googleIdentity)<p class="mb-3 text-xs text-slate-500">Kata sandi opsional karena Anda menggunakan Google. Isi jika juga ingin masuk dengan email dan sandi.</p>@else<p class="mb-3 text-xs text-slate-500">Kata sandi dan konfirmasi wajib diisi, minimal 8 karakter.</p>@endif
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><x-input-label for="password" value="Kata sandi" /><x-text-input id="password" name="password" type="password" x-bind:type="visible ? 'text' : 'password'" :required="!$googleIdentity" autocomplete="new-password" placeholder="Buat kata sandi" /></div>
                <div><x-input-label for="password_confirmation" value="Konfirmasi kata sandi" /><x-text-input id="password_confirmation" name="password_confirmation" type="password" x-bind:type="visible ? 'text' : 'password'" :required="!$googleIdentity" autocomplete="new-password" placeholder="Ulangi kata sandi" /></div>
            </div><label class="mt-3 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" x-model="visible" class="rounded border-slate-300 text-red-600 focus:ring-red-500">Tampilkan kata sandi</label>
        </fieldset>
        <input type="hidden" name="directory_consent" value="0">
        <label class="my-4 flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="directory_consent" value="1" @checked(old('directory_consent') == '1') class="mt-1 rounded border-slate-300 text-red-600 focus:ring-red-500"><span>Saya menyetujui publikasi foto, nama, nomor anggota, dojo, kabupaten/kota, provinsi, dan tingkat sabuk di direktori publik KKI setelah terverifikasi, sesuai <a href="{{ route('privacy.policy') }}" target="_blank" rel="noopener" class="underline">Kebijakan Privasi</a>. Pilihan ini opsional dan dapat ditarik melalui Privasi &amp; PDP. Untuk anggota anak, persetujuan wali harus diperiksa pengurus.</span></label>
        <div class="auth-submit-row registration-submit"><p class="text-xs text-slate-500">Sudah memiliki akun? <a href="{{ route('login', [], false) }}" class="auth-link">Masuk</a></p><button type="submit" class="btn btn-primary" x-text="registrationType === 'official' ? 'Daftar pengurus' : 'Daftar karateka'">Daftar karateka</button></div>
    </form>
