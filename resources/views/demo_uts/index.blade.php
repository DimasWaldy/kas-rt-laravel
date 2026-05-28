@extends('layouts.app')

@section('title', 'Demo UTS')

@section('content')
@php
    $namaBulan = \Carbon\Carbon::create(null, $bulan)->translatedFormat('F');
@endphp

<div class="space-y-6">
    <div class="rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-100">
        <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Ringkasan Presentasi</p>
        <h1 class="mt-2 text-2xl font-black md:text-3xl">Demo Alur Kas RT</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-emerald-50">
            Halaman ini merangkum alur utama UTS: iuran dibuat, tagihan rumah muncul, warga membayar, admin verifikasi, lalu kas masuk bertambah.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Rumah Aktif</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $totalRumah }}</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Komponen Iuran</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $komponenIuran }}</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Tagihan Bulan Ini</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $tagihanBulanIni }}</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Menunggu Verifikasi</p>
            <p class="mt-3 text-2xl font-black text-amber-600">{{ $menungguVerifikasi }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
        <section class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-900">Alur Demo yang Bisa Dijelaskan</h2>
            <div class="mt-5 space-y-4">
                @foreach([
                    ['Iuran dibuat', 'Bendahara/admin menambahkan komponen iuran bulanan seperti keamanan dan kebersihan.'],
                    ['Tagihan rumah digenerate', 'Sistem membuat satu tagihan untuk setiap rumah aktif, bukan per KK.'],
                    ['Warga membayar', 'Penanggung jawab rumah mengajukan pembayaran transfer atau offline dari halaman tagihan.'],
                    ['Admin verifikasi', 'Bendahara/admin mengecek bukti pembayaran lalu mengubah status tagihan menjadi lunas.'],
                    ['Kas masuk bertambah', 'Setelah lunas, sistem mencatat pemasukan sehingga laporan kas ikut berubah.'],
                ] as $index => $step)
                    <div class="flex gap-4 rounded-3xl bg-emerald-50 p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-sm font-black text-white">{{ $index + 1 }}</span>
                        <div>
                            <p class="font-black text-emerald-950">{{ $step[0] }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $step[1] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Periode Demo</p>
                <p class="mt-3 text-2xl font-black text-slate-900">{{ $namaBulan }} {{ $tahun }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $tagihanLunas }} tagihan sudah lunas bulan ini.</p>
            </div>
            <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Kas Bulan Ini</p>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-2xl bg-emerald-50 p-3">
                        <span class="text-sm font-bold text-slate-600">Masuk</span>
                        <span class="font-black text-emerald-700">Rp {{ number_format($kasMasukBulanIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-rose-50 p-3">
                        <span class="text-sm font-bold text-slate-600">Keluar</span>
                        <span class="font-black text-rose-600">Rp {{ number_format($kasKeluarBulanIni, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-6">
                <p class="text-sm font-bold leading-6 text-emerald-900">
                    Fokus UTS tetap pada tagihan iuran, kas masuk, dan kas keluar. Pengaduan bisa disebut fitur tambahan di luar inti kas RT.
                </p>
            </div>
        </aside>
    </div>
</div>
@endsection
