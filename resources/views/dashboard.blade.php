@extends('layouts.app')

@section('title', 'Ringkasan Statistik')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border-l-8 border-green-500 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Kas Masuk</p>
                <h3 class="text-2xl font-extrabold text-slate-800 mt-1">
                    Rp {{ number_format($kasMasuk, 0, ',', '.') }}
                </h3>
            </div>
            <div class="bg-green-100 p-3 rounded-xl text-green-600">
                <i class="fa-solid fa-arrow-trend-up text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border-l-8 border-red-500 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Kas Keluar</p>
                <h3 class="text-2xl font-extrabold text-slate-800 mt-1">
                    Rp {{ number_format($kasKeluar, 0, ',', '.') }}
                </h3>
            </div>
            <div class="bg-red-100 p-3 rounded-xl text-red-600">
                <i class="fa-solid fa-arrow-trend-down text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border-l-8 border-blue-500 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Saldo Warga</p>
                <h3 class="text-2xl font-extrabold text-slate-800 mt-1">
                    Rp {{ number_format($kasMasuk - $kasKeluar, 0, ',', '.') }}
                </h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-xl text-blue-600">
                <i class="fa-solid fa-scale-balanced text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-2 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-chart-column mr-2 text-blue-500"></i>Laporan Keuangan Bulanan</h2>
            <span class="text-xs bg-slate-100 px-3 py-1 rounded-full text-slate-500 font-bold">Update Terkini</span>
        </div>
        <div class="relative h-[300px]">
            <canvas id="kasChart"></canvas>
        </div>
    </div>

    <div class="bg-[#0f172a] p-6 rounded-3xl shadow-xl text-white">
        <h2 class="text-lg font-bold mb-6 flex items-center">
            <i class="fa-solid fa-crown text-yellow-400 mr-2"></i> Top Iuran Warga
        </h2>

        <div class="space-y-4">
            @foreach($leaderboard as $index => $data)
                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-800/50 hover:bg-slate-800 transition-colors">
                    <div class="flex items-center space-x-3">
                        <span class="w-6 h-6 flex items-center justify-center rounded-full text-[10px] font-bold {{ $index == 0 ? 'bg-yellow-500 text-black' : 'bg-slate-700 text-slate-300' }}">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-sm font-medium truncate w-24">{{ $data->user->name }}</span>
                    </div>
                    <span class="text-sm font-extrabold text-green-400">
                        Rp {{ number_format($data->total, 0, ',', '.') }}
                    </span>
                </div>
            @endforeach
        </div>
        
        <button class="w-full mt-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
            Lihat Semua Warga
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('kasChart');

    new Chart(ctx, {
        type: 'bar', // INI JADINYA "CANDLE/BAR" STYLE
        data: {
            labels: {!! json_encode($tanggal) !!},
            datasets: [
                {
                    label: 'Kas Masuk',
                    data: {!! json_encode($dataMasuk) !!},
                    backgroundColor: '#22c55e', // Hijau Tailwind
                    borderRadius: 8, // Bikin ujungnya melengkung biar modern
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.5
                },
                {
                    label: 'Kas Keluar',
                    data: {!! json_encode($dataKeluar) !!},
                    backgroundColor: '#ef4444', // Merah Tailwind
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        font: { weight: 'bold', size: 11 }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10, weight: 'bold' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        font: { size: 10 }
                    }
                }
            }
        }
    });
</script>

@endsection