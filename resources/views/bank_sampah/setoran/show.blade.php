@extends('layouts.app')

@section('title', 'Detail Setoran Sampah')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-emerald-700">Bank Sampah RW</p>
        <h1 class="text-2xl font-black text-slate-900">Detail Setoran Sampah</h1>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-slate-900">{{ $setoran->jenisSampah->nama }}</h2>
                <p class="mt-1 text-sm text-slate-500">Diajukan oleh {{ $setoran->warga->name }}</p>
            </div>
            <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $setoran->status_color }}">{{ $setoran->status_label }}</span>
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Estimasi Berat</dt>
                <dd class="mt-1 font-black text-slate-900">{{ number_format($setoran->estimasi_berat, 2, ',', '.') }} {{ $setoran->jenisSampah->satuan_label }}</dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Berat Aktual</dt>
                <dd class="mt-1 font-black text-slate-900">{{ $setoran->berat_aktual ? number_format($setoran->berat_aktual, 2, ',', '.') . ' ' . $setoran->jenisSampah->satuan_label : '-' }}</dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nilai</dt>
                <dd class="mt-1 font-black text-emerald-700">Rp {{ number_format($setoran->nilai, 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal Setor</dt>
                <dd class="mt-1 font-black text-slate-900">{{ $setoran->tanggal_setor->translatedFormat('d F Y') }}</dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Metode Setor</dt>
                <dd class="mt-1 font-black text-slate-900">{{ $setoran->metode_setor_label }}</dd>
            </div>
        </dl>

        @if($setoran->foto_bukti)
            <div class="mt-6">
                <p class="mb-2 text-sm font-bold text-slate-700">Foto Bukti Setoran</p>
                <img src="{{ route('setoran-sampah.foto-bukti', $setoran) }}" alt="Foto bukti setoran sampah" class="max-h-96 rounded-2xl border border-slate-200 object-cover">
            </div>
        @endif

        <div class="mt-6 space-y-3 text-sm text-slate-600">
            <p><span class="font-bold text-slate-800">Catatan warga:</span> {{ $setoran->catatan_warga ?: '-' }}</p>
            <p><span class="font-bold text-slate-800">Catatan petugas:</span> {{ $setoran->catatan_petugas ?: '-' }}</p>
            <p><span class="font-bold text-slate-800">Petugas:</span> {{ $setoran->petugas?->name ?: '-' }}</p>
        </div>
    </div>
</div>
@endsection
