<x-app-layout>
    <x-slot name="header"><h1 class="page-title">Panduan Sistem</h1><p class="page-subtitle">Petunjuk penggunaan portal KKI untuk setiap peran.</p></x-slot>
    <div class="guide-workspace mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8" x-data="{
        query: '', audience: 'all', expanded: null, entries: @js($guideIndex),
        visible(id) { const entry = this.entries.find(item => item.id === id); return entry && (this.audience === 'all' || entry.audiences.includes(this.audience)) && this.query.toLocaleLowerCase('id').trim().split(/\s+/).filter(Boolean).every(term => entry.text.includes(term)); },
        get resultCount() { return this.entries.filter(entry => this.visible(entry.id)).length; }
    }">
        <section class="guide-hero">
            <span class="guide-eyebrow">PUSAT BANTUAN KKI</span><h2>Kenali sistem.<br>Jalankan tugas dengan tepat.</h2>
            <p>Dari pendaftaran pertama sampai pengesahan UKT dan penerbitan sertifikat. Temukan langkah, kewenangan, dan arti status pada setiap modul.</p>
            <div class="guide-hero-meta"><span>{{ count($sections) }} topik panduan</span><span>Untuk karateka, pengurus, dan petugas</span></div>
        </section>
        <section class="guide-search" aria-label="Cari dan filter panduan">
            <div><label for="guide-search">Cari panduan</label><input id="guide-search" type="search" x-model.debounce.150ms="query" placeholder="Contoh: ijazah, MSH, pengesahan KYU..." autocomplete="off" aria-controls="guide-sections"></div>
            <div><label for="guide-audience">Panduan untuk</label><select id="guide-audience" x-model="audience"><option value="all">Semua pengguna</option><option value="karateka">Karateka</option><option value="pengurus">Pengurus</option><option value="ujian">Panitia & penguji</option><option value="admin">Administrator</option></select></div>
            <button type="button" class="btn btn-secondary" @click="query = ''; audience = 'all'">Reset</button>
        </section>
        <div class="guide-layout">
            <aside class="guide-toc" aria-label="Daftar isi panduan"><p>DAFTAR ISI</p><nav>@foreach($sections as $id => $section)<a href="#panduan-{{ $id }}" @click="document.getElementById('panduan-{{ $id }}').open = true" x-show="visible(@js($id))">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}<span>{{ $section['title'] }}</span></a>@endforeach</nav></aside>
            <div id="guide-sections" class="min-w-0">
                <div class="guide-mobile-toc"><label for="guide-jump">Langsung ke topik</label><select id="guide-jump" @change="if ($event.target.value) { const target = document.getElementById($event.target.value); if (target) { target.open = true; target.scrollIntoView({ behavior: 'smooth', block: 'start' }); } }"><option value="">Pilih topik panduan</option>@foreach($sections as $id => $section)<option value="panduan-{{ $id }}" x-show="visible(@js($id))">{{ $section['title'] }}</option>@endforeach</select></div>
                <div class="guide-results"><p aria-live="polite"><span x-text="resultCount">{{ count($sections) }}</span> topik ditemukan</p><div><button type="button" @click="expanded = true">Buka semua</button><button type="button" @click="expanded = false; query = ''">Tutup semua</button></div></div>
                <div x-show="resultCount === 0" x-cloak class="guide-empty"><h2>Belum ada topik yang cocok</h2><p>Coba kata kunci lebih singkat atau pilih Semua pengguna.</p><button type="button" class="btn btn-secondary" @click="query = ''; audience = 'all'">Tampilkan semua panduan</button></div>
                @foreach($sections as $id => $section)
                    <details id="panduan-{{ $id }}" class="guide-section" data-guide-section x-show="visible(@js($id))" :open="query.trim().length > 0 || expanded === true || (expanded === null && @js($loop->first))" @if($loop->first) open @endif>
                        <summary><span class="guide-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $section['title'] }}</h2><span class="guide-chevron" aria-hidden="true">⌄</span></summary>
                        <div class="guide-section-body"><p class="guide-intro">{{ $section['intro'] }}</p><h3>Langkah penggunaan</h3><ol>@foreach($section['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                            <div class="guide-notes"><h3>Yang perlu dipahami</h3><ul>@foreach($section['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div>
                        </div>
                    </details>
                @endforeach
                <div class="guide-end"><h2>Masih membutuhkan bantuan?</h2><p>Hubungi pengurus wilayah atau administrator dengan menyebutkan menu dan langkah yang bermasalah. Menu yang terlihat mengikuti peran serta wilayah akun Anda.</p><a href="{{ route('profile.edit') }}" class="btn btn-secondary">Periksa profil akun</a></div>
            </div>
        </div>
    </div>
</x-app-layout>
