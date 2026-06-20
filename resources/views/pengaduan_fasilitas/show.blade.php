@extends('layouts.app')

@section('title', 'Detail Pengaduan Fasilitas')

@section('content')
@php($canManage = auth()->user()->hasPermission('manage-fasilitas'))

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('pengaduan-fasilitas.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali</a>
            <h1 class="mt-3 text-2xl font-black text-slate-900">Detail Pengaduan Fasilitas</h1>
            <p class="text-sm text-slate-500">{{ $pengaduan->fasilitas->nama }} · {{ $pengaduan->created_at->format('d M Y H:i') }}</p>
        </div>
        <span class="w-fit rounded-full border px-4 py-2 text-sm font-bold {{ $pengaduan->status_color }}">{{ $pengaduan->status_label }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Fasilitas</p>
                <h2 class="mt-1 text-xl font-black text-slate-900">{{ $pengaduan->fasilitas->nama }}</h2>
                <p class="text-sm text-slate-500">{{ $pengaduan->fasilitas->lokasi_lengkap ?: 'Lokasi belum diisi' }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Jenis Masalah</p>
                <p class="mt-1 font-bold text-slate-800">{{ $pengaduan->jenis_masalah_label }}</p>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Deskripsi</p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $pengaduan->deskripsi }}</p>
            </div>

            @if($pengaduan->foto)
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Foto Bukti</p>
                    <img src="{{ route('pengaduan-fasilitas.foto', $pengaduan) }}" alt="Foto pengaduan" class="max-h-96 w-full rounded-2xl object-cover">
                </div>
            @endif

            @if($pengaduan->catatan_tindak_lanjut)
                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-700">Catatan Tindak Lanjut</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-blue-900">{{ $pengaduan->catatan_tindak_lanjut }}</p>
                </div>
            @endif
        </section>

        <aside class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black text-slate-900">Info Laporan</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-400">Pelapor</dt><dd class="text-slate-700">{{ $pengaduan->pelapor?->name ?? '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Ditindaklanjuti oleh</dt><dd class="text-slate-700">{{ $pengaduan->tindakLanjutOleh?->name ?? '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Tanggal selesai</dt><dd class="text-slate-700">{{ $pengaduan->tanggal_selesai?->format('d M Y H:i') ?? '-' }}</dd></div>
                </dl>
            </div>

            @if($canManage && in_array($pengaduan->status, ['dilaporkan', 'ditindaklanjuti'], true))
                <div class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    @if($pengaduan->status === 'dilaporkan')
                        <form action="{{ route('pengaduan-fasilitas.tindak-lanjut', $pengaduan) }}" method="POST" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Catatan awal</label>
                            <textarea name="catatan_tindak_lanjut" rows="3" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ old('catatan_tindak_lanjut') }}</textarea>
                            <button class="w-full rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">Tindak Lanjuti</button>
                        </form>

                        <form action="{{ route('pengaduan-fasilitas.tolak', $pengaduan) }}" method="POST" class="space-y-3 border-t border-slate-100 pt-4">
                            @csrf
                            @method('PATCH')
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Alasan ditolak</label>
                            <textarea name="catatan_tindak_lanjut" rows="3" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"></textarea>
                            <button class="w-full rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-bold text-red-700 hover:bg-red-100">Tolak Laporan</button>
                        </form>
                    @endif

                    <form action="{{ route('pengaduan-fasilitas.selesai', $pengaduan) }}" method="POST" class="space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        @method('PATCH')
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Catatan selesai</label>
                        <textarea name="catatan_tindak_lanjut" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ old('catatan_tindak_lanjut', $pengaduan->catatan_tindak_lanjut) }}</textarea>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="update_kondisi_baik" value="1" class="rounded border-slate-300 text-emerald-700">
                            Update kondisi fasilitas menjadi baik
                        </label>
                        <button class="w-full rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Tandai Selesai</button>
                    </form>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
