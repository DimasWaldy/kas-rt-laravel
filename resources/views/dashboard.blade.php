@extends('layouts.app')

@section('title', 'Ringkasan Statistik')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .font-inter {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
</style>
@endpush

@section('content')
<div class="font-inter">

{{-- =============================================
     SECTION 0: STATUS PERSONAL (KHUSUS WARGA)
     ============================================= --}}
@if(!auth()->user()->isAdmin())
<div class="mb-6">
    <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl p-6 text-white shadow-lg shadow-indigo-100">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-indigo-100 text-xs font-medium uppercase tracking-wider mb-1">Status Iuran Anda</p>
                <h2 class="text-2xl font-bold">{{ now()->translatedFormat('F Y') }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-4 py-2">
                    <p class="text-[10px] text-indigo-100 leading-none mb-1">Status Saat Ini</p>
                    <p class="text-sm font-bold capitalize">{{ str_replace('_', ' ', $userStatus ?? 'Belum Ada Tagihan') }}</p>
                </div>
                <a href="{{ route('tagihan.index') }}" class="bg-white text-indigo-600 hover:bg-indigo-50 px-5 py-2.5 rounded-xl text-sm font-bold transition-colors">
                    Detail Tagihan
                </a>
            </div>
        </div>
    </div>
</div>
@endif

{{-- =============================================
     SECTION 1: KEUANGAN KESELURUHAN
     ============================================= --}}
<div class="mb-2">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Keuangan keseluruhan</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

    {{-- Kas Masuk --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-slate-500 mb-1">Total kas masuk</p>
                <p class="text-2xl font-semibold text-slate-900 truncate">
                    Rp {{ number_format($kasMasuk ?? 0, 0, ',', '.') }}
                </p>
                <p class="text-xs text-slate-400 mt-1">Sepanjang waktu</p>
            </div>
            <div class="bg-green-50 p-2.5 rounded-xl flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-slate-100">
            <span class="inline-flex items-center gap-1 text-xs font-medium bg-green-50 text-green-700 px-2.5 py-1 rounded-full">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/></svg>
                Bulan ini: Rp {{ number_format($monthlyRevenue ?? 0, 0, ',', '.') }}
            </span>
        </div>
    </div>

    {{-- Kas Keluar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-slate-500 mb-1">Total kas keluar</p>
                <p class="text-2xl font-semibold text-slate-900 truncate">
                    Rp {{ number_format($kasKeluar ?? 0, 0, ',', '.') }}
                </p>
                <p class="text-xs text-slate-400 mt-1">Sepanjang waktu</p>
            </div>
            <div class="bg-red-50 p-2.5 rounded-xl flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.306-4.307a11.95 11.95 0 015.814 5.519l2.74 1.22m0 0l-5.94 2.28m5.94-2.28l-2.28-5.941"/></svg>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-slate-100">
            <span class="inline-flex items-center gap-1 text-xs font-medium bg-red-50 text-red-700 px-2.5 py-1 rounded-full">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3"/></svg>
                Pengeluaran tercatat
            </span>
        </div>
    </div>

    {{-- Saldo Bersih --}}
    @php
        $saldo = ($kasMasuk ?? 0) - ($kasKeluar ?? 0);
        $saldoPositif = $saldo >= 0;
    @endphp
    <div class="bg-white rounded-2xl border p-5 {{ $saldoPositif ? 'border-slate-200' : 'border-red-200' }}">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-slate-500 mb-1">Saldo bersih</p>
                <p class="text-2xl font-semibold truncate {{ $saldoPositif ? 'text-green-700' : 'text-red-600' }}">
                    Rp {{ number_format(abs($saldo), 0, ',', '.') }}
                </p>
                <p class="text-xs text-slate-400 mt-1">Kas masuk − kas keluar</p>
            </div>
            <div class="{{ $saldoPositif ? 'bg-blue-50' : 'bg-red-50' }} p-2.5 rounded-xl flex-shrink-0">
                <svg class="w-5 h-5 {{ $saldoPositif ? 'text-blue-500' : 'text-red-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z"/></svg>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-slate-100">
            @if($saldoPositif)
                <span class="inline-flex items-center gap-1 text-xs font-medium bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    Saldo positif
                </span>
            @else
                <span class="inline-flex items-center gap-1 text-xs font-medium bg-red-50 text-red-700 px-2.5 py-1 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    Saldo defisit
                </span>
            @endif
        </div>
    </div>

</div>

{{-- =============================================
     SECTION 2: DATA WARGA
     ============================================= --}}
<div class="mb-2">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Data warga</p>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="bg-indigo-50 w-9 h-9 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </div>
        <p class="text-xs font-medium text-slate-500">Warga RT</p>
        <p class="text-2xl font-semibold text-slate-900 mt-0.5">{{ $totalWarga ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">orang terdaftar</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="bg-amber-50 w-9 h-9 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/></svg>
        </div>
        <p class="text-xs font-medium text-slate-500">Rumah terdaftar</p>
        <p class="text-2xl font-semibold text-slate-900 mt-0.5">{{ $totalKK ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">unit hunian</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="bg-emerald-50 w-9 h-9 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
        </div>
        <p class="text-xs font-medium text-slate-500">Rumah aktif</p>
        <p class="text-2xl font-semibold text-slate-900 mt-0.5">{{ $kepalaKeluargaAktif ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">dari {{ $totalKK ?? 0 }} rumah</p>
    </div>

    {{-- Card Belum Bayar - border merah kalau ada tunggakan --}}
    <div class="bg-white rounded-2xl border p-5 {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'border-red-200' : 'border-slate-200' }}">
        <div class="{{ ($totalUnpaidTagihan ?? 0) > 0 ? 'bg-red-50' : 'bg-slate-50' }} w-9 h-9 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-red-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5"/></svg>
        </div>
        <p class="text-xs font-medium text-slate-500">Belum bayar</p>
        <p class="text-2xl font-semibold mt-0.5 {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $totalUnpaidTagihan ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">tagihan tertunggak</p>
    </div>

</div>

{{-- =============================================
     SECTION 3: DETAIL BULAN INI + AKTIVITAS
     ============================================= --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">

    {{-- Kolom kiri: ringkasan bulan ini + top rumah --}}
    <div class="flex flex-col gap-6">

        {{-- Ringkasan bulan ini + alert pending --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold text-slate-800">Ringkasan bulan ini</p>
                <span class="text-xs font-medium bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">
                    {{ now()->translatedFormat('F Y') }}
                </span>
            </div>

            <div class="space-y-3 mb-4">
                <div>
                    <div class="flex justify-between items-center text-xs mb-1">
                        <span class="text-slate-500">Pendapatan masuk</span>
                        <span class="font-medium text-slate-800">Rp {{ number_format($monthlyRevenue ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        @php
                            $maxVal = max($monthlyRevenue ?? 0, 1);
                            $pctMasuk = min(100, round((($monthlyRevenue ?? 0) / $maxVal) * 100));
                        @endphp
                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $pctMasuk }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center text-xs mb-1">
                        <span class="text-slate-500">Tagihan lunas</span>
                        <span class="font-medium text-slate-800">{{ $totalPaidTagihan ?? 0 }} tagihan</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        @php
                            $totalTagihan = ($totalPaidTagihan ?? 0) + ($totalUnpaidTagihan ?? 0);
                            $pctLunas = $totalTagihan > 0 ? min(100, round((($totalPaidTagihan ?? 0) / $totalTagihan) * 100)) : 0;
                        @endphp
                        <div class="h-full bg-blue-500 rounded-full" style="width: {{ $pctLunas }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center text-xs mb-1">
                        <span class="text-slate-500">Tagihan belum bayar</span>
                        <span class="font-medium {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-red-600' : 'text-slate-800' }}">{{ $totalUnpaidTagihan ?? 0 }} tagihan</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        @php
                            $pctUnpaid = $totalTagihan > 0 ? min(100, round((($totalUnpaidTagihan ?? 0) / $totalTagihan) * 100)) : 0;
                        @endphp
                        <div class="h-full bg-red-400 rounded-full" style="width: {{ $pctUnpaid }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Alert bukti transfer pending --}}
            @if(auth()->user()->isAdmin() && ($pendingTransferCount ?? 0) > 0)
                <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-3">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-xs font-semibold text-amber-800">{{ $pendingTransferCount }} bukti transfer menunggu verifikasi</p>
                        <p class="text-xs text-amber-700 mt-0.5">Segera tindaklanjuti agar pembayaran warga tercatat.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Top 5 rumah --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-sm font-semibold text-slate-800 mb-4">Top 5 Rumah - total iuran</p>

            @forelse($topKKIuran as $topKK)
                <div class="flex items-center justify-between gap-3 py-2.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0
                            {{ $loop->first ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $loop->iteration }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $topKK->kepala_keluarga }}</p>
                            <p class="text-xs text-slate-400">Rumah {{ $topKK->no_kk }}</p>
                        </div>
                    </div>
                    <p class="text-sm font-semibold text-slate-900 flex-shrink-0">
                        Rp {{ number_format($topKK->total_iuran, 0, ',', '.') }}
                    </p>
                </div>
            @empty
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400">Belum ada data iuran.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Kolom kanan: grafik + log aktivitas --}}
    <div class="flex flex-col gap-6">

        {{-- Chart tren pembayaran --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-semibold text-slate-800">Tren pembayaran</p>
                    <p class="text-xs text-slate-400 mt-0.5">Kas masuk & keluar per hari</p>
                </div>
                <span class="text-xs font-medium bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">Bulan ini</span>
            </div>
            <div class="h-56">
                <canvas id="kasChart" class="w-full h-full"></canvas>
            </div>
        </div>

        @if(auth()->user()->isAdmin())
            {{-- Aktivitas terakhir --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="text-sm font-semibold text-slate-800 mb-4">Aktivitas terakhir</p>

                @forelse($recentAuditLogs as $log)
                    <div class="py-2.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-slate-800 leading-snug">{{ $log->event }}</p>
                            <span class="text-xs text-slate-400 flex-shrink-0 mt-0.5">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        @if($log->notes)
                            <p class="text-xs text-slate-500 mt-1">{{ $log->notes }}</p>
                        @endif
                        <p class="text-xs text-slate-400 mt-1">Oleh: {{ $log->user?->name ?? 'Sistem' }}</p>
                    </div>
                @empty
                    <div class="text-center py-6">
                        <p class="text-sm text-slate-400">Belum ada aktivitas tercatat.</p>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Tampilan Alternatif untuk Warga: Panduan Pembayaran --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-slate-800">Panduan Pembayaran</p>
                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>

                <div class="space-y-4">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-700 mb-1">Metode Transfer</p>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Silakan transfer ke rekening <strong>BCA 1234-567-890</strong> a/n Bendahara RT. Pastikan simpan bukti transfer untuk diunggah di menu Tagihan.
                        </p>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <p class="text-xs font-bold text-slate-700 mb-1">Metode Offline</p>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Pembayaran tunai dapat diserahkan langsung kepada Bendahara atau Ketua RT pada jam kerja pengurus (19:00 - 21:00).
                        </p>
                    </div>

                    <a href="{{ route('tagihan.index') }}" class="flex items-center justify-center gap-2 w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-indigo-100">
                        <span>Cek Tagihan Sekarang</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>

{{-- =============================================
     CHART.JS
     ============================================= --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const ctx = document.getElementById('kasChart');
    if (!ctx) return;

    const tanggal  = @json($tanggal ?? []);
    const masuk    = @json($dataMasuk ?? []);
    const keluar   = @json($dataKeluar ?? []);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: tanggal,
            datasets: [
                {
                    label: 'Kas Masuk',
                    data: masuk,
                    backgroundColor: '#22c55e',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.65,
                    categoryPercentage: 0.55
                },
                {
                    label: 'Kas Keluar',
                    data: keluar,
                    backgroundColor: '#f87171',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.65,
                    categoryPercentage: 0.55
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
                        pointStyle: 'circle',
                        font: { size: 11, weight: '500' },
                        color: '#64748b',
                        padding: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const val = context.parsed.y ?? 0;
                            return ' Rp ' + val.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        font: { size: 10 },
                        color: '#94a3b8'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    border: { display: false, dash: [4, 4] },
                    ticks: {
                        callback: function (value) {
                            if (value === 0) return 'Rp 0';
                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        font: { size: 10 },
                        color: '#94a3b8',
                        maxTicksLimit: 5
                    }
                }
            }
        }
    });
})();
</script>

</div>
@endsection
