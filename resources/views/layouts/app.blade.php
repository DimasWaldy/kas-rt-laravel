<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Smart RW - Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <script>
        (() => {
            const storedTheme = localStorage.getItem('smart-rw-theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-item { transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease; }
        .active-menu { background-color: #16a34a; color: #fff; box-shadow: 0 10px 18px -12px rgb(22 163 74 / 0.65); }
        .active-menu i { color: #fff; }
        .sidebar-scroll { scrollbar-width: thin; scrollbar-color: #c7d2fe #eef2ff; }
        .sidebar-scroll::-webkit-scrollbar { width: 8px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: #eef2ff; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #a5b4fc; border-radius: 999px; border: 2px solid #eef2ff; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #818cf8; }
        .dark .active-menu { background-color: #059669; color: #fff; box-shadow: 0 10px 18px -12px rgb(16 185 129 / 0.75); }
        .dark .active-menu i { color: #fff; }
        .dark .sidebar-scroll { scrollbar-color: #4f46e5 #0f172a; }
        .dark .sidebar-scroll::-webkit-scrollbar-track { background: #0f172a; }
        .dark .sidebar-scroll::-webkit-scrollbar-thumb { background: #4f46e5; border-color: #0f172a; }
    </style>
</head>
@php
    $user = auth()->user();
    $roleLabel = match($user->role_name) {
        'super_admin' => 'Super Admin',
        'admin' => 'Operator Sistem',
        'ketua_rw' => 'Ketua RW',
        'sekretaris_rw' => 'Sekretaris RW',
        'bendahara_rw' => 'Bendahara RW',
        'petugas_bank_sampah' => 'Petugas Bank Sampah',
        'petugas_posyandu' => 'Petugas Posyandu',
        'ketua_rt' => 'Ketua RT',
        'sekretaris_rt' => 'Sekretaris RT',
        'bendahara_rt' => 'Bendahara RT',
        'bendahara' => 'Bendahara RT',
        'sekretaris' => 'Sekretaris RT',
        default => 'Warga',
    };

    $menuGroups = [
        [
            'label' => 'Menu Utama',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'active' => 'dashboard',
                    'icon' => 'fa-house',
                    'iconClass' => 'text-emerald-600',
                    'visible' => true,
                ],
                [
                    'label' => 'Admin Dashboard',
                    'route' => 'admin.dashboard',
                    'active' => 'admin/dashboard',
                    'icon' => 'fa-chart-line',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->isAdmin(),
                ],
                [
                    'label' => 'Direktori RW',
                    'route' => 'direktori-rw.index',
                    'active' => 'direktori-rw*',
                    'icon' => 'fa-address-book',
                    'iconClass' => 'text-emerald-600',
                    'permission' => 'view-direktori-rw',
                    'visible' => $user->hasPermission('view-direktori-rw'),
                ],
            ],
        ],
        [
            'label' => $user->isRwOfficial() ? 'Rekap Keuangan RW' : 'Keuangan & Kas',
            'items' => [
                [
                    'label' => 'Kas Masuk',
                    'route' => 'kas-masuk.index',
                    'active' => 'kas-masuk*',
                    'icon' => 'fa-money-bill-trend-up',
                    'iconClass' => 'text-emerald-600',
                    'visible' => ! $user->isRwOfficial() && $user->canViewFinance(),
                ],
                [
                    'label' => 'Kas Keluar',
                    'route' => 'kas-keluar.index',
                    'active' => 'kas-keluar*',
                    'icon' => 'fa-money-bill-transfer',
                    'iconClass' => 'text-green-600',
                    'visible' => ! $user->isRwOfficial() && $user->canViewFinance(),
                ],
                [
                    'label' => $user->canManageFinance() ? 'Tagihan Warga' : 'Tagihan Iuran',
                    'route' => 'tagihan.index',
                    'active' => ['tagihan', 'tagihan/*'],
                    'icon' => 'fa-receipt',
                    'iconClass' => 'text-lime-600',
                    'visible' => ! $user->isRwOfficial(),
                ],
                [
                    'label' => 'Iuran Bulanan',
                    'route' => 'iuran-bulanan.index',
                    'active' => 'admin/iuran-bulanan*',
                    'icon' => 'fa-list-check',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->canManageFinance() || in_array($user->role_name, ['ketua_rt', 'rt', 'ketua-rt'], true),
                ],
                [
                    'label' => 'Iuran Khusus',
                    'route' => 'iuran-khusus.index',
                    'active' => 'iuran-khusus*',
                    'icon' => 'fa-hand-holding-heart',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->hasPermission('manage-finance'),
                ],
                [
                    'label' => 'Verifikasi Tagihan',
                    'route' => 'tagihan.admin',
                    'active' => 'admin/tagihan*',
                    'icon' => 'fa-clipboard-list',
                    'iconClass' => 'text-green-600',
                    'visible' => $user->canManageFinance(),
                ],
                [
                    'label' => 'Laporan Kas',
                    'route' => 'laporan-kas.index',
                    'active' => 'admin/laporan-kas*',
                    'icon' => 'fa-chart-pie',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->canViewFinance(),
                ],
                [
                    'label' => 'Laporan Iuran Warga',
                    'route' => 'laporan-iuran.index',
                    'active' => 'admin/laporan-iuran*',
                    'icon' => 'fa-file-invoice-dollar',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->canViewFinance(),
                ],
            ],
        ],
        [
            'label' => 'Kewargaan & Wilayah',
            'items' => [
                [
                    'label' => 'Verifikasi Warga',
                    'route' => 'verifikasi-warga.index',
                    'active' => 'verifikasi-warga*',
                    'icon' => 'fa-user-check',
                    'iconClass' => 'text-emerald-600',
                    'permission' => 'manage-warga',
                    'visible' => $user->hasPermission('manage-warga'),
                ],
                [
                    'label' => 'Verifikasi KTP (RFID)',
                    'route' => \App\Support\RfidAccess::canManageWargaKtp($user) ? 'admin.warga.rfid.show' : 'warga.rfid.show',
                    'active' => \App\Support\RfidAccess::canManageWargaKtp($user) ? 'admin/warga/rfid*' : 'kartu-rfid*',
                    'icon' => 'fa-id-card',
                    'iconClass' => 'text-emerald-600',
                    'visible' => true,
                ],
                [
                    'label' => 'Data Warga',
                    'route' => 'admin.warga.index',
                    'active' => ['admin/warga', 'admin/warga/create', 'admin/warga/*/edit'],
                    'icon' => 'fa-users',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->canManageWarga(),
                ],
                [
                    'label' => 'Data Rumah',
                    'route' => 'admin.rumah.index',
                    'active' => 'admin/rumah*',
                    'icon' => 'fa-house-user',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->canManageWarga(),
                ],
                [
                    'label' => 'Manajemen Akun',
                    'route' => 'admin.accounts.create',
                    'active' => 'admin/accounts*',
                    'icon' => 'fa-users-gear',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->isGlobalOperator() && $user->isAktif() && $user->hasPermission('admin-only'),
                ],
            ],
        ],
        [
            'label' => 'Layanan & Fasilitas',
            'items' => [
                [
                    'label' => 'Surat Menyurat',
                    'route' => 'surat.index',
                    'active' => 'surat*',
                    'icon' => 'fa-envelope-open-text',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->canViewSurat(),
                ],
                [
                    'label' => 'Aset RT',
                    'route' => 'aset.index',
                    'active' => ['aset', 'aset/*'],
                    'icon' => 'fa-boxes-stacked',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->hasPermission('view-aset'),
                ],
                [
                    'label' => 'Peminjaman Aset RT',
                    'route' => 'peminjaman-aset.index',
                    'active' => 'peminjaman-aset*',
                    'icon' => 'fa-hand-holding',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->hasPermission('pinjam-aset'),
                ],
                [
                    'label' => 'Aset RW',
                    'route' => 'aset-rw.index',
                    'active' => ['aset-rw', 'aset-rw/*'],
                    'icon' => 'fa-building-user',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->hasPermission('view-aset-rw'),
                ],
                [
                    'label' => 'Peminjaman Aset RW',
                    'route' => 'peminjaman-aset-rw.index',
                    'active' => 'peminjaman-aset-rw*',
                    'icon' => 'fa-hand-holding-hand',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->hasPermission('pinjam-aset-rw'),
                ],
                [
                    'label' => 'Fasilitas Lingkungan',
                    'route' => 'fasilitas.index',
                    'active' => 'fasilitas*',
                    'icon' => 'fa-building-shield',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->hasPermission('view-fasilitas'),
                ],
                [
                    'label' => 'Pengaduan Fasilitas',
                    'route' => 'pengaduan-fasilitas.index',
                    'active' => 'pengaduan-fasilitas*',
                    'icon' => 'fa-triangle-exclamation',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->hasPermission('lapor-fasilitas'),
                ],
                [
                    'label' => 'Keamanan & Ronda',
                    'route' => 'keamanan.index',
                    'active' => 'keamanan*',
                    'icon' => 'fa-shield-halved',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->hasPermission('manage-fasilitas'),
                ],
                [
                    'label' => 'Pengaduan Warga',
                    'route' => 'pengaduan.index',
                    'active' => ['pengaduan', 'pengaduan/*'],
                    'icon' => 'fa-bullhorn',
                    'iconClass' => 'text-emerald-600',
                    'visible' => true,
                ],
                [
                    'label' => 'Agenda Kegiatan',
                    'route' => 'kegiatan.index',
                    'active' => 'kegiatan*',
                    'icon' => 'fa-calendar-days',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->hasPermission('view-kegiatan'),
                ],
                [
                    'label' => 'E-Voting Pemilu RT/RW',
                    'route' => 'evoting.index',
                    'active' => 'evoting*',
                    'icon' => 'fa-check-to-slot',
                    'iconClass' => 'text-purple-600',
                    'visible' => $user->hasPermission('view-voting'),
                ],
                [
                    'label' => 'Kelola Pemilihan',
                    'route' => 'admin.evoting.index',
                    'active' => 'admin/evoting*',
                    'icon' => 'fa-box-archive',
                    'iconClass' => 'text-purple-600',
                    'visible' => $user->hasPermission('manage-voting'),
                ],
                [
                    'label' => 'Rukun Kematian (Rukem)',
                    'route' => 'rukem.index',
                    'active' => ['rukem', 'rukem/*'],
                    'icon' => 'fa-hand-holding-heart',
                    'iconClass' => 'text-rose-600',
                    'visible' => $user->hasPermission('view-rukem'),
                ],
                [
                    'label' => 'Kelola Rukem',
                    'route' => 'admin.rukem.index',
                    'active' => 'admin/rukem*',
                    'icon' => 'fa-hands-holding-child',
                    'iconClass' => 'text-amber-600',
                    'visible' => $user->hasPermission('manage-rukem'),
                ],
            ],
        ],
        [
            'label' => 'Pemberdayaan & Ekonomi',
            'items' => [
                [
                    'label' => 'Koperasi Warga',
                    'route' => 'koperasi.index',
                    'active' => 'koperasi*',
                    'icon' => 'fa-wallet',
                    'iconClass' => 'text-blue-600',
                    'visible' => $user->hasPermission('view-koperasi'),
                ],
                [
                    'label' => 'Kelola Koperasi',
                    'route' => 'admin.koperasi.index',
                    'active' => 'admin/koperasi*',
                    'icon' => 'fa-vault',
                    'iconClass' => 'text-indigo-600',
                    'visible' => $user->hasPermission('manage-koperasi'),
                ],
                [
                    'label' => 'Katalog UMKM',
                    'route' => 'umkm.index',
                    'active' => ['umkm', 'umkm/*'],
                    'icon' => 'fa-store',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->hasPermission('view-umkm'),
                ],
                [
                    'label' => 'UMKM Saya',
                    'route' => 'umkm.saya',
                    'active' => 'umkm-saya*',
                    'icon' => 'fa-shop',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->hasPermission('daftar-umkm'),
                ],
                [
                    'label' => 'Bank Sampah',
                    'route' => 'bank-sampah.index',
                    'active' => 'bank-sampah*',
                    'icon' => 'fa-recycle',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->hasPermission('view-bank-sampah'),
                ],
                [
                    'label' => 'Posyandu',
                    'route' => 'posyandu.index',
                    'active' => 'posyandu*',
                    'icon' => 'fa-baby',
                    'iconClass' => 'text-rose-500',
                    'visible' => $user->hasPermission('view-posyandu'),
                ],
            ],
        ],
    ];

    $visibleMenuGroups = collect($menuGroups)
        ->map(function ($group) {
            $group['items'] = collect($group['items'])->filter(fn($item) => $item['visible'])->values()->all();
            return $group;
        })
        ->filter(fn($group) => count($group['items']) > 0)
        ->values();

    $unreadNotifications = $user->unreadNotifications;
    $visibleUnreadNotifications = $unreadNotifications->filter(function ($notification) use ($user) {
        $type = class_basename($notification->type);
        $url = $notification->data['url'] ?? '';
        $isPeminjaman = str_starts_with($type, 'Peminjaman') || str_contains($url, 'peminjaman-aset');
        $isTagihan = str_contains($type, 'Tagihan') || str_contains($url, 'tagihan') || array_key_exists('tagihan_id', $notification->data);

        if ($isPeminjaman && in_array($user->role_name, ['bendahara', 'bendahara_rt', 'bendahara_rw'], true)) {
            return false;
        }

        if ($isTagihan && ! $user->canManageFinance()) {
            return false;
        }

        return true;
    });
    $notificationItems = $visibleUnreadNotifications->take(5);
    $visibleUnreadCount = $visibleUnreadNotifications->count();
    $isRegistrationPending = in_array($user->status_akun, ['pending_verifikasi', 'ditolak'], true);
    $isOnboardingDomisili = $user->role_name === 'warga' && $user->onboarding_domisili && ! $user->rumah_id;
    $profileCompletionIssues = ($user->role_name === 'warga' && ! $isRegistrationPending && ! $isOnboardingDomisili)
        ? $user->profileCompletionIssues()
        : [];

    $quickActions = [
        [
            'group' => 'Aksi Cepat',
            'label' => 'Ganti Mode Tampilan (Terang atau Gelap)',
            'action' => 'toggleTheme',
            'icon' => 'fa-circle-half-stroke',
            'iconClass' => 'text-amber-500',
            'keywords' => 'dark light theme mode tampilan tema',
            'type' => 'action',
        ],
        [
            'group' => 'Aksi Cepat',
            'label' => 'Buka atau Lipat Menu Sidebar',
            'action' => 'toggleDesktopSidebar',
            'icon' => 'fa-table-columns',
            'iconClass' => 'text-emerald-500',
            'keywords' => 'sidebar menu toggle minimize collapse lipat',
            'type' => 'action',
        ],
        [
            'group' => 'Aksi Cepat',
            'label' => 'Buka Pengaturan Akun & Profil',
            'url' => route('profile.edit'),
            'icon' => 'fa-user-gear',
            'iconClass' => 'text-blue-500',
            'keywords' => 'profile account user password setting pengaturan profil',
            'type' => 'link',
        ],
    ];

    if ($user->canManageFinance()) {
        $quickActions[] = [
            'group' => 'Aksi Cepat',
            'label' => 'Catat Kas Masuk Baru',
            'url' => route('kas-masuk.create'),
            'icon' => 'fa-circle-plus',
            'iconClass' => 'text-emerald-600',
            'keywords' => 'tambah uang kas masuk pemasukan setor',
            'type' => 'link',
        ];
        $quickActions[] = [
            'group' => 'Aksi Cepat',
            'label' => 'Catat Kas Keluar Baru',
            'url' => route('kas-keluar.create'),
            'icon' => 'fa-circle-minus',
            'iconClass' => 'text-rose-600',
            'keywords' => 'tambah kas keluar pengeluaran bayar belanja',
            'type' => 'link',
        ];
        $quickActions[] = [
            'group' => 'Aksi Cepat',
            'label' => 'Buat Tagihan Warga Baru',
            'url' => route('tagihan.create'),
            'icon' => 'fa-file-invoice-dollar',
            'iconClass' => 'text-lime-600',
            'keywords' => 'tagihan iuran buat generate billing warga',
            'type' => 'link',
        ];
    }

    if (\App\Support\RfidAccess::canManageWargaKtp($user)) {
        $quickActions[] = [
            'group' => 'Aksi Cepat',
            'label' => 'Buka Sesi Alat Verifikasi KTP (RFID)',
            'url' => route('admin.warga.rfid.show'),
            'icon' => 'fa-id-card-clip',
            'iconClass' => 'text-emerald-600',
            'keywords' => 'rfid ktp tap alat esp32 reader sesi enroll buka',
            'type' => 'link',
        ];
    }

    if ($user->hasPermission('create-kegiatan') || $user->hasPermission('manage-kegiatan')) {
        $quickActions[] = [
            'group' => 'Aksi Cepat',
            'label' => 'Tambah Agenda Kegiatan Lingkungan',
            'url' => route('kegiatan.create'),
            'icon' => 'fa-calendar-plus',
            'iconClass' => 'text-emerald-600',
            'keywords' => 'kegiatan acara agenda rapat gotong royong jadwal',
            'type' => 'link',
        ];
    }

    $paletteItems = collect($quickActions)
        ->concat(
            collect($visibleMenuGroups)->flatMap(function ($group) {
                return collect($group['items'])->map(function ($item) use ($group) {
                    return [
                        'group' => $group['label'],
                        'label' => $item['label'],
                        'url' => route($item['route']),
                        'icon' => $item['icon'],
                        'iconClass' => $item['iconClass'] ?? 'text-emerald-600',
                        'keywords' => $item['label'] . ' ' . $group['label'],
                        'type' => 'link',
                    ];
                });
            })
        )
        ->values()
        ->all();
@endphp
<body class="bg-fintech font-sans text-slate-900 transition-colors duration-300 dark:bg-slate-900 dark:text-slate-100" x-data="appShell()" x-on:keydown.escape.window="mobileMenuOpen = false" @toggle-sidebar.window="toggleDesktopSidebar()" @toggle-theme.window="toggleTheme()">

    <div x-cloak x-show="mobileMenuOpen" class="fixed inset-0 z-40 bg-slate-900/35 lg:hidden" x-transition.opacity x-on:click="mobileMenuOpen = false"></div>
    <div x-cloak x-show="navigating" x-transition.opacity class="fixed left-1/2 top-4 z-[9999] -translate-x-1/2 rounded-full bg-emerald-600 px-4 py-2 text-xs font-black text-white shadow-2xl shadow-emerald-900/20">
        Membuka halaman...
    </div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[min(20rem,calc(100vw-2rem))] lg:w-72 flex-col border-r border-slate-100 bg-white text-slate-700 shadow-xl shadow-slate-900/10 transition-transform duration-300 ease-in-out dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
        :class="{
            'translate-x-0': mobileMenuOpen,
            '-translate-x-full': !mobileMenuOpen,
            'lg:translate-x-0': !desktopSidebarCollapsed,
            'lg:-translate-x-full': desktopSidebarCollapsed
        }"
        aria-label="Navigasi utama"
    >
        <div class="flex items-center justify-between border-b border-slate-100 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 lg:p-6">
            <a href="{{ route('dashboard') }}" class="group flex min-w-0 items-center gap-3">
                <img src="{{ asset('images/logo.svg') }}" alt="Logo Smart RW" class="h-10 w-10 shrink-0 rounded-xl shadow-xs shadow-emerald-500/30 transition-transform duration-200 group-hover:scale-105">
                <span class="truncate text-xl font-extrabold tracking-wider text-slate-900 dark:text-slate-100">Smart RW</span>
            </a>
            <button type="button" class="btn-tactile flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 hover:bg-emerald-100 hover:text-emerald-950 active:scale-92 transition lg:hidden" x-on:click="mobileMenuOpen = false" aria-label="Tutup menu">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
<nav class="sidebar-scroll flex-1 space-y-6 overflow-y-auto p-4">
            @foreach($visibleMenuGroups as $group)
                <div>
                    <p class="mb-3 px-2 text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-700/70">{{ $group['label'] }}</p>
                    <ul class="space-y-1">
                        @foreach($group['items'] as $item)
                            @php
                                $isActive = collect((array) $item['active'])
                                    ->contains(fn($pattern) => request()->is($pattern));
                                $isLocked = $isRegistrationPending && $item['route'] !== 'dashboard';
                            @endphp
                            <li>
                                @if($isLocked)
                                    <span
                                        class="sidebar-item flex min-h-12 touch-manipulation cursor-not-allowed items-center gap-3 rounded-xl p-3 text-sm font-semibold text-slate-400 dark:text-slate-500"
                                        title="Terkunci sampai pendaftaran disetujui"
                                    >
                                        <i class="fa-solid {{ $item['icon'] }} w-5 shrink-0 text-center {{ $item['iconClass'] }} opacity-50"></i>
                                        <span class="min-w-0 truncate font-medium">{{ $item['label'] }}</span>
                                        <i class="fa-solid fa-lock ml-auto text-xs text-slate-300"></i>
                                    </span>
                                @else
                                    <a
                                        href="{{ route($item['route'], $item['params'] ?? []) }}"
                                        x-on:click="goToMenu($event)"
                                        class="sidebar-item flex min-h-12 touch-manipulation items-center gap-3 rounded-xl p-3 text-sm font-semibold hover:bg-emerald-100 hover:text-emerald-950 {{ $isActive ? 'active-menu' : '' }}"
                                    >
                                        <i class="fa-solid {{ $item['icon'] }} w-5 shrink-0 text-center {{ $isActive ? 'text-white' : $item['iconClass'] }}"></i>
                                        <span class="min-w-0 truncate font-medium">{{ $item['label'] }}</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-emerald-100 bg-white/70 p-4 dark:border-slate-800 dark:bg-slate-900/70">
            <a href="{{ route('profile.edit') }}" class="group mb-3 block rounded-2xl border {{ request()->is('profile*') ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/60 dark:border-emerald-500' : 'border-emerald-100 bg-white dark:border-slate-800 dark:bg-slate-900/60' }} p-3 shadow-xs transition hover:border-emerald-300 hover:bg-emerald-50/50 dark:hover:bg-slate-800/80" title="Klik untuk edit profil">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        @if($user->foto_profil)
                            <img src="{{ $user->foto_url }}" alt="{{ $user->name }}" class="h-10 w-10 shrink-0 rounded-full object-cover border border-emerald-300 shadow-xs dark:border-emerald-600">
                        @else
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700 transition group-hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-900 transition group-hover:text-emerald-800 dark:text-slate-100 dark:group-hover:text-emerald-300">{{ $user->name }}</p>
                            <p class="truncate text-[10px] capitalize text-emerald-700 dark:text-emerald-400">{{ $roleLabel }}</p>
                        </div>
                    </div>
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-400 transition group-hover:bg-emerald-100 group-hover:text-emerald-700 dark:bg-slate-800 dark:text-slate-400 dark:group-hover:bg-slate-700 dark:group-hover:text-emerald-300">
                        <i class="fa-solid fa-gear text-xs"></i>
                    </div>
                </div>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100 dark:border-slate-700 dark:bg-slate-800 dark:text-emerald-400 dark:hover:bg-slate-700">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div
        class="min-h-screen transition-[padding] duration-300 ease-in-out"
        :class="{ 'lg:pl-72': !desktopSidebarCollapsed, 'lg:pl-0': desktopSidebarCollapsed }"
    >
        <header class="sticky top-0 z-30 border-b bg-white/95 px-4 py-3 shadow-xs backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/95 lg:hidden">
            <div class="flex items-center justify-between gap-3">
                <button type="button" class="btn-tactile flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30 transition hover:bg-emerald-700 active:scale-95" x-on:click="toggleMobileMenu()" aria-label="Buka menu navigasi">
                    <div class="lottie-sidebar-icon h-6 w-6 pointer-events-none text-white" x-data="sidebarLottie(() => mobileMenuOpen)"></div>
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">Smart RW</p>
                    <h1 class="truncate text-base font-bold text-slate-800">@yield('title', 'Dashboard')</h1>
                    <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-500">
                        <span x-text="formattedDate"></span> · <span x-text="formattedTime"></span> WIB
                    </p>
                </div>

                <button type="button" @click="$dispatch('open-command-palette')" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" aria-label="Buka Pencarian Menu (Ctrl + K)">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </button>

                <button type="button" x-on:click="toggleTheme()" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-amber-300 dark:hover:bg-slate-700" aria-label="Ganti mode terang gelap">
                    <i class="fa-solid" :class="theme === 'dark' ? 'fa-sun' : 'fa-moon'"></i>
                </button>

                <div class="relative" x-data="{ open: false }">
                    <button type="button" x-on:click="open = !open" class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700" aria-label="Notifikasi">
                        <i class="fa-solid fa-bell"></i>
                        @if($visibleUnreadCount)
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-white bg-red-500 px-1 text-[10px] font-bold text-white">
                                {{ $visibleUnreadCount }}
                            </span>
                        @endif
                    </button>

                    <div
                        x-cloak
                        x-show="open"
                        x-transition:enter="transition ease-out duration-160"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-120"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        x-on:click.outside="open = false"
                        class="absolute right-0 top-12 z-50 w-80 origin-top-right overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                            <p class="text-sm font-black text-slate-800 dark:text-slate-100">Notifikasi</p>
                            @if($visibleUnreadCount)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button class="text-xs font-bold text-emerald-700 hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-200">Tandai semua dibaca</button>
                                </form>
                            @endif
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @forelse($notificationItems as $notification)
                                @php
                                    $type = class_basename($notification->type);
                                    [$notifIcon, $notifColor] = match ($type) {
                                        'PeminjamanDiajukan' => ['fa-hand-holding', 'bg-green-50 text-green-700'],
                                        'PeminjamanDisetujui' => ['fa-circle-check', 'bg-emerald-50 text-emerald-700'],
                                        'PeminjamanDitolak' => ['fa-circle-xmark', 'bg-red-50 text-red-700'],
                                        default => ['fa-money-bill', 'bg-blue-50 text-blue-700'],
                                    };
                                    $notifUrl = $notification->data['url'] ?? null;
                                @endphp
                                <div class="flex gap-3 border-b border-slate-100 px-4 py-3 last:border-0 dark:border-slate-700">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $notifColor }}"><i class="fa-solid {{ $notifIcon }}"></i></span>
                                    <div class="min-w-0 flex-1">
                                        @if($notifUrl)
                                            <a href="{{ $notifUrl }}" class="block text-sm font-bold text-slate-800 hover:text-emerald-700 dark:text-slate-100 dark:hover:text-emerald-300">{{ $notification->data['pesan'] ?? 'Notifikasi baru' }}</a>
                                        @else
                                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $notification->data['pesan'] ?? 'Notifikasi baru' }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="mt-2">
                                            @csrf
                                            <button class="text-xs font-bold text-slate-500 hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-300">Tandai dibaca</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada notifikasi baru.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <header class="hidden h-20 items-center justify-between border-b bg-white px-8 shadow-xs dark:border-slate-800 dark:bg-slate-900 lg:flex">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    class="btn-tactile flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50 text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-emerald-700 dark:hover:bg-slate-700 dark:hover:text-emerald-300 shadow-2xs active:scale-95"
                    x-on:click="toggleDesktopSidebar()"
                    :title="desktopSidebarCollapsed ? 'Buka Sidebar' : 'Tutup Sidebar'"
                    aria-label="Buka/Tutup Sidebar"
                >
                    <div class="lottie-sidebar-icon h-5 w-5 pointer-events-none" x-data="sidebarLottie(() => desktopSidebarCollapsed)"></div>
                </button>
                <h3 class="truncate text-lg font-bold capitalize text-slate-800 dark:text-slate-100">
                    @yield('title', 'Dashboard')
                </h3>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="$dispatch('open-command-palette')"
                    class="btn-tactile hidden md:flex items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-500 shadow-2xs transition hover:border-emerald-300 hover:bg-emerald-50/60 hover:text-emerald-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-emerald-700 dark:hover:bg-slate-700 dark:hover:text-emerald-300 active:scale-95"
                    title="Navigasi Cepat Pengurus (Ctrl + K)"
                    aria-label="Cari menu atau aksi"
                >
                    <i class="fa-solid fa-magnifying-glass text-slate-400 dark:text-slate-400 text-xs"></i>
                    <span>Cari menu / aksi...</span>
                    <kbd class="inline-flex items-center gap-0.5 rounded border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-600 shadow-2xs dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300">Ctrl K</kbd>
                </button>

                <div class="hidden items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-emerald-900 shadow-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 xl:flex">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-xs dark:bg-slate-900 dark:text-emerald-300">
                        <i class="fa-solid fa-calendar-days"></i>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-xs font-bold" x-text="formattedDate"></span>
                        <span class="block text-sm font-black" x-text="formattedTime + ' WIB'"></span>
                    </span>
                </div>

                <button type="button" x-on:click="toggleTheme()" class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-amber-300 dark:hover:bg-slate-700" aria-label="Ganti mode terang gelap">
                    <i class="fa-solid" :class="theme === 'dark' ? 'fa-sun' : 'fa-moon'"></i>
                </button>

                <div class="relative" x-data="{ open: false }">
                    <button type="button" x-on:click="open = !open" class="relative flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 transition hover:bg-slate-100" aria-label="Notifikasi">
                        <i class="fa-solid fa-bell"></i>
                        @if($visibleUnreadCount)
                            <span class="absolute right-0 top-0 inline-flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-red-500 text-[10px] font-bold text-white">
                                {{ $visibleUnreadCount }}
                            </span>
                        @endif
                    </button>

                    <div
                        x-cloak
                        x-show="open"
                        x-transition:enter="transition ease-out duration-160"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-120"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        x-on:click.outside="open = false"
                        class="absolute right-0 top-14 z-50 w-96 origin-top-right overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                            <p class="text-sm font-black text-slate-800 dark:text-slate-100">Notifikasi</p>
                            @if($visibleUnreadCount)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button class="text-xs font-bold text-emerald-700 hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-200">Tandai semua dibaca</button>
                                </form>
                            @endif
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @forelse($notificationItems as $notification)
                                @php
                                    $type = class_basename($notification->type);
                                    [$notifIcon, $notifColor] = match ($type) {
                                        'PeminjamanDiajukan' => ['fa-hand-holding', 'bg-green-50 text-green-700'],
                                        'PeminjamanDisetujui' => ['fa-circle-check', 'bg-emerald-50 text-emerald-700'],
                                        'PeminjamanDitolak' => ['fa-circle-xmark', 'bg-red-50 text-red-700'],
                                        default => ['fa-money-bill', 'bg-blue-50 text-blue-700'],
                                    };
                                    $notifUrl = $notification->data['url'] ?? null;
                                @endphp
                                <div class="flex gap-3 border-b border-slate-100 px-4 py-3 last:border-0 dark:border-slate-700">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $notifColor }}"><i class="fa-solid {{ $notifIcon }}"></i></span>
                                    <div class="min-w-0 flex-1">
                                        @if($notifUrl)
                                            <a href="{{ $notifUrl }}" class="block text-sm font-bold text-slate-800 hover:text-emerald-700 dark:text-slate-100 dark:hover:text-emerald-300">{{ $notification->data['pesan'] ?? 'Notifikasi baru' }}</a>
                                        @else
                                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $notification->data['pesan'] ?? 'Notifikasi baru' }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="mt-2">
                                            @csrf
                                            <button class="text-xs font-bold text-slate-500 hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-300">Tandai dibaca</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada notifikasi baru.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-full bg-emerald-50 px-3 py-1.5 dark:bg-slate-800">
                    <span class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ $roleLabel }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex items-center gap-2 text-sm font-bold text-red-500 transition-colors hover:text-red-700">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </header>

        @if($user->canManageFinance() && $visibleUnreadCount)
            <div class="border-b border-amber-100 bg-amber-50 px-4 py-3 text-amber-800 dark:border-amber-900/40 dark:bg-amber-500/10 dark:text-amber-200 md:px-8">
                <p class="text-sm font-semibold">
                    Ada {{ $visibleUnreadCount }} notifikasi baru.
                    <a href="{{ route('tagihan.admin') }}" class="underline">Klik untuk verifikasi</a>.
                </p>
            </div>
        @endif

        @if(count($profileCompletionIssues) > 0)
            <div class="border-b border-amber-200 bg-amber-50 px-4 py-4 text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100 md:px-8">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex gap-3">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-200">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </span>
                        <div>
                            <p class="text-sm font-black">Profil warga belum lengkap</p>
                            <p class="mt-1 text-sm font-semibold">
                                Lengkapi data diri dengan benar agar akses layanan warga, tagihan, surat, dan koperasi tidak bermasalah.
                                Untuk penetapan RT, rumah, nomor KK, dan kepala keluarga, hubungi pengurus.
                            </p>
                            <p class="mt-1 text-xs font-bold text-amber-700 dark:text-amber-200">
                                Belum lengkap: {{ implode(', ', $profileCompletionIssues) }}.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white shadow-xs hover:bg-amber-700">
                        <i class="fa-solid fa-user-pen"></i>
                        Lengkapi Profil
                    </a>
                </div>
            </div>
        @endif

        @if(session('success') || session('error'))
            <div id="notif" class="toast-entrance fixed left-4 right-4 top-20 z-50 mx-auto flex max-w-sm items-center justify-between gap-3 rounded-2xl p-4 text-white shadow-2xl {{ session('success') ? 'bg-emerald-600 shadow-emerald-950/20' : 'bg-red-600 shadow-red-950/20' }} md:left-auto md:right-5 md:top-5 border border-white/15 backdrop-blur-xs">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/20 text-xs">
                        <i class="fa-solid {{ session('success') ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                    </span>
                    <div class="text-sm font-bold truncate">{{ session('success') ?? session('error') }}</div>
                </div>
                <button type="button" onclick="const n=document.getElementById('notif');if(n){n.style.transition='opacity 160ms cubic-bezier(0.23,1,0.32,1), transform 160ms cubic-bezier(0.23,1,0.32,1)';n.style.opacity='0';n.style.transform='translateY(-12px) scale(0.96)';setTimeout(()=>n.remove(),160);}" class="btn-tactile -mr-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-white/80 hover:bg-white/20 hover:text-white" aria-label="Tutup notifikasi">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        @endif

        <main class="min-w-0 p-4 pb-24 md:p-6 lg:p-8 md:pb-8">
            @yield('content')
        </main>
    </div>

    {{-- Mobile Bottom Navigation Bar (Khusus Layanan Mobile / Android) --}}
    @auth
    <nav aria-label="Menu Utama Mobile" class="fixed bottom-0 left-0 right-0 z-40 block border-t border-slate-200/90 bg-white/95 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/95 md:hidden">
        <div class="mx-auto flex h-16 max-w-md items-center justify-around px-2">
            <a href="{{ route('dashboard') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center transition active:scale-95 {{ request()->routeIs('dashboard') ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                <i class="fa-solid fa-house text-base"></i>
                <span class="text-[10px] tracking-tight">Beranda</span>
            </a>
            @if(Route::has('tagihan.index') && ! auth()->user()->isRwOfficial())
            <a href="{{ route('tagihan.index') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center transition active:scale-95 {{ request()->routeIs('tagihan*') ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                <i class="fa-solid fa-wallet text-base"></i>
                <span class="text-[10px] tracking-tight">Iuran</span>
            </a>
            @endif
            @if(Route::has('surat.index') && auth()->user()->canViewSurat())
            <a href="{{ route('surat.index') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center transition active:scale-95 {{ request()->routeIs('surat*') ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                <i class="fa-solid fa-envelope-open-text text-base"></i>
                <span class="text-[10px] tracking-tight">Surat</span>
            </a>
            @endif
            <a href="{{ \App\Support\RfidAccess::canManageWargaKtp(auth()->user()) ? route('admin.warga.rfid.show') : route('warga.rfid.show') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center transition active:scale-95 {{ request()->routeIs('*rfid*') || request()->is('kartu-rfid*') ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                <i class="fa-solid fa-id-card text-base"></i>
                <span class="text-[10px] tracking-tight">KTP RFID</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-center transition active:scale-95 {{ request()->routeIs('profile*') ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                <i class="fa-solid fa-circle-user text-base"></i>
                <span class="text-[10px] tracking-tight">Profil</span>
            </a>
        </div>
    </nav>
    @endauth

    <script>
        setTimeout(() => {
            const notif = document.getElementById('notif');
            if (notif) {
                notif.style.transition = 'opacity 180ms cubic-bezier(0.23, 1, 0.32, 1), transform 180ms cubic-bezier(0.23, 1, 0.32, 1)';
                notif.style.opacity = '0';
                notif.style.transform = 'translateY(-12px) scale(0.96)';
                setTimeout(() => notif.remove(), 180);
            }
        }, 3200);
    </script>

    {{-- Command Palette (Ctrl + K) --}}
    <div
        x-data="commandPalette(@js($paletteItems))"
        @open-command-palette.window="show()"
        class="relative z-[99999]"
        role="dialog"
        aria-modal="true"
        aria-label="Navigasi Cepat Pengurus (Command Palette)"
    >
        <div
            x-cloak
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs"
            @click="close()"
        ></div>

        <div
            x-cloak
            x-show="open"
            class="fixed inset-0 overflow-y-auto p-4 pt-16 sm:p-6 sm:pt-24 flex justify-center"
            @click.self="close()"
        >
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-2xl transition-all dark:border-slate-700/80 dark:bg-slate-900"
            >
                {{-- Search Header --}}
                <div class="relative flex items-center border-b border-slate-100 px-5 dark:border-slate-800">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm mr-3"></i>
                    <input
                        x-ref="searchInput"
                        type="text"
                        x-model="search"
                        @keydown.arrow-down.prevent="selectNext()"
                        @keydown.arrow-up.prevent="selectPrev()"
                        @keydown.enter.prevent="executeActive()"
                        @keydown.escape="close()"
                        placeholder="Ketik untuk mencari menu atau aksi pengurus..."
                        class="w-full border-0 bg-transparent py-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:outline-hidden focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
                    />
                    <kbd class="ml-2 rounded-lg border border-slate-200 bg-slate-100 px-2 py-0.5 text-[10px] font-mono font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">ESC</kbd>
                </div>

                {{-- Results Container --}}
                <div class="max-h-80 overflow-y-auto p-2.5 space-y-1">
                    <template x-if="filteredItems.length === 0">
                        <div class="py-12 text-center text-xs text-slate-500 dark:text-slate-400">
                            <i class="fa-regular fa-face-meh text-2xl text-slate-400 mb-2 block"></i>
                            <p class="font-bold text-slate-700 dark:text-slate-300">Tidak ada menu atau aksi yang cocok</p>
                            <p class="mt-0.5">Coba kata kunci lain seperti "kas", "warga", "rfid", atau "tagihan"</p>
                        </div>
                    </template>

                    <template x-for="(item, idx) in filteredItems" :key="item.label + item.group">
                        <button
                            type="button"
                            :data-palette-idx="idx"
                            @click="execute(item)"
                            @mouseenter="activeIndex = idx"
                            class="flex w-full items-center justify-between rounded-2xl px-3.5 py-2.5 text-left text-xs transition duration-150"
                            :class="activeIndex === idx ? 'bg-emerald-50 text-emerald-950 font-bold dark:bg-emerald-950/50 dark:text-emerald-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60'"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-sm"
                                    :class="activeIndex === idx ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                >
                                    <i :class="'fa-solid ' + item.icon"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold" :class="activeIndex === idx ? 'text-emerald-950 dark:text-emerald-100' : 'text-slate-800 dark:text-slate-200'" x-text="item.label"></p>
                                    <p class="truncate text-[10px] text-slate-400 dark:text-slate-500" x-text="item.group"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pl-3">
                                <span
                                    class="rounded-lg px-2 py-0.5 text-[10px] font-semibold"
                                    :class="item.type === 'action' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                                    x-text="item.type === 'action' ? 'Aksi' : 'Buka'"
                                ></span>
                                <i x-show="activeIndex === idx" class="fa-solid fa-arrow-turn-down-left text-[11px] text-emerald-600 dark:text-emerald-400"></i>
                            </div>
                        </button>
                    </template>
                </div>

                {{-- Footer Keyboard Navigation Hints --}}
                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/80 px-4 py-2.5 text-[11px] text-slate-500 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1"><kbd class="rounded border border-slate-200 bg-white px-1 font-mono text-[9px] shadow-2xs dark:border-slate-700 dark:bg-slate-900">↑↓</kbd> Navigasi</span>
                        <span class="inline-flex items-center gap-1"><kbd class="rounded border border-slate-200 bg-white px-1 font-mono text-[9px] shadow-2xs dark:border-slate-700 dark:bg-slate-900">↵</kbd> Buka</span>
                        <span class="inline-flex items-center gap-1"><kbd class="rounded border border-slate-200 bg-white px-1 font-mono text-[9px] shadow-2xs dark:border-slate-700 dark:bg-slate-900">ESC</kbd> Tutup</span>
                    </div>
                    <span class="font-bold text-emerald-700 dark:text-emerald-400">Smart RW Navigasi</span>
                </div>
            </div>
        </div>
    </div>

    @include('components.assistant-widget', ['bottomOffsetClass' => 'bottom-20 md:bottom-5'])
</body>
</html>
