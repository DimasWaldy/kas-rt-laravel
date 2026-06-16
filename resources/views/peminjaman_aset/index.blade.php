@extends('layouts.app')

@section('title', 'Peminjaman Aset')

@section('content')
@php
    $isRw = ($scope ?? 'rt') === 'rw';
    $indexRoute = $isRw ? 'peminjaman-aset-rw.index' : 'peminjaman-aset.index';
    $createRoute = $isRw ? 'peminjaman-aset-rw.create' : 'peminjaman-aset.create';
    $showRoute = $isRw ? 'peminjaman-aset-rw.show' : 'peminjaman-aset.show';
    $canPinjam = auth()->user()->hasPermission($isRw ? 'pinjam-aset-rw' : 'pinjam-aset');
@endphp
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">{{ $isRw ? 'Pakai fasilitas RW dengan tertib' : 'Pakai aset RT dengan tertib' }}</p>
            <h1 class="text-2xl font-black text-slate-900">Peminjaman Aset {{ $isRw ? 'RW' : 'RT' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $canManage ? ($isRw ? 'Pantau dan proses peminjaman aset RW lintas RT.' : 'Pantau dan proses peminjaman aset RT.') : 'Lihat riwayat peminjaman aset Anda.' }}</p>
        </div>

        @if($canPinjam)
            <a href="{{ route($createRoute) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
                <i class="fa-solid fa-plus"></i> Ajukan Peminjaman
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route($indexRoute) }}" class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <button type="submit" name="status" value="" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === '' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Semua
        </button>
        @foreach($statuses as $item)
            <button type="submit" name="status" value="{{ $item }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === $item ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ str($item)->headline() }}
            </button>
        @endforeach
    </form>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-4">Aset</th>
                        <th class="px-5 py-4">Pemohon</th>
                        <th class="px-5 py-4">Tanggal Pinjam</th>
                        <th class="px-5 py-4">Durasi</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($peminjamans as $peminjaman)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4"><p class="font-bold text-slate-800">{{ $peminjaman->aset->nama }}</p><p class="text-xs text-slate-400">{{ $peminjaman->aset->kategori_label }}</p></td>
                            <td class="px-5 py-4 text-slate-600">{{ $peminjaman->pemohon->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $peminjaman->tanggal_mulai->translatedFormat('d M Y') }} - {{ $peminjaman->tanggal_selesai->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $peminjaman->durasi_hari }} hari</td>
                            <td class="px-5 py-4"><span class="rounded-full border px-3 py-1 text-xs font-bold {{ $peminjaman->status_color }}">{{ $peminjaman->status_label }}</span></td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route($showRoute, $peminjaman) }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center text-slate-500">Belum ada data peminjaman aset.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($peminjamans->hasPages())
        <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $peminjamans->links() }}</div>
    @endif
</div>
@endsection
