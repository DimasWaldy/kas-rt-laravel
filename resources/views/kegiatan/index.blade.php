@extends('layouts.app')

@section('title', 'Kegiatan RW')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Agenda bersama lintas RT</p>
            <h1 class="text-2xl font-black text-slate-900">Kegiatan RW</h1>
            <p class="mt-1 text-sm text-slate-500">Temukan kegiatan lingkungan dan konfirmasikan kehadiran Anda.</p>
        </div>

        @if(auth()->user()->hasPermission('manage-kegiatan'))
            <a href="{{ route('kegiatan.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
                <i class="fa-solid fa-plus"></i> Buat Kegiatan
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('kegiatan.index') }}" class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @foreach(['' => 'Semua', 'akan_datang' => 'Akan Datang', 'berlangsung' => 'Berlangsung', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $value => $label)
            <button type="submit" name="status" value="{{ $value }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === $value ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </form>

    @if($kegiatans->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400"><i class="fa-regular fa-calendar-xmark"></i></span>
            <h2 class="mt-4 font-bold text-slate-800">Belum ada kegiatan</h2>
            <p class="mt-1 text-sm text-slate-500">Kegiatan RW yang dibuat pengurus akan tampil di sini.</p>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($kegiatans as $kegiatan)
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="relative h-48 bg-slate-100">
                        @if($kegiatan->foto)
                            <img src="{{ route('kegiatan.foto', $kegiatan) }}" alt="Foto {{ $kegiatan->nama }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-regular fa-image text-5xl"></i></div>
                        @endif
                        <span class="absolute left-4 top-4 rounded-full border px-3 py-1.5 text-xs font-bold {{ $kegiatan->status_color }}">{{ $kegiatan->status_label }}</span>
                    </div>

                    <div class="p-5">
                        <h2 class="line-clamp-2 text-lg font-black text-slate-900">{{ $kegiatan->nama }}</h2>
                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <p class="flex items-start gap-2"><i class="fa-regular fa-calendar mt-0.5 w-4 text-emerald-600"></i><span>{{ $kegiatan->tanggal_mulai->translatedFormat('l, d F Y') }}<br><span class="text-xs text-slate-400">Pukul {{ $kegiatan->tanggal_mulai->format('H:i') }} WIB</span></span></p>
                            <p class="flex items-center gap-2"><i class="fa-solid fa-location-dot w-4 text-emerald-600"></i><span>{{ $kegiatan->lokasi ?: 'Lokasi menyusul' }}</span></p>
                            @if($kegiatan->estimasi_biaya > 0)
                                <p class="flex items-center gap-2"><i class="fa-solid fa-coins w-4 text-amber-500"></i><span>Estimasi Rp {{ number_format($kegiatan->estimasi_biaya, 0, ',', '.') }}</span></p>
                            @endif
                            <p class="flex items-center gap-2"><i class="fa-solid fa-users w-4 text-blue-500"></i><span>{{ $kegiatan->hadirs_count }} warga konfirmasi hadir</span></p>
                        </div>

                        <a href="{{ route('kegiatan.show', $kegiatan) }}" class="mt-5 flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100">
                            Lihat Detail <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        @if($kegiatans->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $kegiatans->links() }}</div>
        @endif
    @endif
</div>
@endsection
