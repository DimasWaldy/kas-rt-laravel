@extends('layouts.app')

@section('title', 'Data Kas Masuk')

@section('content')
@php
    $totalMasuk = $data->sum('jumlah');
    $jumlahTransaksi = $data->count();
    $tahunAktif = request('tahun', date('Y'));
@endphp

<div class="space-y-6" x-data="{ showKasMasukForm: {{ old('_form') === 'kas_masuk' ? 'true' : 'false' }} }">
    <div class="rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-100">
        <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Transaksi Masuk</p>
                <h1 class="mt-2 text-2xl font-black md:text-3xl">Data Kas Masuk</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50">
                    Semua pemasukan RT tercatat sebagai riwayat transparan. Data yang sudah masuk tidak diedit agar laporan tetap aman.
                </p>
            </div>

            @can('manage-finance')
                <button type="button" x-on:click="showKasMasukForm = true" class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-emerald-800 shadow-sm transition hover:bg-emerald-50">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah Kas Masuk
                </button>
            @endcan
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Total Pemasukan</p>
            <p class="mt-3 text-2xl font-black text-slate-900">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</p>
            <p class="mt-1 text-sm text-slate-500">Berdasarkan filter aktif.</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Transaksi</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $jumlahTransaksi }}</p>
            <p class="mt-1 text-sm text-slate-500">Catatan pemasukan ditemukan.</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Tahun</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $tahunAktif }}</p>
            <p class="mt-1 text-sm text-slate-500">Periode laporan saat ini.</p>
        </div>
    </div>

    <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
        <form method="GET" class="grid gap-4 lg:grid-cols-[1.5fr_1fr_1fr_1fr_auto] lg:items-end">
            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">Cari</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Penyetor atau keterangan"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm font-semibold text-slate-700 focus:border-emerald-600 focus:ring-emerald-200">
                </div>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">Bulan</label>
                <select name="bulan" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-emerald-600 focus:ring-emerald-200">
                    <option value="">Semua Bulan</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">Tahun</label>
                <select name="tahun" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-emerald-600 focus:ring-emerald-200">
                    @for($i = date('Y'); $i >= 2024; $i--)
                        <option value="{{ $i }}" {{ request('tahun', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">Urutan</label>
                <select name="filter" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-emerald-600 focus:ring-emerald-200">
                    <option value="terbaru" {{ request('filter', 'terbaru') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                    <option value="terlama" {{ request('filter') == 'terlama' ? 'selected' : '' }}>Terlama</option>
                    <option value="terbesar" {{ request('filter') == 'terbesar' ? 'selected' : '' }}>Nominal terbesar</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-emerald-800 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-900 lg:flex-none">
                    <i class="fa-solid fa-filter mr-2"></i>
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'bulan', 'filter']))
                    <a href="{{ route('kas-masuk.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-slate-500 transition hover:bg-slate-50">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-emerald-50">
                    <tr>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Penyetor</th>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Keterangan</th>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Tanggal</th>
                        <th class="px-5 py-4 text-right text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($data as $item)
                        <tr class="transition hover:bg-emerald-50/60">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-sm font-black text-emerald-700">
                                        {{ substr($item->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ $item->user->name ?? 'Anonim' }}</p>
                                        <p class="text-xs text-slate-400">Pemasukan RT</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-700">{{ $item->keterangan }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                    <i class="fa-regular fa-calendar mr-2 text-emerald-600"></i>
                                    {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="font-black text-emerald-600">+ Rp {{ number_format($item->jumlah, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-300">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                    <p class="mt-4 font-bold text-slate-500">Data kas masuk tidak ditemukan.</p>
                                    <a href="{{ route('kas-masuk.index') }}" class="mt-2 text-sm font-bold text-emerald-700 hover:underline">Reset filter</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('manage-finance')
        <div x-cloak x-show="showKasMasukForm" x-transition.opacity class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" x-on:click.self="showKasMasukForm = false">
            <section x-transition class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between bg-emerald-800 p-6 text-white">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-100">Kas Masuk</p>
                        <h2 class="mt-2 text-xl font-black">Tambah Data Pemasukan</h2>
                        <p class="mt-1 text-sm text-emerald-50">Catat pemasukan manual tanpa pindah halaman.</p>
                    </div>
                    <button type="button" x-on:click="showKasMasukForm = false" class="rounded-xl p-2 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Tutup form">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('kas-masuk.store') }}" method="POST" class="space-y-5 p-6">
                    @csrf
                    <input type="hidden" name="_form" value="kas_masuk">

                    @if(old('_form') === 'kas_masuk' && $errors->any())
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                            Periksa kembali data pemasukan yang diisi.
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Keterangan</label>
                        <input type="text" name="keterangan" value="{{ old('_form') === 'kas_masuk' ? old('keterangan') : '' }}"
                            placeholder="Contoh: Saldo awal kas RT"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @if(old('_form') === 'kas_masuk') @error('keterangan') border-red-500 @enderror @endif" required>
                        @if(old('_form') === 'kas_masuk')
                            @error('keterangan')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Jumlah</label>
                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-200 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-200 @if(old('_form') === 'kas_masuk') @error('jumlah') border-red-500 @enderror @endif">
                                <span class="flex items-center bg-emerald-50 px-4 text-sm font-bold text-emerald-800">Rp</span>
                                <input type="number" name="jumlah" value="{{ old('_form') === 'kas_masuk' ? old('jumlah') : '' }}" min="1" step="1"
                                    placeholder="0" class="w-full border-0 px-4 py-3 text-sm focus:ring-0" required>
                            </div>
                            @if(old('_form') === 'kas_masuk')
                                @error('jumlah')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ old('_form') === 'kas_masuk' ? old('tanggal', now()->toDateString()) : now()->toDateString() }}"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @if(old('_form') === 'kas_masuk') @error('tanggal') border-red-500 @enderror @endif" required>
                            @if(old('_form') === 'kas_masuk')
                                @error('tanggal')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900">
                        Untuk iuran warga, lebih rapi gunakan alur Tagihan. Form ini khusus pemasukan tambahan di luar tagihan bulanan.
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                        <button type="button" x-on:click="showKasMasukForm = false" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-100 transition hover:bg-emerald-900">
                            Simpan Pemasukan
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endcan
</div>

@endsection
