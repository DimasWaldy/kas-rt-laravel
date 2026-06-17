@extends('layouts.app')

@section('title', 'Ajukan Setoran Sampah')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-emerald-700">Bank Sampah RW</p>
        <h1 class="text-2xl font-black text-slate-900">Ajukan Setoran Sampah</h1>
        <p class="mt-1 text-sm text-slate-500">Isi estimasi setoran Anda. Nilai final dihitung setelah petugas menimbang berat aktual.</p>
    </div>

    <form method="POST" action="{{ route('setoran-sampah.store') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{
        selected: '',
        berat: '{{ old('estimasi_berat', '') }}',
        items: @js($jenisSampah->map(fn($item) => ['id' => $item->id, 'nama' => $item->nama, 'harga' => $item->harga_per_satuan, 'satuan' => $item->satuan_label])->values()),
        get current() { return this.items.find(item => String(item.id) === String(this.selected)); },
        get estimasi() { return this.current ? Math.round((parseFloat(this.berat) || 0) * this.current.harga) : 0; }
    }">
        @csrf

        @if($errors->any())
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Jenis Sampah</label>
                <select name="jenis_sampah_id" x-model="selected" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Pilih jenis sampah</option>
                    @foreach($jenisSampah as $jenis)
                        <option value="{{ $jenis->id }}" @selected(old('jenis_sampah_id') == $jenis->id)>
                            {{ $jenis->nama }} - Rp {{ number_format($jenis->harga_per_satuan, 0, ',', '.') }}/{{ $jenis->satuan_label }}
                        </option>
                    @endforeach
                </select>
                <p x-show="current" class="mt-2 text-xs font-semibold text-emerald-700">
                    Harga: Rp <span x-text="current.harga.toLocaleString('id-ID')"></span> / <span x-text="current.satuan"></span>
                </p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Estimasi Berat</label>
                <input type="number" step="0.01" min="0.1" name="estimasi_berat" x-model="berat" value="{{ old('estimasi_berat') }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div class="rounded-2xl bg-emerald-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Estimasi Nilai</p>
                <p class="mt-1 text-2xl font-black text-emerald-900">Rp <span x-text="estimasi.toLocaleString('id-ID')"></span></p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Tanggal Setor</label>
                <input type="date" name="tanggal_setor" value="{{ old('tanggal_setor', now()->toDateString()) }}" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Catatan</label>
                <textarea name="catatan_warga" rows="4" class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: sampah sudah dipilah dan dikemas">{{ old('catatan_warga') }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Kirim Setoran</button>
            <a href="{{ route('bank-sampah.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Batal</a>
        </div>
    </form>
</div>
@endsection
