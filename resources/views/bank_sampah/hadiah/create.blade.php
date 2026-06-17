@extends('layouts.app')

@section('title', 'Tambah Hadiah Bank Sampah')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-emerald-700">Bank Sampah RW</p>
        <h1 class="text-2xl font-black text-slate-900">Tambah Hadiah</h1>
    </div>

    <form method="POST" action="{{ route('hadiah-sampah.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Nama Hadiah</label>
            <input name="nama" value="{{ old('nama') }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Deskripsi</label>
            <textarea name="deskripsi" rows="4" class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('deskripsi') }}</textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Nilai Tukar</label>
                <input type="number" name="nilai_tukar" min="1" value="{{ old('nilai_tukar') }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Stok</label>
                <input type="number" name="stok" min="0" value="{{ old('stok', 0) }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Foto</label>
            <input type="file" name="foto" accept="image/*" class="w-full rounded-xl border border-slate-300 p-3 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            Aktif
        </label>
        <div class="flex gap-3">
            <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Simpan Hadiah</button>
            <a href="{{ route('hadiah-sampah.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Batal</a>
        </div>
    </form>
</div>
@endsection
