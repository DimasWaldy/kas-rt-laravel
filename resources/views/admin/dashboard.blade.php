@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-500 to-green-500 p-6 text-white shadow-lg shadow-emerald-100 md:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] text-emerald-50">Kontrol Pengurus</p>
                <h1 class="text-2xl font-black md:text-3xl">Dashboard Admin</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium text-emerald-50">
                    Pantau kondisi kas, tagihan, data rumah, warga, dan pengaduan tambahan yang perlu ditindaklanjuti.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <a href="{{ route('tagihan.admin') }}" class="rounded-2xl bg-white/15 px-4 py-3 font-bold text-white backdrop-blur transition hover:bg-white/25">
                    Verifikasi Tagihan
                </a>
                <a href="{{ route('pengaduan.index', ['filter' => 'pending']) }}" class="rounded-2xl bg-white/15 px-4 py-3 font-bold text-white backdrop-blur transition hover:bg-white/25">
                    Pengaduan Tambahan
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('tagihan.admin') }}" class="rounded-2xl border {{ $pendingTransferCount + $pendingOfflineCount > 0 ? 'border-amber-200 bg-amber-50' : 'border-emerald-100 bg-white' }} p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Perlu Verifikasi</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $pendingTransferCount + $pendingOfflineCount }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-600">{{ $pendingTransferCount }} transfer, {{ $pendingOfflineCount }} offline</p>
                </div>
                <div class="rounded-xl bg-amber-100 p-3 text-amber-700">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('tagihan.admin') }}" class="rounded-2xl border {{ $rumahBelumBayarCount > 0 ? 'border-lime-200 bg-lime-50' : 'border-emerald-100 bg-white' }} p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Rumah Belum Lunas</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $rumahBelumBayarCount }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-600">Nominal tertunggak: Rp {{ number_format($nominalTagihanTertunggak, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl bg-lime-100 p-3 text-lime-700">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('pengaduan.index', ['filter' => 'pending']) }}" class="rounded-2xl border {{ $pengaduanPending > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-emerald-100 bg-white' }} p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Pengaduan Tambahan</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $pengaduanPending }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-600">{{ $pengaduanPending }} pending, {{ $pengaduanProses }} proses, {{ $pengaduanSelesai }} selesai</p>
                </div>
                <div class="rounded-xl bg-emerald-100 p-3 text-emerald-700">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.warga.index') }}" class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Data Warga</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $totalKepalaKeluarga }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-600">{{ $totalWarga }} warga terdaftar</p>
                </div>
                <div class="rounded-xl bg-teal-100 p-3 text-teal-700">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-600">Total Kas Masuk</p>
            <h2 class="mt-2 text-2xl font-black text-slate-900">Rp {{ number_format($totalKasMasuk, 0, ',', '.') }}</h2>
            <p class="mt-2 text-xs font-semibold text-emerald-600">Bulan ini: Rp {{ number_format($masukBulanIni, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-600">Total Kas Keluar</p>
            <h2 class="mt-2 text-2xl font-black text-slate-900">Rp {{ number_format($totalKasKeluar, 0, ',', '.') }}</h2>
            <p class="mt-2 text-xs font-semibold text-green-700">Bulan ini: Rp {{ number_format($keluarBulanIni, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-600">Saldo Akhir</p>
            <h2 class="mt-2 text-2xl font-black {{ $saldoAkhir >= 0 ? 'text-emerald-700' : 'text-red-600' }}">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</h2>
            <p class="mt-2 text-xs font-semibold {{ $saldoAkhir >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $saldoAkhir >= 0 ? 'Saldo positif' : 'Saldo negatif' }}</p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-600">Net Bulan Ini</p>
            <h2 class="mt-2 text-2xl font-black {{ $netBulanIni >= 0 ? 'text-emerald-700' : 'text-red-600' }}">Rp {{ number_format($netBulanIni, 0, ',', '.') }}</h2>
            <p class="mt-2 text-xs font-semibold text-slate-500">Masuk dikurangi keluar bulan ini</p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-600">Rasio Lunas Tagihan</p>
            @php
                $totalTagihanRatio = max($totalTagihan, 1);
                $lunasPercent = round(($tagihanSudahLunas / $totalTagihanRatio) * 100);
            @endphp
            <h2 class="mt-2 text-2xl font-black text-slate-900">{{ $lunasPercent }}%</h2>
            <div class="mt-3 h-2 rounded-full bg-emerald-100">
                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $lunasPercent }}%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-900">Prioritas Hari Ini</h2>
            <div class="mt-4 space-y-3">
                <a href="{{ route('tagihan.admin') }}" class="flex items-center justify-between rounded-2xl bg-amber-50 p-4 transition hover:bg-amber-100">
                    <div>
                        <p class="text-sm font-black text-amber-900">Verifikasi Pembayaran</p>
                        <p class="mt-1 text-xs text-amber-700">{{ $pendingTransferCount + $pendingOfflineCount }} pembayaran menunggu keputusan</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-amber-600"></i>
                </a>
                <a href="{{ route('admin.rumah.index') }}" class="flex items-center justify-between rounded-2xl bg-lime-50 p-4 transition hover:bg-lime-100">
                    <div>
                        <p class="text-sm font-black text-lime-900">Rumah Belum Lunas</p>
                        <p class="mt-1 text-xs text-lime-700">{{ $rumahBelumBayarCount }} dari {{ $totalRumahAktif }} rumah aktif perlu ditindaklanjuti</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-lime-600"></i>
                </a>
                <a href="{{ route('laporan-kas.index') }}" class="flex items-center justify-between rounded-2xl bg-emerald-50 p-4 transition hover:bg-emerald-100">
                    <div>
                        <p class="text-sm font-black text-emerald-900">Pantau Kas Bulan Ini</p>
                        <p class="mt-1 text-xs text-emerald-700">Net: Rp {{ number_format($netBulanIni, 0, ',', '.') }}</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-emerald-600"></i>
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Rumah Belum Bayar Bulan Ini</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Diurutkan dari nominal tagihan terbesar.</p>
                </div>
                <a href="{{ route('admin.rumah.index') }}" class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100">Data Rumah</a>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @forelse($rumahBelumBayarBulanIni->take(6) as $item)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-slate-900">{{ $item->rumah?->kode_rumah ?? 'Rumah belum diatur' }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $item->rumah?->alamat ?? $item->user?->name ?? '-' }}</p>
                                <span class="mt-2 inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $item->status }}</span>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-emerald-700">Rp {{ number_format($item->total, 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $item->belum_lunas }}/{{ $item->jumlah_tagihan }} nota</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700 md:col-span-2">Semua rumah aktif sudah lunas untuk bulan ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm xl:col-span-2">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Grafik Kas {{ $chartData['label'] }}</h2>
                    <p class="text-xs font-semibold text-slate-500">Perbandingan kas masuk dan keluar {{ $chartMode === 'daily' ? 'per tanggal bulan ini.' : 'per bulan.' }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.dashboard', ['chart' => 'monthly']) }}" class="rounded-xl px-3 py-2 text-xs font-bold {{ $chartMode === 'monthly' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700' }}">Bulanan</a>
                    <a href="{{ route('admin.dashboard', ['chart' => 'daily']) }}" class="rounded-xl px-3 py-2 text-xs font-bold {{ $chartMode === 'daily' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700' }}">Harian</a>
                </div>
            </div>
            <div class="relative h-80">
                <canvas id="kasChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">Tagihan Menunggu Verifikasi</h2>
            <div class="space-y-3">
                @forelse($tagihanMenungguVerifikasi as $tagihan)
                    <div class="rounded-xl bg-emerald-50 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{{ $tagihan->user?->name ?? 'Warga' }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $tagihan->status_label }} - {{ $tagihan->bulan }}/{{ $tagihan->tahun }}</p>
                            </div>
                            <p class="flex-shrink-0 text-sm font-black text-emerald-700">Rp {{ number_format($tagihan->total, 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">Tidak ada pembayaran yang menunggu verifikasi.</p>
                @endforelse
            </div>
            <a href="{{ route('tagihan.admin') }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-600">
                Buka Verifikasi
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-900">Tagihan Jatuh Tempo</h2>
            <p class="mt-1 text-xs font-semibold text-slate-500">Menampilkan tagihan yang overdue atau mendekati akhir bulan.</p>
            <div class="mt-4 space-y-3">
                @forelse($tagihanJatuhTempo as $tagihan)
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-900">{{ $tagihan->display_title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $tagihan->rumah?->kode_rumah ?? $tagihan->user?->name ?? '-' }} - jatuh tempo {{ $tagihan->due_date->translatedFormat('d M Y') }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $tagihan->due_status_class }}">{{ $tagihan->due_status_label }}</span>
                    </div>
                @empty
                    <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">Tidak ada tagihan yang mendekati jatuh tempo.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-900">Kas Keluar Terbesar Bulan Ini</h2>
            <p class="mt-1 text-xs font-semibold text-slate-500">Bantu cek pengeluaran besar yang perlu dipertanggungjawabkan.</p>
            <div class="mt-4 space-y-3">
                @forelse($kasKeluarTerbesarBulanIni as $item)
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-rose-50 p-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-900">{{ $item->keterangan }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</p>
                        </div>
                        <p class="text-sm font-black text-rose-600">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">Belum ada kas keluar bulan ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">Top 5 Warga - Sepanjang Masa</h2>
            <div class="space-y-3">
                @forelse($topWarga as $index => $warga)
                    <div class="flex items-center justify-between rounded-xl bg-emerald-50/70 p-3 transition hover:bg-emerald-50">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">{{ $index + 1 }}</span>
                            <p class="truncate font-semibold text-slate-800">{{ $warga->name }}</p>
                        </div>
                        <p class="flex-shrink-0 font-bold text-emerald-700">Rp {{ number_format($warga->total_iuran, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="py-4 text-center text-slate-500">Belum ada data transaksi</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-lg font-black text-slate-900">Pengaduan Terbaru</h2>
            <p class="mb-4 text-xs font-semibold text-slate-500">Fitur tambahan di luar fokus utama kas RT.</p>
            <div class="space-y-3">
                @forelse($pengaduanTerbaru as $pengaduan)
                    <a href="{{ route('pengaduan.show', $pengaduan) }}" class="block rounded-xl bg-emerald-50/70 p-3 transition hover:bg-emerald-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{{ $pengaduan->judul }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $pengaduan->user?->name ?? 'Warga' }} - {{ $pengaduan->kategori }}</p>
                            </div>
                            <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ $pengaduan->status === 'pending' ? 'bg-amber-100 text-amber-700' : ($pengaduan->status === 'proses' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ $pengaduan->status }}
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">Belum ada pengaduan terbaru.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-black text-slate-900">Transaksi Terbaru</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-emerald-100 text-sm">
                <thead class="bg-emerald-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Warga</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Keterangan</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-100">
                    @forelse($transaksiTerbaru as $transaksi)
                        <tr class="transition hover:bg-emerald-50/70">
                            <td class="px-4 py-3 text-slate-600">{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $transaksi->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $transaksi->keterangan ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-600">Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}</td>
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
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData['months']) !!},
            datasets: [
                {
                    label: 'Kas Masuk',
                    data: {!! json_encode($chartData['masukData']) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.12)',
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
                    borderColor: '#65a30d',
                    backgroundColor: 'rgba(101, 163, 13, 0.12)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#65a30d',
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
                        font: { size: 12, weight: 'bold' }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#dcfce7' },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>
@endsection
