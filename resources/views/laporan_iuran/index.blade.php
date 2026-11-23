@extends('layouts.app')

@section('title', 'Laporan Iuran Warga')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-teal-100 px-2.5 py-1 text-xs font-black text-teal-800 dark:bg-teal-950/60 dark:text-teal-300">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Keuangan Lingkungan
                </span>
                <span class="text-xs font-bold text-slate-400">•</span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ $rtLabel }}</span>
            </div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">Laporan Iuran Warga</h1>
            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Rekapitulasi target tagihan, penerimaan iuran warga, dan pemantauan tunggakan per rumah.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('laporan-kas.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80">
                <i class="fa-solid fa-chart-pie text-emerald-600"></i>
                Buku Kas Umum
            </a>

            @if(auth()->user()->hasPermission('export-finance') || auth()->user()->canManageFinance())
                <a href="{{ route('laporan-iuran.export', request()->query()) }}" target="_blank" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-teal-800">
                    <i class="fa-solid fa-print"></i>
                    Ekspor / Cetak Laporan
                </a>
            @endif
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('laporan-iuran.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
            <div>
                <label for="filter_bulan" class="block text-xs font-bold text-slate-600 dark:text-slate-400">Bulan</label>
                <select id="filter_bulan" name="bulan" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-teal-600 focus:ring-1 focus:ring-teal-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @foreach($bulanList as $m => $namaBulan)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>{{ $namaBulan }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter_tahun" class="block text-xs font-bold text-slate-600 dark:text-slate-400">Tahun</label>
                <select id="filter_tahun" name="tahun" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-teal-600 focus:ring-1 focus:ring-teal-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @foreach($tahunList as $y)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter_status" class="block text-xs font-bold text-slate-600 dark:text-slate-400">Status Pembayaran</label>
                <select id="filter_status" name="status" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-teal-600 focus:ring-1 focus:ring-teal-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="semua" {{ $statusFilter === 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="lunas" {{ $statusFilter === 'lunas' ? 'selected' : '' }}>Lunas (Terbayar)</option>
                    <option value="belum_bayar" {{ $statusFilter === 'belum_bayar' ? 'selected' : '' }}>Belum Lunas (Tunggakan)</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                </select>
            </div>

            <div>
                <label for="filter_rumah" class="block text-xs font-bold text-slate-600 dark:text-slate-400">Rumah / Alamat</label>
                <select id="filter_rumah" name="rumah_id" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-teal-600 focus:ring-1 focus:ring-teal-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="">Semua Rumah</option>
                    @foreach($rumahList as $r)
                        <option value="{{ $r->id }}" {{ $rumahId == $r->id ? 'selected' : '' }}>{{ $r->kode_rumah }} - {{ $r->alamat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-teal-800">
                    <i class="fa-solid fa-filter"></i> Terapkan
                </button>
                <a href="{{ route('laporan-iuran.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Target Tagihan ({{ $periodeNama }})</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">Rp {{ number_format($totalNominal, 0, ',', '.') }}</p>
            <p class="mt-1 text-2xs font-semibold text-slate-500 dark:text-slate-400">{{ $totalTagihan }} tagihan rumah tercatat</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Iuran Terbayar (Lunas)</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-emerald-700 dark:text-emerald-400">Rp {{ number_format($totalLunas, 0, ',', '.') }}</p>
            <p class="mt-1 text-2xs font-semibold text-emerald-600 dark:text-emerald-500">{{ $countLunas }} dari {{ $totalTagihan }} warga lunas</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tunggakan Belum Bayar</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-rose-600 dark:text-rose-400">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</p>
            <p class="mt-1 text-2xs font-semibold text-rose-500">{{ $countBelumLunas }} warga belum melunasi</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Kepatuhan Warga</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-teal-700 dark:text-teal-400">{{ $persentaseLunas }}%</p>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div class="h-full rounded-full bg-teal-600" style="width: {{ min(100, $persentaseLunas) }}%"></div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-100 p-5 dark:border-slate-800">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Daftar Tagihan Iuran Periode {{ $periodeNama }}</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Menampilkan {{ $tagihans->total() }} catatan pembayaran warga</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3.5">Rumah / Alamat</th>
                        <th class="px-5 py-3.5">Nama Warga / KK</th>
                        <th class="px-5 py-3.5">Judul Iuran</th>
                        <th class="px-5 py-3.5 text-right">Nominal</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Tanggal Bayar</th>
                        <th class="px-5 py-3.5">Metode Bayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($tagihans as $tagihan)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">
                                {{ $tagihan->rumah?->kode_rumah ?? '-' }}
                                <span class="block text-2xs font-normal text-slate-400">{{ $tagihan->rumah?->alamat ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $tagihan->user?->name ?? 'Belum Ditautkan' }}
                                @if($tagihan->rt)
                                    <span class="block text-2xs text-slate-400">RT {{ $tagihan->rt->name }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs font-medium">
                                {{ $tagihan->judul }}
                            </td>
                            <td class="px-5 py-4 text-right font-bold text-slate-900 dark:text-slate-100">
                                Rp {{ number_format($tagihan->total, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4">
                                @if($tagihan->status === 'lunas')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-2xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <i class="fa-solid fa-check"></i> Lunas
                                    </span>
                                @elseif(in_array($tagihan->status, ['pending_transfer', 'pending_offline']))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-2xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                        <i class="fa-solid fa-clock"></i> Menunggu Verifikasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-2xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        <i class="fa-solid fa-xmark"></i> Belum Bayar
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs">
                                {{ $tagihan->paid_at ? $tagihan->paid_at->translatedFormat('d M Y') : '-' }}
                            </td>
                            <td class="px-5 py-4 text-xs">
                                @if($tagihan->payment_method)
                                    <span class="font-semibold uppercase text-slate-700 dark:text-slate-300">{{ $tagihan->payment_method }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-500">
                                Tidak ada data tagihan iuran pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tagihans->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                {{ $tagihans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
