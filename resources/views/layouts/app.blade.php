<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KAS RT - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Smooth transition untuk sidebar */
        .sidebar-item { transition: all 0.3s ease; }
        .active-menu { @apply bg-blue-600 text-white shadow-md; }
    </style>
</head>
<body class="bg-gray-50 flex font-sans">

    <aside class="w-72 bg-[#0f172a] text-slate-300 min-h-screen flex flex-col shadow-xl">
        <div class="p-6 border-b border-slate-800 flex items-center space-x-3">
            <div class="bg-blue-600 p-2 rounded-lg">
                <i class="fa-solid fa-wallet text-white text-xl"></i>
            </div>
            <h2 class="text-xl font-extrabold text-white tracking-wider">KAS RT</h2>
        </div>

        <nav class="flex-1 p-4 mt-4 space-y-6">
            
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-4 px-2">Menu Utama</p>
                <ul class="space-y-1">
                    <li>
                        <a href="/dashboard" class="sidebar-item flex items-center space-x-3 p-3 rounded-xl hover:bg-slate-800 hover:text-white {{ request()->is('dashboard') ? 'active-menu' : '' }}">
                            <i class="fa-solid fa-house w-5 text-center"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-4 px-2">Transaksi</p>
                <ul class="space-y-1">
                    <li>
                        <a href="/kas-masuk" class="sidebar-item flex items-center space-x-3 p-3 rounded-xl hover:bg-slate-800 hover:text-white {{ request()->is('kas-masuk*') ? 'active-menu' : '' }}">
                            <i class="fa-solid fa-money-bill-trend-up w-5 text-center text-green-400"></i>
                            <span class="font-medium">Kas Masuk</span>
                        </a>
                    </li>
                    <li>
                        <a href="/kas-keluar" class="sidebar-item flex items-center space-x-3 p-3 rounded-xl hover:bg-slate-800 hover:text-white {{ request()->is('kas-keluar*') ? 'active-menu' : '' }}">
                            <i class="fa-solid fa-money-bill-transfer w-5 text-center text-red-400"></i>
                            <span class="font-medium">Kas Keluar</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-4 px-2">Laporan</p>
                <ul class="space-y-1">
                    <li>
                        <a href="/laporan-warga" class="sidebar-item flex items-center space-x-3 p-3 rounded-xl hover:bg-slate-800 hover:text-white {{ request()->is('laporan-warga*') ? 'active-menu' : '' }}">
                            <i class="fa-solid fa-file-lines w-5 text-center text-blue-400"></i>
                            <span class="font-medium">Laporan Warga</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <div class="bg-slate-800/50 rounded-2xl p-4 flex items-center space-x-3">
                <div class="bg-blue-500/20 text-blue-400 w-10 h-10 rounded-full flex items-center justify-center font-bold">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-500 capitalize">{{ auth()->user()->role }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col">

        <header class="bg-white h-20 border-b flex justify-between items-center px-8 shadow-sm">
            <div class="flex items-center space-x-2">
                <h3 class="text-lg font-bold text-slate-700 capitalize">
                    @yield('title', 'Dashboard')
                </h3>
            </div>

            <div class="flex items-center space-x-6">
                <div class="flex items-center space-x-3 bg-gray-100 py-1.5 px-3 rounded-full">
                    <span class="text-sm font-semibold text-slate-700">
                        {{ auth()->user()->role == 'admin' ? '🛠️ Admin' : (auth()->user()->role == 'bendahara' ? '💸 Bendahara' : '👥 Warga') }}
                    </span>
                </div>

                <form method="POST" action="/logout">
                    @csrf
                    <button class="flex items-center space-x-2 text-red-500 hover:text-red-700 font-bold text-sm transition-colors">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </header>

        @if(session('success') || session('error'))
            <div id="notif" class="fixed top-5 right-5 z-50 flex items-center p-4 mb-4 w-full max-w-xs text-white {{ session('success') ? 'bg-green-600' : 'bg-red-600' }} rounded-2xl shadow-2xl animate-bounce">
                <div class="ml-3 text-sm font-bold">{{ session('success') ?? session('error') }}</div>
            </div>
        @endif

        <main class="p-8 flex-1 overflow-y-auto">
            @yield('content')
        </main>

    </div>

    <script>
        // Logic Notif
        setTimeout(() => {
            const notif = document.getElementById('notif');
            if (notif) {
                notif.style.transition = 'all 0.5s ease';
                notif.style.opacity = '0';
                notif.style.transform = 'translateX(20px)';
                setTimeout(() => notif.remove(), 500);
            }
        }, 3000);
    </script>
</body>
</html>