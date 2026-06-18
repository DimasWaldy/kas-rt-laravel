@extends('layouts.app')

@section('title', 'Catat Penjualan Sampah')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-emerald-700">Kas Bank Sampah RW</p>
        <h1 class="text-2xl font-black text-slate-900">Catat Penjualan Sampah</h1>
        <p class="mt-1 text-sm text-slate-500">Total kas dihitung dari berat total dikali harga jual per satuan.</p>
    </div>

    <form method="POST" action="{{ route('penjualan-sampah.store') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{
        selected: '',
        berat: '{{ old('berat_total', '') }}',
        harga: '{{ old('harga_jual', '') }}',
        items: @js($jenisSampah->map(fn($item) => ['id' => $item->id, 'nama' => $item->nama, 'satuan' => $item->satuan_label])->values()),
        get current() { return this.items.find(item => String(item.id) === String(this.selected)); },
        get total() { return Math.round((parseFloat(this.berat) || 0) * (parseInt(this.harga) || 0)); }
    }">
        @csrf

        @if($errors->any())
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Tanggal Jual</label>
                <input type="date" name="tanggal_jual" value="{{ old('tanggal_jual', now()->toDateString()) }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Jenis Sampah</label>
                <select name="jenis_sampah_id" x-model="selected" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Pilih jenis sampah</option>
                    @foreach($jenisSampah as $jenis)
                        <option value="{{ $jenis->id }}" @selected(old('jenis_sampah_id') == $jenis->id)>{{ $jenis->nama }} / {{ $jenis->satuan_label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">Berat Total</label>
                    <input type="number" step="0.01" min="0.1" name="berat_total" x-model="berat" value="{{ old('berat_total') }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <p x-show="current" class="mt-2 text-xs font-semibold text-slate-500">Satuan: <span x-text="current.satuan"></span></p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">Harga Jual per Satuan</label>
                    <input type="number" min="1" name="harga_jual" x-model="harga" value="{{ old('harga_jual') }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>

            <div class="rounded-2xl bg-emerald-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Total Uang Masuk Kas Bank Sampah</p>
                <p class="mt-1 text-2xl font-black text-emerald-900">Rp <span x-text="total.toLocaleString('id-ID')"></span></p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Nama Pengepul</label>
                <input name="nama_pengepul" value="{{ old('nama_pengepul') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: CV Hijau Lestari">
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Catatan</label>
                <textarea name="catatan" rows="4" class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('catatan') }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Simpan Penjualan</button>
            <a href="{{ route('penjualan-sampah.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Batal</a>
        </div>
    </form>
</div>
@endsection
