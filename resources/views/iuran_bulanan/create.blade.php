@extends('layouts.app')

@section('title', 'Tambah Iuran Bulanan')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-6 rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-100">
        <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Komponen Iuran</p>
        <h2 class="mt-2 text-2xl font-black md:text-3xl">Tambah Iuran Bulanan</h2>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50">
            Komponen yang disimpan akan ikut membentuk total tagihan untuk setiap rumah pada periode yang dipilih.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[0.9fr_1.4fr]">
        <aside class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-800">
                <i class="fa-solid fa-list-check text-xl"></i>
            </div>
            <h3 class="mt-5 text-lg font-black text-slate-900">Tips Pengisian</h3>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <p>Contoh komponen: iuran keamanan, kebersihan, atau dana sosial.</p>
                </div>
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <p>Pilih periode dengan benar karena tagihan dibuat berdasarkan bulan dan tahun ini.</p>
                </div>
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <p>Komponen wajib cocok untuk tagihan rutin yang harus dibayar setiap rumah.</p>
                </div>
            </div>
        </aside>

        <section class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                    Periksa kembali data iuran yang diisi.
                </div>
            @endif

            <form action="{{ route('iuran-bulanan.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-slate-700">Nama Iuran</label>
                    <input type="text" name="nama" value="{{ old('nama') }}"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @error('nama') border-red-500 @enderror"
                        placeholder="Contoh: Iuran kebersihan" required>
                    @error('nama')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700">Keterangan</label>
                    <textarea name="keterangan" rows="3"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @error('keterangan') border-red-500 @enderror"
                        placeholder="Opsional, contoh: Iuran rutin untuk petugas kebersihan">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-bold text-slate-700">Jumlah</label>
                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-200 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-200 @error('jumlah') border-red-500 @enderror">
                            <span class="flex items-center bg-emerald-50 px-4 text-sm font-bold text-emerald-800">Rp</span>
                            <input type="number" name="jumlah" value="{{ old('jumlah') }}" min="0"
                                class="w-full border-0 px-4 py-3 text-sm focus:ring-0"
                                placeholder="0" required>
                        </div>
                        @error('jumlah')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Bulan</label>
                        <select name="bulan" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @error('bulan') border-red-500 @enderror" required>
                            @foreach(range(1, 12) as $month)
                                @php
                                    $monthName = \Carbon\Carbon::create(null, $month)->translatedFormat('F');
                                @endphp
                                <option value="{{ $month }}" {{ old('bulan', now()->month) == $month ? 'selected' : '' }}>
                                    {{ $monthName }}
                                </option>
                            @endforeach
                        </select>
                        @error('bulan')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Tahun</label>
                        <input type="number" name="tahun" value="{{ old('tahun', now()->year) }}"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @error('tahun') border-red-500 @enderror" required>
                        @error('tahun')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700">Sifat Iuran</label>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 transition hover:bg-emerald-100">
                            <input type="radio" name="is_wajib" value="1" {{ old('is_wajib', '1') == '1' ? 'checked' : '' }} class="mt-1 border-emerald-300 text-emerald-700 focus:ring-emerald-600">
                            <span>
                                <span class="block text-sm font-black text-emerald-900">Wajib</span>
                                <span class="block text-xs leading-5 text-emerald-700">Masuk tagihan utama rumah.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50 p-4 transition hover:bg-sky-100">
                            <input type="radio" name="is_wajib" value="0" {{ old('is_wajib') == '0' ? 'checked' : '' }} class="mt-1 border-sky-300 text-sky-700 focus:ring-sky-600">
                            <span>
                                <span class="block text-sm font-black text-sky-900">Opsional</span>
                                <span class="block text-xs leading-5 text-sky-700">Untuk komponen tambahan bila diperlukan.</span>
                            </span>
                        </label>
                    </div>
                    @error('is_wajib')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-3xl border border-emerald-100 bg-emerald-50/70 p-4">
                    <div class="flex gap-3">
                        <i class="fa-solid fa-circle-info mt-0.5 text-emerald-700"></i>
                        <p class="text-sm leading-6 text-emerald-900">
                            Setelah iuran disimpan, sistem akan membuat atau memperbarui tagihan untuk rumah pada periode ini.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-between">
                    <a href="{{ route('iuran-bulanan.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-100 transition hover:bg-emerald-900">
                        Simpan Iuran
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
