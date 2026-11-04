@php
    $sections = [
        'Warga' => [
            ['slug' => 'mulai', 'title' => 'Mulai di Smart RW', 'icon' => 'fa-compass'],
            ['slug' => 'daftar', 'title' => 'Daftar dan verifikasi', 'icon' => 'fa-user-plus'],
            ['slug' => 'dashboard-warga', 'title' => 'Mengenal dashboard', 'icon' => 'fa-gauge-high'],
            ['slug' => 'tagihan', 'title' => 'Cek dan bayar iuran', 'icon' => 'fa-receipt'],
            ['slug' => 'layanan-warga', 'title' => 'Surat dan pengaduan', 'icon' => 'fa-headset'],
        ],
        'Pengurus & Superadmin' => [
            ['slug' => 'verifikasi', 'title' => 'Verifikasi warga', 'icon' => 'fa-user-check'],
            ['slug' => 'kelola-warga', 'title' => 'Kelola warga dan rumah', 'icon' => 'fa-users-gear'],
            ['slug' => 'tagihan-admin', 'title' => 'Kelola tagihan', 'icon' => 'fa-file-invoice-dollar'],
            ['slug' => 'laporan', 'title' => 'Laporan dan transparansi', 'icon' => 'fa-chart-line'],
        ],
    ];

    $articles = [
        'mulai' => ['eyebrow' => 'Panduan Warga', 'title' => 'Mulai menggunakan Smart RW', 'intro' => 'Satu tempat untuk administrasi warga, iuran, layanan surat, dan komunikasi lingkungan.', 'steps' => [
            ['title' => 'Buat akun', 'text' => 'Daftar memakai nama, email, dan kata sandi. Gunakan data sesuai identitas agar pengurus mudah memeriksa pengajuan.'],
            ['title' => 'Lengkapi data domisili', 'text' => 'Setelah masuk, buka dashboard lalu isi rumah dan data keluarga. Pengajuan akan diperiksa pengurus sebelum akses layanan dibuka.'],
            ['title' => 'Gunakan layanan', 'text' => 'Saat akun aktif, menu iuran, surat, pengaduan, dan layanan lain tersedia sesuai hak akses Anda.'],
        ]],
        'daftar' => ['eyebrow' => 'Panduan Warga', 'title' => 'Daftar dan ikuti verifikasi', 'intro' => 'Proses pendaftaran dibuat bertahap supaya data warga dan rumah tetap akurat.', 'steps' => [
            ['title' => 'Daftar dari halaman depan', 'text' => 'Pilih Daftar Sekarang, isi data dasar, lalu buat kata sandi yang kuat. Jangan membagikan kata sandi kepada siapa pun.'],
            ['title' => 'Masuk ke dashboard terbatas', 'text' => 'Sebelum disetujui, Anda tetap bisa masuk untuk melihat status dan melengkapi pengajuan. Fitur layanan belum dapat dibuka.'],
            ['title' => 'Tunggu pemeriksaan pengurus', 'text' => 'Pengurus memeriksa identitas, alamat, rumah, dan data keluarga. Jika perlu perbaikan, ikuti alasan yang tampil di dashboard.'],
        ]],
        'dashboard-warga' => ['eyebrow' => 'Panduan Warga', 'title' => 'Mengenal dashboard warga', 'intro' => 'Dashboard menampilkan ringkasan yang paling penting tanpa membuat Anda berpindah-pindah halaman.', 'steps' => [
            ['title' => 'Status akun', 'text' => 'Lihat status verifikasi dan notifikasi perbaikan di bagian atas halaman. Selesaikan pengajuan sebelum memakai layanan.'],
            ['title' => 'Ringkasan iuran', 'text' => 'Kartu iuran menunjukkan tagihan aktif, status pembayaran, dan riwayat transaksi Anda.'],
            ['title' => 'Menu layanan', 'text' => 'Gunakan menu sidebar untuk surat, pengaduan, kegiatan, dan layanan lain yang tersedia di wilayah Anda.'],
        ]],
        'tagihan' => ['eyebrow' => 'Panduan Warga', 'title' => 'Cek dan bayar iuran', 'intro' => 'Pantau kewajiban iuran dan kirim bukti pembayaran dengan alur yang jelas.', 'steps' => [
            ['title' => 'Buka menu Iuran', 'text' => 'Pilih periode tagihan untuk melihat nominal, jatuh tempo, dan status pembayaran.'],
            ['title' => 'Kirim pembayaran', 'text' => 'Pilih metode yang diarahkan pengurus, lalu unggah bukti pembayaran yang terbaca dan sesuai nominal.'],
            ['title' => 'Pantau verifikasi', 'text' => 'Status berubah setelah pengurus memeriksa bukti. Simpan riwayat sebagai referensi pribadi.'],
        ]],
        'layanan-warga' => ['eyebrow' => 'Panduan Warga', 'title' => 'Ajukan surat dan pengaduan', 'intro' => 'Sampaikan kebutuhan administrasi dan masalah lingkungan melalui formulir digital.', 'steps' => [
            ['title' => 'Ajukan surat', 'text' => 'Pilih jenis surat, isi tujuan, dan lengkapi data yang diminta. Periksa kembali sebelum mengirim.'],
            ['title' => 'Kirim pengaduan', 'text' => 'Tulis lokasi, kronologi singkat, dan lampirkan foto bila membantu pengurus memahami masalah.'],
            ['title' => 'Lihat perkembangan', 'text' => 'Buka riwayat untuk melihat status pengajuan dan tanggapan pengurus.'],
        ]],
        'verifikasi' => ['eyebrow' => 'Panduan Pengurus', 'title' => 'Verifikasi warga baru', 'intro' => 'Satu antrean verifikasi membantu pengurus memeriksa pendaftaran dan domisili tanpa duplikasi kerja.', 'steps' => [
            ['title' => 'Buka Verifikasi Warga', 'text' => 'Periksa antrean calon warga dan pengajuan domisili dari dashboard admin.'],
            ['title' => 'Cocokkan data', 'text' => 'Periksa identitas, rumah, hubungan kepala keluarga, dan dokumen pendukung sebelum mengambil keputusan.'],
            ['title' => 'Setujui atau kembalikan', 'text' => 'Setujui data yang benar. Jika ada kesalahan, kembalikan dengan alasan jelas agar warga dapat memperbaiki pengajuan.'],
        ]],
        'kelola-warga' => ['eyebrow' => 'Panduan Pengurus', 'title' => 'Kelola warga dan rumah', 'intro' => 'Jaga hubungan warga, rumah, RT, dan KK tetap konsisten agar akses data aman.', 'steps' => [
            ['title' => 'Cari warga', 'text' => 'Gunakan pencarian dan filter RT untuk menemukan data yang perlu diperbarui.'],
            ['title' => 'Perbarui dengan bukti', 'text' => 'Edit data hanya berdasarkan informasi yang sudah dikonfirmasi. Hindari membuat hubungan rumah atau KK ganda.'],
            ['title' => 'Periksa dampak', 'text' => 'Setelah menyimpan perubahan, pastikan status warga, tagihan, dan anggota keluarga tetap sesuai.'],
        ]],
        'tagihan-admin' => ['eyebrow' => 'Panduan Pengurus', 'title' => 'Kelola tagihan warga', 'intro' => 'Buat tagihan secara teratur dan pastikan setiap rumah memiliki penanggung jawab iuran.', 'steps' => [
            ['title' => 'Siapkan periode', 'text' => 'Pastikan data rumah aktif dan penanggung jawab iuran sudah benar sebelum membuat tagihan.'],
            ['title' => 'Generate tagihan', 'text' => 'Pilih periode dan RT sesuai kebutuhan. Tinjau hasil sebelum memberi tahu warga.'],
            ['title' => 'Verifikasi pembayaran', 'text' => 'Periksa bukti pembayaran, setujui transaksi valid, dan beri catatan bila perlu koreksi.'],
        ]],
        'laporan' => ['eyebrow' => 'Panduan Pengurus', 'title' => 'Laporan dan transparansi', 'intro' => 'Gunakan laporan untuk melihat kondisi iuran dan layanan tanpa mengubah data sumber.', 'steps' => [
            ['title' => 'Pilih rentang waktu', 'text' => 'Gunakan periode yang relevan agar ringkasan pemasukan, tunggakan, dan transaksi mudah dibaca.'],
            ['title' => 'Gunakan filter wilayah', 'text' => 'Filter RT membantu pengurus melihat kondisi wilayah tanpa mencampur data yang tidak terkait.'],
            ['title' => 'Bagikan ringkasan', 'text' => 'Gunakan hasil laporan untuk rapat dan komunikasi warga. Lindungi data pribadi yang tidak diperlukan.'],
        ]],
    ];

    $article = $articles[$activeSlug];
    $flatSections = collect($sections)->flatten(1)->values();
    $activeIndex = $flatSections->search(fn ($item) => $item['slug'] === $activeSlug);
    $previous = $activeIndex > 0 ? $flatSections[$activeIndex - 1] : null;
    $next = $activeIndex < $flatSections->count() - 1 ? $flatSections[$activeIndex + 1] : null;
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Panduan penggunaan Smart RW untuk warga dan pengurus.">
    <title>Dokumentasi Smart RW | Panduan Warga dan Pengurus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <nav class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Smart RW - Beranda">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-lg shadow-emerald-200/50"><i class="fa-solid fa-city"></i></span>
                <span class="font-black tracking-tight text-slate-900 dark:text-white">Smart <span class="text-emerald-600">RW</span> <span class="ml-1 font-semibold text-slate-400">Docs</span></span>
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ url('/') }}" class="hidden rounded-lg px-3 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 sm:inline-flex">Beranda</a>
                <button type="button" data-theme-toggle class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Ganti tema"><i class="fa-solid fa-moon"></i></button>
                @auth<a href="{{ route('dashboard') }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-700">Dashboard</a>@else<a href="{{ route('login') }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-700">Masuk</a>@endauth
            </div>
        </div>
    </nav>

    <div class="mx-auto grid max-w-7xl lg:grid-cols-[250px_minmax(0,1fr)]">
        <aside class="border-b border-slate-200 px-4 py-6 dark:border-slate-800 lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)] lg:overflow-y-auto lg:border-b-0 lg:border-r lg:px-6">
            <p class="mb-5 text-xs font-black uppercase tracking-[0.2em] text-slate-400">Dokumentasi</p>
            @foreach ($sections as $heading => $items)
                <p class="mb-2 mt-6 text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 first:mt-0">{{ $heading }}</p>
                <nav class="space-y-1" aria-label="{{ $heading }}">
                    @foreach ($items as $item)
                        <a href="#doc-{{ $item['slug'] }}" data-doc-nav="{{ $item['slug'] }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $activeSlug === $item['slug'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-xs"></i><span>{{ $item['title'] }}</span>
                        </a>
                    @endforeach
                </nav>
            @endforeach
        </aside>

        <main class="min-w-0 px-5 py-10 sm:px-10 sm:py-14 lg:px-20 lg:py-16">
            <div class="mx-auto max-w-3xl">
                @foreach ($articles as $slug => $currentArticle)
                    <article id="doc-{{ $slug }}" data-doc-section="{{ $slug }}" class="scroll-mt-24 {{ $slug === $activeSlug ? '' : 'mt-20' }}" data-reveal>
                        <div class="mb-6 flex items-center gap-2 text-sm font-bold text-emerald-600"><span>{{ $currentArticle['eyebrow'] }}</span><i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i><span class="text-slate-400">Dokumentasi</span></div>
                        <h1 class="text-4xl font-black tracking-tight text-slate-950 dark:text-white sm:text-5xl">{{ $currentArticle['title'] }}</h1>
                        <p class="mt-6 text-lg leading-8 text-slate-600 dark:text-slate-300">{{ $currentArticle['intro'] }}</p>
                        <div class="my-10 h-px bg-slate-200 dark:bg-slate-800"></div>
                        <div class="space-y-5">
                            @foreach ($currentArticle['steps'] as $index => $step)
                                <section class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-100/40 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-800 dark:hover:shadow-none sm:p-6">
                                    <div class="flex gap-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ $index + 1 }}</span><div><h2 class="text-lg font-black text-slate-900 dark:text-white">{{ $step['title'] }}</h2><p class="mt-2 leading-7 text-slate-600 dark:text-slate-300">{{ $step['text'] }}</p></div></div>
                                </section>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </main>
    </div>
    <script>
        (() => {
            const navItems = [...document.querySelectorAll('[data-doc-nav]')];
            const sections = [...document.querySelectorAll('[data-doc-section]')];
            const activeClasses = ['bg-emerald-50', 'text-emerald-700', 'dark:bg-emerald-950/60', 'dark:text-emerald-300'];
            const inactiveClasses = ['text-slate-600', 'hover:bg-slate-100', 'dark:text-slate-300', 'dark:hover:bg-slate-800'];
            const setActive = (slug, updateUrl = false) => {
                navItems.forEach((item) => {
                    const active = item.dataset.docNav === slug;
                    item.classList.toggle('bg-emerald-50', active);
                    item.classList.toggle('text-emerald-700', active);
                    item.classList.toggle('dark:bg-emerald-950/60', active);
                    item.classList.toggle('dark:text-emerald-300', active);
                    item.classList.toggle('text-slate-600', !active);
                    item.classList.toggle('hover:bg-slate-100', !active);
                    item.classList.toggle('dark:text-slate-300', !active);
                    item.classList.toggle('dark:hover:bg-slate-800', !active);
                });
                if (updateUrl && history.replaceState) history.replaceState(null, '', `{{ url('/docs') }}/${slug}`);
            };
            const observer = new IntersectionObserver((entries) => {
                const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                if (visible) setActive(visible.target.dataset.docSection, true);
            }, { rootMargin: '-18% 0px -62% 0px', threshold: [0.1, 0.3, 0.6] });
            sections.forEach((section) => observer.observe(section));
            navItems.forEach((item) => item.addEventListener('click', () => setActive(item.dataset.docNav)));
            document.getElementById('doc-{{ $activeSlug }}')?.scrollIntoView({ block: 'start' });

            const key = 'smart-rw-theme';
            const root = document.documentElement;
            const toggle = document.querySelector('[data-theme-toggle]');
            const apply = (theme) => { root.classList.toggle('dark', theme === 'dark'); if (toggle) toggle.innerHTML = theme === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>'; };
            const saved = localStorage.getItem(key) || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            apply(saved);
            toggle?.addEventListener('click', () => { const next = root.classList.contains('dark') ? 'light' : 'dark'; localStorage.setItem(key, next); apply(next); });
        })();
    </script>
</body>
</html>
