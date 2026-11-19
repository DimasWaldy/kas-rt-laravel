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
        <section class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-xs lg:col-span-2">
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

            @if($pengaduan->foto && $pengaduan->foto_selesai)
                {{-- BEFORE / AFTER COMPARISON SLIDER (Gaya Kamus Front End) --}}
                <div x-data="{ sliderPos: 50, viewMode: 'slider' }" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Hasil Perbaikan Fasilitas</p>
                            <h3 class="text-base font-bold text-slate-900">Perbandingan Sebelum &amp; Sesudah</h3>
                        </div>
                        {{-- Mode Selector --}}
                        <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-bold text-slate-600">
                            <button type="button" @click="viewMode = 'slider'" class="rounded-lg px-2.5 py-1 transition" :class="viewMode === 'slider' ? 'bg-white text-emerald-700 shadow-xs' : 'hover:text-slate-900'">
                                <i class="fa-solid fa-left-right mr-1"></i> Slider
                            </button>
                            <button type="button" @click="viewMode = 'side'" class="rounded-lg px-2.5 py-1 transition" :class="viewMode === 'side' ? 'bg-white text-emerald-700 shadow-xs' : 'hover:text-slate-900'">
                                <i class="fa-solid fa-table-columns mr-1"></i> Berdampingan
                            </button>
                        </div>
                    </div>

                    {{-- Slider View --}}
                    <div x-show="viewMode === 'slider'" class="relative select-none overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 aspect-video max-h-[460px] w-full shadow-lg">
                        {{-- Image After / Sesudah (Base) --}}
                        <img src="{{ route('pengaduan-fasilitas.foto-selesai', $pengaduan) }}" alt="Foto Sesudah Perbaikan" class="absolute inset-0 h-full w-full object-cover">
                        <span class="absolute top-4 right-4 z-10 rounded-full bg-emerald-600/90 backdrop-blur-xs px-3 py-1 text-[11px] font-black uppercase tracking-wider text-white shadow-xs">
                            <i class="fa-solid fa-check mr-1"></i> Sesudah (Hasil Perbaikan)
                        </span>

                        {{-- Image Before / Sebelum (Overlay with clip-path) --}}
                        <div class="absolute inset-0 overflow-hidden" :style="'clip-path: inset(0 ' + (100 - sliderPos) + '% 0 0)'">
                            <img src="{{ route('pengaduan-fasilitas.foto', $pengaduan) }}" alt="Foto Sebelum Perbaikan" class="absolute inset-0 h-full w-full object-cover">
                            <span class="absolute top-4 left-4 z-10 rounded-full bg-rose-600/90 backdrop-blur-xs px-3 py-1 text-[11px] font-black uppercase tracking-wider text-white shadow-xs">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> Sebelum (Kondisi Rusak)
                            </span>
                        </div>

                        {{-- Vertical Divider Line with Draggable Handle --}}
                        <div class="pointer-events-none absolute top-0 bottom-0 z-20 w-0.5 bg-white shadow-md" :style="'left: ' + sliderPos + '%'">
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-800 shadow-xl border-2 border-slate-300">
                                <i class="fa-solid fa-left-right text-xs"></i>
                            </div>
                        </div>

                        {{-- Interactive Range Input Slider --}}
                        <input type="range" min="0" max="100" x-model="sliderPos" aria-label="Geser untuk membandingkan sebelum dan sesudah"
                            class="absolute inset-0 z-30 h-full w-full cursor-ew-resize opacity-0 m-0 p-0">
                    </div>

                    {{-- Side-by-Side View --}}
                    <div x-show="viewMode === 'side'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 border border-rose-200 px-3 py-1 text-xs font-bold text-rose-700">
                                <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Sebelum (Kondisi Rusak)
                            </span>
                            <img src="{{ route('pengaduan-fasilitas.foto', $pengaduan) }}" alt="Foto Sebelum Perbaikan" class="h-64 w-full rounded-2xl object-cover border border-slate-200">
                        </div>
                        <div class="space-y-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-bold text-emerald-700">
                                <i class="fa-solid fa-check text-[10px]"></i> Sesudah (Hasil Perbaikan)
                            </span>
                            <img src="{{ route('pengaduan-fasilitas.foto-selesai', $pengaduan) }}" alt="Foto Sesudah Perbaikan" class="h-64 w-full rounded-2xl object-cover border border-slate-200">
                        </div>
                    </div>

                    <p class="text-center text-xs text-slate-400">
                        <i class="fa-solid fa-hand-pointer mr-1"></i> Geser garis pemisah ke kiri dan kanan untuk membandingkan kondisi fasilitas sebelum vs sesudah perbaikan.
                    </p>
                </div>
            @elseif($pengaduan->foto)
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Foto Bukti Kerusakan</p>
                    <img src="{{ route('pengaduan-fasilitas.foto', $pengaduan) }}" alt="Foto pengaduan" class="max-h-96 w-full rounded-2xl object-cover">
                </div>
            @elseif($pengaduan->foto_selesai)
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-wider text-emerald-600">Foto Hasil Perbaikan</p>
                    <img src="{{ route('pengaduan-fasilitas.foto-selesai', $pengaduan) }}" alt="Foto sesudah" class="max-h-96 w-full rounded-2xl object-cover">
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
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xs">
                <h2 class="font-black text-slate-900">Info Laporan</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-400">Pelapor</dt><dd class="text-slate-700">{{ $pengaduan->pelapor?->name ?? '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Ditindaklanjuti oleh</dt><dd class="text-slate-700">{{ $pengaduan->tindakLanjutOleh?->name ?? '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Tanggal selesai</dt><dd class="text-slate-700">{{ $pengaduan->tanggal_selesai?->format('d M Y H:i') ?? '-' }}</dd></div>
                </dl>
            </div>

            @if($canManage && in_array($pengaduan->status, ['dilaporkan', 'ditindaklanjuti'], true))
                <div class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-xs">
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

                    <form action="{{ route('pengaduan-fasilitas.selesai', $pengaduan) }}" method="POST" enctype="multipart/form-data" class="space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        @method('PATCH')
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Catatan selesai</label>
                        <textarea name="catatan_tindak_lanjut" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ old('catatan_tindak_lanjut', $pengaduan->catatan_tindak_lanjut) }}</textarea>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Foto Hasil Perbaikan (Sesudah)</label>
                            <input type="file" name="foto_selesai" accept="image/jpeg,image/png,image/jpg" class="mt-1 w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-2.5 text-xs text-slate-600 file:mr-2 file:rounded-xl file:border-0 file:bg-emerald-600 file:px-3 file:py-1 file:text-xs file:font-bold file:text-white hover:file:bg-emerald-700">
                            <p class="mt-1 text-[11px] text-slate-400">Opsional. Foto ini akan mengaktifkan slider perbandingan Sebelum &amp; Sesudah bagi warga.</p>
                            @error('foto_selesai')
                                <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="update_kondisi_baik" value="1" class="rounded-sm border-slate-300 text-emerald-700">
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
