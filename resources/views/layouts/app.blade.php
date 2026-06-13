<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart RW - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-item { transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease; }
        .active-menu { background-color: #16a34a; color: #fff; box-shadow: 0 10px 18px -12px rgb(22 163 74 / 0.65); }
        .sidebar-scroll { scrollbar-width: thin; scrollbar-color: #86efac #ecfdf5; }
        .sidebar-scroll::-webkit-scrollbar { width: 8px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: #ecfdf5; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #86efac; border-radius: 999px; border: 2px solid #ecfdf5; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #4ade80; }
    </style>
</head>
@php
    $user = auth()->user();
    $roleLabel = match($user->role_name) {
        'admin' => 'Admin RT',
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
                    'label' => 'Edit Profil',
                    'route' => 'profile.edit',
                    'active' => 'profile',
                    'icon' => 'fa-user',
                    'iconClass' => 'text-emerald-600',
                    'visible' => true,
                ],
            ],
        ],
        [
            'label' => 'Transaksi',
            'items' => [
                [
                    'label' => 'Kas Masuk',
                    'route' => 'kas-masuk.index',
                    'active' => 'kas-masuk*',
                    'icon' => 'fa-money-bill-trend-up',
                    'iconClass' => 'text-emerald-600',
                    'visible' => true,
                ],
                [
                    'label' => 'Kas Keluar',
                    'route' => 'kas-keluar.index',
                    'active' => 'kas-keluar*',
                    'icon' => 'fa-money-bill-transfer',
                    'iconClass' => 'text-green-600',
                    'visible' => true,
                ],
                [
                    'label' => 'Tagihan',
                    'route' => 'tagihan.index',
                    'active' => 'tagihan*',
                    'icon' => 'fa-receipt',
                    'iconClass' => 'text-lime-600',
                    'visible' => true,
                ],
                [
                    'label' => 'Laporan Kas',
                    'route' => 'laporan-kas.index',
                    'active' => 'admin/laporan-kas*',
                    'icon' => 'fa-chart-pie',
                    'iconClass' => 'text-emerald-600',
                    'visible' => $user->canManageFinance(),
                ],
            ],
        ],
        [
            'label' => 'Pengurus RT',
            'items' => [
                [
                    'label' => 'Iuran Bulanan',
                    'route' => 'iuran-bulanan.index',
                    'active' => 'admin/iuran-bulanan*',
                    'icon' => 'fa-list-check',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->canManageFinance(),
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
                    'label' => 'Demo Aplikasi',
                    'route' => 'demo-uts.index',
                    'active' => 'admin/demo-uts*',
                    'icon' => 'fa-chalkboard-user',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->canManageFinance(),
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
                    'label' => 'Data Warga',
                    'route' => 'admin.warga.index',
                    'active' => 'admin/warga*',
                    'icon' => 'fa-users',
                    'iconClass' => 'text-teal-600',
                    'visible' => $user->canManageWarga(),
                ],
            ],
        ],
        [
            'label' => 'Layanan',
            'items' => [
                [
                    'label' => 'Pengaduan',
                    'route' => 'pengaduan.index',
                    'active' => 'pengaduan*',
                    'icon' => 'fa-bullhorn',
                    'iconClass' => 'text-emerald-600',
                    'visible' => true,
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

    $unreadNotifications = $user->canManageFinance() ? $user->unreadNotifications : collect();
@endphp
<body class="bg-gray-50 font-sans text-slate-900" x-data="{ mobileMenuOpen: false }" x-on:keydown.escape.window="mobileMenuOpen = false">

    <div x-cloak x-show="mobileMenuOpen" class="fixed inset-0 z-40 bg-emerald-950/35 lg:hidden" x-transition.opacity x-on:click="mobileMenuOpen = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[min(20rem,calc(100vw-2rem))] -translate-x-full flex-col border-r border-emerald-100 bg-gradient-to-b from-emerald-50 via-green-50 to-white text-slate-700 shadow-2xl shadow-emerald-950/10 transition-transform duration-300 lg:translate-x-0"
        :class="{ 'translate-x-0': mobileMenuOpen, '-translate-x-full': !mobileMenuOpen }"
        aria-label="Navigasi utama"
    >
        <div class="flex items-center justify-between border-b border-emerald-100 bg-white/70 p-5 lg:p-6">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-500 shadow-sm shadow-emerald-500/30">
                    <i class="fa-solid fa-wallet text-lg text-white"></i>
                </span>
                <span class="truncate text-xl font-extrabold tracking-wider text-emerald-950">Smart RW</span>
            </a>

            <button type="button" class="flex h-10 w-10 items-center justify-center rounded-lg text-emerald-700 hover:bg-emerald-100 hover:text-emerald-950 lg:hidden" x-on:click="mobileMenuOpen = false" aria-label="Tutup menu">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <nav class="sidebar-scroll flex-1 space-y-6 overflow-y-auto p-4">
            @foreach($visibleMenuGroups as $group)
                <div>
                    <p class="mb-3 px-2 text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-700/70">{{ $group['label'] }}</p>
                    <ul class="space-y-1">
                        @foreach($group['items'] as $item)
                            <li>
                                <a
                                    href="{{ route($item['route']) }}"
                                    x-on:click="mobileMenuOpen = false"
                                    class="sidebar-item flex min-h-12 items-center gap-3 rounded-xl p-3 text-sm font-semibold hover:bg-emerald-100 hover:text-emerald-950 {{ request()->is($item['active']) ? 'active-menu' : '' }}"
                                >
                                    <i class="fa-solid {{ $item['icon'] }} w-5 flex-shrink-0 text-center {{ request()->is($item['active']) ? 'text-white' : $item['iconClass'] }}"></i>
                                    <span class="min-w-0 truncate font-medium">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-emerald-100 bg-white/70 p-4">
            <div class="mb-3 rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-[10px] capitalize text-emerald-700">{{ $roleLabel }}</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 border-b bg-white/95 px-4 py-3 shadow-sm backdrop-blur lg:hidden">
            <div class="flex items-center justify-between gap-3">
                <button type="button" class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm shadow-emerald-500/30" x-on:click="mobileMenuOpen = true" aria-label="Buka menu">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">Smart RW</p>
                    <h1 class="truncate text-base font-bold text-slate-800">@yield('title', 'Dashboard')</h1>
                </div>

                @if($user->canManageFinance())
                    <a href="{{ route('tagihan.admin') }}" class="relative flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700" aria-label="Notifikasi pembayaran">
                        <i class="fa-solid fa-bell"></i>
                        @if($unreadNotifications->count())
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-white bg-red-500 px-1 text-[10px] font-bold text-white">
                                {{ $unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>
                @endif
            </div>
        </header>

        <header class="hidden h-20 items-center justify-between border-b bg-white px-8 shadow-sm lg:flex">
            <div class="flex min-w-0 items-center gap-2">
                <h3 class="truncate text-lg font-bold capitalize text-slate-700">
                    @yield('title', 'Dashboard')
                </h3>
            </div>

            <div class="flex items-center gap-4">
                @if($user->canManageFinance())
                    <a href="{{ route('tagihan.admin') }}" class="relative flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" aria-label="Notifikasi pembayaran">
                        <i class="fa-solid fa-bell"></i>
                        @if($unreadNotifications->count())
                            <span class="absolute right-0 top-0 inline-flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-red-500 text-[10px] font-bold text-white">
                                {{ $unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>
                @endif

                <div class="flex items-center gap-3 rounded-full bg-emerald-50 px-3 py-1.5">
                    <span class="text-sm font-semibold text-emerald-800">{{ $roleLabel }}</span>
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

        @if($user->canManageFinance() && $unreadNotifications->count())
            <div class="border-b border-amber-100 bg-amber-50 px-4 py-3 text-amber-800 md:px-8">
                <p class="text-sm font-semibold">
                    Ada {{ $unreadNotifications->count() }} notifikasi pembayaran baru.
                    <a href="{{ route('tagihan.admin') }}" class="underline">Klik untuk verifikasi</a>.
                </p>
            </div>
        @endif

        @if(session('success') || session('error'))
            <div id="notif" class="fixed left-4 right-4 top-20 z-50 mx-auto flex max-w-sm items-center rounded-2xl p-4 text-white shadow-2xl {{ session('success') ? 'bg-green-600' : 'bg-red-600' }} md:left-auto md:right-5 md:top-5">
                <div class="text-sm font-bold">{{ session('success') ?? session('error') }}</div>
            </div>
        @endif

        <main class="min-w-0 p-4 md:p-6 lg:p-8">
            @yield('content')
        </main>
    </div>

    <script>
        setTimeout(() => {
            const notif = document.getElementById('notif');
            if (notif) {
                notif.style.transition = 'all 0.5s ease';
                notif.style.opacity = '0';
                notif.style.transform = 'translateY(-8px)';
                setTimeout(() => notif.remove(), 500);
            }
        }, 3000);
    </script>
</body>
</html>
