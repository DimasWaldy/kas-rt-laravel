<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart RW adalah platform digital terpadu untuk administrasi, keuangan, dan layanan warga RW.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Smart RW | Sistem Informasi Warga RW Modern</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    <nav class="sticky top-0 z-50 border-b border-emerald-100/80 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-20 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Smart RW - Beranda">
                <img src="{{ asset('images/logo.svg') }}" alt="Logo Smart RW" class="h-10 w-10 shrink-0 rounded-2xl shadow-lg shadow-emerald-500/20">
                <span class="text-lg font-black tracking-tight text-slate-900 sm:text-xl">Smart <span class="text-emerald-600">RW</span></span>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="#umkm" class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50/70 px-3 py-2.5 text-sm font-bold text-emerald-700 transition hover:border-emerald-600 hover:bg-emerald-100 sm:px-4">
                    <i class="fa-solid fa-store"></i>
                    <span class="hidden sm:inline">UMKM Warga</span>
                    <span class="sm:hidden">UMKM</span>
                </a>
                <a href="#agenda" class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-white px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:border-emerald-600 hover:bg-emerald-50 md:inline-flex">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Agenda &amp; Kas</span>
                </a>
                <a href="{{ route('docs.index') }}" class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-2.5 text-sm font-bold text-emerald-700 transition hover:border-emerald-600 hover:bg-emerald-50 sm:px-4">
                    <i class="fa-solid fa-book-open"></i>
                    <span class="hidden sm:inline">Panduan</span>
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-200 transition hover:bg-emerald-700 sm:px-5">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:border-emerald-600 hover:bg-emerald-50 sm:px-5">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Masuk</span>
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    @if(isset($highlightPengumuman) && $highlightPengumuman)
        @php
            $stripTone = match ($highlightPengumuman->prioritas) {
                'darurat' => 'border-red-200 bg-red-600 text-white',
                'penting' => 'border-amber-200 bg-amber-500 text-slate-950',
                default => 'border-emerald-200 bg-emerald-700 text-white',
            };
        @endphp
        <div class="border-b {{ $stripTone }}">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-2.5 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-black/15 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>{{ $highlightPengumuman->prioritas_label }}</span>
                    </span>
                    <span class="shrink-0 font-bold opacity-90">{{ $highlightPengumuman->scope_label }}:</span>
                    <p class="truncate font-extrabold">{{ $highlightPengumuman->judul }}</p>
                </div>
                <a href="#pengumuman" class="inline-flex shrink-0 items-center gap-1.5 font-black underline underline-offset-4 hover:opacity-80">
                    <span>Baca Pengumuman</span>
                    <i class="fa-solid fa-arrow-down text-[10px]"></i>
                </a>
            </div>
        </div>
    @endif

    <main>
        <section class="relative overflow-hidden bg-linear-to-b/srgb from-emerald-50/80 via-white to-white">
            <div class="pointer-events-none absolute -left-40 top-20 h-80 w-80 rounded-full bg-emerald-200/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-40 bottom-0 h-96 w-96 rounded-full bg-teal-100/50 blur-3xl"></div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-28">
                <div data-reveal>
                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-white px-4 py-2 text-xs font-extrabold tracking-wide text-emerald-700 shadow-xs sm:text-sm">
                        🏘️ Sistem Informasi RW Modern
                    </span>

                    <h1 class="mt-6 text-4xl font-black leading-tight tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                        Kelola Wilayah RW Lebih <span class="text-emerald-600">Cerdas, Transparan,</span> dan Terhubung.
                    </h1>

                    <p class="mt-6 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                        Smart RW menghadirkan satu platform digital untuk seluruh kebutuhan administrasi, keuangan, dan layanan warga — dari iuran bulanan hingga posyandu, semua dalam genggaman.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-emerald-200 transition hover:-translate-y-0.5 hover:bg-emerald-700">
                            Daftar Sekarang
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="#fitur" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-300 bg-white px-6 py-3.5 text-sm font-extrabold text-emerald-700 transition hover:border-emerald-600 hover:bg-emerald-50">
                            Pelajari Fitur
                            <i class="fa-solid fa-arrow-down"></i>
                        </a>
                    </div>

                    <dl class="mt-10 grid grid-cols-3 divide-x divide-emerald-100 border-t border-emerald-100 pt-6">
                        <div class="pr-3">
                            <dt class="text-xl font-black text-emerald-700 sm:text-2xl">{{ $rwStats['rt_aktif'] }} RT</dt>
                            <dd class="mt-1 text-xs font-semibold leading-5 text-slate-500 sm:text-sm">{{ $rwStats['warga_terverifikasi'] }} Warga Terverifikasi</dd>
                        </div>
                        <a href="#umkm" class="group px-3 transition hover:opacity-80 sm:px-5">
                            <dt class="text-xl font-black text-emerald-700 sm:text-2xl">{{ $rwStats['umkm_aktif'] }} UMKM</dt>
                            <dd class="mt-1 text-xs font-semibold leading-5 text-slate-500 sm:text-sm">{{ $rwStats['produk_aktif'] }} Produk Siap Order</dd>
                        </a>
                        <a href="#agenda" class="group pl-3 transition hover:opacity-80 sm:pl-5">
                            <dt class="text-xl font-black text-emerald-700 sm:text-2xl">{{ $rwStats['agenda_mendatang'] }} Agenda</dt>
                            <dd class="mt-1 text-xs font-semibold leading-5 text-slate-500 sm:text-sm">Kegiatan RT &amp; RW Mendatang</dd>
                        </a>
                    </dl>
                </div>

                <div data-reveal style="--reveal-delay:120ms" class="relative mx-auto w-full max-w-lg lg:max-w-none">
                    <div class="absolute inset-8 rounded-full bg-emerald-200/50 blur-3xl"></div>
                    <div class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-emerald-50 p-4 shadow-2xl shadow-emerald-100/80 sm:p-6">
                        <img src="{{ asset('images/landing/hero.png') }}" alt="Ilustrasi layanan digital Smart RW" class="mx-auto w-full max-w-md object-contain" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                        <div hidden class="flex aspect-square w-full items-center justify-center rounded-3xl bg-emerald-100 text-7xl text-emerald-500" role="img" aria-label="Placeholder ilustrasi Smart RW">
                            <i class="fa-solid fa-city"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="fitur" class="scroll-mt-20 bg-slate-50/70 py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-emerald-600">Layanan Terintegrasi</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Semua yang Dibutuhkan Warga RW</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 sm:text-lg">Dari administrasi surat hingga bank sampah, semua terintegrasi dalam satu sistem.</p>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-6">
                    <article data-reveal="left" style="--reveal-delay:0ms" class="reveal reveal-left group rounded-3xl border border-emerald-100 bg-white p-5 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 lg:col-span-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/surat.png') }}" alt="Ilustrasi surat menyurat digital" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-envelope-open-text"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Surat Menyurat Digital</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Ajukan surat keterangan domisili, pengantar nikah, SKCK, dan lainnya secara online. Disetujui RT dan RW, langsung bisa dicetak.</p>
                    </article>

                    <article data-reveal="scale" style="--reveal-delay:80ms" class="reveal reveal-scale group rounded-3xl border border-emerald-100 bg-white p-5 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 lg:col-span-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/iuran.png') }}" alt="Ilustrasi iuran dan tagihan warga" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Iuran &amp; Tagihan Transparan</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Bayar iuran bulanan, iuran insidental, dan pantau tagihan secara real-time. Upload bukti bayar, bendahara verifikasi otomatis.</p>
                    </article>

                    <article data-reveal="right" style="--reveal-delay:160ms" class="reveal reveal-right group rounded-3xl border border-emerald-100 bg-white p-5 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 lg:col-span-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/bank-sampah.png') }}" alt="Ilustrasi layanan bank sampah digital" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-recycle"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Bank Sampah Digital</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Setor sampah, kumpulkan saldo, tukar dengan hadiah atau uang tunai. Jadwal setor Rabu sore dan Sabtu pagi.</p>
                    </article>

                    <article data-reveal="left" style="--reveal-delay:200ms" class="reveal reveal-left group rounded-3xl border border-emerald-100 bg-white p-5 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 md:col-span-1 lg:col-span-2 lg:col-start-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/umkm.png') }}" alt="Ilustrasi direktori UMKM warga" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-store"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Direktori UMKM Warga</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Daftarkan usaha Anda dan temukan produk dari tetangga sekitar. Belanja lokal, dukung ekonomi warga RW sendiri.</p>
                        <a href="#umkm" class="mt-4 inline-flex items-center gap-1.5 text-sm font-black text-emerald-700 transition hover:text-emerald-900">
                            <span>Lihat &amp; Order Tanpa Login</span>
                            <i class="fa-solid fa-arrow-down text-xs"></i>
                        </a>
                    </article>

                    <article data-reveal="right" style="--reveal-delay:280ms" class="reveal reveal-right group rounded-3xl border border-emerald-100 bg-white p-5 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 md:col-span-1 lg:col-span-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/kegiatan.png') }}" alt="Ilustrasi kegiatan RT dan RW" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-calendar-days"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Kegiatan RT &amp; RW</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Pantau kegiatan lingkungan, konfirmasi kehadiran, dan dokumentasi kegiatan RT maupun RW dalam satu tempat.</p>
                        <a href="#agenda" class="mt-4 inline-flex items-center gap-1.5 text-sm font-black text-emerald-700 transition hover:text-emerald-900">
                            <span>Lihat Agenda Mendatang</span>
                            <i class="fa-solid fa-arrow-down text-xs"></i>
                        </a>
                    </article>
                </div>
            </div>
        </section>

        @php
            $hariLabels = \App\Models\JamOperasionalUmkm::HARI;
            $hariIni = \App\Models\JamOperasionalUmkm::getHariIni();

            $umkmDetailsMap = $publicUmkms->mapWithKeys(function ($umkm) use ($hariLabels, $hariIni) {
                $jadwalByHari = $umkm->jamOperasional->keyBy('hari');
                $jadwal = collect($hariLabels)->map(function ($label, $nomorHari) use ($jadwalByHari, $hariIni) {
                    $row = $jadwalByHari->get($nomorHari);

                    return [
                        'hari' => $nomorHari,
                        'label' => $label,
                        'jam_teks' => $row?->jam_teks ?? 'Belum diatur',
                        'is_tutup' => (bool) ($row?->is_tutup ?? false),
                        'is_today' => $hariIni === $nomorHari,
                    ];
                })->values()->all();

                $produkList = $umkm->produkUmkms->map(fn ($produk) => [
                    'id' => $produk->id,
                    'nama_produk' => $produk->nama_produk,
                    'deskripsi' => $produk->deskripsi,
                    'harga' => $produk->harga,
                    'satuan_harga' => $produk->satuan_harga,
                    'harga_formatted' => $produk->harga_formatted,
                    'foto_url' => $produk->foto ? route('public.produk-umkm.foto', $produk) : null,
                    'whatsapp_order_url' => $produk->whatsapp_order_url,
                ])->values()->all();

                return [
                    $umkm->id => [
                        'id' => $umkm->id,
                        'nama_usaha' => $umkm->nama_usaha,
                        'kategori' => $umkm->kategori,
                        'kategori_label' => $umkm->kategori_label,
                        'deskripsi' => $umkm->deskripsi,
                        'alamat_lokasi' => $umkm->alamat_lokasi ?: 'Hubungi penjual untuk lokasi detail',
                        'rt_name' => $umkm->rt?->name ?? 'Lingkungan RW',
                        'is_buka' => $umkm->isBukaSekarang(),
                        'jam_ringkas' => $umkm->jam_operasional_ringkas,
                        'foto_url' => $umkm->foto_usaha ? route('public.umkm.foto', $umkm) : null,
                        'whatsapp_phone' => $umkm->whatsapp_phone,
                        'whatsapp_chat_url' => $umkm->generateWhatsappChatUrl(),
                        'jadwal' => $jadwal,
                        'produk' => $produkList,
                    ],
                ];
            })->all();

            $produkMeta = $publicProduks->map(fn ($produk) => [
                'id' => $produk->id,
                'kategori' => $produk->umkm?->kategori ?? '',
                'search' => trim($produk->nama_produk.' '.$produk->deskripsi.' '.($produk->umkm?->nama_usaha ?? '').' '.($produk->umkm?->kategori_label ?? '')),
            ])->values()->all();

            $usahaMeta = $publicUmkms->map(fn ($umkm) => [
                'id' => $umkm->id,
                'kategori' => $umkm->kategori,
                'search' => trim($umkm->nama_usaha.' '.$umkm->deskripsi.' '.$umkm->kategori_label.' '.($umkm->rt?->name ?? '')),
            ])->values()->all();

            $defaultUmkmTab = $publicProduks->isNotEmpty() ? 'produk' : 'usaha';
        @endphp

        <section
            id="umkm"
            class="scroll-mt-20 bg-linear-to-b/srgb from-white via-emerald-50/40 to-white py-20 sm:py-24"
            x-data="publicUmkmCatalog({
                defaultTab: @js($defaultUmkmTab),
                produkMeta: @js($produkMeta),
                usahaMeta: @js($usahaMeta),
                umkmDetails: @js($umkmDetailsMap),
            })"
            @keydown.escape.window="closeModal()"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-xs font-black uppercase tracking-wider text-emerald-700">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span>Belanja Lokal Tanpa Perlu Login</span>
                    </span>
                    <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Etalase &amp; Direktori UMKM Warga</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 sm:text-lg">
                        Cek usaha tetangga sekitar, lihat harga produk dan jam operasional, lalu langsung order ke WhatsApp penjual tanpa harus masuk akun.
                    </p>
                </div>

                @if($publicUmkms->isEmpty())
                    <div class="mt-12 rounded-3xl border border-dashed border-emerald-200 bg-white px-6 py-14 text-center shadow-xs">
                        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-3xl text-emerald-600">
                            <i class="fa-solid fa-store"></i>
                        </span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Etalase UMKM Segera Hadir</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">
                            Belum ada usaha warga yang dipublikasikan. Punya usaha makanan, jasa, atau warung di lingkungan RW?
                        </p>
                        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                            @auth
                                <a href="{{ route('umkm.create') }}" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-md shadow-emerald-200 transition hover:bg-emerald-700">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Daftarkan Usaha Anda</span>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-md shadow-emerald-200 transition hover:bg-emerald-700">
                                    <i class="fa-solid fa-right-to-bracket"></i>
                                    <span>Masuk untuk Daftarkan UMKM</span>
                                </a>
                            @endauth
                        </div>
                    </div>
                @else
                    {{-- Control Panel: Search, Tabs, Category Filter --}}
                    <div class="mt-10 space-y-4 rounded-3xl border border-emerald-100 bg-white p-4 shadow-xs sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    @click="activeTab = 'produk'"
                                    :class="activeTab === 'produk' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                    class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black transition"
                                >
                                    <i class="fa-solid fa-bag-shopping"></i>
                                    <span>Etalase Produk ({{ $publicProduks->count() }})</span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeTab = 'usaha'"
                                    :class="activeTab === 'usaha' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                    class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black transition"
                                >
                                    <i class="fa-solid fa-store"></i>
                                    <span>Daftar Usaha ({{ $publicUmkms->count() }})</span>
                                </button>
                            </div>

                            <div class="relative w-full lg:max-w-sm">
                                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                                <input
                                    type="text"
                                    x-model="search"
                                    placeholder="Cari produk, jasa, atau nama toko..."
                                    aria-label="Cari produk atau usaha UMKM"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-10 text-sm font-semibold text-slate-900 transition focus:border-emerald-600 focus:bg-white focus:outline-hidden"
                                >
                                <button
                                    type="button"
                                    x-show="search.length > 0"
                                    x-cloak
                                    @click="search = ''"
                                    aria-label="Hapus pencarian"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1 text-slate-400 hover:text-slate-700"
                                >
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 overflow-x-auto border-t border-slate-100 pt-4 pb-1">
                            <span class="shrink-0 text-xs font-extrabold uppercase tracking-wider text-slate-400">Kategori:</span>
                            <button
                                type="button"
                                @click="selectedCategory = ''"
                                :class="selectedCategory === '' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="shrink-0 rounded-xl px-3.5 py-1.5 text-xs font-bold transition"
                            >
                                Semua
                            </button>
                            @foreach($umkmCategories as $catKey => $catLabel)
                                <button
                                    type="button"
                                    @click="selectedCategory = '{{ $catKey }}'"
                                    :class="selectedCategory === '{{ $catKey }}' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="shrink-0 rounded-xl px-3.5 py-1.5 text-xs font-bold transition"
                                >
                                    {{ $catLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tab 1: Etalase Produk --}}
                    <div x-show="activeTab === 'produk'" class="mt-8">
                        @if($publicProduks->isEmpty())
                            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center">
                                <i class="fa-solid fa-bag-shopping text-4xl text-emerald-300"></i>
                                <p class="mt-3 text-base font-bold text-slate-800">Belum ada produk satuan yang ditambahkan.</p>
                                <p class="mt-1 text-sm text-slate-500">Anda tetap bisa melihat daftar usaha aktif dan menghubungi pemilik langsung lewat tab Daftar Usaha.</p>
                                <button type="button" @click="activeTab = 'usaha'" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-black text-white hover:bg-emerald-700">
                                    <i class="fa-solid fa-store"></i>
                                    <span>Lihat Daftar Usaha</span>
                                </button>
                            </div>
                        @else
                            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($publicProduks as $idx => $produk)
                                    @php
                                        $umkm = $produk->umkm;
                                        $isBuka = $umkm->isBukaSekarang();
                                        $searchString = trim($produk->nama_produk.' '.$produk->deskripsi.' '.$umkm->nama_usaha.' '.$umkm->kategori_label);
                                    @endphp
                                    <article
                                        x-show="shouldShowProduk({{ $idx }}, @js($umkm->kategori), @js($searchString))"
                                        class="group flex flex-col justify-between overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-xs transition duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-emerald-100/70"
                                    >
                                        <div>
                                            <div class="relative h-48 w-full overflow-hidden bg-slate-100">
                                                @if($produk->foto)
                                                    <img src="{{ route('public.produk-umkm.foto', $produk) }}" alt="{{ $produk->nama_produk }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                                @elseif($umkm->foto_usaha)
                                                    <img src="{{ route('public.umkm.foto', $umkm) }}" alt="{{ $umkm->nama_usaha }}" class="h-full w-full object-cover opacity-90 transition duration-500 group-hover:scale-105">
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center bg-emerald-50 text-emerald-300">
                                                        <i class="fa-solid fa-bag-shopping text-5xl"></i>
                                                    </div>
                                                @endif

                                                <span class="absolute left-3 top-3 rounded-xl bg-slate-900/75 px-3 py-1 text-[11px] font-black text-white backdrop-blur-xs">
                                                    {{ $umkm->kategori_label }}
                                                </span>
                                                <span class="absolute right-3 top-3 rounded-full px-3 py-1 text-[11px] font-black {{ $isBuka ? 'bg-emerald-600 text-white' : 'bg-slate-800/80 text-slate-200' }} backdrop-blur-xs">
                                                    {{ $isBuka ? 'Buka' : 'Tutup' }}
                                                </span>
                                            </div>

                                            <div class="space-y-3 p-5">
                                                <div>
                                                    <h3 class="line-clamp-1 text-lg font-black text-slate-900">{{ $produk->nama_produk }}</h3>
                                                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-600">
                                                        {{ $produk->deskripsi ?: 'Produk warga siap pesan langsung melalui WhatsApp.' }}
                                                    </p>
                                                </div>

                                                <div class="rounded-2xl bg-emerald-50/80 px-3.5 py-2.5">
                                                    <span class="text-[11px] font-bold text-emerald-800/80">Harga</span>
                                                    <p class="text-lg font-black text-emerald-700">{{ $produk->harga_formatted }}</p>
                                                </div>

                                                <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                                                    <span class="truncate font-bold text-slate-700">
                                                        <i class="fa-solid fa-store mr-1 text-emerald-600"></i>{{ $umkm->nama_usaha }}
                                                    </span>
                                                    <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
                                                        {{ $umkm->rt?->name ?? 'RW' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-2 border-t border-slate-100 p-5 pt-4">
                                            <a
                                                href="{{ $produk->whatsapp_order_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-xs font-black text-white shadow-sm shadow-emerald-200 transition hover:bg-emerald-700"
                                            >
                                                <i class="fa-brands fa-whatsapp text-base"></i>
                                                <span>Order via WhatsApp</span>
                                            </a>
                                            <button
                                                type="button"
                                                @click="openUmkm({{ $umkm->id }})"
                                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                <i class="fa-regular fa-clock text-emerald-600"></i>
                                                <span>Cek Toko &amp; Jam Buka</span>
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div x-show="filteredProdukCount === 0" x-cloak class="mt-6 rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center">
                                <p class="text-sm font-bold text-slate-700">Produk yang Anda cari belum ditemukan.</p>
                                <button type="button" @click="resetFilters()" class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-700 hover:bg-emerald-100">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    <span>Reset Filter</span>
                                </button>
                            </div>

                            @if($publicProduks->count() > 6)
                                <div x-show="!showAllProduk && search.trim() === '' && selectedCategory === ''" class="mt-8 text-center">
                                    <button
                                        type="button"
                                        @click="showAllProduk = true"
                                        class="inline-flex items-center gap-2 rounded-2xl border border-emerald-300 bg-white px-6 py-3 text-sm font-extrabold text-emerald-700 transition hover:border-emerald-600 hover:bg-emerald-50"
                                    >
                                        <span>Tampilkan Semua Produk ({{ $publicProduks->count() }})</span>
                                        <i class="fa-solid fa-chevron-down text-xs"></i>
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Tab 2: Daftar Usaha / Toko --}}
                    <div x-show="activeTab === 'usaha'" x-cloak class="mt-8">
                        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($publicUmkms as $idx => $umkm)
                                @php
                                    $isBuka = $umkm->isBukaSekarang();
                                    $searchString = trim($umkm->nama_usaha.' '.$umkm->deskripsi.' '.$umkm->kategori_label.' '.($umkm->rt?->name ?? ''));
                                @endphp
                                <article
                                    x-show="shouldShowUsaha({{ $idx }}, @js($umkm->kategori), @js($searchString))"
                                    class="group flex flex-col justify-between overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-xs transition duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-emerald-100/70"
                                >
                                    <div>
                                        <div class="relative h-48 w-full overflow-hidden bg-slate-100">
                                            @if($umkm->foto_usaha)
                                                <img src="{{ route('public.umkm.foto', $umkm) }}" alt="Foto {{ $umkm->nama_usaha }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center bg-emerald-50 text-emerald-300">
                                                    <i class="fa-solid fa-store text-5xl"></i>
                                                </div>
                                            @endif
                                            <span class="absolute left-3 top-3 rounded-xl bg-slate-900/75 px-3 py-1 text-[11px] font-black text-white backdrop-blur-xs">
                                                {{ $umkm->kategori_label }}
                                            </span>
                                            <span class="absolute right-3 top-3 rounded-full px-3 py-1 text-[11px] font-black {{ $isBuka ? 'bg-emerald-600 text-white' : 'bg-slate-800/80 text-slate-200' }} backdrop-blur-xs">
                                                {{ $isBuka ? 'Buka Sekarang' : 'Tutup' }}
                                            </span>
                                        </div>

                                        <div class="p-5">
                                            <div class="flex items-start justify-between gap-2">
                                                <h3 class="text-lg font-black text-slate-900">{{ $umkm->nama_usaha }}</h3>
                                                <span class="shrink-0 rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                                                    {{ $umkm->rt?->name ?? 'RW' }}
                                                </span>
                                            </div>
                                            <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $umkm->deskripsi }}</p>

                                            <div class="mt-4 flex items-center justify-between gap-2 rounded-2xl bg-slate-50 px-3.5 py-2.5 text-xs">
                                                <span class="truncate font-semibold text-slate-600">
                                                    <i class="fa-regular fa-clock mr-1 text-emerald-600"></i>{{ $umkm->jam_operasional_ringkas }}
                                                </span>
                                                <span class="shrink-0 font-black text-emerald-700">
                                                    {{ $umkm->produk_umkms_count }} produk
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-2 border-t border-slate-100 p-5 pt-4 sm:grid-cols-2">
                                        <a
                                            href="{{ $umkm->generateWhatsappChatUrl() }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-2xl bg-emerald-600 px-3.5 py-2.5 text-xs font-black text-white shadow-sm shadow-emerald-200 transition hover:bg-emerald-700"
                                        >
                                            <i class="fa-brands fa-whatsapp text-base"></i>
                                            <span>Order / Chat WA</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUmkm({{ $umkm->id }})"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            <span>Cek Toko &amp; Menu</span>
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div x-show="filteredUsahaCount === 0" x-cloak class="mt-6 rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center">
                            <p class="text-sm font-bold text-slate-700">Usaha UMKM yang Anda cari belum ditemukan.</p>
                            <button type="button" @click="resetFilters()" class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-700 hover:bg-emerald-100">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Reset Filter</span>
                            </button>
                        </div>

                        @if($publicUmkms->count() > 6)
                            <div x-show="!showAllUsaha && search.trim() === '' && selectedCategory === ''" class="mt-8 text-center">
                                <button
                                    type="button"
                                    @click="showAllUsaha = true"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-emerald-300 bg-white px-6 py-3 text-sm font-extrabold text-emerald-700 transition hover:border-emerald-600 hover:bg-emerald-50"
                                >
                                    <span>Tampilkan Semua Usaha ({{ $publicUmkms->count() }})</span>
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Modal Detail UMKM & Order WhatsApp --}}
            <div
                x-cloak
                x-show="modalOpen"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs"
                role="dialog"
                aria-modal="true"
                aria-labelledby="public-umkm-modal-title"
                @click.self="closeModal()"
            >
                <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                    <template x-if="selectedUmkm">
                        <div>
                            <div class="relative h-52 w-full bg-slate-100 sm:h-64">
                                <template x-if="selectedUmkm.foto_url">
                                    <img :src="selectedUmkm.foto_url" :alt="selectedUmkm.nama_usaha" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!selectedUmkm.foto_url">
                                    <div class="flex h-full w-full items-center justify-center bg-emerald-50 text-emerald-300">
                                        <i class="fa-solid fa-store text-6xl"></i>
                                    </div>
                                </template>

                                <button
                                    type="button"
                                    @click="closeModal()"
                                    aria-label="Tutup detail UMKM"
                                    class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-md transition hover:bg-white"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>

                                <span
                                    :class="selectedUmkm.is_buka ? 'bg-emerald-600 text-white' : 'bg-slate-900/80 text-slate-200'"
                                    class="absolute bottom-4 left-4 rounded-full px-3.5 py-1 text-xs font-black backdrop-blur-xs"
                                    x-text="selectedUmkm.is_buka ? 'Buka Sekarang' : 'Sedang Tutup'"
                                ></span>
                            </div>

                            <div class="space-y-6 p-6 sm:p-8">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700" x-text="selectedUmkm.kategori_label"></span>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600" x-text="selectedUmkm.rt_name"></span>
                                        </div>
                                        <h3 id="public-umkm-modal-title" class="mt-3 text-2xl font-black text-slate-950" x-text="selectedUmkm.nama_usaha"></h3>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">
                                            <i class="fa-solid fa-location-dot mr-1 text-emerald-600"></i>
                                            <span x-text="selectedUmkm.alamat_lokasi"></span>
                                        </p>
                                    </div>

                                    <a
                                        :href="selectedUmkm.whatsapp_chat_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-xs font-black text-white shadow-md shadow-emerald-200 transition hover:bg-emerald-700"
                                    >
                                        <i class="fa-brands fa-whatsapp text-base"></i>
                                        <span>Chat Penjual</span>
                                    </a>
                                </div>

                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600" x-text="selectedUmkm.deskripsi"></p>

                                {{-- Produk & Form Order Cepat --}}
                                <div class="space-y-3 border-t border-slate-100 pt-5">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <h4 class="text-base font-black text-slate-900">Daftar Produk &amp; Order WhatsApp</h4>
                                        <span class="text-xs font-semibold text-slate-500">Atur jumlah lalu klik Order WA</span>
                                    </div>

                                    <template x-if="(selectedUmkm.produk || []).length > 0">
                                        <div class="space-y-3">
                                            <input
                                                type="text"
                                                x-model="orderNote"
                                                placeholder="Catatan tambahan (opsional, misal: tanpa sambal / antar ke blok B)"
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-medium text-slate-800 focus:border-emerald-600 focus:bg-white focus:outline-hidden"
                                            >

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <template x-for="produk in selectedUmkm.produk" :key="produk.id">
                                                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                                        <div>
                                                            <div class="flex items-start gap-3">
                                                                <template x-if="produk.foto_url">
                                                                    <img :src="produk.foto_url" :alt="produk.nama_produk" class="h-14 w-14 shrink-0 rounded-xl object-cover">
                                                                </template>
                                                                <div class="min-w-0 flex-1">
                                                                    <h5 class="truncate text-sm font-black text-slate-900" x-text="produk.nama_produk"></h5>
                                                                    <p class="mt-0.5 text-xs font-black text-emerald-700" x-text="produk.harga_formatted"></p>
                                                                    <p class="mt-1 line-clamp-2 text-[11px] text-slate-500" x-text="produk.deskripsi || 'Siap dipesan via WhatsApp'"></p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="mt-4 flex items-center justify-between gap-2 border-t border-slate-200/70 pt-3">
                                                            <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white">
                                                                <button type="button" @click="decrementQty(produk.id)" class="px-2.5 py-1 text-xs font-black text-slate-600 hover:text-emerald-700" aria-label="Kurangi jumlah">-</button>
                                                                <span class="min-w-7 text-center text-xs font-black text-slate-900" x-text="getQty(produk.id)"></span>
                                                                <button type="button" @click="incrementQty(produk.id)" class="px-2.5 py-1 text-xs font-black text-slate-600 hover:text-emerald-700" aria-label="Tambah jumlah">+</button>
                                                            </div>

                                                            <a
                                                                :href="buildProductWhatsappUrl(produk)"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white transition hover:bg-emerald-700"
                                                            >
                                                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                                                <span>Order WA</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="(selectedUmkm.produk || []).length === 0">
                                        <div class="rounded-2xl border border-dashed border-slate-200 p-5 text-center text-xs text-slate-500">
                                            Pemilik belum menambahkan daftar produk satuan. Klik <strong>Chat Penjual</strong> di atas untuk memesan atau menanyakan menu langsung.
                                        </div>
                                    </template>
                                </div>

                                {{-- Jam Operasional --}}
                                <div class="border-t border-slate-100 pt-5">
                                    <h4 class="text-sm font-black text-slate-900">Jam Operasional</h4>
                                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        <template x-for="item in selectedUmkm.jadwal" :key="item.hari">
                                            <div
                                                :class="item.is_today ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-slate-50 text-slate-700'"
                                                class="rounded-xl border px-3 py-2 text-xs"
                                            >
                                                <p class="font-black" x-text="item.label"></p>
                                                <p class="mt-0.5 text-[11px]" x-text="item.jam_teks"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <section id="agenda" class="scroll-mt-20 bg-slate-50/70 py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-4 py-1.5 text-xs font-black uppercase tracking-wider text-emerald-700">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Data Nyata &amp; Transparansi Publik</span>
                    </span>
                    <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Agenda Kegiatan &amp; Ringkasan RW</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 sm:text-lg">
                        Pantau agenda kerja bakti, posyandu, dan transparansi ringkas kas serta program lingkungan RW dalam data agregat yang aman bagi privasi warga.
                    </p>
                </div>

                {{-- 4 Kartu Transparansi Kas & Program Lingkungan --}}
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <article data-reveal="scale" style="--reveal-delay:0ms" class="reveal reveal-scale rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                                <i class="fa-solid fa-wallet text-lg"></i>
                            </span>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">Agregat Kas</span>
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Saldo Kas Lingkungan</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-slate-950">Rp {{ number_format($rwStats['saldo_kas'], 0, ',', '.') }}</p>
                        <p class="mt-2 text-xs text-slate-500">Akumulasi kas RT &amp; RW aktif</p>
                    </article>

                    <article data-reveal="scale" style="--reveal-delay:80ms" class="reveal reveal-scale rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-100 text-teal-600">
                                <i class="fa-solid fa-arrow-trend-up text-lg"></i>
                            </span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $rwStats['periode_kas'] }}</span>
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Pemasukan Bulan Ini</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-emerald-700">+Rp {{ number_format($rwStats['kas_masuk_bulan_ini'], 0, ',', '.') }}</p>
                        <p class="mt-2 text-xs text-slate-500">Pengeluaran: Rp {{ number_format($rwStats['kas_keluar_bulan_ini'], 0, ',', '.') }}</p>
                    </article>

                    <article data-reveal="scale" style="--reveal-delay:160ms" class="reveal reveal-scale rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-lime-100 text-lime-700">
                                <i class="fa-solid fa-recycle text-lg"></i>
                            </span>
                            <span class="rounded-full bg-lime-50 px-2.5 py-1 text-[11px] font-bold text-lime-700">Bank Sampah</span>
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Sampah Terkelola</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-slate-950">{{ number_format($rwStats['bank_sampah_kg'], 1, ',', '.') }} kg</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $rwStats['bank_sampah_setoran'] }} setoran terverifikasi</p>
                    </article>

                    <article data-reveal="scale" style="--reveal-delay:240ms" class="reveal reveal-scale rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                                <i class="fa-solid fa-heart-pulse text-lg"></i>
                            </span>
                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700">Posyandu &amp; Warga</span>
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Balita Terpantau</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-slate-950">{{ $rwStats['balita_posyandu'] }} Balita</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $rwStats['warga_terverifikasi'] }} warga terverifikasi di {{ $rwStats['rt_aktif'] }} RT</p>
                    </article>
                </div>

                {{-- Papan Pengumuman Publik RT/RW --}}
                <div id="pengumuman" class="mt-12 scroll-mt-24 rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs sm:p-8">
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black text-slate-950 sm:text-2xl">Papan Pengumuman Warga</h3>
                                @if(($publicPengumumans ?? collect())->isNotEmpty())
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">{{ $publicPengumumans->count() }} Terbaru</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Pengumuman resmi, himbauan darurat, jadwal fogging, dan kabar lingkungan dari pengurus RT/RW.</p>
                        </div>

                        @auth
                            <a href="{{ route('pengumuman.index') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm shadow-emerald-200 transition hover:bg-emerald-700">
                                <i class="fa-solid fa-bullhorn"></i>
                                <span>Semua Pengumuman</span>
                            </a>
                        @endauth
                    </div>

                    @if(($publicPengumumans ?? collect())->isEmpty())
                        <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-10 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl text-amber-500 shadow-2xs">
                                <i class="fa-solid fa-bullhorn"></i>
                            </span>
                            <p class="mt-3 text-sm font-black text-slate-800">Belum Ada Pengumuman Publik</p>
                            <p class="mt-1 text-xs text-slate-500">Pengumuman terbaru dari pengurus RT dan RW akan langsung tampil di sini.</p>
                        </div>
                    @else
                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            @foreach($publicPengumumans as $item)
                                @php
                                    $itemCardTone = match ($item->prioritas) {
                                        'darurat' => 'border-red-200 bg-red-50/50',
                                        'penting' => 'border-amber-200 bg-amber-50/50',
                                        default => 'border-slate-200/90 bg-slate-50/50',
                                    };
                                @endphp
                                <article x-data="{ expanded: false }" class="flex flex-col justify-between rounded-2xl border {{ $itemCardTone }} p-5 transition hover:bg-white hover:shadow-md">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-black {{ $item->prioritas_badge_class }}">
                                                {{ $item->prioritas_label }}
                                            </span>
                                            <span class="rounded-full bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                                                {{ $item->kategori_label }}
                                            </span>
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">
                                                {{ $item->scope_label }}
                                            </span>
                                            @if($item->is_pinned)
                                                <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-black text-indigo-700">
                                                    <i class="fa-solid fa-thumbtack mr-1"></i>Disematkan
                                                </span>
                                            @endif
                                        </div>

                                        <h4 class="mt-3 text-lg font-black text-slate-900">{{ $item->judul }}</h4>
                                        <p :class="expanded ? '' : 'line-clamp-3'" class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $item->isi }}</p>
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200/70 pt-3 text-xs text-slate-500">
                                        <span>
                                            <i class="fa-regular fa-clock mr-1 text-emerald-600"></i>{{ $item->created_at->translatedFormat('d M Y, H:i') }} WIB
                                        </span>
                                        @if(mb_strlen($item->isi) > 150)
                                            <button type="button" @click="expanded = !expanded" class="font-black text-emerald-700 hover:underline" x-text="expanded ? 'Ringkas' : 'Baca Selengkapnya'"></button>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Daftar Agenda Kegiatan RT/RW Mendatang --}}
                <div class="mt-8 rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs sm:p-8">
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black text-slate-950 sm:text-2xl">Agenda Kegiatan Mendatang</h3>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">{{ $rwStats['agenda_mendatang'] }} Terjadwal</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Jadwal kegiatan tingkat RT dan RW yang terbuka untuk partisipasi warga.</p>
                        </div>

                        @auth
                            <a href="{{ route('kegiatan.index') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm shadow-emerald-200 transition hover:bg-emerald-700">
                                <i class="fa-solid fa-calendar-check"></i>
                                <span>Buka Menu Kegiatan</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-black text-emerald-700 transition hover:bg-emerald-100">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <span>Masuk untuk Konfirmasi Hadir</span>
                            </a>
                        @endauth
                    </div>

                    @if($agendaKegiatans->isEmpty())
                        <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-12 text-center">
                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl text-emerald-500 shadow-2xs">
                                <i class="fa-regular fa-calendar-xmark"></i>
                            </span>
                            <p class="mt-4 text-base font-black text-slate-800">Belum Ada Agenda Kegiatan Mendatang</p>
                            <p class="mt-1 text-sm text-slate-500">Agenda kerja bakti, rapat warga, atau posyandu terbaru akan tampil di sini setelah dijadwalkan pengurus.</p>
                        </div>
                    @else
                        <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($agendaKegiatans as $kegiatan)
                                <article class="flex flex-col justify-between rounded-2xl border border-slate-200/90 bg-slate-50/50 p-5 transition hover:-translate-y-1 hover:border-emerald-200 hover:bg-white hover:shadow-md">
                                    <div>
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-black text-white">
                                                <i class="fa-regular fa-calendar"></i>
                                                <span>{{ $kegiatan->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                            </span>
                                            <span class="rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $kegiatan->status_color }}">
                                                {{ $kegiatan->status_label }}
                                            </span>
                                        </div>

                                        <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px] font-bold">
                                            <span class="rounded-lg bg-white px-2.5 py-1 text-slate-700 ring-1 ring-slate-200">
                                                {{ $kegiatan->rt ? $kegiatan->rt->name : 'Lingkup RW' }}
                                            </span>
                                            <span class="text-slate-500">
                                                <i class="fa-regular fa-clock mr-1 text-emerald-600"></i>{{ $kegiatan->tanggal_mulai->format('H:i') }} WIB
                                            </span>
                                        </div>

                                        <h4 class="mt-3 text-lg font-black text-slate-900">{{ $kegiatan->nama }}</h4>
                                        <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-600">
                                            {{ $kegiatan->deskripsi ?: 'Kegiatan lingkungan bersama warga RT/RW.' }}
                                        </p>
                                    </div>

                                    <div class="mt-4 space-y-1.5 border-t border-slate-200/70 pt-3 text-xs text-slate-600">
                                        <p class="truncate font-semibold">
                                            <i class="fa-solid fa-location-dot mr-1.5 text-emerald-600"></i>
                                            {{ $kegiatan->lokasi ?: 'Lokasi menyusul' }}
                                        </p>
                                        <p class="font-bold text-emerald-700">
                                            <i class="fa-solid fa-users mr-1.5"></i>
                                            {{ $kegiatan->hadirs_count }} warga konfirmasi hadir
                                        </p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="bg-white py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-emerald-600">Mudah &amp; Aman</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Mulai dalam 3 Langkah</h2>
                </div>

                <div class="relative mt-14 grid gap-10 md:grid-cols-3 md:gap-8">
                    <div class="absolute left-[16.67%] right-[16.67%] top-10 hidden h-px bg-emerald-200 md:block"></div>

                    <article data-reveal="scale" style="--reveal-delay:0ms" class="reveal reveal-scale relative text-center">
                        <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-3xl border-8 border-white bg-emerald-100 text-2xl text-emerald-600 shadow-xs">
                            <i class="fa-solid fa-user-plus"></i>
                            <span class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-black text-white">1</span>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-slate-900">Daftar Akun</h3>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-7 text-slate-600">Isi data dasar dan pilih domisili rumah Anda di wilayah RT.</p>
                    </article>

                    <article data-reveal="scale" style="--reveal-delay:120ms" class="reveal reveal-scale relative text-center">
                        <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-3xl border-8 border-white bg-emerald-100 text-2xl text-emerald-600 shadow-xs">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-black text-white">2</span>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-slate-900">Verifikasi RT</h3>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-7 text-slate-600">Pengurus RT memverifikasi identitas Anda — bisa tatap muka atau upload dokumen KTP/KK.</p>
                    </article>

                    <article data-reveal="scale" style="--reveal-delay:240ms" class="reveal reveal-scale relative text-center">
                        <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-3xl border-8 border-white bg-emerald-100 text-2xl text-emerald-600 shadow-xs">
                            <i class="fa-solid fa-rocket"></i>
                            <span class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-black text-white">3</span>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-slate-900">Akses Semua Fitur</h3>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-7 text-slate-600">Setelah terverifikasi, nikmati seluruh layanan Smart RW kapan saja dan di mana saja.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="keamanan" class="scroll-mt-20 bg-slate-50/70 py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-emerald-600">Keamanan Terjamin</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Data Warga Terlindungi Sepenuhnya</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 sm:text-lg">Smart RW dirancang dengan standar keamanan modern untuk melindungi setiap data dan privasi warga.</p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article data-reveal="left" style="--reveal-delay:0ms" class="reveal reveal-left rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-xl text-emerald-600">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                        <h3 class="mt-4 text-lg font-black text-slate-900">Isolasi Data per RT</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Setiap RT hanya dapat mengakses data warganya sendiri. Pengurus RT 01 tidak bisa melihat data RT 02 — privasi antar lingkungan terjaga sepenuhnya.</p>
                    </article>

                    <article data-reveal="scale" style="--reveal-delay:80ms" class="reveal reveal-scale rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-xl text-emerald-600">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <h3 class="mt-4 text-lg font-black text-slate-900">Enkripsi Password</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Password warga di-hash menggunakan algoritma Bcrypt sehingga tidak dapat dibaca oleh siapa pun — termasuk administrator sistem.</p>
                    </article>

                    <article data-reveal="right" style="--reveal-delay:160ms" class="reveal reveal-right rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-xl text-emerald-600">
                            <i class="fa-solid fa-globe"></i>
                        </span>
                        <h3 class="mt-4 text-lg font-black text-slate-900">Koneksi HTTPS/SSL</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Seluruh data yang dikirim antara browser dan server dienkripsi menggunakan protokol HTTPS, mencegah penyadapan oleh pihak ketiga.</p>
                    </article>

                    <article data-reveal="left" style="--reveal-delay:200ms" class="reveal reveal-left rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-xl text-emerald-600">
                            <i class="fa-solid fa-user-shield"></i>
                        </span>
                        <h3 class="mt-4 text-lg font-black text-slate-900">Proteksi CSRF & XSS</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Setiap form dilindungi token CSRF dan semua input di-sanitasi untuk mencegah serangan Cross-Site Scripting (XSS) dan pemalsuan permintaan.</p>
                    </article>

                    <article data-reveal="scale" style="--reveal-delay:280ms" class="reveal reveal-scale rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-xl text-emerald-600">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        <h3 class="mt-4 text-lg font-black text-slate-900">Otorisasi Berbasis Role</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Sistem role dan permission granular memastikan setiap pengguna hanya bisa mengakses fitur sesuai jabatannya — dari warga hingga superadmin.</p>
                    </article>

                    <article data-reveal="right" style="--reveal-delay:360ms" class="reveal reveal-right rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs transition duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-xl text-emerald-600">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </span>
                        <h3 class="mt-4 text-lg font-black text-slate-900">Verifikasi Identitas Warga</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Setiap pendaftar harus diverifikasi oleh pengurus RT sebelum bisa mengakses fitur — mencegah akun palsu dan penyalahgunaan data.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="px-4 pb-20 sm:px-6 sm:pb-24 lg:px-8">
            <div class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-emerald-700 px-6 py-14 text-center text-white shadow-2xl shadow-emerald-200 sm:px-12 sm:py-16">
                <div class="absolute -left-16 -top-20 h-56 w-56 rounded-full border-[32px] border-white/5"></div>
                <div class="absolute -bottom-24 -right-12 h-64 w-64 rounded-full bg-emerald-500/50"></div>
                <div data-reveal class="relative mx-auto max-w-2xl">
                    <h2 class="text-3xl font-black tracking-tight sm:text-4xl">Siap Bergabung dengan Smart RW?</h2>
                    <p class="mt-4 text-base leading-7 text-emerald-50 sm:text-lg">Warga baru dapat mendaftar dan langsung memilih domisili di wilayah RT Anda.</p>
                    <a href="{{ route('register') }}" class="mt-8 inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-7 py-3.5 text-sm font-black text-emerald-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-50">
                        Daftar Sekarang
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-emerald-100 bg-slate-50">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-8 text-center text-sm text-slate-500 sm:px-6 md:flex-row md:text-left lg:px-8">
            <p class="font-bold text-slate-700">Smart RW &copy; 2026</p>
            <p>Sistem Informasi Warga RW Modern</p>
            <a href="{{ route('login') }}" class="font-bold text-emerald-700 transition hover:text-emerald-900">Login</a>
        </div>
    </footer>

    @include('components.assistant-widget', ['bottomOffsetClass' => 'bottom-5'])
</body>
</html>
