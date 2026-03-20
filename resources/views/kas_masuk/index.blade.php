@extends('layouts.app')

@section('title', 'Data Kas Masuk')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-extrabold text-slate-800 flex items-center">
        <span class="bg-green-100 text-green-600 p-2 rounded-lg mr-3">
            <i class="fa-solid fa-shield-check"></i>
        </span>
        Transparansi Kas Masuk
    </h1>
    <p class="text-sm text-slate-500 mt-1 italic group">
        <i class="fa-solid fa-lock text-xs mr-1"></i> Data yang sudah masuk tidak dapat diubah/dihapus demi menjaga kepercayaan warga.
    </p>
</div>

<div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Tahun</label>
            <select name="tahun" class="bg-slate-50 border-none text-slate-700 py-3 px-4 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="">Semua Tahun</option>
                @for($i = date('Y'); $i >= 2024; $i--)
                    <option value="{{ $i }}" {{ request('tahun') == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Bulan</label>
            <select name="bulan" class="bg-slate-50 border-none text-slate-700 py-3 px-4 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="">Semua Bulan</option>
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Urutan</label>
            <select name="filter" class="bg-slate-50 border-none text-slate-700 py-3 px-4 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="terbaru" {{ request('filter') == 'terbaru' ? 'selected' : '' }}>📅 Terbaru</option>
                <option value="terlama" {{ request('filter') == 'terlama' ? 'selected' : '' }}>⏳ Terlama</option>
                <option value="terbesar" {{ request('filter') == 'terbesar' ? 'selected' : '' }}>💰 Terbesar</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white py-3 rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95">
                <i class="fa-solid fa-magnifying-glass mr-2"></i> Cari
            </button>
            <a href="/kas-masuk/create" class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-xl transition-all shadow-lg shadow-blue-200 active:scale-95" title="Tambah Data">
                <i class="fa-solid fa-plus"></i>
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-6 py-5 text-[10px] uppercase tracking-[0.2em] font-extrabold text-slate-400">Penyetor</th>
                    <th class="px-6 py-5 text-[10px] uppercase tracking-[0.2em] font-extrabold text-slate-400 text-center">Keterangan</th>
                    <th class="px-6 py-5 text-[10px] uppercase tracking-[0.2em] font-extrabold text-slate-400 text-center">Jumlah Pemasukan</th>
                    <th class="px-6 py-5 text-[10px] uppercase tracking-[0.2em] font-extrabold text-slate-400 text-center">Tanggal Transaksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($data as $item)
                <tr class="hover:bg-slate-50/80 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-md shadow-blue-100">
                                {{ substr($item->user->name ?? '?', 0, 1) }}
                            </div>
                            <span class="font-bold text-slate-700 text-sm">{{ $item->user->name ?? 'Anonim' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full uppercase tracking-tighter">
                            {{ $item->keterangan }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-extrabold text-green-600">
                            Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="inline-flex items-center gap-2 text-slate-500 text-xs font-bold bg-gray-50 px-3 py-1.5 rounded-lg border border-slate-100">
                            <i class="fa-regular fa-calendar text-blue-500"></i>
                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center">
                            <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mb-4">
                                <i class="fa-solid fa-box-open text-slate-300 text-3xl"></i>
                            </div>
                            <p class="text-slate-400 font-bold">Data tidak ditemukan untuk periode ini.</p>
                            <a href="/kas-masuk" class="text-blue-500 text-xs mt-2 font-bold hover:underline">Reset Filter</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection