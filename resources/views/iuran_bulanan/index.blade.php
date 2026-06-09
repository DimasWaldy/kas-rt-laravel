@extends('layouts.app')

@section('title', 'Iuran Bulanan')

@section('content')
@php
    $bulanAktif = now()->month;
    $tahunAktif = now()->year;
    $iuranBulanIni = $items->where('bulan', $bulanAktif)->where('tahun', $tahunAktif);
    $totalBulanIni = $iuranBulanIni->sum('jumlah');
    $wajibBulanIni = $iuranBulanIni->where('is_wajib', true)->count();
    $namaBulanAktif = \Carbon\Carbon::create(null, $bulanAktif)->translatedFormat('F');
@endphp

<div class="space-y-6" x-data="{ showIuranForm: {{ old('_form') === 'iuran_bulanan' ? 'true' : 'false' }} }">
    <div class="rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-100">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Iuran Bulanan</p>
                <h2 class="mt-2 text-2xl font-black md:text-3xl">Pusat Generate Tagihan Rumah</h2>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-emerald-50">
                    Komponen iuran di halaman ini akan dijumlahkan dan dibuat menjadi tagihan per rumah aktif, bukan per KK.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <form action="{{ route('iuran-bulanan.generate') }}" method="POST"
                    onsubmit="return confirm('Sistem akan memproses tagihan rumah untuk periode {{ $namaBulanAktif }} {{ $tahunAktif }}. Lanjutkan?')">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $bulanAktif }}">
                    <input type="hidden" name="tahun" value="{{ $tahunAktif }}">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-emerald-800 shadow-sm transition hover:bg-emerald-50 sm:w-auto">
                        <i class="fa-solid fa-bolt mr-2"></i>
                        Generate Tagihan
                    </button>
                </form>

                <button type="button" x-on:click="showIuranForm = true" class="inline-flex items-center justify-center rounded-2xl border border-white/40 px-5 py-3 text-sm font-black text-white transition hover:bg-white/10">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah Iuran
                </button>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Periode Aktif</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $namaBulanAktif }} {{ $tahunAktif }}</p>
            <p class="mt-1 text-sm text-slate-500">Bulan yang akan diproses tombol generate.</p>
        </div>

        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Total Per Rumah</p>
            <p class="mt-3 text-2xl font-black text-slate-900">Rp {{ number_format($totalBulanIni, 0, ',', '.') }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $iuranBulanIni->count() }} komponen iuran bulan ini.</p>
        </div>

        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Komponen Wajib</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $wajibBulanIni }}</p>
            <p class="mt-1 text-sm text-slate-500">Komponen yang masuk tagihan utama.</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[0.85fr_1.6fr]">
        <aside class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-800">
                <i class="fa-solid fa-house-circle-check text-xl"></i>
            </div>
            <h3 class="mt-5 text-lg font-black text-slate-900">Alur Tagihan Rumah</h3>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-black text-white">1</span>
                    <p>Tambah komponen iuran untuk periode bulan berjalan.</p>
                </div>
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-black text-white">2</span>
                    <p>Klik generate agar sistem membuat tagihan untuk setiap rumah aktif.</p>
                </div>
                <div class="flex gap-3 rounded-2xl bg-emerald-50 p-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-black text-white">3</span>
                    <p>Penanggung jawab rumah membayar dari halaman tagihan warga.</p>
                </div>
            </div>
        </aside>

        <section class="overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h3 class="text-lg font-black text-slate-900">Daftar Komponen Iuran</h3>
                <p class="mt-1 text-sm text-slate-500">Data terbaru ditampilkan dari periode paling baru.</p>
            </div>

            <div class="overflow-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-emerald-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-emerald-900">Nama</th>
                            <th class="px-4 py-3 text-left font-bold text-emerald-900">Keterangan</th>
                            <th class="px-4 py-3 text-left font-bold text-emerald-900">Sifat</th>
                            <th class="px-4 py-3 text-left font-bold text-emerald-900">Jumlah</th>
                            <th class="px-4 py-3 text-left font-bold text-emerald-900">Periode</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $item)
                            @php
                                $namaBulan = \Carbon\Carbon::create(null, $item->bulan)->translatedFormat('F');
                            @endphp
                            <tr class="hover:bg-emerald-50/50">
                                <td class="px-4 py-4 font-bold text-slate-800">{{ $item->nama }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $item->keterangan ?? '-' }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $item->is_wajib ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">
                                        {{ $item->is_wajib ? 'Wajib' : 'Opsional' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 font-bold text-slate-900">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $namaBulan }} {{ $item->tahun }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                    Belum ada iuran bulanan. Tambahkan komponen iuran pertama sebelum generate tagihan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div x-cloak x-show="showIuranForm" x-transition.opacity class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" x-on:click.self="showIuranForm = false">
        <section x-transition class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between bg-emerald-800 p-6 text-white">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-100">Komponen Iuran</p>
                    <h2 class="mt-2 text-xl font-black">Tambah Iuran Bulanan</h2>
                    <p class="mt-1 text-sm text-emerald-50">Komponen baru langsung memproses tagihan rumah pada periode terpilih.</p>
                </div>
                <button type="button" x-on:click="showIuranForm = false" class="rounded-xl p-2 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Tutup form">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('iuran-bulanan.store') }}" method="POST" class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="_form" value="iuran_bulanan">

                @if(old('_form') === 'iuran_bulanan' && $errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        Periksa kembali data iuran yang diisi.
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-bold text-slate-700">Nama Iuran</label>
                    <input type="text" name="nama" value="{{ old('_form') === 'iuran_bulanan' ? old('nama') : '' }}"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @if(old('_form') === 'iuran_bulanan') @error('nama') border-red-500 @enderror @endif"
                        placeholder="Contoh: Iuran kebersihan" required>
                    @if(old('_form') === 'iuran_bulanan')
                        @error('nama')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700">Keterangan</label>
                    <textarea name="keterangan" rows="3"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @if(old('_form') === 'iuran_bulanan') @error('keterangan') border-red-500 @enderror @endif"
                        placeholder="Opsional, contoh: Iuran rutin untuk petugas kebersihan">{{ old('_form') === 'iuran_bulanan' ? old('keterangan') : '' }}</textarea>
                    @if(old('_form') === 'iuran_bulanan')
                        @error('keterangan')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-bold text-slate-700">Jumlah</label>
                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-200 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-200 @if(old('_form') === 'iuran_bulanan') @error('jumlah') border-red-500 @enderror @endif">
                            <span class="flex items-center bg-emerald-50 px-4 text-sm font-bold text-emerald-800">Rp</span>
                            <input type="number" name="jumlah" value="{{ old('_form') === 'iuran_bulanan' ? old('jumlah') : '' }}" min="1" step="1"
                                class="w-full border-0 px-4 py-3 text-sm focus:ring-0" placeholder="0" required>
                        </div>
                        @if(old('_form') === 'iuran_bulanan')
                            @error('jumlah')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Bulan</label>
                        <select name="bulan" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @if(old('_form') === 'iuran_bulanan') @error('bulan') border-red-500 @enderror @endif" required>
                            @foreach(range(1, 12) as $month)
                                @php $monthName = \Carbon\Carbon::create(null, $month)->translatedFormat('F'); @endphp
                                <option value="{{ $month }}" {{ (old('_form') === 'iuran_bulanan' ? old('bulan', now()->month) : now()->month) == $month ? 'selected' : '' }}>
                                    {{ $monthName }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('_form') === 'iuran_bulanan')
                            @error('bulan')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Tahun</label>
                        <input type="number" name="tahun" value="{{ old('_form') === 'iuran_bulanan' ? old('tahun', now()->year) : now()->year }}"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @if(old('_form') === 'iuran_bulanan') @error('tahun') border-red-500 @enderror @endif" required>
                        @if(old('_form') === 'iuran_bulanan')
                            @error('tahun')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700">Sifat Iuran</label>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 transition hover:bg-emerald-100">
                            <input type="radio" name="is_wajib" value="1" {{ old('_form') === 'iuran_bulanan' ? (old('is_wajib', '1') == '1' ? 'checked' : '') : 'checked' }} class="mt-1 border-emerald-300 text-emerald-700 focus:ring-emerald-600">
                            <span>
                                <span class="block text-sm font-black text-emerald-900">Wajib</span>
                                <span class="block text-xs leading-5 text-emerald-700">Masuk tagihan utama rumah.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50 p-4 transition hover:bg-sky-100">
                            <input type="radio" name="is_wajib" value="0" {{ old('_form') === 'iuran_bulanan' && old('is_wajib') == '0' ? 'checked' : '' }} class="mt-1 border-sky-300 text-sky-700 focus:ring-sky-600">
                            <span>
                                <span class="block text-sm font-black text-sky-900">Opsional</span>
                                <span class="block text-xs leading-5 text-sky-700">Untuk komponen tambahan bila diperlukan.</span>
                            </span>
                        </label>
                    </div>
                    @if(old('_form') === 'iuran_bulanan')
                        @error('is_wajib')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                    <button type="button" x-on:click="showIuranForm = false" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-100 transition hover:bg-emerald-900">
                        Simpan Iuran
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
