@extends('layouts.app')

@section('title', 'Setoran Sampah')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Bank Sampah RW</p>
            <h1 class="text-2xl font-black text-slate-900">Setoran Sampah</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $canManage ? 'Verifikasi setoran warga dalam RW.' : 'Pantau riwayat setoran sampah Anda.' }}</p>
        </div>
        <a href="{{ route('setoran-sampah.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">
            <i class="fa-solid fa-plus"></i> Ajukan Setoran
        </a>
    </div>

    <form method="GET" action="{{ route('setoran-sampah.index') }}" class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @foreach(['' => 'Semua', 'menunggu' => 'Menunggu', 'diverifikasi' => 'Diverifikasi', 'ditolak' => 'Ditolak'] as $value => $label)
            <button type="submit" name="status" value="{{ $value }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === $value ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Warga</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Estimasi</th>
                        <th class="px-4 py-3">Aktual</th>
                        <th class="px-4 py-3">Nilai</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($setorans as $setoran)
                        <tr x-data="{ verifyOpen: false, rejectOpen: false }">
                            <td class="px-4 py-3">
                                <p class="font-bold text-slate-800">{{ $setoran->warga->name }}</p>
                                <p class="text-xs text-slate-500">{{ $setoran->tanggal_setor->translatedFormat('d M Y') }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $setoran->jenisSampah->nama }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ number_format($setoran->estimasi_berat, 2, ',', '.') }} {{ $setoran->jenisSampah->satuan_label }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $setoran->berat_aktual ? number_format($setoran->berat_aktual, 2, ',', '.') . ' ' . $setoran->jenisSampah->satuan_label : '-' }}</td>
                            <td class="px-4 py-3 font-bold text-slate-800">Rp {{ number_format($setoran->nilai, 0, ',', '.') }}</td>
                            <td class="px-4 py-3"><span class="rounded-full border px-3 py-1 text-xs font-bold {{ $setoran->status_color }}">{{ $setoran->status_label }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('setoran-sampah.show', $setoran) }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200">Detail</a>
                                    @if($canManage && $setoran->status === 'menunggu')
                                        <button type="button" x-on:click="verifyOpen = true" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-800">Verifikasi</button>
                                        <button type="button" x-on:click="rejectOpen = true" class="rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100">Tolak</button>
                                    @endif
                                </div>

                                @if($canManage && $setoran->status === 'menunggu')
                                    <div x-cloak x-show="verifyOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                                        <form method="POST" action="{{ route('setoran-sampah.verifikasi', $setoran) }}" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                                            @csrf
                                            @method('PATCH')
                                            <h3 class="text-lg font-black text-slate-900">Verifikasi Setoran</h3>
                                            <label class="mt-4 block text-sm font-bold text-slate-700">Berat Aktual</label>
                                            <input type="number" step="0.01" min="0.1" name="berat_aktual" required class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                            <label class="mt-4 block text-sm font-bold text-slate-700">Catatan</label>
                                            <textarea name="catatan_petugas" rows="3" class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                                            <div class="mt-5 flex justify-end gap-2">
                                                <button type="button" x-on:click="verifyOpen = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Batal</button>
                                                <button class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-bold text-white">Simpan</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div x-cloak x-show="rejectOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                                        <form method="POST" action="{{ route('setoran-sampah.tolak', $setoran) }}" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                                            @csrf
                                            @method('PATCH')
                                            <h3 class="text-lg font-black text-slate-900">Tolak Setoran</h3>
                                            <label class="mt-4 block text-sm font-bold text-slate-700">Alasan Penolakan</label>
                                            <textarea name="catatan_petugas" rows="4" required class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"></textarea>
                                            <div class="mt-5 flex justify-end gap-2">
                                                <button type="button" x-on:click="rejectOpen = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Batal</button>
                                                <button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white">Tolak</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada setoran sampah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($setorans->hasPages())
        <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $setorans->links() }}</div>
    @endif
</div>
@endsection
