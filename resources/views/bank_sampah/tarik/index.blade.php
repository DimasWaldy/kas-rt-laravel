@extends('layouts.app')

@section('title', 'Penarikan Bank Sampah')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Bank Sampah RW</p>
            <h1 class="text-2xl font-black text-slate-900">Penarikan Saldo</h1>
        </div>
        <a href="{{ route('penarikan-sampah.create') }}" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Ajukan Penarikan</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Warga</th>
                        <th class="px-4 py-3">Jumlah</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($penarikans as $penarikan)
                        <tr>
                            <td class="px-4 py-3 font-bold text-slate-800">{{ $penarikan->warga->name }}</td>
                            <td class="px-4 py-3 font-black text-slate-900">Rp {{ number_format($penarikan->jumlah, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $penarikan->status_label }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $penarikan->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                @if($canManage && $penarikan->status === 'menunggu')
                                    <form method="POST" action="{{ route('penarikan-sampah.konfirmasi', $penarikan) }}" class="flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input name="catatan_petugas" placeholder="Catatan" class="w-40 rounded-lg border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
                                        <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-800">Konfirmasi</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada penarikan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($penarikans->hasPages())
        <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $penarikans->links() }}</div>
    @endif
</div>
@endsection
