@extends('layouts.app')

@section('title', 'Data Kas Keluar')

@section('content')
@php
    $totalKeluar = $data->sum('jumlah');
    $jumlahTransaksi = $data->count();
    $tahunAktif = request('tahun', date('Y'));
@endphp

<div class="space-y-6" x-data="{ showKasKeluarForm: {{ old('_form') === 'kas_keluar' ? 'true' : 'false' }} }">
    <div class="rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-800 to-green-700 p-6 text-white shadow-lg shadow-emerald-100">
        <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Transaksi Keluar</p>
                <h1 class="mt-2 text-2xl font-black md:text-3xl">Data Kas Keluar</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50">
                    Pantau setiap pengeluaran RT lengkap dengan nominal, tanggal, dan bukti nota bila tersedia.
                </p>
            </div>

            @can('manage-finance')
                <button type="button" x-on:click="showKasKeluarForm = true" class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-emerald-800 shadow-sm transition hover:bg-emerald-50">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah Kas Keluar
                </button>
            @endcan
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Total Pengeluaran</p>
            <p class="mt-3 text-2xl font-black text-slate-900">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</p>
            <p class="mt-1 text-sm text-slate-500">Berdasarkan filter aktif.</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Transaksi</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $jumlahTransaksi }}</p>
            <p class="mt-1 text-sm text-slate-500">Catatan pengeluaran ditemukan.</p>
        </div>
        <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Tahun</p>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ $tahunAktif }}</p>
            <p class="mt-1 text-sm text-slate-500">Periode laporan saat ini.</p>
        </div>
    </div>

    <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
        <form action="{{ route('kas-keluar.index') }}" method="GET" class="grid gap-4 lg:grid-cols-[1.5fr_1fr_1fr_auto] lg:items-end">
            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">Cari Keperluan</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Lampu, perbaikan, konsumsi"
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
                    @php $currentYear = date('Y'); @endphp
                    @for($y = $currentYear; $y >= $currentYear - 3; $y--)
                        <option value="{{ $y }}" {{ request('tahun', $currentYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-emerald-800 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-900 lg:flex-none">
                    <i class="fa-solid fa-filter mr-2"></i>
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'bulan']))
                    <a href="{{ route('kas-keluar.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-slate-500 transition hover:bg-slate-50">
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
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Keperluan</th>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Tanggal</th>
                        <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Bukti</th>
                        <th class="px-5 py-4 text-right text-xs font-black uppercase tracking-[0.18em] text-emerald-900">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($data as $item)
                        <tr class="transition hover:bg-emerald-50/60">
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-800">{{ $item->keterangan }}</p>
                                <p class="mt-1 text-xs text-slate-400">Pengeluaran RT</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                    <i class="fa-regular fa-calendar mr-2 text-emerald-600"></i>
                                    {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if($item->bukti)
                                    <button type="button"
                                        class="inline-flex items-center rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100"
                                        data-keterangan="{{ $item->keterangan }}"
                                        data-jumlah="{{ number_format($item->jumlah, 0, ',', '.') }}"
                                        data-tanggal="{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}"
                                        data-bukti="{{ route('kas-keluar.bukti', $item) }}"
                                        data-filetype="{{ pathinfo($item->bukti, PATHINFO_EXTENSION) }}"
                                        onclick="openExpenseModal(this)">
                                        <i class="fa-solid fa-eye mr-2"></i>
                                        Lihat Bukti
                                    </button>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-400">Tidak ada bukti</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="font-black text-rose-600">- Rp {{ number_format($item->jumlah, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-300">
                                        <i class="fa-solid fa-receipt"></i>
                                    </div>
                                    <p class="mt-4 font-bold text-slate-500">Belum ada catatan pengeluaran.</p>
                                    <a href="{{ route('kas-keluar.index') }}" class="mt-2 text-sm font-bold text-emerald-700 hover:underline">Reset filter</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('manage-finance')
        <div x-cloak x-show="showKasKeluarForm" x-transition.opacity class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" x-on:click.self="showKasKeluarForm = false">
            <section x-transition class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between bg-emerald-800 p-6 text-white">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-100">Kas Keluar</p>
                        <h2 class="mt-2 text-xl font-black">Tambah Data Pengeluaran</h2>
                        <p class="mt-1 text-sm text-emerald-50">Catat pengeluaran dan bukti tanpa pindah halaman.</p>
                    </div>
                    <button type="button" x-on:click="showKasKeluarForm = false" class="rounded-xl p-2 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Tutup form">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('kas-keluar.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-6">
                    @csrf
                    <input type="hidden" name="_form" value="kas_keluar">

                    @if(old('_form') === 'kas_keluar' && $errors->any())
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                            Periksa kembali data pengeluaran yang diisi.
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Keterangan</label>
                        <input type="text" name="keterangan" value="{{ old('_form') === 'kas_keluar' ? old('keterangan') : '' }}"
                            placeholder="Contoh: Pembelian alat kebersihan"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @if(old('_form') === 'kas_keluar') @error('keterangan') border-red-500 @enderror @endif" required>
                        @if(old('_form') === 'kas_keluar')
                            @error('keterangan')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Jumlah</label>
                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-200 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-200 @if(old('_form') === 'kas_keluar') @error('jumlah') border-red-500 @enderror @endif">
                                <span class="flex items-center bg-emerald-50 px-4 text-sm font-bold text-emerald-800">Rp</span>
                                <input type="number" name="jumlah" value="{{ old('_form') === 'kas_keluar' ? old('jumlah') : '' }}" min="1" step="1"
                                    placeholder="0" class="w-full border-0 px-4 py-3 text-sm focus:ring-0" required>
                            </div>
                            @if(old('_form') === 'kas_keluar')
                                @error('jumlah')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ old('_form') === 'kas_keluar' ? old('tanggal', now()->toDateString()) : now()->toDateString() }}"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-200 @if(old('_form') === 'kas_keluar') @error('tanggal') border-red-500 @enderror @endif" required>
                            @if(old('_form') === 'kas_keluar')
                                @error('tanggal')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Bukti Pengeluaran</label>
                        <label class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-emerald-200 bg-emerald-50/60 px-4 py-7 text-center transition hover:bg-emerald-50">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-emerald-700"></i>
                            <span class="mt-2 text-sm font-bold text-emerald-900">Unggah foto bukti</span>
                            <span class="mt-1 text-xs text-emerald-700">Format JPG, JPEG, PNG. Maksimal 2MB.</span>
                            <input type="file" name="bukti" accept="image/jpeg,image/png,image/jpg" class="sr-only">
                        </label>
                        @if(old('_form') === 'kas_keluar')
                            @error('bukti')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                        <button type="button" x-on:click="showKasKeluarForm = false" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-100 transition hover:bg-emerald-900">
                            Simpan Pengeluaran
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endcan
</div>

<div id="expense-modal" class="fixed inset-0 z-[999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="flex items-start justify-between bg-emerald-800 p-6 text-white">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-100">Detail Pengeluaran</p>
                <h2 id="modal-keterangan" class="mt-2 text-xl font-black"></h2>
                <p id="modal-tanggal" class="mt-1 text-sm text-emerald-100"></p>
            </div>
            <button type="button" onclick="closeExpenseModal()" class="rounded-xl p-2 text-white/80 transition hover:bg-white/10 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-6">
            <div class="mb-5 rounded-2xl bg-emerald-50 p-4">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Nominal</p>
                <p class="mt-1 text-2xl font-black text-emerald-900">Rp <span id="modal-jumlah"></span></p>
            </div>
            <div id="modal-gambar" class="overflow-hidden rounded-2xl border border-slate-100 bg-slate-50"></div>
            <button type="button" onclick="closeExpenseModal()" class="mt-6 w-full rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-200">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
function openExpenseModal(button) {
    const modal = document.getElementById('expense-modal');
    const imageUrl = button.dataset.bukti;
    const fileType = (button.dataset.filetype || '').toLowerCase();

    document.getElementById('modal-keterangan').innerText = button.dataset.keterangan;
    document.getElementById('modal-jumlah').innerText = button.dataset.jumlah;
    document.getElementById('modal-tanggal').innerText = button.dataset.tanggal;
    document.getElementById('modal-gambar').innerHTML = fileType === 'pdf'
        ? `<div class="p-8 text-center"><i class="fa-solid fa-file-pdf text-4xl text-rose-500"></i><p class="mt-3 text-sm font-bold text-slate-700">Bukti berupa PDF</p><a href="${imageUrl}" target="_blank" class="mt-4 inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white">Buka PDF</a></div>`
        : `<img src="${imageUrl}" class="max-h-[420px] w-full object-contain" alt="Bukti pengeluaran">`;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeExpenseModal() {
    const modal = document.getElementById('expense-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('expense-modal')?.addEventListener('click', function (event) {
    if (event.target === this) {
        closeExpenseModal();
    }
});
</script>

@endsection
