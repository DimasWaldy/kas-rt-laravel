<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart RW adalah platform digital terpadu untuk administrasi, keuangan, dan layanan warga RW.">
    <title>Smart RW | Sistem Informasi Warga RW Modern</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    <nav class="sticky top-0 z-50 border-b border-emerald-100/80 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-20 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Smart RW - Beranda">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-200">
                    <i class="fa-solid fa-city"></i>
                </span>
                <span class="text-lg font-black tracking-tight text-slate-900 sm:text-xl">Smart <span class="text-emerald-600">RW</span></span>
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
    </nav>

    <main>
        <section class="relative overflow-hidden bg-gradient-to-b from-emerald-50/80 via-white to-white">
            <div class="pointer-events-none absolute -left-40 top-20 h-80 w-80 rounded-full bg-emerald-200/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-40 bottom-0 h-96 w-96 rounded-full bg-teal-100/50 blur-3xl"></div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-28">
                <div>
                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-white px-4 py-2 text-xs font-extrabold tracking-wide text-emerald-700 shadow-sm sm:text-sm">
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
                            <dt class="text-xl font-black text-emerald-700 sm:text-2xl">9+</dt>
                            <dd class="mt-1 text-xs font-semibold leading-5 text-slate-500 sm:text-sm">Fitur Aktif</dd>
                        </div>
                        <div class="px-3 sm:px-5">
                            <dt class="text-xl font-black text-emerald-700 sm:text-2xl">Multi RT</dt>
                            <dd class="mt-1 text-xs font-semibold leading-5 text-slate-500 sm:text-sm">Satu Wilayah RW</dd>
                        </div>
                        <div class="pl-3 sm:pl-5">
                            <dt class="text-base font-black leading-7 text-emerald-700 sm:text-xl">Posyandu &amp; Bank Sampah</dt>
                            <dd class="mt-1 text-xs font-semibold leading-5 text-slate-500 sm:text-sm">Layanan Terpadu</dd>
                        </div>
                    </dl>
                </div>

                <div class="relative mx-auto w-full max-w-lg lg:max-w-none">
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
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-emerald-600">Layanan Terintegrasi</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Semua yang Dibutuhkan Warga RW</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 sm:text-lg">Dari administrasi surat hingga bank sampah, semua terintegrasi dalam satu sistem.</p>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-6">
                    <article class="group rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 lg:col-span-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/surat.png') }}" alt="Ilustrasi surat menyurat digital" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-envelope-open-text"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Surat Menyurat Digital</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Ajukan surat keterangan domisili, pengantar nikah, SKCK, dan lainnya secara online. Disetujui RT dan RW, langsung bisa dicetak.</p>
                    </article>

                    <article class="group rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 lg:col-span-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/iuran.png') }}" alt="Ilustrasi iuran dan tagihan warga" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Iuran &amp; Tagihan Transparan</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Bayar iuran bulanan, iuran insidental, dan pantau tagihan secara real-time. Upload bukti bayar, bendahara verifikasi otomatis.</p>
                    </article>

                    <article class="group rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 lg:col-span-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/bank-sampah.png') }}" alt="Ilustrasi layanan bank sampah digital" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-recycle"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Bank Sampah Digital</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Setor sampah, kumpulkan saldo, tukar dengan hadiah atau uang tunai. Jadwal setor Rabu sore dan Sabtu pagi.</p>
                    </article>

                    <article class="group rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 md:col-span-1 lg:col-span-2 lg:col-start-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/umkm.png') }}" alt="Ilustrasi direktori UMKM warga" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-store"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Direktori UMKM Warga</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Daftarkan usaha Anda dan temukan produk dari tetangga sekitar. Belanja lokal, dukung ekonomi warga RW sendiri.</p>
                    </article>

                    <article class="group rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-100/70 md:col-span-1 lg:col-span-2">
                        <div class="overflow-hidden rounded-2xl bg-emerald-50">
                            <img src="{{ asset('images/landing/kegiatan.png') }}" alt="Ilustrasi kegiatan RT dan RW" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                            <div hidden class="flex h-40 items-center justify-center text-4xl text-emerald-300"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <span class="mt-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"><i class="fa-solid fa-calendar-days"></i></span>
                        <h3 class="mt-4 text-xl font-black text-slate-900">Kegiatan RT &amp; RW</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Pantau kegiatan lingkungan, konfirmasi kehadiran, dan dokumentasi kegiatan RT maupun RW dalam satu tempat.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-emerald-600">Mudah &amp; Aman</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Mulai dalam 3 Langkah</h2>
                </div>

                <div class="relative mt-14 grid gap-10 md:grid-cols-3 md:gap-8">
                    <div class="absolute left-[16.67%] right-[16.67%] top-10 hidden h-px bg-emerald-200 md:block"></div>

                    <article class="relative text-center">
                        <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-3xl border-8 border-white bg-emerald-100 text-2xl text-emerald-600 shadow-sm">
                            <i class="fa-solid fa-user-plus"></i>
                            <span class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-black text-white">1</span>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-slate-900">Daftar Akun</h3>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-7 text-slate-600">Isi data dasar dan pilih domisili rumah Anda di wilayah RT.</p>
                    </article>

                    <article class="relative text-center">
                        <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-3xl border-8 border-white bg-emerald-100 text-2xl text-emerald-600 shadow-sm">
                            <i class="fa-solid fa-shield-check"></i>
                            <span class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-black text-white">2</span>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-slate-900">Verifikasi RT</h3>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-7 text-slate-600">Pengurus RT memverifikasi identitas Anda — bisa tatap muka atau upload dokumen KTP/KK.</p>
                    </article>

                    <article class="relative text-center">
                        <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-3xl border-8 border-white bg-emerald-100 text-2xl text-emerald-600 shadow-sm">
                            <i class="fa-solid fa-rocket"></i>
                            <span class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-black text-white">3</span>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-slate-900">Akses Semua Fitur</h3>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-7 text-slate-600">Setelah terverifikasi, nikmati seluruh layanan Smart RW kapan saja dan di mana saja.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="px-4 pb-20 sm:px-6 sm:pb-24 lg:px-8">
            <div class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-emerald-700 px-6 py-14 text-center text-white shadow-2xl shadow-emerald-200 sm:px-12 sm:py-16">
                <div class="absolute -left-16 -top-20 h-56 w-56 rounded-full border-[32px] border-white/5"></div>
                <div class="absolute -bottom-24 -right-12 h-64 w-64 rounded-full bg-emerald-500/50"></div>
                <div class="relative mx-auto max-w-2xl">
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
</body>
</html>
