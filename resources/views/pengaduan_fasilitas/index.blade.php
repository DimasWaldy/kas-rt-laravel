@extends('layouts.app')

@section('title', 'Pengaduan Fasilitas')

@section('content')
@php
    $tabs = [
        '' => 'Semua',
        'dilaporkan' => 'Dilaporkan',
        'ditindaklanjuti' => 'Ditindaklanjuti',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">{{ $canManage ? 'Tindak lanjut laporan warga' : 'Laporan fasilitas saya' }}</p>
            <h1 class="text-2xl font-black text-slate-900">Pengaduan Fasilitas</h1>
            <p class="mt-1 text-sm text-slate-500">Laporkan dan pantau masalah fasilitas seperti CCTV, lampu jalan, drainase, atau pos keamanan.</p>
        </div>

        <a href="{{ route('pengaduan-fasilitas.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
            <i class="fa-solid fa-plus"></i> Buat Laporan
        </a>
    </div>

    <div class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        @foreach($tabs as $value => $label)
            <a href="{{ route('pengaduan-fasilitas.index', ['status' => $value]) }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === $value ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($pengaduans->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400"><i class="fa-solid fa-clipboard-check"></i></span>
            <h2 class="mt-4 font-bold text-slate-800">Belum ada laporan</h2>
            <p class="mt-1 text-sm text-slate-500">Laporan fasilitas sesuai filter akan tampil di sini.</p>
        </div>
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($pengaduans as $pengaduan)
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $pengaduan->status_color }}">{{ $pengaduan->status_label }}</span>
                            <h2 class="mt-3 text-lg font-black text-slate-900">{{ $pengaduan->fasilitas->nama }}</h2>
                            <p class="text-sm text-slate-500">{{ $pengaduan->fasilitas->lokasi_lengkap ?: 'Lokasi belum diisi' }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">{{ $pengaduan->jenis_masalah_label }}</span>
                    </div>

                    <p class="mt-4 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $pengaduan->deskripsi }}</p>

                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-500">
                        <span>{{ $pengaduan->pelapor?->name ?? 'Pelapor' }}</span>
                        <span>{{ $pengaduan->created_at->diffForHumans() }}</span>
                    </div>

                    <a href="{{ route('pengaduan-fasilitas.show', $pengaduan) }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100">
                        Lihat Detail <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </article>
            @endforeach
        </div>

        @if($pengaduans->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $pengaduans->links() }}</div>
        @endif
    @endif
</div>
@endsection
