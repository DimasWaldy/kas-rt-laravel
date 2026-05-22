@extends('layouts.app')

@section('title', 'Tambah Iuran Bulanan')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-3xl shadow-sm p-6 border-l-8 border-cyan-500">
        <h2 class="text-xl font-bold text-slate-800">Tambah Iuran Bulanan</h2>
        <p class="text-slate-500 mt-2">Buat komponen iuran baru untuk bulan dan tahun tertentu.</p>
    </div>

    <div class="bg-white rounded-3xl shadow-sm p-6 border border-slate-200 max-w-3xl">
        <form action="{{ route('iuran-bulanan.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-slate-700">Nama Iuran</label>
                <input type="text" name="nama" value="{{ old('nama') }}" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm" placeholder="Contoh: Iuran kebersihan" required>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700">Keterangan</label>
                <textarea name="keterangan" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm" placeholder="Opsional">{{ old('keterangan') }}</textarea>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Jumlah</label>
                    <input type="number" name="jumlah" value="{{ old('jumlah') }}" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm" placeholder="0" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Bulan</label>
                    <select name="bulan" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm" required>
                        @foreach(range(1, 12) as $month)
                            <option value="{{ $month }}" {{ old('bulan') == $month ? 'selected' : '' }}>{{ $month }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Tahun</label>
                    <input type="number" name="tahun" value="{{ old('tahun', now()->year) }}" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm" required>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('iuran-bulanan.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</a>
                <button type="submit" class="rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white hover:bg-cyan-700 transition">Simpan Iuran</button>
            </div>
        </form>
    </div>
</div>
@endsection
