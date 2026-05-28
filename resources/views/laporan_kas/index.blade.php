@extends('layouts.app')

@section('title', 'Laporan Kas')

@section('content')
@php
    $namaBulan = \Carbon\Carbon::create(null, $bulan)->translatedFormat('F');
@endphp

<div class="space-y-6">
    <div class="rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-100">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Laporan Kas</p>
                <h1 class="mt-2 text-2xl font-black md:text-3xl">{{ $namaBulan }} {{ $tahun }}</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50">
                    Ringkasan kas masuk, kas keluar, dan saldo periode berdasarkan bulan yang dipilih.
                </p>
            </div>

            <form method="GET" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                <select name="bulan" class="rounded-2xl border-0 bg-white px-4 py-3 text-sm font-bold text-emerald-900 focus:ring-2 focus:ring-emerald-200">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
                <select name="tahun" class="rounded-2xl border-0 bg-white px-4 py-3 text-sm font-bold text-emerald-900 focus:ring-2 focus:ring-emerald-200">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button class="rounded-2xl bg-emerald-950 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-950">
                    Filter
                </button>
            </form>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Kas Masuk</p>
            <p class="mt-3 text-2xl font-black text-emerald-700">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $kasMasuk->count() }} transaksi pemasukan.</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Kas Keluar</p>
            <p class="mt-3 text-2xl font-black text-rose-600">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $kasKeluar->count() }} transaksi pengeluaran.</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Saldo Periode</p>
            <p class="mt-3 text-2xl font-black {{ $saldoPeriode >= 0 ? 'text-slate-900' : 'text-rose-600' }}">Rp {{ number_format($saldoPeriode, 0, ',', '.') }}</p>
            <p class="mt-1 text-sm text-slate-500">Kas masuk dikurangi kas keluar.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-lg font-black text-slate-900">Riwayat Transaksi Periode Ini</h2>
            <p class="mt-1 text-sm text-slate-500">Gabungan pemasukan dan pengeluaran berdasarkan tanggal terbaru.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-emerald-50">
                    <tr>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Tanggal</th>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Tipe</th>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Keterangan</th>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Pencatat</th>
                        <th class="px-5 py-4 text-right text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($riwayat as $item)
                        <tr class="hover:bg-emerald-50/60">
                            <td class="px-5 py-4 font-semibold text-slate-600">{{ \Carbon\Carbon::parse($item['tanggal'])->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $item['tipe'] === 'Masuk' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $item['tipe'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-800">{{ $item['keterangan'] }}</td>
                            <td class="px-5 py-4 text-slate-500">{{ $item['user'] }}</td>
                            <td class="px-5 py-4 text-right font-black {{ $item['tipe'] === 'Masuk' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $item['tipe'] === 'Masuk' ? '+' : '-' }} Rp {{ number_format($item['jumlah'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center font-bold text-slate-400">Belum ada transaksi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
