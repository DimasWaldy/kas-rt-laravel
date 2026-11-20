@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
<style>
    .civic-card {
        transition: transform 150ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 150ms cubic-bezier(0.16, 1, 0.3, 1), border-color 150ms ease-out;
    }
    .civic-card:hover {
        transform: translateY(-1px);
    }
    .btn-tactile {
        transition: transform 120ms cubic-bezier(0.16, 1, 0.3, 1), background-color 120ms ease-out, box-shadow 120ms ease-out;
    }
    .btn-tactile:active {
        transform: scale(0.97);
    }
</style>
@endpush

@section('content')
<div class="space-y-6">

    {{-- Civic Admin Hero Header --}}
    <div class="overflow-hidden rounded-3xl bg-linear-to-br from-emerald-900 via-emerald-800 to-teal-900 p-6 md:p-8 text-white shadow-xl shadow-emerald-950/10 ring-1 ring-white/10 relative">
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-2 text-emerald-200">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10">
                        <i class="fa-solid fa-shield-halved text-xs"></i>
                    </span>
                    <span class="text-xs font-semibold tracking-wide">Pusat Kendali Pengurus &amp; Operator</span>
                </div>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight md:text-3xl">Dashboard Admin</h1>
                <p class="mt-1.5 max-w-2xl text-xs sm:text-sm text-emerald-100/80 leading-relaxed">
                    Pantau kondisi kas, tagihan, data rumah, warga, dan pengaduan tambahan yang perlu ditindaklanjuti.
                </p>
            </div>

            <div class="flex flex-wrap gap-2.5 text-xs">
                <a href="{{ route('tagihan.admin') }}" class="btn-tactile inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 font-bold text-white backdrop-blur-md transition hover:bg-white/25 border border-white/10">
                    <i class="fa-solid fa-clipboard-check text-xs"></i>
                    <span>Verifikasi Tagihan</span>
                </a>
                <a href="{{ route('admin.warga.rfid.show') }}" class="btn-tactile inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 font-bold text-white backdrop-blur-md transition hover:bg-white/25 border border-white/10">
                    <i class="fa-solid fa-id-card text-xs"></i>
                    <span>Tap KTP (RFID)</span>
                </a>
                <a href="{{ route('pengaduan.index', ['filter' => 'pending']) }}" class="btn-tactile inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 font-bold text-white backdrop-blur-md transition hover:bg-white/25 border border-white/10">
                    <i class="fa-solid fa-bullhorn text-xs"></i>
                    <span>Pengaduan Tambahan</span>
                </a>
                @if(auth()->user()->isGlobalOperator() && auth()->user()->isAktif() && auth()->user()->hasPermission('admin-only'))
                    <a href="{{ route('admin.accounts.create') }}" class="btn-tactile inline-flex items-center gap-2 rounded-xl bg-emerald-500/80 px-4 py-2.5 font-bold text-white backdrop-blur-md transition hover:bg-emerald-500 border border-emerald-400/30">
                        <i class="fa-solid fa-user-plus text-xs"></i>
                        <span>Bikin Akun</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- 4 Primary Action Metric Cards --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('tagihan.admin') }}" class="civic-card rounded-2xl border {{ $pendingTransferCount + $pendingOfflineCount > 0 ? 'border-amber-200/90 bg-amber-50/40 dark:border-amber-900/40 dark:bg-amber-950/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900' }} p-5 shadow-2xs">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Perlu Verifikasi</span>
                    <p class="mt-1.5 text-2xl font-extrabold text-slate-900 dark:text-slate-100 tabular-nums">{{ $pendingTransferCount + $pendingOfflineCount }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $pendingTransferCount }} transfer &bull; {{ $pendingOfflineCount }} tunai</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $pendingTransferCount + $pendingOfflineCount > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-200' : 'bg-slate-100 text-slate-500' }}">
                    <i class="fa-solid fa-file-circle-check text-base"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('tagihan.admin') }}" class="civic-card rounded-2xl border {{ $rumahBelumBayarCount > 0 ? 'border-rose-200/90 bg-rose-50/30 dark:border-rose-900/40 dark:bg-rose-950/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900' }} p-5 shadow-2xs">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Rumah Belum Lunas</span>
                    <p class="mt-1.5 text-2xl font-extrabold {{ $rumahBelumBayarCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }} tabular-nums">{{ $rumahBelumBayarCount }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Tertunggak: Rp {{ number_format($nominalTagihanTertunggak, 0, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $rumahBelumBayarCount > 0 ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/60 dark:text-rose-200' : 'bg-slate-100 text-slate-500' }}">
                    <i class="fa-solid fa-receipt text-base"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('pengaduan.index', ['filter' => 'pending']) }}" class="civic-card rounded-2xl border {{ $pengaduanPending > 0 ? 'border-cyan-200/90 bg-cyan-50/40 dark:border-cyan-900/40 dark:bg-cyan-950/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900' }} p-5 shadow-2xs">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pengaduan Tambahan</span>
                    <p class="mt-1.5 text-2xl font-extrabold text-slate-900 dark:text-slate-100 tabular-nums">{{ $pengaduanPending }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $pengaduanPending }} pending &bull; {{ $pengaduanProses }} proses</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 dark:bg-cyan-900/60 dark:text-cyan-200">
                    <i class="fa-solid fa-bullhorn text-base"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.warga.index') }}" class="civic-card rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Data Warga</span>
                    <p class="mt-1.5 text-2xl font-extrabold text-slate-900 dark:text-slate-100 tabular-nums">{{ $totalKepalaKeluarga }} <span class="text-xs font-normal text-slate-400">KK</span></p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $totalWarga }} jiwa terdaftar</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <i class="fa-solid fa-users text-base"></i>
                </div>
            </div>
        </a>
    </div>

    {{-- Financial Statistics Row --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-5">
        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Kas Masuk</span>
            <p class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100 tabular-nums truncate">Rp {{ number_format($totalKasMasuk, 0, ',', '.') }}</p>
            <p class="mt-0.5 text-[11px] text-emerald-600 dark:text-emerald-400 truncate">Bulan ini: Rp {{ number_format($masukBulanIni, 0, ',', '.') }}</p>
        </div>

        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Kas Keluar</span>
            <p class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100 tabular-nums truncate">Rp {{ number_format($totalKasKeluar, 0, ',', '.') }}</p>
            <p class="mt-0.5 text-[11px] text-rose-600 dark:text-rose-400 truncate">Bulan ini: Rp {{ number_format($keluarBulanIni, 0, ',', '.') }}</p>
        </div>

        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Saldo Akhir</span>
            <p class="mt-1 text-lg font-bold {{ $saldoAkhir >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600' }} tabular-nums truncate">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</p>
            <p class="mt-0.5 text-[11px] text-slate-400">{{ $saldoAkhir >= 0 ? 'Saldo kas aman' : 'Perlu perhatian' }}</p>
        </div>

        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Net Bulan Ini</span>
            <p class="mt-1 text-lg font-bold {{ $netBulanIni >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600' }} tabular-nums truncate">Rp {{ number_format($netBulanIni, 0, ',', '.') }}</p>
            <p class="mt-0.5 text-[11px] text-slate-400">Selisih kas berjalan</p>
        </div>

        <div class="civic-card col-span-2 sm:col-span-1 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Rasio Lunas Tagihan</span>
            @php
                $totalTagihanRatio = max($totalTagihan, 1);
                $lunasPercent = round(($tagihanSudahLunas / $totalTagihanRatio) * 100);
            @endphp
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-lg font-bold text-slate-900 dark:text-slate-100 tabular-nums">{{ $lunasPercent }}%</span>
                <span class="text-[11px] text-slate-400">{{ $tagihanSudahLunas }}/{{ $totalTagihan }} lunas</span>
            </div>
            <div class="mt-2 h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $lunasPercent }}%"></div>
            </div>
        </div>
    </div>

    {{-- Prioritas Hari Ini & Rumah Belum Bayar --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Prioritas Hari Ini</h2>
                    <p class="text-xs text-slate-400">Tindakan mendesak untuk pengurus</p>
                </div>
                <i class="fa-solid fa-list-check text-emerald-600"></i>
            </div>
            <div class="space-y-3">
                <a href="{{ route('tagihan.admin') }}" class="btn-tactile flex items-center justify-between rounded-2xl border border-amber-200/80 bg-amber-50/70 p-3.5 transition hover:bg-amber-100/70 dark:bg-amber-950/30 dark:border-amber-900/50">
                    <div>
                        <p class="text-xs font-bold text-amber-900 dark:text-amber-200">Verifikasi Pembayaran</p>
                        <p class="mt-0.5 text-[11px] text-amber-700 dark:text-amber-300">{{ $pendingTransferCount + $pendingOfflineCount }} pembayaran menunggu keputusan</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-xs text-amber-600"></i>
                </a>
                <a href="{{ route('admin.rumah.index') }}" class="btn-tactile flex items-center justify-between rounded-2xl border border-slate-200/70 bg-slate-50 p-3.5 transition hover:bg-slate-100/80 dark:bg-slate-800/60 dark:border-slate-700/60">
                    <div>
                        <p class="text-xs font-bold text-slate-900 dark:text-slate-200">Rumah Belum Lunas</p>
                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ $rumahBelumBayarCount }} dari {{ $totalRumahAktif }} rumah aktif perlu penagihan</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-xs text-slate-400"></i>
                </a>
                <a href="{{ route('laporan-kas.index') }}" class="btn-tactile flex items-center justify-between rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-3.5 transition hover:bg-emerald-100/70 dark:bg-emerald-950/30 dark:border-emerald-900/50">
                    <div>
                        <p class="text-xs font-bold text-emerald-900 dark:text-emerald-200">Pantau Kas Bulan Ini</p>
                        <p class="mt-0.5 text-[11px] text-emerald-700 dark:text-emerald-300">Net berjalan: Rp {{ number_format($netBulanIni, 0, ',', '.') }}</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-xs text-emerald-600"></i>
                </a>
            </div>
        </div>

        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs xl:col-span-2 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Rumah Belum Bayar Bulan Ini</h2>
                    <p class="text-xs text-slate-400">Diurutkan berdasarkan nominal tagihan terbesar</p>
                </div>
                <a href="{{ route('admin.rumah.index') }}" class="btn-tactile rounded-xl border border-emerald-200/80 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800/40 dark:bg-emerald-950/40 dark:text-emerald-300">Data Rumah</a>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                @forelse(array_slice($rumahBelumBayarBulanIni, 0, 6) as $item)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-900 dark:text-slate-100">{{ $item['rumah_kode'] ?? 'Rumah belum diatur' }}</p>
                                <p class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $item['rumah_alamat'] ?? $item['user_name'] ?? '-' }}</p>
                                <span class="mt-1.5 inline-flex rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-600">{{ $item['status'] }}</span>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">Rp {{ number_format($item['total'], 0, ',', '.') }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">{{ $item['belum_lunas'] }}/{{ $item['jumlah_tagihan'] }} nota</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-emerald-50/70 p-4 text-xs font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 md:col-span-2">Semua rumah aktif sudah lunas untuk bulan ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Grafik Kas & Verifikasi Tagihan --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs xl:col-span-2 dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Grafik Kas {{ $chartData['label'] }}</h2>
                    <p class="text-xs text-slate-400">Perbandingan kas masuk dan keluar {{ $chartMode === 'daily' ? 'per tanggal bulan ini.' : 'per bulan.' }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.dashboard', ['chart' => 'monthly']) }}" class="btn-tactile rounded-xl px-3 py-1.5 text-xs font-bold {{ $chartMode === 'monthly' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">Bulanan</a>
                    <a href="{{ route('admin.dashboard', ['chart' => 'daily']) }}" class="btn-tactile rounded-xl px-3 py-1.5 text-xs font-bold {{ $chartMode === 'daily' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">Harian</a>
                </div>
            </div>
            <div class="relative h-72">
                <canvas id="kasChart"></canvas>
            </div>
        </div>

        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-1 text-sm font-bold text-slate-900 dark:text-slate-100">Tagihan Menunggu Verifikasi</h2>
            <p class="mb-4 text-xs text-slate-400">Pembayaran warga yang menunggu konfirmasi</p>
            <div class="space-y-2.5">
                @forelse($tagihanMenungguVerifikasi as $tagihan)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-800/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-900 dark:text-slate-100">{{ $tagihan['user_name'] }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $tagihan['status_label'] }} &bull; {{ $tagihan['bulan'] }}/{{ $tagihan['tahun'] }}</p>
                            </div>
                            <p class="shrink-0 text-xs font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">Rp {{ number_format($tagihan['total'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-emerald-50/60 p-4 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Tidak ada pembayaran yang menunggu verifikasi.</p>
                @endforelse
            </div>
            <a href="{{ route('tagihan.admin') }}" class="btn-tactile mt-4 inline-flex w-full items-center justify-center gap-1.5 rounded-2xl bg-emerald-700 px-4 py-3 text-xs font-bold text-white hover:bg-emerald-800 shadow-xs">
                <span>Buka Verifikasi Tagihan</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>

    {{-- Tagihan Jatuh Tempo & Kas Keluar Terbesar --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Tagihan Jatuh Tempo</h2>
            <p class="mt-0.5 text-xs text-slate-400">Tagihan yang overdue atau mendekati batas akhir bulan</p>
            <div class="mt-4 space-y-2.5">
                @forelse($tagihanJatuhTempo as $tagihan)
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50/70 p-3.5 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-900 dark:text-slate-100">{{ $tagihan['display_title'] }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ $tagihan['owner_label'] }} &bull; tempo {{ $tagihan['due_date_label'] }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $tagihan['due_status_class'] }}">{{ $tagihan['due_status_label'] }}</span>
                    </div>
                @empty
                    <p class="rounded-2xl bg-emerald-50/60 p-4 text-xs font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Tidak ada tagihan yang mendekati jatuh tempo.</p>
                @endforelse
            </div>
        </div>

        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Kas Keluar Terbesar Bulan Ini</h2>
            <p class="mt-0.5 text-xs text-slate-400">Pengeluaran signifikan yang perlu dipertanggungjawabkan</p>
            <div class="mt-4 space-y-2.5">
                @forelse($kasKeluarTerbesarBulanIni as $item)
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-rose-50/60 p-3.5 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-900/40">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-900 dark:text-slate-100">{{ $item['keterangan'] }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ $item['tanggal_label'] }}</p>
                        </div>
                        <p class="text-xs font-bold text-rose-600 dark:text-rose-400 tabular-nums">Rp {{ number_format($item['jumlah'], 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="rounded-2xl bg-emerald-50/60 p-4 text-xs font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Belum ada kas keluar bulan ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Top 5 Warga & Pengaduan Terbaru --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-1 text-sm font-bold text-slate-900 dark:text-slate-100">Top 5 Warga - Sepanjang Masa</h2>
            <p class="mb-4 text-xs text-slate-400">Warga paling aktif dalam kontribusi iuran lingkungan</p>
            <div class="space-y-2.5">
                @forelse($topWarga as $warga)
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50/70 p-3 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white shadow-xs">{{ $loop->iteration }}</span>
                            <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $warga['name'] }}</p>
                        </div>
                        <p class="shrink-0 text-xs font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">Rp {{ number_format($warga['total_iuran'], 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="py-4 text-center text-xs text-slate-400">Belum ada data transaksi</p>
                @endforelse
            </div>
        </div>

        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-1 text-sm font-bold text-slate-900 dark:text-slate-100">Pengaduan Terbaru</h2>
            <p class="mb-4 text-xs text-slate-400">Aspirasi warga yang masuk ke sistem RT</p>
            <div class="space-y-2.5">
                @forelse($pengaduanTerbaru as $pengaduan)
                    <a href="{{ route('pengaduan.show', $pengaduan['id']) }}" class="block rounded-2xl bg-slate-50/70 p-3 hover:bg-slate-100 dark:bg-slate-800/60 dark:border-slate-800 border border-slate-100 transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-900 dark:text-slate-100">{{ $pengaduan['judul'] }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $pengaduan['user_name'] ?? 'Warga' }} &bull; {{ $pengaduan['kategori'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $pengaduan['status'] === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-200' : ($pengaduan['status'] === 'proses' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-200' : 'bg-slate-100 text-slate-600') }}">
                                {{ $pengaduan['status'] }}
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="rounded-2xl bg-emerald-50/60 p-4 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">Belum ada pengaduan terbaru.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Transaksi Terbaru --}}
    <div class="civic-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-4 text-sm font-bold text-slate-900 dark:text-slate-100">Transaksi Kas Terbaru</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold">
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Warga</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($transaksiTerbaru as $transaksi)
                        <tr class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3 text-slate-500">{{ \Carbon\Carbon::parse($transaksi['tanggal'])->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-bold text-slate-800 dark:text-slate-200">{{ $transaksi['user_name'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $transaksi['keterangan'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">Rp {{ number_format($transaksi['jumlah'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-400">Belum ada transaksi</td>
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
                    backgroundColor: 'rgba(16, 185, 129, 0.10)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'Kas Keluar',
                    data: {!! json_encode($chartData['keluarData']) !!},
                    borderColor: '#f43f5e',
                    backgroundColor: 'rgba(244, 63, 94, 0.10)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: '#f43f5e',
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
                        padding: 16,
                        font: { size: 11, weight: 'bold' }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        font: { size: 10 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });
</script>
@endsection
