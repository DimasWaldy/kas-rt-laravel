@extends('layouts.app')

@section('title', 'Laporan Kas')

@section('content')
<style>
    @media print {
        aside, header, .no-print { display: none !important; }
        main { padding: 0 !important; }
        body { background: #fff !important; }
        .print-sheet { border: 0 !important; box-shadow: none !important; border-radius: 0 !important; }
        .print-break { page-break-inside: avoid; }
    }
</style>

<div class="space-y-6">
    <div class="no-print rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-100">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Laporan Kas</p>
                <h1 class="mt-2 text-2xl font-black md:text-3xl">{{ $periodeLabel }}</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50">
                    Laporan formal kas RT dengan saldo awal, mutasi kas, saldo akhir, dan kategori transaksi.
                </p>
            </div>

            <button type="button" onclick="window.print()" class="rounded-2xl bg-white px-5 py-3 text-sm font-black text-emerald-800 shadow-sm transition hover:bg-emerald-50">
                <i class="fa-solid fa-print mr-2"></i>
                Cetak
            </button>
        </div>
    </div>

    <div class="no-print rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
        <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto_auto] lg:items-end">
            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai->toDateString() }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-emerald-600 focus:ring-emerald-200">
            </div>
            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai->toDateString() }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-emerald-600 focus:ring-emerald-200">
            </div>
            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">Kategori</label>
                <select name="kategori" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-emerald-600 focus:ring-emerald-200">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriOptions as $kategori)
                        <option value="{{ $kategori }}" {{ $kategoriFilter === $kategori ? 'selected' : '' }}>{{ $kategori }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-2xl bg-emerald-800 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-900">
                Filter
            </button>
            <a href="{{ route('laporan-kas.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-center text-sm font-black text-slate-600 transition hover:bg-slate-50">
                Reset
            </a>
        </form>
    </div>

    <div class="print-sheet rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
        <div class="mb-6 border-b border-slate-200 pb-5 text-center">
            <p class="text-xs font-black uppercase tracking-[0.3em] text-emerald-700">Kas RT</p>
            <h2 class="mt-2 text-2xl font-black text-slate-900">Laporan Kas RT</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">Periode {{ $periodeLabel }}</p>
            @if($kategoriFilter)
                <p class="mt-1 text-xs font-bold text-emerald-700">Kategori: {{ $kategoriFilter }}</p>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="print-break rounded-2xl border border-slate-100 bg-slate-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Saldo Awal</p>
                <p class="mt-3 text-xl font-black text-slate-900">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</p>
            </div>
            <div class="print-break rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Kas Masuk</p>
                <p class="mt-3 text-xl font-black text-emerald-700">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</p>
            </div>
            <div class="print-break rounded-2xl border border-rose-100 bg-rose-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-rose-700">Kas Keluar</p>
                <p class="mt-3 text-xl font-black text-rose-600">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</p>
            </div>
            <div class="print-break rounded-2xl border border-emerald-100 bg-white p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Saldo Akhir</p>
                <p class="mt-3 text-xl font-black {{ $saldoAkhir >= 0 ? 'text-slate-900' : 'text-rose-600' }}">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[0.85fr_1.4fr]">
            <section class="print-break overflow-hidden rounded-3xl border border-emerald-100">
                <div class="border-b border-slate-100 bg-emerald-50 p-4">
                    <h3 class="font-black text-slate-900">Ringkasan Kategori</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3 text-right">Masuk</th>
                                <th class="px-4 py-3 text-right">Keluar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($ringkasanKategori as $row)
                                <tr>
                                    <td class="px-4 py-3 font-bold text-slate-700">{{ $row['kategori'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-emerald-700">Rp {{ number_format($row['masuk'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-rose-600">Rp {{ number_format($row['keluar'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-slate-400">Belum ada transaksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-emerald-100">
                <div class="border-b border-slate-100 bg-emerald-50 p-4">
                    <h3 class="font-black text-slate-900">Buku Kas Periode</h3>
                    <p class="mt-1 text-xs text-slate-500">Debit untuk kas masuk, kredit untuk kas keluar.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Keterangan</th>
                                <th class="px-4 py-3 text-right">Debit</th>
                                <th class="px-4 py-3 text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($riwayat as $item)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-600">{{ \Carbon\Carbon::parse($item['tanggal'])->translatedFormat('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $item['kategori'] }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-slate-800">{{ $item['keterangan'] }}</p>
                                        <p class="text-xs text-slate-400">Pencatat: {{ $item['user'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-emerald-700">
                                        {{ $item['debit'] ? 'Rp ' . number_format($item['debit'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-rose-600">
                                        {{ $item['kredit'] ? 'Rp ' . number_format($item['kredit'], 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center font-bold text-slate-400">Belum ada transaksi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-50 font-black text-slate-800">
                            <tr>
                                <td colspan="3" class="px-4 py-4">Total Periode</td>
                                <td class="px-4 py-4 text-right text-emerald-700">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-right text-rose-600">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 p-5">
                <p class="text-sm font-bold text-slate-600">Catatan Bendahara</p>
                <div class="mt-10 border-b border-dashed border-slate-300"></div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Mengetahui, Pengurus RT</p>
                <div class="mx-auto mt-16 w-48 border-b border-slate-300"></div>
            </div>
        </div>
    </div>
</div>
@endsection
