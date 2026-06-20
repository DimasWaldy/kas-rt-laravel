@extends('layouts.app')

@section('title', 'Detail Fasilitas')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('manage-fasilitas');
    $canReport = auth()->user()->hasPermission('lapor-fasilitas');
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('fasilitas.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali</a>
            <h1 class="mt-3 text-2xl font-black text-slate-900">{{ $fasilitas->nama }}</h1>
            <p class="text-sm text-slate-500">{{ $fasilitas->kategori_label }} · {{ $fasilitas->lokasi_lengkap ?: 'Lokasi belum diisi' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($canReport)
                <a href="{{ route('pengaduan-fasilitas.create', ['fasilitas_id' => $fasilitas->id]) }}" class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-bold text-amber-800 hover:bg-amber-100">Laporkan Masalah</a>
            @endif
            @if($canManage)
                <a href="{{ route('fasilitas.edit', $fasilitas) }}" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Edit</a>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="h-72 bg-slate-100">
                @if($fasilitas->foto)
                    <img src="{{ route('fasilitas.foto', $fasilitas) }}" alt="Foto {{ $fasilitas->nama }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-building-shield text-6xl"></i></div>
                @endif
            </div>
            <div class="space-y-5 p-6">
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $fasilitas->kategori_label }}</span>
                    <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $fasilitas->kondisi_color }}">{{ $fasilitas->kondisi_label }}</span>
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600">{{ $fasilitas->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div>
                    <h2 class="font-black text-slate-900">Lokasi</h2>
                    <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $fasilitas->lokasi_deskripsi ?: $fasilitas->lokasi_lengkap ?: 'Belum ada detail lokasi.' }}</p>
                </div>
                <div>
                    <h2 class="font-black text-slate-900">Catatan</h2>
                    <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $fasilitas->catatan ?: 'Belum ada catatan tambahan.' }}</p>
                </div>
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black text-slate-900">Info Scope</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-400">RW</dt><dd class="text-slate-700">{{ $fasilitas->rw?->name ?? '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">RT</dt><dd class="text-slate-700">{{ $fasilitas->rt?->name ?? 'Fasilitas RW / lintas RT' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Dibuat</dt><dd class="text-slate-700">{{ $fasilitas->created_at->format('d M Y H:i') }}</dd></div>
                </dl>
            </div>

            @if($canManage)
                <form action="{{ route('fasilitas.destroy', $fasilitas) }}" method="POST" onsubmit="return confirm('Hapus fasilitas ini?')" class="rounded-3xl border border-red-100 bg-red-50 p-5">
                    @csrf
                    @method('DELETE')
                    <button class="w-full rounded-2xl bg-red-600 px-5 py-3 text-sm font-bold text-white hover:bg-red-700">Hapus Fasilitas</button>
                    <p class="mt-2 text-xs text-red-700">Tidak bisa dihapus jika masih punya pengaduan aktif.</p>
                </form>
            @endif
        </aside>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-black text-slate-900">Riwayat Pengaduan Fasilitas</h2>
        <div class="mt-4 space-y-3">
            @forelse($fasilitas->pengaduanFasilitas as $pengaduan)
                <a href="{{ route('pengaduan-fasilitas.show', $pengaduan) }}" class="flex flex-col gap-2 rounded-2xl border border-slate-200 p-4 transition hover:border-emerald-300 hover:bg-emerald-50/40 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-bold text-slate-800">{{ $pengaduan->jenis_masalah_label }}</p>
                        <p class="text-sm text-slate-500">{{ str($pengaduan->deskripsi)->limit(90) }}</p>
                    </div>
                    <span class="w-fit rounded-full border px-3 py-1 text-xs font-bold {{ $pengaduan->status_color }}">{{ $pengaduan->status_label }}</span>
                </a>
            @empty
                <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada pengaduan untuk fasilitas ini.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
