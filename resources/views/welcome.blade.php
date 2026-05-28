<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kas RT - Transparansi Warga</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-emerald-50/40 font-sans antialiased text-slate-900">

    <nav class="sticky top-0 z-50 border-b border-emerald-100 bg-white/90 backdrop-blur-md">
        <div class="mx-auto flex h-20 max-w-6xl items-center justify-between px-6">
            <div class="flex min-w-0 items-center gap-3">
                <div class="rounded-xl bg-emerald-500 p-2 shadow-lg shadow-emerald-200">
                    <i class="fa-solid fa-wallet text-lg text-white"></i>
                </div>
                <span class="truncate text-xl font-black uppercase tracking-tighter text-emerald-950">Kas RT Kita</span>
            </div>

            @auth
                <a href="{{ route('dashboard') }}" class="rounded-full bg-emerald-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition-all hover:bg-emerald-600 active:scale-95">
                    <i class="fa-solid fa-gauge-high mr-2"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-full bg-emerald-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition-all hover:bg-emerald-600 active:scale-95">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i> Masuk Sistem
                </a>
            @endauth
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-6 py-12">
        <div class="mb-16 text-center">
            <p class="mb-3 text-xs font-black uppercase tracking-[0.28em] text-emerald-600">Transparansi Kas Warga</p>
            <h1 class="mb-4 text-4xl font-black tracking-tight text-emerald-950 md:text-5xl">
                Transparansi Dana <span class="text-emerald-600">RT</span> Jadi Lebih Mudah.
            </h1>
            <p class="mx-auto max-w-2xl text-lg font-medium leading-relaxed text-slate-600">
                Pantau penggunaan dana kas secara real-time. Dari warga, oleh warga, untuk warga. Aman, jujur, dan terbuka.
            </p>
        </div>

        <div class="mb-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="group relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-50 transition-transform group-hover:scale-110"></div>
                <i class="fa-solid fa-arrow-trend-up relative mb-4 text-2xl text-emerald-500"></i>
                <h2 class="relative mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/70">Total Kas Masuk</h2>
                <p class="relative text-3xl font-black text-slate-900">Rp {{ number_format($kasMasuk, 0, ',', '.') }}</p>
            </div>

            <div class="group relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-green-50 transition-transform group-hover:scale-110"></div>
                <i class="fa-solid fa-arrow-trend-down relative mb-4 text-2xl text-green-600"></i>
                <h2 class="relative mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/70">Total Kas Keluar</h2>
                <p class="relative text-3xl font-black text-slate-900">Rp {{ number_format($kasKeluar, 0, ',', '.') }}</p>
            </div>

            <div class="group relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-lime-50 transition-transform group-hover:scale-110"></div>
                <i class="fa-solid fa-users relative mb-4 text-2xl text-lime-600"></i>
                <h2 class="relative mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/70">Warga Terdaftar</h2>
                <p class="relative text-3xl font-black text-slate-900">{{ $totalWarga }} <span class="text-sm font-bold text-slate-400">Jiwa</span></p>
            </div>

            <div class="group relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-teal-50 transition-transform group-hover:scale-110"></div>
                <i class="fa-solid fa-house-chimney relative mb-4 text-2xl text-teal-600"></i>
                <h2 class="relative mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/70">Rumah Terdaftar</h2>
                <p class="relative text-3xl font-black text-slate-900">{{ $totalRumah ?? 0 }} <span class="text-sm font-bold text-slate-400">Rumah</span></p>
            </div>
        </div>

        <div class="mb-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="group relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-50 transition-transform group-hover:scale-110"></div>
                <i class="fa-solid fa-users-medical relative mb-4 text-2xl text-emerald-500"></i>
                <h2 class="relative mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/70">Total Data KK</h2>
                <p class="relative text-3xl font-black text-slate-900">{{ $totalKK }} <span class="text-sm font-bold text-slate-400">KK</span></p>
            </div>

            <div class="group relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-green-50 transition-transform group-hover:scale-110"></div>
                <i class="fa-solid fa-user-check relative mb-4 text-2xl text-green-600"></i>
                <h2 class="relative mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/70">Rumah Aktif Bayar</h2>
                <p class="relative text-3xl font-black text-slate-900">{{ $kepalaKeluargaAktif }}</p>
            </div>

            <div class="group relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-lime-50 transition-transform group-hover:scale-110"></div>
                <i class="fa-solid fa-bell-slash relative mb-4 text-2xl text-lime-600"></i>
                <h2 class="relative mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/70">Rumah Belum Bayar</h2>
                <p class="relative text-3xl font-black text-slate-900">{{ $keluargaBelumBayar }}</p>
            </div>

            <div class="group relative overflow-hidden rounded-[2rem] bg-emerald-500 p-8 shadow-xl shadow-emerald-100">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/15 transition-transform group-hover:scale-110"></div>
                <i class="fa-solid fa-wallet relative mb-4 text-2xl text-emerald-100"></i>
                <h2 class="relative mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-50">Saldo Saat Ini</h2>
                <p class="relative text-3xl font-black text-white">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                <div class="rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                    <h3 class="mb-6 flex items-center text-lg font-black text-slate-900">
                        <span class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-xs text-emerald-600">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                        Pemasukan Terbaru
                    </h3>
                    <div class="space-y-4">
                        @forelse($recentMasuk as $item)
                            <div class="flex items-center justify-between rounded-2xl border border-transparent bg-emerald-50/70 p-4 transition-colors hover:border-emerald-100 hover:bg-emerald-50">
                                <span class="text-sm font-bold text-slate-700">{{ $item->keterangan }}</span>
                                <span class="text-sm font-black text-emerald-600">+ Rp {{ number_format($item->jumlah, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">Belum ada pemasukan terbaru.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                    <h3 class="mb-6 flex items-center text-lg font-black text-slate-900">
                        <span class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-xs text-green-700">
                            <i class="fa-solid fa-minus"></i>
                        </span>
                        Pengeluaran Terbaru
                    </h3>
                    <div class="space-y-4">
                        @forelse($recentKeluar as $item)
                            <div class="flex items-center justify-between rounded-2xl border border-transparent bg-green-50/70 p-4 transition-colors hover:border-green-100 hover:bg-green-50">
                                <span class="text-sm font-bold text-slate-700">{{ $item->keterangan }}</span>
                                <span class="text-sm font-black text-green-700">- Rp {{ number_format($item->jumlah, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="rounded-2xl bg-green-50 p-4 text-sm font-semibold text-green-700">Belum ada pengeluaran terbaru.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="h-fit rounded-[2.5rem] border border-emerald-100 bg-white p-8 shadow-xl shadow-emerald-100/70 lg:sticky lg:top-28">
                <h3 class="mb-8 flex items-center text-lg font-black text-slate-900">
                    <i class="fa-solid fa-crown mr-3 text-emerald-500"></i> Top Warga Iuran
                </h3>
                <div class="space-y-5">
                    @forelse($leaderboard as $index => $data)
                        <div class="group flex items-center justify-between">
                            <div class="flex min-w-0 items-center gap-4">
                                <span class="text-sm font-black text-emerald-600">{{ sprintf("%02d", $index+1) }}</span>
                                <div class="flex min-w-0 flex-col">
                                    <span class="truncate text-sm font-bold capitalize tracking-tight text-slate-800">{{ $data->user->name }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-700/60">Warga Aktif</span>
                                </div>
                            </div>
                            <span class="text-sm font-black tracking-tighter text-emerald-600">
                                Rp {{ number_format($data->total, 0, ',', '.') }}
                            </span>
                        </div>
                        @if(!$loop->last)
                            <div class="h-px w-full bg-emerald-100"></div>
                        @endif
                    @empty
                        <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">Belum ada data iuran warga.</p>
                    @endforelse
                </div>

                <div class="mt-10 rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                    <p class="text-[11px] font-medium leading-relaxed text-emerald-800">
                        Terima kasih kepada seluruh warga yang telah berkontribusi membangun lingkungan kita.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-12 border-t border-emerald-100 py-12 text-center">
        <p class="mb-2 text-xs font-bold uppercase tracking-[0.3em] text-emerald-700">&copy; 2026 Pengelola Kas RT</p>
        <p class="text-[10px] text-emerald-600">Dibuat untuk transparansi bersama</p>
    </footer>

</body>
</html>
