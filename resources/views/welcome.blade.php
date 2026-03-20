<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kas RT - Transparansi Warga</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f8fafc] font-sans antialiased text-slate-900">

    <nav class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-100">
        <div class="max-w-6xl mx-auto px-6 h-20 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-600 p-2 rounded-xl shadow-lg shadow-blue-200">
                    <i class="fa-solid fa-wallet text-white text-lg"></i>
                </div>
                <span class="text-xl font-black tracking-tighter text-slate-800 uppercase">Kas RT Kita</span>
            </div>
            <a href="/login" class="bg-slate-900 hover:bg-slate-800 text-white px-6 py-2.5 rounded-full font-bold text-sm transition-all active:scale-95 shadow-lg shadow-slate-200">
                <i class="fa-solid fa-right-to-bracket mr-2"></i> Masuk Sistem
            </a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-6 py-12">
        
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-4 tracking-tight">
                Transparansi Dana <span class="text-blue-600">RT</span> Jadi Lebih Mudah.
            </h1>
            <p class="text-slate-500 max-w-2xl mx-auto font-medium leading-relaxed text-lg">
                Pantau penggunaan dana kas secara real-time. Dari warga, oleh warga, untuk warga. Aman, jujur, dan terbuka.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 bg-green-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
                <i class="fa-solid fa-arrow-trend-up text-green-500 text-2xl mb-4 relative"></i>
                <h2 class="text-slate-400 font-bold text-xs uppercase tracking-[0.2em] mb-2 relative">Total Kas Masuk</h2>
                <p class="text-3xl font-black text-slate-800 relative">Rp {{ number_format($kasMasuk, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 bg-red-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
                <i class="fa-solid fa-arrow-trend-down text-red-500 text-2xl mb-4 relative"></i>
                <h2 class="text-slate-400 font-bold text-xs uppercase tracking-[0.2em] mb-2 relative">Total Kas Keluar</h2>
                <p class="text-3xl font-black text-slate-800 relative">Rp {{ number_format($kasKeluar, 0, ',', '.') }}</p>
            </div>

            <div class="bg-blue-600 p-8 rounded-[2.5rem] shadow-xl shadow-blue-100 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 bg-white/10 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
                <i class="fa-solid fa-vault text-blue-200 text-2xl mb-4 relative"></i>
                <h2 class="text-blue-100/70 font-bold text-xs uppercase tracking-[0.2em] mb-2 relative">Saldo Saat Ini</h2>
                <p class="text-3xl font-black text-white relative">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center">
                        <span class="w-8 h-8 bg-green-100 text-green-600 rounded-lg flex items-center justify-center mr-3 text-xs">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                        Pemasukan Terbaru
                    </h3>
                    <div class="space-y-4">
                        @foreach($recentMasuk as $item)
                        <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl hover:bg-green-50/50 transition-colors border border-transparent hover:border-green-100">
                            <span class="font-bold text-slate-700 text-sm">{{ $item->keterangan }}</span>
                            <span class="font-black text-green-600 text-sm">+ Rp {{ number_format($item->jumlah, 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center">
                        <span class="w-8 h-8 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mr-3 text-xs">
                            <i class="fa-solid fa-minus"></i>
                        </span>
                        Pengeluaran Terbaru
                    </h3>
                    <div class="space-y-4">
                        @foreach($recentKeluar as $item)
                        <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl hover:bg-red-50/50 transition-colors border border-transparent hover:border-red-100">
                            <span class="font-bold text-slate-700 text-sm">{{ $item->keterangan }}</span>
                            <span class="font-black text-red-600 text-sm">- Rp {{ number_format($item->jumlah, 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-[#0f172a] p-8 rounded-[2.5rem] shadow-2xl h-fit sticky top-28">
                <h3 class="text-lg font-black text-white mb-8 flex items-center">
                    <i class="fa-solid fa-crown text-yellow-400 mr-3"></i> Top Warga Iuran
                </h3>
                <div class="space-y-5">
                    @foreach($leaderboard as $index => $data)
                    <div class="flex items-center justify-between group">
                        <div class="flex items-center space-x-4">
                            <span class="text-slate-500 font-black text-sm">{{ sprintf("%02d", $index+1) }}</span>
                            <div class="flex flex-col">
                                <span class="text-slate-200 font-bold text-sm tracking-tight capitalize">{{ $data->user->name }}</span>
                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Warga Aktif</span>
                            </div>
                        </div>
                        <span class="text-green-400 font-black text-sm tracking-tighter">
                            Rp {{ number_format($data->total, 0, ',', '.') }}
                        </span>
                    </div>
                    @if(!$loop->last)
                    <div class="h-[1px] bg-slate-800 w-full"></div>
                    @endif
                    @endforeach
                </div>
                
                <div class="mt-10 p-5 bg-blue-600/10 rounded-2xl border border-blue-500/20">
                    <p class="text-blue-300 text-[11px] leading-relaxed font-medium">
                        Terima kasih kepada seluruh warga yang telah berkontribusi membangun lingkungan kita.
                    </p>
                </div>
            </div>

        </div>
    </main>

    <footer class="text-center py-12 border-t border-slate-100 mt-12">
        <p class="text-slate-400 text-xs font-bold uppercase tracking-[0.3em] mb-2">&copy; 2026 Pengelola Kas RT</p>
        <p class="text-slate-300 text-[10px]">Dibuat dengan ❤️ untuk transparansi bersama</p>
    </footer>

</body>
</html>