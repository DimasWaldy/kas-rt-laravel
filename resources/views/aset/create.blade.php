@extends('layouts.app')

@section('title', 'Tambah Aset')

@section('content')
@php
    $isRw = ($scope ?? 'rt') === 'rw';
    $indexRoute = $isRw ? 'aset-rw.index' : 'aset.index';
    $storeRoute = $isRw ? 'aset-rw.store' : 'aset.store';
    $kategoriLabels = [
        'furniture' => 'Furniture',
        'elektronik' => 'Elektronik',
        'tenda_dan_terpal' => 'Tenda & Terpal',
        'kebersihan' => 'Kebersihan',
        'olahraga' => 'Olahraga',
        'gedung' => 'Gedung / Balai',
        'lapangan' => 'Lapangan',
        'panggung' => 'Panggung',
        'lainnya' => 'Lainnya',
    ];
    $kondisiLabels = [
        'baik' => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat' => 'Rusak Berat',
    ];
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route($indexRoute) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke daftar aset</a>

    <form method="POST" action="{{ route($storeRoute) }}" enctype="multipart/form-data" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" x-data="{ preview: null }">
        @csrf
        <input type="hidden" name="scope" value="{{ $scope ?? 'rt' }}">
        <div>
            <p class="text-sm font-semibold text-emerald-700">{{ $isRw ? 'Inventaris RW' : 'Inventaris RT' }}</p>
            <h1 class="text-2xl font-black text-slate-900">Tambah Aset {{ $isRw ? 'RW' : 'RT' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $isRw ? 'Masukkan data fasilitas milik RW yang bisa dipinjam lintas RT.' : 'Masukkan data barang yang dimiliki RT.' }}</p>
        </div>

        <div class="mt-7 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Nama Aset</label>
                <input type="text" name="nama" value="{{ old('nama') }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('nama')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Kategori</label>
                <select name="kategori" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(old('kategori') === $category)>{{ $kategoriLabels[$category] ?? str($category)->headline() }}</option>
                    @endforeach
                </select>
                @error('kategori')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Kondisi</label>
                <select name="kondisi" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach($conditions as $condition)
                        <option value="{{ $condition }}" @selected(old('kondisi', 'baik') === $condition)>{{ $kondisiLabels[$condition] ?? str($condition)->headline() }}</option>
                    @endforeach
                </select>
                @error('kondisi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jumlah Total</label>
                <input type="number" name="jumlah_total" value="{{ old('jumlah_total', 1) }}" min="1" max="100" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('jumlah_total')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Nilai Perkiraan</label>
                <input type="number" name="nilai_perkiraan" value="{{ old('nilai_perkiraan') }}" min="0" step="1000" placeholder="Contoh: 500000" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('nilai_perkiraan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Tanggal Pengadaan</label>
                <input type="date" name="tanggal_pengadaan" value="{{ old('tanggal_pengadaan') }}" max="{{ now()->toDateString() }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('tanggal_pengadaan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Lokasi Penyimpanan</label>
                <input type="text" name="lokasi_penyimpanan" value="{{ old('lokasi_penyimpanan') }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('lokasi_penyimpanan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Deskripsi</label>
                <textarea name="deskripsi" rows="4" maxlength="1000" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Foto Aset</label>
                <input type="file" name="foto" accept="image/jpeg,image/png,image/jpg" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                @error('foto')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                <template x-if="preview">
                    <img :src="preview" alt="Preview foto aset" class="mt-4 h-52 w-full rounded-2xl object-cover">
                </template>
            </div>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route($indexRoute) }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
            <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Simpan Aset</button>
        </div>
    </form>
</div>
@endsection
