@extends('layouts.app')

@section('title', 'Edit Komponen Iuran')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6 rounded-3xl border border-amber-100 bg-linear-to-r from-amber-600 to-orange-500 p-6 text-white shadow-lg shadow-amber-100">
        <p class="text-xs font-bold uppercase tracking-[0.25em] text-amber-100">Komponen Iuran</p>
        <h2 class="mt-2 text-2xl font-black md:text-3xl">Edit Iuran Bulanan</h2>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-amber-50">
            Ubah detail komponen iuran bulan
            <strong>{{ \Carbon\Carbon::create(null, $iuranBulanan->bulan)->translatedFormat('F') }} {{ $iuranBulanan->tahun }}</strong>.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('iuran-bulanan.update', $iuranBulanan) }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-xs">
        @csrf
        @method('PATCH')

        {{-- Nama --}}
        <div class="mb-5">
            <label for="nama" class="block text-sm font-bold text-slate-700">Nama Komponen <span class="text-red-500">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $iuranBulanan->nama) }}"
                class="mt-2 w-full rounded-2xl border @error('nama') border-red-500 @else border-slate-200 @enderror bg-white px-4 py-3 text-sm focus:border-amber-500 focus:outline-hidden focus:ring-2 focus:ring-amber-200"
                placeholder="cth. Iuran Kebersihan" required>
            @error('nama')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Jumlah --}}
        <div class="mb-5">
            <label for="jumlah" class="block text-sm font-bold text-slate-700">Jumlah (Rp) <span class="text-red-500">*</span></label>
            <div class="mt-2 flex overflow-hidden rounded-2xl border @error('jumlah') border-red-500 @else border-slate-200 @enderror focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-200">
                <span class="flex items-center bg-slate-50 px-4 text-sm font-bold text-slate-500">Rp</span>
                <input type="number" id="jumlah" name="jumlah" value="{{ old('jumlah', $iuranBulanan->jumlah) }}"
                    min="1" step="1"
                    class="flex-1 bg-white px-4 py-3 text-sm focus:outline-hidden"
                    placeholder="50000" required>
            </div>
            @error('jumlah')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Keterangan --}}
        <div class="mb-5">
            <label for="keterangan" class="block text-sm font-bold text-slate-700">Keterangan</label>
            <textarea id="keterangan" name="keterangan" rows="3"
                class="mt-2 w-full rounded-2xl border @error('keterangan') border-red-500 @else border-slate-200 @enderror bg-white px-4 py-3 text-sm focus:border-amber-500 focus:outline-hidden focus:ring-2 focus:ring-amber-200"
                placeholder="Opsional — deskripsi singkat komponen ini">{{ old('keterangan', $iuranBulanan->keterangan) }}</textarea>
            @error('keterangan')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Sifat --}}
        <div class="mb-6">
            <p class="text-sm font-bold text-slate-700">Sifat Iuran <span class="text-red-500">*</span></p>
            <div class="mt-3 flex gap-4">
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-emerald-50 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50 flex-1">
                    <input type="radio" name="is_wajib" value="1"
                        {{ old('is_wajib', $iuranBulanan->is_wajib ? '1' : '0') == '1' ? 'checked' : '' }}
                        class="mt-1 border-emerald-300 text-emerald-700 focus:ring-emerald-600">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Wajib</p>
                        <p class="text-xs text-slate-500">Semua rumah aktif dikenakan iuran ini.</p>
                    </div>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-sky-50 has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50 flex-1">
                    <input type="radio" name="is_wajib" value="0"
                        {{ old('is_wajib', $iuranBulanan->is_wajib ? '1' : '0') == '0' ? 'checked' : '' }}
                        class="mt-1 border-sky-300 text-sky-700 focus:ring-sky-600">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Opsional</p>
                        <p class="text-xs text-slate-500">Tidak semua rumah wajib membayar.</p>
                    </div>
                </label>
            </div>
            @error('is_wajib')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Info periode (readonly) --}}
        <div class="mb-6 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Periode:
            <strong>{{ \Carbon\Carbon::create(null, $iuranBulanan->bulan)->translatedFormat('F') }} {{ $iuranBulanan->tahun }}</strong>
            — periode tidak dapat diubah.
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="rounded-2xl bg-amber-600 px-6 py-3 text-sm font-bold text-white shadow-xs hover:bg-amber-700 transition">
                Simpan Perubahan
            </button>
            <a href="{{ route('iuran-bulanan.index', ['bulan' => $iuranBulanan->bulan, 'tahun' => $iuranBulanan->tahun]) }}"
               class="rounded-2xl border border-slate-200 px-6 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
