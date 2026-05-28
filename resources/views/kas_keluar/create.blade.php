@extends('layouts.app')

@section('title', 'Tambah Kas Keluar')

@section('content')

<div class="mx-auto max-w-5xl">
    <div class="mb-6 rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-800 to-green-700 p-6 text-white shadow-lg shadow-emerald-100">
        <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Kas Keluar</p>
        <h1 class="mt-2 text-2xl font-black md:text-3xl">Tambah Data Pengeluaran</h1>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50">
            Catat setiap pengeluaran RT dengan keterangan, tanggal, dan bukti agar laporan kas tetap transparan.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[0.9fr_1.4fr]">
        <aside class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-800">
                <i class="fa-solid fa-receipt text-xl"></i>
            </div>
            <h2 class="mt-5 text-lg font-black text-slate-900">Checklist Pencatatan</h2>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <p>Tulis keterangan yang mudah dipahami warga.</p>
                </div>
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <p>Masukkan nominal sesuai bukti transaksi.</p>
                </div>
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <p>Unggah bukti foto jika tersedia.</p>
                </div>
            </div>

            <a href="{{ route('kas-keluar.index') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-2xl border border-emerald-200 px-5 py-3 text-sm font-bold text-emerald-800 transition hover:bg-emerald-50">
                Lihat Riwayat Kas Keluar
            </a>
        </aside>

        <section class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                    Periksa kembali data pengeluaran yang diisi.
                </div>
            @endif

            <form action="{{ route('kas-keluar.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-slate-700">Keterangan</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan') }}"
                        placeholder="Contoh: Pembelian alat kebersihan"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @error('keterangan') border-red-500 @enderror">
                    @error('keterangan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-bold text-slate-700">Jumlah</label>
                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-200 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-200 @error('jumlah') border-red-500 @enderror">
                            <span class="flex items-center bg-emerald-50 px-4 text-sm font-bold text-emerald-800">Rp</span>
                            <input type="number" name="jumlah" value="{{ old('jumlah') }}" min="1"
                                placeholder="0"
                                class="w-full border-0 px-4 py-3 text-sm focus:ring-0">
                        </div>
                        @error('jumlah')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @error('tanggal') border-red-500 @enderror">
                        @error('tanggal')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700">Bukti Pengeluaran</label>
                    <label class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-emerald-200 bg-emerald-50/60 px-4 py-8 text-center transition hover:bg-emerald-50">
                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-emerald-700"></i>
                        <span class="mt-2 text-sm font-bold text-emerald-900">Unggah foto bukti</span>
                        <span class="mt-1 text-xs text-emerald-700">Format JPG, JPEG, PNG. Maksimal 2MB.</span>
                        <input type="file" name="bukti" accept="image/jpeg,image/png,image/jpg" class="sr-only">
                    </label>
                    @error('bukti')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-between">
                    <a href="{{ route('kas-keluar.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-100 transition hover:bg-emerald-900">
                        Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

@endsection
