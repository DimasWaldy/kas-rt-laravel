@extends('layouts.app')

@section('title', 'Iuran Bulanan')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-3xl shadow-sm p-6 border-l-8 border-cyan-500">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Iuran Bulanan</h2>
                <p class="text-slate-500 mt-2">Daftar item iuran untuk bulan dan tahun tertentu.</p>
            </div>
            <a href="{{ route('iuran-bulanan.create') }}" class="inline-flex items-center rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white hover:bg-cyan-700 transition">
                <i class="fa-solid fa-plus mr-2"></i> Tambah Iuran
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm p-6 border border-slate-200 overflow-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Keterangan</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Jumlah</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Bulan</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tahun</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($items as $item)
                    <tr>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">{{ $item->nama }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">{{ $item->keterangan ?? '-' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">{{ $item->bulan }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">{{ $item->tahun }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada iuran bulanan untuk ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
