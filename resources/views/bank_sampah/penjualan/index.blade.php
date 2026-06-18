@extends('layouts.app')

@section('title', 'Penjualan Sampah ke Pengepul')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Kas Bank Sampah RW</p>
            <h1 class="text-2xl font-black text-slate-900">Penjualan Sampah ke Pengepul</h1>
            <p class="mt-1 text-sm text-slate-500">Catat uang masuk dari sampah terkumpul yang dijual ke pengepul.</p>
        </div>

        <a href="{{ route('penjualan-sampah.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">
            <i class="fa-solid fa-plus"></i> Catat Penjualan
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Kas Bank Sampah</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">Rp {{ number_format($totalKas, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Penjualan Bulan Ini</p>
            <p class="mt-2 text-3xl font-black text-slate-900">Rp {{ number_format($totalBulanIni, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Jenis Sampah</th>
                        <th class="px-4 py-3">Berat</th>
                        <th class="px-4 py-3">Harga Jual</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Pengepul</th>
                        <th class="px-4 py-3">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($penjualans as $penjualan)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $penjualan->tanggal_jual->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $penjualan->jenisSampah->nama }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ number_format($penjualan->berat_total, 2, ',', '.') }} {{ $penjualan->jenisSampah->satuan_label }}</td>
                            <td class="px-4 py-3 text-slate-600">Rp {{ number_format($penjualan->harga_jual, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 font-black text-emerald-700">Rp {{ number_format($penjualan->total, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $penjualan->nama_pengepul ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $penjualan->petugas->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada penjualan sampah ke pengepul.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($penjualans->hasPages())
        <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $penjualans->links() }}</div>
    @endif
</div>
@endsection
