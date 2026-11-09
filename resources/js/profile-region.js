export default function profileRegion(data) {
    return {
        ...data, loadingCities: false, loadingDojos: false, regionError: '', failedStep: '', version: 0,
        async fetchList(path) {
            const response = await fetch(`${this.baseUrl}/${path}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat data wilayah.');
            return response.json();
        },
        async changeProvince() {
            const version = ++this.version;
            this.city = ''; this.dojo = ''; this.district = '';
            this.cities = []; this.dojos = []; this.districts = [];
            this.regionError = ''; this.failedStep = ''; this.loadingDojos = false;
            this.loadingCities = !!this.province;
            if (!this.province) return;
            try {
                const cities = await this.fetchList(`cities/${this.province}`);
                if (version === this.version) this.cities = cities;
            } catch {
                if (version === this.version) { this.regionError = 'Daftar kabupaten / kota belum dapat dimuat.'; this.failedStep = 'province'; }
            } finally { if (version === this.version) this.loadingCities = false; }
        },
        async changeCity() {
            const version = ++this.version;
            this.dojo = ''; this.district = ''; this.dojos = []; this.districts = [];
            this.regionError = ''; this.failedStep = ''; this.loadingDojos = !!this.city;
            if (!this.city) return;
            try {
                const [dojos, districts] = await Promise.all([this.fetchList(`dojos/${this.city}`), this.fetchList(`districts/${this.city}`)]);
                if (version === this.version) { this.dojos = dojos; this.districts = districts; }
            } catch {
                if (version === this.version) { this.regionError = 'Daftar dojo dan kecamatan belum dapat dimuat.'; this.failedStep = 'city'; }
            } finally { if (version === this.version) this.loadingDojos = false; }
        },
        retry() { return this.failedStep === 'province' ? this.changeProvince() : this.changeCity(); },
    };
}
