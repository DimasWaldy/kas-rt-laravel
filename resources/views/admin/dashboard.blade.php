@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="rounded-3xl bg-gradient-to-r from-blue-600 to-blue-700 shadow-lg p-8 text-white">
        <h1 class="text-3xl font-bold mb-2">Dashboard Admin</h1>
        <p class="text-blue-100">Kelola keuangan RT dan monitor semua transaksi secara real-time</p>
    </div>

    <!-- Main Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Kas Masuk -->
        <div class="rounded-2xl bg-white shadow-sm p-6 border-l-4 border-emerald-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Total Kas Masuk</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">
                        Rp {{ number_format($totalKasMasuk, 0, ',', '.') }}
                    </h2>
                    <p class="text-xs text-emerald-600 font-semibold mt-2">
                        Bulan ini: Rp {{ number_format($masukBulanIni, 0, ',', '.') }}
                    </p>
                </div>
                <div class="bg-emerald-100 p-3 rounded-lg">
                    <i class="fa-solid fa-arrow-up text-emerald-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Kas Keluar -->
        <div class="rounded-2xl bg-white shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Total Kas Keluar</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">
                        Rp {{ number_format($totalKasKeluar, 0, ',', '.') }}
                    </h2>
                    <p class="text-xs text-red-600 font-semibold mt-2">
                        Bulan ini: Rp {{ number_format($keluarBulanIni, 0, ',', '.') }}
                    </p>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="fa-solid fa-arrow-down text-red-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Saldo Akhir -->
        <div class="rounded-2xl bg-white shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Saldo Akhir</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">
                        Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
                    </h2>
                    <p class="text-xs {{ $saldoAkhir >= 0 ? 'text-blue-600' : 'text-red-600' }} font-semibold mt-2">
                        {{ $saldoAkhir >= 0 ? '✓ Positif' : '✗ Negatif' }}
                    </p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fa-solid fa-wallet text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Warga Info -->
        <div class="rounded-2xl bg-white shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Total Kepala Keluarga</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">{{ $totalKepalaKeluarga }}</h2>
                    <p class="text-xs text-purple-600 font-semibold mt-2">
                        Aktif: {{ $wargaAktifBulanIni }} | Belum: {{ $wargaBelumBayarBulanIni }}
                    </p>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fa-solid fa-users text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart Kas Masuk/Keluar -->
        <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Transaksi 12 Bulan Terakhir</h2>
            <div class="relative h-80">
                <canvas id="kasChart"></canvas>
            </div>
        </div>

        <!-- Tagihan Summary -->
        <div class="rounded-2xl bg-white shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Status Tagihan</h2>
            <div class="space-y-4">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-4">
                    <p class="text-sm text-slate-600">Total Tagihan</p>
                    <h3 class="text-2xl font-bold text-blue-600">{{ $totalTagihan }}</h3>
                </div>
                <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 rounded-xl p-4">
                    <p class="text-sm text-slate-600">Sudah Lunas</p>
                    <h3 class="text-2xl font-bold text-emerald-600">{{ $tagihanSudahLunas }}</h3>
                </div>
                <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-xl p-4">
                    <p class="text-sm text-slate-600">Belum Lunas</p>
                    <h3 class="text-2xl font-bold text-red-600">{{ $tagihanBelumLunas }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Warga Sepanjang Masa -->
        <div class="rounded-2xl bg-white shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">🏆 Top 5 Warga (Sepanjang Masa)</h2>
            <div class="space-y-3">
                @forelse($topWarga as $index => $warga)
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ 
                                $index == 0 ? 'bg-yellow-400 text-white' : 
                                ($index == 1 ? 'bg-gray-400 text-white' : 
                                ($index == 2 ? 'bg-orange-400 text-white' : 'bg-slate-300 text-slate-700'))
                            }} font-bold text-sm">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <p class="font-semibold text-slate-800">{{ $warga->name }}</p>
                            </div>
                        </div>
                        <p class="font-bold text-slate-900">Rp {{ number_format($warga->total_iuran, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="text-slate-500 text-center py-4">Belum ada data transaksi</p>
                @endforelse
            </div>
        </div>

        <!-- Top Warga Bulan Ini -->
        <div class="rounded-2xl bg-white shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">⭐ Top 5 Warga (Bulan Ini)</h2>
            <div class="space-y-3">
                @forelse($topWargaBulanIni as $index => $warga)
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ 
                                $index == 0 ? 'bg-yellow-400 text-white' : 
                                ($index == 1 ? 'bg-gray-400 text-white' : 
                                ($index == 2 ? 'bg-orange-400 text-white' : 'bg-slate-300 text-slate-700'))
                            }} font-bold text-sm">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <p class="font-semibold text-slate-800">{{ $warga->name }}</p>
                            </div>
                        </div>
                        <p class="font-bold text-slate-900">Rp {{ number_format($warga->total_iuran, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="text-slate-500 text-center py-4">Belum ada data bulan ini</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Transaksi Terbaru -->
    <div class="rounded-2xl bg-white shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Transaksi Terbaru</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Warga</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Keterangan</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($transaksiTerbaru as $transaksi)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-slate-600">
                                {{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $transaksi->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $transaksi->keterangan ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-600">
                                Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('kasChart').getContext('2d');
    const kasChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData['months']) !!},
            datasets: [
                {
                    label: 'Kas Masuk',
                    data: {!! json_encode($chartData['masukData']) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'Kas Keluar',
                    data: {!! json_encode($chartData['keluarData']) !!},
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
</script>
@endsection
