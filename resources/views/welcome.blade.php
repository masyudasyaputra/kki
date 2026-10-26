<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-kki.png') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#dc171c">

    <title>PP KKI | Kushin Ryu M Karate-Do Indonesia</title>
    <meta name="description"
        content="Pengurus Pusat Kushin Ryu M Karate-Do Indonesia (PP KKI). Temukan dojo dan cabang KKI di seluruh Indonesia.">
    <meta name="keywords"
        content="PP KKI, karate Indonesia, perguruan karate Indonesia, dojo karate Indonesia, KKI">
    <meta name="author" content="PP KKI">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="{{ url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="PP KKI">
    <meta property="og:title" content="PP KKI | Kushin Ryu M Karate-Do Indonesia">
    <meta property="og:description"
        content="Membentuk karateka berkarakter, menjaga tradisi, dan menumbuhkan prestasi bersama PP KKI.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/logo-kki.png') }}">
    <meta property="og:image:alt" content="Lambang Perguruan Karate-Do KKI">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="PP KKI">
    <meta name="twitter:description" content="Perguruan Karate-Do untuk pembinaan karakter, tradisi, dan prestasi.">
    <meta name="twitter:image" content="{{ asset('images/logo-kki.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=barlow:400,500,600,700,800|barlow-condensed:600,700,800,900"
        rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif

    <style>
        :root {
            --kki-red: #dc171c;
            --kki-deep: #7f1014;
            --kki-gold: #f3f4f6;
            --kki-ink: #171515;
            --kki-paper: #f7f4ee;
        }

        body {
            font-family: 'Barlow', sans-serif;
            background: var(--kki-paper);
        }

        .font-display {
            font-family: 'Barlow Condensed', sans-serif;
            font-style: normal;
            text-transform: uppercase;
            letter-spacing: .01em;
        }

        .hero-grid {
            background-image: linear-gradient(rgba(255, 255, 255, .055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .055) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .paper-noise {
            background-image: radial-gradient(rgba(23, 21, 21, .08) .75px, transparent .75px);
            background-size: 8px 8px;
        }

        .gold-line {
            background: linear-gradient(90deg, transparent, var(--kki-gold), transparent);
        }

        .vertical-label {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
        }

        [x-cloak] {
            display: none !important;
        }

        .reveal-up {
            animation: revealUp .75s ease-out both;
        }

        .reveal-delay-1 {
            animation-delay: .12s;
        }

        .reveal-delay-2 {
            animation-delay: .24s;
        }

        @keyframes revealUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .reveal-up {
                animation: none;
            }
        }
    </style>

    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@type": "SportsOrganization",
            "name": "Pengurus Pusat KKI",
            "alternateName": "PP KKI - Kushin Ryu M Karate-Do Indonesia",
            "url": "{{ url('/') }}",
            "logo": "{{ asset('images/logo-kki.png') }}",
            "description": "Perguruan Karate-Do untuk pembinaan karakter, tradisi, dan prestasi di Indonesia.",
            "sport": "Karate",
            "areaServed": {
                "@type": "Country",
                "name": "Indonesia"
            }
        }
    </script>
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [
                {
                    "@type": "Question",
                    "name": "Bagaimana menemukan dojo KKI di Indonesia?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Gunakan bagian Eksplor Dojo dan Cabang untuk melihat dojo aktif berdasarkan kabupaten atau kota di Indonesia."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Apa saja pembinaan yang tersedia?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Pembinaan meliputi latihan teknik karate, penguatan karakter dan kedisiplinan, ujian kenaikan tingkat, serta jalur pembinaan prestasi."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Apakah daftar dojo dikelompokkan berdasarkan kabupaten dan kota?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Ya. Dojo aktif dikelompokkan berdasarkan cabang kabupaten atau kota agar lokasi pembinaan terdekat lebih mudah ditemukan."
                    }
                }
            ]
        }
    </script>
</head>

<body class="overflow-x-hidden bg-[#050505] text-white antialiased selection:bg-[#dc171c] selection:text-white"
    x-data="{ menuOpen: false }">
    <a href="#konten-utama"
        class="fixed left-4 top-3 z-[100] -translate-y-20 rounded-lg bg-white px-4 py-2 text-sm font-bold text-[#dc171c] shadow-xl transition-transform focus:translate-y-0">
        Lewati ke konten
    </a>

    <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#050505]/90 text-white backdrop-blur-xl">
        <div class="mx-auto flex h-[76px] max-w-7xl items-center justify-between px-5 lg:px-8">
            <a href="#beranda" class="flex items-center gap-3" aria-label="PP KKI - Beranda">
                <span class="grid h-11 w-11 place-items-center rounded-full bg-white p-1 shadow-lg shadow-black/30">
                    <img src="{{ asset('images/logo-kki.png') }}" alt=""
                        class="h-full w-full object-contain" width="44" height="44">
                </span>
                <span class="leading-none">
                    <span class="block text-lg font-extrabold tracking-[0.12em]">PP KKI</span>
                    <span class="mt-1 block text-[9px] font-bold uppercase tracking-[0.22em] text-[#f3f4f6]">Pengurus Pusat</span>
                </span>
            </a>

            <nav class="hidden items-center gap-7 lg:flex" aria-label="Navigasi utama">
                <a href="#tentang"
                    class="text-sm font-semibold text-white/55 transition hover:text-[#f3f4f6]">Tentang</a>
                <a href="#nilai" class="text-sm font-semibold text-white/55 transition hover:text-[#f3f4f6]">Nilai</a>
                <a href="#program"
                    class="text-sm font-semibold text-white/55 transition hover:text-[#f3f4f6]">Program</a>
                <a href="#agenda"
                    class="text-sm font-semibold text-white/55 transition hover:text-[#f3f4f6]">Agenda</a>
                <a href="#dojo" class="text-sm font-semibold text-white/55 transition hover:text-[#f3f4f6]">Dojo &
                    Cabang</a>
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="rounded-full bg-[#dc171c] px-5 py-2.5 text-xs font-extrabold uppercase tracking-wider text-white transition hover:bg-[#a10d12]">Dashboard</a>
                @else
                    <a href="{{ route('login') }}"
                        class="text-sm font-semibold text-white/55 transition hover:text-[#f3f4f6]">Masuk</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="rounded-full bg-[#dc171c] px-5 py-2.5 text-xs font-extrabold uppercase tracking-wider text-white transition hover:bg-[#a10d12]">Daftar
                            Anggota</a>
                    @endif
                @endauth
            </nav>

            <button type="button"
                class="grid h-11 w-11 place-items-center rounded-full border border-white/20 lg:hidden"
                @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="mobile-menu"
                aria-label="Buka navigasi">
                <svg x-show="!menuOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
                <svg x-cloak x-show="menuOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        <nav id="mobile-menu" x-cloak x-show="menuOpen" x-transition @click.outside="menuOpen = false"
            class="border-t border-white/10 bg-[#0a0a0a] px-5 py-5 text-white shadow-xl lg:hidden"
            aria-label="Navigasi seluler">
            <div class="mx-auto grid max-w-7xl gap-1">
                <a href="#tentang" @click="menuOpen = false"
                    class="rounded-xl px-3 py-3 text-sm font-semibold hover:bg-white/5">Tentang</a>
                <a href="#nilai" @click="menuOpen = false"
                    class="rounded-xl px-3 py-3 text-sm font-semibold hover:bg-white/5">Nilai</a>
                <a href="#program" @click="menuOpen = false"
                    class="rounded-xl px-3 py-3 text-sm font-semibold hover:bg-white/5">Program</a>
                <a href="#agenda" @click="menuOpen = false"
                    class="rounded-xl px-3 py-3 text-sm font-semibold hover:bg-white/5">Agenda Kegiatan</a>
                <a href="#dojo" @click="menuOpen = false"
                    class="rounded-xl px-3 py-3 text-sm font-semibold hover:bg-white/5">Dojo & Cabang</a>
                <div class="mt-3 grid grid-cols-2 gap-3 border-t border-white/10 pt-4">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="col-span-2 rounded-xl bg-[#dc171c] px-4 py-3 text-center text-sm font-extrabold text-white">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="rounded-xl border border-white/20 px-4 py-3 text-center text-sm font-bold">Masuk</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="rounded-xl bg-[#dc171c] px-4 py-3 text-center text-sm font-extrabold text-white">Daftar</a>
                        @endif
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main id="konten-utama">
        <section id="beranda"
            class="relative isolate min-h-screen overflow-hidden bg-[#050505] pt-[76px] text-white">
            <div class="hero-grid absolute inset-0 -z-20 opacity-20"></div>
            <div class="absolute inset-y-0 left-0 -z-10 w-1/2 bg-gradient-to-r from-[#dc171c]/20 to-transparent"></div>
            <div class="absolute inset-y-0 right-0 -z-10 w-1/2 bg-gradient-to-l from-[#f3f4f6]/10 to-transparent"></div>
            <div class="absolute -right-32 top-24 -z-10 h-[540px] w-[540px] rounded-full border border-white/5"></div>
            <div class="absolute -right-20 top-36 -z-10 h-[420px] w-[420px] rounded-full border border-[#f3f4f6]/10">
            </div>
            <p class="font-display pointer-events-none absolute left-1/2 top-32 -z-10 -translate-x-1/2 whitespace-nowrap text-[19vw] font-black leading-none tracking-[.04em] text-white/[.035]"
                aria-hidden="true">PP KKI</p>

            <div
                class="mx-auto grid min-h-[calc(100vh-76px)] max-w-7xl items-center gap-8 px-5 py-16 lg:grid-cols-[.82fr_1.18fr] lg:px-8 lg:py-20">
                <div class="relative z-10 max-w-3xl">
                    <div class="reveal-up mb-7 flex items-center gap-3">
                        <span class="h-px w-10 bg-[#f3f4f6]"></span>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.3em] text-[#f3f4f6]">Perguruan
                            Karate-Do
                            • Indonesia</p>
                    </div>
                    <h1
                        class="font-display reveal-up reveal-delay-1 text-6xl font-black leading-[.86] sm:text-7xl lg:text-[84px]">
                        Kushin Ryu.<br>
                        <span class="text-[#f3f4f6]">Satu keluarga.</span>
                    </h1>
                    <p class="reveal-up reveal-delay-2 mt-7 max-w-md text-base leading-7 text-white/55">
                        Kushin Ryu M Karate-Do Indonesia. Rumah pembinaan karateka di seluruh Indonesia yang menjunjung disiplin, persaudaraan, dan
                        semangat untuk terus bertumbuh.
                    </p>
                    <div class="reveal-up reveal-delay-2 mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="#dojo"
                            class="inline-flex items-center justify-center gap-3 rounded-full bg-[#dc171c] px-7 py-4 text-sm font-extrabold text-white shadow-xl shadow-[#dc171c]/15 transition hover:-translate-y-0.5 hover:bg-[#a10d12]">
                            Eksplor Dojo & Cabang
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="m9 5 7 7-7 7" />
                            </svg>
                        </a>
                        <a href="#tentang"
                            class="inline-flex items-center justify-center rounded-full border border-white/20 px-7 py-4 text-sm font-bold text-white transition hover:border-[#f3f4f6] hover:text-[#f3f4f6]">Kenal
                            Lebih Dekat</a>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-[900px] lg:ml-auto">
                    <div class="absolute inset-8 rounded-full bg-[#dc171c]/20 blur-[90px]"></div>
                    <img src="{{ asset('images/logo-kki.png') }}"
                        alt="Lambang Kushin Ryu M Karate-Do Indonesia"
                        class="relative mx-auto w-full max-w-[520px] object-contain px-8 pb-16 pt-4 drop-shadow-[0_25px_60px_rgba(220,23,28,.2)]"
                        width="520" height="520" fetchpriority="high">

                    <aside
                        class="relative z-20 ml-auto -mt-10 w-[92%] rounded-[1.5rem] border border-white/15 bg-black/85 p-5 text-white shadow-xl shadow-black/30 backdrop-blur sm:w-72 lg:absolute lg:-bottom-8 lg:right-0 lg:mt-0 lg:w-64"
                        aria-label="Perguruan KKI">
                        <div class="flex items-center gap-4">
                            <img src="{{ asset('images/logo-kki.png') }}" alt="Lambang KKI"
                                class="h-14 w-14 object-contain" width="56" height="56">
                            <div>
                                <p class="text-[9px] font-extrabold uppercase tracking-[.2em] text-[#f3f4f6]">Perguruan
                                </p>
                                <h2 class="font-display text-xl font-black">Kushin Ryu</h2>
                            </div>
                        </div>
                        <p class="mt-4 border-t border-white/10 pt-4 text-xs leading-5 text-white/50">Bagian dari
                            keluarga
                            Kushin Ryu M Karate-Do Indonesia.</p>
                    </aside>

                </div>
            </div>

            <div class="relative z-20 mx-auto -mt-8 max-w-7xl px-5 pb-12 lg:px-8">
                <div
                    class="grid overflow-hidden rounded-[2rem] bg-[#171515] text-white shadow-2xl shadow-black/15 sm:grid-cols-3">
                    <div class="border-white/15 px-7 py-7 text-center sm:border-r">
                        <p class="font-display text-4xl font-black">{{ $dojos->count() }}<span
                                class="text-[#d72b36]">+</span></p>
                        <p class="mt-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-white/45">Dojo aktif
                            terverifikasi</p>
                    </div>
                    <div class="border-y border-white/15 px-7 py-7 text-center sm:border-y-0 sm:border-r">
                        <p class="font-display text-4xl font-black">{{ $dojoRegions->count() }}<span
                                class="text-[#d72b36]">+</span></p>
                        <p class="mt-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-white/45">Cabang
                            kabupaten/kota</p>
                    </div>
                    <div class="px-7 py-7 text-center">
                        <p class="font-display text-4xl font-black">KKI</p>
                        <p class="mt-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-white/45">Perguruan
                            Kushin Ryu Indonesia</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-white/10 bg-[#090909] py-10 text-white" aria-label="Identitas perguruan KKI">
            <div class="mx-auto flex max-w-7xl flex-col items-center gap-6 px-5 text-center sm:flex-row sm:text-left lg:px-8">
                <img src="{{ asset('images/logo-kki.png') }}" alt="Lambang Kushin Ryu M Karate-Do Indonesia" class="h-20 w-20 shrink-0 object-contain" width="80" height="80">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.25em] text-red-400">Satu perguruan. Satu keluarga.</p>
                    <h2 class="font-display mt-2 text-2xl font-black sm:text-3xl">Kushin Ryu M Karate-Do Indonesia</h2>
                    <p class="mt-2 text-sm text-white/50">Pembinaan karate, karakter, dan persaudaraan di Indonesia.</p>
                </div>
                <a href="#lambang" class="sm:ml-auto shrink-0 rounded-full border border-white/20 px-5 py-3 text-xs font-bold transition hover:border-red-500">Makna lambang KKI &rarr;</a>
            </div>
        </section>
        <section id="tentang" class="scroll-mt-20 bg-[#050505] px-5 py-16 sm:py-24 lg:px-8">
            <div
                class="mx-auto grid max-w-7xl gap-14 overflow-hidden rounded-[2.5rem] bg-[#171515] px-7 py-16 text-white sm:px-12 lg:grid-cols-[.8fr_1.2fr] lg:px-16 lg:py-20">
                <div>
                    <p
                        class="mb-5 flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.3em] text-[#ef4651]">
                        <span class="h-px w-9 bg-[#ef4651]"></span> Tentang PP KKI
                    </p>
                    <h2 class="font-display text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">Lebih
                        dari
                        sekadar<br><span class="text-white/25">olahraga bela diri.</span></h2>
                </div>
                <div class="lg:pt-8">
                    <p class="text-xl font-semibold leading-9 text-white/85 sm:text-2xl sm:leading-10">Kushin Ryu M Karate-Do Indonesia (KKI) adalah keluarga karate beraliran Kushin Ryu yang menjunjung kesatuan, persaudaraan, dan pembentukan karakter.</p>
                    <div class="mt-8 grid gap-6 border-t border-white/10 pt-8 sm:grid-cols-2">
                        <p class="text-sm leading-7 text-white/50">Pengurus Pusat KKI mengoordinasikan proses
                            pembinaan
                            organisasi dan karateka melalui latihan yang terarah, tata kelola yang tertib, serta
                            semangat
                            kekeluargaan.</p>
                        <p class="text-sm leading-7 text-white/50">Setiap anggota didorong untuk tumbuh seimbang: kuat
                            dalam
                            teknik, matang dalam sikap, dan siap membawa nilai-nilai budo dalam kehidupan sehari-hari.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="lambang" class="scroll-mt-20 border-y border-white/10 bg-[#0a0a0a] py-20 sm:py-28">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 lg:grid-cols-[.8fr_1.2fr] lg:px-8">
                <div class="text-center">
                    <img src="{{ asset('images/logo-kki.png') }}" alt="Lambang KKI: bunga melati, lingkaran Kushin Ryu, merah putih, dan pita Indonesia" class="mx-auto w-full max-w-sm object-contain" width="384" height="384" loading="lazy">
                    <p class="mt-6 text-xs font-bold uppercase tracking-[.25em] text-white/40">Kesatuan &amp; persaudaraan</p>
                    <a href="{{ asset('images/makna-lambang-kki.png') }}" target="_blank" rel="noopener noreferrer" class="mt-7 inline-flex items-center gap-2 rounded-full border border-white/20 px-5 py-3 text-xs font-bold transition hover:border-red-500">Lihat penjelasan lambang lengkap &nearr;</a>
                </div>
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[.3em] text-red-400">Identitas yang menyatukan</p>
                    <h2 class="font-display mt-4 text-4xl font-black sm:text-5xl">Makna lambang KKI.</h2>
                    <p class="mt-5 text-base leading-7 text-white/60">Bunga melati berkelopak putih dengan pusat merah putih menjadi lambang kesatuan dan persatuan keluarga besar KKI di seluruh Indonesia.</p>
                    <dl class="mt-8 grid gap-6 sm:grid-cols-2">
                        <div class="border-t border-white/15 pt-5"><dt class="font-bold">Bunga melati</dt><dd class="mt-2 text-sm leading-6 text-white/55">Melambangkan jiwa muda yang berkembang. Lima daun bunga mengingatkan pada lima sila Pancasila.</dd></div>
                        <div class="border-t border-white/15 pt-5"><dt class="font-bold">Lingkaran Kushin Ryu</dt><dd class="mt-2 text-sm leading-6 text-white/55">Tulisan pada lingkaran hitam menunjukkan identitas perguruan dan aliran karate Kushin Ryu.</dd></div>
                        <div class="border-t border-white/15 pt-5"><dt class="font-bold">Pusat merah putih</dt><dd class="mt-2 text-sm leading-6 text-white/55">Melambangkan bendera Indonesia serta semangat keberanian dan kesucian dalam berlatih dan berkarya.</dd></div>
                        <div class="border-t border-white/15 pt-5"><dt class="font-bold">Pita Indonesia</dt><dd class="mt-2 text-sm leading-6 text-white/55">Menyatukan keberagaman anggota KKI dalam semangat Bhinneka Tunggal Ika dan persaudaraan.</dd></div>
                    </dl>
                </div>
            </div>
        </section>
        <section id="nilai" class="scroll-mt-20 bg-[#050505] py-24 text-white sm:py-32">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="mb-14 flex flex-col justify-between gap-7 md:flex-row md:items-end">
                    <div>
                        <p class="mb-5 text-[10px] font-extrabold uppercase tracking-[.3em] text-[#f3f4f6]">Semangat KKI •
                            Nilai
                            yang dihidupi</p>
                        <h2 class="font-display max-w-xl text-4xl font-extrabold leading-tight sm:text-5xl">Teguh dalam
                            prinsip,<br><span class="text-white/20">rendah hati dalam sikap.</span></h2>
                    </div>
                    <p class="max-w-md text-sm leading-7 text-white/50">Nilai utama yang menjadi napas pembinaan dan
                        hubungan
                        antarkarateka di lingkungan PP KKI.</p>
                </div>

                <div class="grid border-y border-white/10 md:grid-cols-3">
                    @php
                        $values = [
                            [
                                '01',
                                'Karakter',
                                'Menjaga kejujuran, tanggung jawab, dan rasa hormat di dalam maupun di luar dojo.',
                                'character',
                            ],
                            [
                                '02',
                                'Ketekunan',
                                'Berlatih konsisten, berani menghadapi proses, dan terus memperbaiki diri setiap hari.',
                                'perseverance',
                            ],
                            [
                                '03',
                                'Persaudaraan',
                                'Tumbuh sebagai satu keluarga yang saling mendukung tanpa kehilangan semangat berprestasi.',
                                'brotherhood',
                            ],
                        ];
                    @endphp
                    @foreach ($values as [$number, $title, $description, $icon])
                        <article class="group border-white/10 px-2 py-10 md:border-l md:px-8 first:md:border-l-0">
                            <div class="mb-10 flex items-center justify-between">
                                <span class="font-display text-lg text-[#f3f4f6]">{{ $number }}</span>
                                <span
                                    class="grid h-14 w-14 place-items-center rounded-full border border-[#f3f4f6]/35 bg-[#f3f4f6]/5 text-[#f3f4f6] transition duration-300 group-hover:border-[#dc171c] group-hover:bg-[#dc171c] group-hover:text-white">
                                    @if ($icon === 'character')
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                                d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                                d="m9 12 2 2 4-4" />
                                        </svg>
                                    @elseif ($icon === 'perseverance')
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <circle cx="11" cy="13" r="7.5" stroke-width="1.7" />
                                            <circle cx="11" cy="13" r="3" stroke-width="1.7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                                d="m13.5 10.5 6-6M16 4.5h3.5V8" />
                                        </svg>
                                    @else
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <circle cx="9" cy="8" r="3" stroke-width="1.7" />
                                            <circle cx="17" cy="10" r="2.5" stroke-width="1.7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                                d="M3 20v-1.5A4.5 4.5 0 0 1 7.5 14h3A4.5 4.5 0 0 1 15 18.5V20M15 15h1.5a4 4 0 0 1 4 4v1" />
                                        </svg>
                                    @endif
                                </span>
                            </div>
                            <h3 class="font-display text-3xl font-bold">{{ $title }}</h3>
                            <p class="mt-4 text-sm leading-7 text-white/50">{{ $description }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="program" class="scroll-mt-20 overflow-hidden bg-[#0a0a0a] py-24 text-white sm:py-32">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="mx-auto mb-16 max-w-2xl text-center">
                    <p class="mb-4 text-[10px] font-extrabold uppercase tracking-[.3em] text-[#dc171c]">Ruang untuk
                        bertumbuh</p>
                    <h2 class="font-display text-4xl font-extrabold tracking-tight sm:text-5xl">Pembinaan yang utuh
                        untuk
                        <span class="italic text-[#dc171c]">setiap langkah.</span>
                    </h2>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $programs = [
                            [
                                'Latihan Karate-Do',
                                'Pembinaan kihon, kata, dan kumite yang terarah sesuai tingkat kemampuan karateka.',
                                'M7 4h10M5 20h14M12 4v16M4 7h16',
                            ],
                            [
                                'Ujian Kenaikan Tingkat',
                                'Proses evaluasi kemampuan dan sikap sebagai bagian dari perjalanan karateka.',
                                'm9 12 2 2 4-4m6 2a9 9 0 1 1-3-6.7M20 4v5h-5',
                            ],
                            [
                                'Pembinaan Prestasi',
                                'Pendampingan latihan untuk menyiapkan karateka menghadapi ruang kompetisi secara sportif.',
                                'M8 21h8m-4-4v4m-7-18h14v4a7 7 0 0 1-14 0V3Zm0 4H3v1a4 4 0 0 0 4 4m10-4h4v1a4 4 0 0 1-4 4',
                            ],
                            [
                                'Organisasi Digital',
                                'Administrasi anggota, dojo, ujian, dan informasi organisasi dalam satu ekosistem.',
                                'M4 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Zm4 3h8M8 12h8M8 16h5',
                            ],
                        ];
                    @endphp
                    @foreach ($programs as [$title, $description, $icon])
                        <article
                            class="group relative flex min-h-[340px] flex-col justify-end overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#111111] p-7 transition duration-300 hover:-translate-y-1 hover:border-[#f3f4f6]/40 hover:shadow-2xl hover:shadow-black/40">
                            <img src="{{ asset('images/logo-kki.png') }}"
                                alt="" aria-hidden="true"
                                class="absolute right-2 top-4 h-44 w-44 object-contain opacity-10 transition duration-500 group-hover:scale-105 group-hover:opacity-20"
                                loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent"></div>
                            <span
                                class="relative grid h-12 w-12 place-items-center rounded-2xl bg-[#dc171c] text-white shadow-lg shadow-[#dc171c]/15">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                        d="{{ $icon }}" />
                                </svg>
                            </span>
                            <h3 class="relative mt-6 font-display text-2xl font-bold leading-tight">{{ $title }}</h3>
                            <p class="relative mt-3 text-sm leading-6 text-white/60">{{ $description }}</p>
                            <div class="relative mt-6 h-px w-10 bg-[#f3f4f6] transition-all duration-300 group-hover:w-full">
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="agenda" class="relative scroll-mt-20 overflow-hidden bg-[#171515] py-24 text-white sm:py-32">
            <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full border border-[#f3f4f6]/10">
            </div>
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full border border-[#dc171c]/25">
            </div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#f3f4f6]/30 to-transparent">
            </div>

            <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
                <div class="mb-14 flex flex-col justify-between gap-7 md:flex-row md:items-end">
                    <div>
                        <p class="mb-5 flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.3em] text-[#f3f4f6]">
                            <span class="h-px w-9 bg-[#f3f4f6]"></span> Kalender PP KKI
                        </p>
                        <h2 class="font-display max-w-2xl text-4xl font-black leading-[.95] sm:text-6xl">Agenda
                            <span class="text-[#f3f4f6]">kegiatan.</span>
                        </h2>
                    </div>
                    <p class="max-w-md text-sm leading-7 text-white/50">Pantau jadwal kegiatan dan ujian resmi di
                        lingkungan PP KKI.</p>
                </div>

                @if ($agendas->isNotEmpty())
                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($agendas as $agenda)
                            @php
                                $agendaMonths = [
                                    1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR', 5 => 'MEI', 6 => 'JUN',
                                    7 => 'JUL', 8 => 'AGU', 9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES',
                                ];
                            @endphp
                            <article
                                class="group relative overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#0a0a0a] p-7 transition duration-300 hover:-translate-y-1 hover:border-[#f3f4f6]/40">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#dc171c] to-[#f3f4f6]"></div>
                                <div class="flex items-start justify-between gap-5">
                                    <time datetime="{{ $agenda->execution_date->toDateString() }}"
                                        class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-[#dc171c] text-center shadow-lg shadow-[#dc171c]/15">
                                        <span class="font-display text-3xl font-black leading-none">{{ $agenda->execution_date->format('d') }}</span>
                                        <span class="mt-1 text-[9px] font-extrabold uppercase tracking-[.18em] text-[#f1cf86]">
                                            {{ $agendaMonths[(int) $agenda->execution_date->format('n')] }}
                                            {{ $agenda->execution_date->format('Y') }}
                                        </span>
                                    </time>
                                    <span
                                        class="rounded-full border border-[#f3f4f6]/25 bg-[#f3f4f6]/10 px-3 py-2 text-[8px] font-extrabold uppercase tracking-[.16em] text-[#f3f4f6]">
                                        {{ $agenda->status === 'ongoing' ? 'Berlangsung' : 'Pendaftaran dibuka' }}
                                    </span>
                                </div>

                                <p class="mt-8 text-[9px] font-extrabold uppercase tracking-[.2em] text-[#f3f4f6]">Agenda
                                    resmi</p>
                                <h3 class="mt-3 font-display text-2xl font-black leading-tight">{{ $agenda->name }}</h3>

                                <div class="mt-7 space-y-3 border-t border-white/10 pt-6 text-sm text-white/50">
                                    <p class="flex items-start gap-3">
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#f3f4f6]" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Z" />
                                            <circle cx="12" cy="9" r="2.2" stroke-width="1.8" />
                                        </svg>
                                        <span>{{ $agenda->location }}</span>
                                    </p>
                                    @if ($agenda->province)
                                        <p class="flex items-center gap-3">
                                            <svg class="h-4 w-4 shrink-0 text-[#f3f4f6]" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                    d="M4 19.5V6.8L12 3l8 3.8v12.7M8 21V9h8v12M3 21h18" />
                                            </svg>
                                            <span>{{ $agenda->province->name }}</span>
                                        </p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div
                        class="grid gap-8 overflow-hidden rounded-[2rem] border border-white/10 bg-[#0a0a0a] p-8 sm:p-10 md:grid-cols-[auto_1fr] md:items-center">
                        <span
                            class="grid h-20 w-20 place-items-center rounded-2xl bg-[#dc171c] text-[#f1cf86] shadow-xl shadow-[#dc171c]/15">
                            <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="2" stroke-width="1.7" />
                                <path stroke-linecap="round" stroke-width="1.7" d="M8 3v4M16 3v4M3 10h18" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="m9.5 15 1.7 1.7 3.5-3.7" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[.2em] text-[#f3f4f6]">Segera hadir</p>
                            <h3 class="mt-2 font-display text-2xl font-black sm:text-3xl">Agenda berikutnya sedang
                                disiapkan.</h3>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-white/50">Jadwal resmi kegiatan PP KKI akan tampil di sini setelah pendaftaran dibuka.</p>
                        </div>
                    </div>
                @endif

                <p class="mt-6 text-xs leading-6 text-white/35">Jadwal dapat berubah mengikuti keputusan panitia dan
                    pengurus pusat dan pengurus daerah.</p>
            </div>
        </section>

        <section id="dojo" class="scroll-mt-20 bg-[#050505] py-24 text-white sm:py-32" x-data="{ query: '' }">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-[1fr_.72fr] lg:items-end">
                    <div>
                        <p class="mb-5 text-[10px] font-extrabold uppercase tracking-[.3em] text-[#dc171c]">Jejak
                            KKI di seluruh Indonesia</p>
                        <h2 class="font-display text-5xl font-black leading-[.92] sm:text-6xl">Temukan dojo dan
                            <span class="text-[#dc171c]">cabang terdekat.</span>
                        </h2>
                        <p class="mt-6 max-w-2xl text-base leading-7 text-white/50">Eksplor dojo aktif dalam jaringan PP KKI di seluruh Indonesia, berdasarkan cabang kabupaten dan kota.</p>
                    </div>

                    <div
                        class="grid grid-cols-2 overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#111111] shadow-lg shadow-black/30">
                        <div class="border-r border-white/10 p-6">
                            <p class="font-display text-4xl font-black text-[#f3f4f6]">{{ $dojos->count() }}</p>
                            <p class="mt-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-white/45">Dojo
                                aktif</p>
                        </div>
                        <div class="p-6">
                            <p class="font-display text-4xl font-black text-[#f3f4f6]">{{ $dojoRegions->count() }}</p>
                            <p class="mt-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-white/45">Cabang
                                kab/kota</p>
                        </div>
                    </div>
                </div>

                @if ($dojos->isNotEmpty())
                    <div class="relative mt-12">
                        <label for="dojo-search" class="sr-only">Cari dojo atau kabupaten/kota</label>
                        <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-black/35"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke-width="2" />
                            <path stroke-linecap="round" stroke-width="2" d="m16 16 4 4" />
                        </svg>
                        <input id="dojo-search" type="search" x-model="query"
                            placeholder="Cari nama dojo, pelatih, atau kabupaten/kota..."
                            class="w-full rounded-2xl border-0 bg-white py-4 pl-14 pr-5 text-sm shadow-lg shadow-black/5 ring-1 ring-black/10 placeholder:text-black/35 focus:ring-2 focus:ring-[#dc171c]">
                    </div>

                    <div class="mt-10 space-y-8">
                        @foreach ($dojoRegions as $region => $regionDojos)
                            @php
                                $regionSearch = strtolower(
                                    $region .
                                        ' ' .
                                        $regionDojos->pluck('name')->implode(' ') .
                                        ' ' .
                                        $regionDojos->pluck('address')->implode(' ') .
                                        ' ' .
                                        $regionDojos->pluck('sensei_name')->implode(' '),
                                );
                            @endphp
                            <article data-search="{{ $regionSearch }}"
                                x-show="query === '' || $el.dataset.search.includes(query.toLowerCase())"
                                x-transition.opacity.duration.200ms
                                class="overflow-hidden rounded-[2rem] bg-white shadow-lg shadow-black/5">
                                <div
                                    class="flex flex-col justify-between gap-4 border-b border-black/10 bg-[#171515] px-6 py-5 text-white sm:flex-row sm:items-center sm:px-8">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="grid h-9 w-9 place-items-center rounded-full bg-[#dc171c] text-[#f3f4f6]">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="1.8"
                                                    d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Z" />
                                                <circle cx="12" cy="9" r="2.2" stroke-width="1.8" />
                                            </svg>
                                        </span>
                                        <div>
                                            <p class="text-[9px] font-bold uppercase tracking-[.2em] text-white/45">
                                                Cabang kabupaten/kota</p>
                                            <h3 class="font-display text-2xl font-black">{{ $region }}</h3>
                                        </div>
                                    </div>
                                    <span
                                        class="w-fit rounded-full border border-white/15 px-4 py-2 text-[9px] font-extrabold uppercase tracking-[.16em] text-[#f3f4f6]">
                                        {{ $regionDojos->count() }} Dojo
                                    </span>
                                </div>

                                <div class="grid gap-px bg-black/10 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($regionDojos as $dojo)
                                        <div class="flex min-h-64 flex-col bg-white p-6 sm:p-7">
                                            <div class="mb-6 flex items-center justify-between gap-4">
                                                <span
                                                    class="rounded-full bg-green-50 px-3 py-1.5 text-[9px] font-extrabold uppercase tracking-[.15em] text-green-700">Aktif</span>
                                                <span
                                                    class="font-display text-xl font-black text-black/10">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                            </div>
                                            <h4 class="font-display text-2xl font-black leading-none text-[#171515]">
                                                {{ $dojo->name }}</h4>
                                            @if ($dojo->sensei_name)
                                                <p class="mt-3 text-xs font-bold text-[#dc171c]">Pelatih:
                                                    {{ $dojo->sensei_name }}</p>
                                            @endif
                                            <p class="mt-4 text-sm leading-6 text-black/50">{{ $dojo->address }}</p>
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($dojo->name . ' ' . $dojo->address . ' ' . $region) }}"
                                                target="_blank" rel="noopener noreferrer"
                                                class="mt-auto inline-flex items-center gap-2 pt-7 text-xs font-extrabold uppercase tracking-[.12em] text-[#dc171c] transition hover:text-[#a10d12]">
                                                Lihat lokasi
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M7 17 17 7M8 7h9v9" />
                                                </svg>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div
                        class="mt-12 grid min-h-72 place-items-center rounded-[2rem] border border-dashed border-white/15 bg-[#111111] p-8 text-center">
                        <div class="max-w-md">
                            <span
                                class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-[#dc171c] text-white shadow-lg shadow-[#dc171c]/20">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                        d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Z" />
                                    <circle cx="12" cy="9" r="2.2" stroke-width="1.7" />
                                </svg>
                            </span>
                            <h3 class="font-display mt-6 text-3xl font-black">Direktori dojo sedang disiapkan</h3>
                            <p class="mt-3 text-sm leading-7 text-white/50">Data dojo dan cabang kabupaten/kota akan
                                tampil
                                otomatis di sini setelah diverifikasi oleh PP KKI.</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="bg-[#0a0a0a] py-24 text-white sm:py-28" aria-labelledby="faq-title">
            <div class="mx-auto max-w-4xl px-5 lg:px-8" x-data="{ active: 0 }">
                <div class="mb-12 text-center">
                    <p class="mb-4 text-[10px] font-extrabold uppercase tracking-[.3em] text-[#dc171c]">Informasi umum
                    </p>
                    <h2 id="faq-title" class="font-display text-4xl font-extrabold sm:text-5xl">Pertanyaan yang sering
                        ditanyakan</h2>
                </div>
                @php
                    $faqs = [
                        [
                            'Bagaimana menemukan dojo KKI di Indonesia?',
                            'Gunakan bagian Eksplor Dojo dan Cabang untuk melihat dojo aktif berdasarkan kabupaten atau kota di Indonesia.',
                        ],
                        [
                            'Apa saja pembinaan yang tersedia?',
                            'Pembinaan meliputi latihan teknik karate, penguatan karakter dan kedisiplinan, ujian kenaikan tingkat, serta jalur pembinaan prestasi.',
                        ],
                        [
                            'Apakah daftar dojo dikelompokkan berdasarkan kabupaten dan kota?',
                            'Ya. Dojo aktif dikelompokkan berdasarkan cabang kabupaten atau kota agar lokasi pembinaan terdekat lebih mudah ditemukan.',
                        ],
                    ];
                @endphp
                <div class="divide-y divide-white/10 border-y border-white/10">
                    @foreach ($faqs as [$question, $answer])
                        <article>
                            <h3>
                                <button type="button"
                                    @click="active = active === {{ $loop->index }} ? -1 : {{ $loop->index }}"
                                    class="flex w-full items-center justify-between gap-6 py-6 text-left font-bold"
                                    :aria-expanded="(active === {{ $loop->index }}).toString()">
                                    <span>{{ $question }}</span>
                                    <span
                                        class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-white/15 text-[#f3f4f6]">
                                        <svg class="h-4 w-4 transition"
                                            :class="active === {{ $loop->index }} && 'rotate-45'" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14" />
                                        </svg>
                                    </span>
                                </button>
                            </h3>
                            <div x-cloak x-show="active === {{ $loop->index }}" x-transition.opacity.duration.200ms>
                                <p class="max-w-3xl pb-6 pr-14 text-sm leading-7 text-white/50">{{ $answer }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-[#dc171c] py-20 text-white">
            <div class="hero-grid absolute inset-0 opacity-30"></div>
            <img src="{{ asset('images/logo-kki.png') }}" alt="" aria-hidden="true"
                class="absolute -bottom-32 -right-20 w-96 opacity-[.06] brightness-0 invert">
            <div
                class="relative mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 px-5 md:flex-row md:items-center lg:px-8">
                <div>
                    <p class="mb-3 text-[10px] font-extrabold uppercase tracking-[.3em] text-[#f3f4f6]">Dari Indonesia
                        untuk karate-do</p>
                    <h2 class="font-display max-w-2xl text-4xl font-black leading-[.95] sm:text-5xl">Temukan tempat
                        berlatih
                        dan keluarga KKI terdekat.</h2>
                </div>
                <a href="#dojo"
                    class="inline-flex shrink-0 items-center gap-3 rounded-full bg-white px-7 py-4 text-sm font-extrabold text-[#a10d12] transition hover:bg-[#f1dfb6]">
                    Eksplor Dojo & Cabang
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 5 7 7-7 7" />
                    </svg>
                </a>
            </div>
        </section>
    </main>

    <footer class="bg-[#171515] text-white">
        <div class="mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:grid-cols-2 lg:grid-cols-[1.4fr_.7fr_.7fr] lg:px-8">
            <div>
                <a href="#beranda" class="inline-flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-white p-1">
                        <img src="{{ asset('images/logo-kki.png') }}" alt=""
                            class="h-full w-full object-contain" width="48" height="48" loading="lazy">
                    </span>
                    <span>
                        <span class="block text-xl font-extrabold tracking-[.12em]">PP KKI</span>
                        <span class="text-[9px] font-bold uppercase tracking-[.2em] text-[#f3f4f6]">Pengurus Pusat</span>
                    </span>
                </a>
                <p class="mt-6 max-w-md text-sm leading-7 text-white/45">Wadah pembinaan karate-do yang menumbuhkan
                    karakter, menjaga tradisi, dan mendorong prestasi karateka di seluruh Indonesia.</p>
            </div>
            <div>
                <h2 class="text-xs font-extrabold uppercase tracking-[.18em] text-[#f3f4f6]">Jelajahi</h2>
                <nav class="mt-5 grid gap-3 text-sm text-white/55" aria-label="Navigasi footer">
                    <a href="#tentang" class="transition hover:text-white">Tentang kami</a>
                    <a href="#lambang" class="transition hover:text-white">Makna lambang KKI</a>
                    <a href="#nilai" class="transition hover:text-white">Nilai organisasi</a>
                    <a href="#program" class="transition hover:text-white">Program pembinaan</a>
                    <a href="#agenda" class="transition hover:text-white">Agenda kegiatan</a>
                    <a href="#dojo" class="transition hover:text-white">Dojo & cabang</a>
                </nav>
            </div>
            <div>
                <h2 class="text-xs font-extrabold uppercase tracking-[.18em] text-[#f3f4f6]">Akses</h2>
                <div class="mt-5 grid gap-3 text-sm text-white/55">
                    <a href="{{ route('login') }}" class="transition hover:text-white">Masuk portal</a>
                    <a href="#dojo" class="transition hover:text-white">Direktori dojo</a>
                    <p>Indonesia</p>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-6 text-[10px] font-bold uppercase tracking-[.16em] text-white/35 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <p>&copy; {{ date('Y') }} PP KKI.</p>
                <p>Karate-do • Karakter • Persaudaraan</p>
            </div>
        </div>
    </footer>
</body>

</html>
