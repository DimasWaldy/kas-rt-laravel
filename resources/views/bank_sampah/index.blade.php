@extends('layouts.app')

@section('title', 'Bank Sampah')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Tabungan lingkungan Smart RW</p>
            <h1 class="text-2xl font-black text-slate-900">Bank Sampah RW</h1>
            <p class="mt-1 text-sm text-slate-500">Setor sampah, kumpulkan saldo, tarik tunai, atau tukar hadiah.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('setoran-sampah.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">
                <i class="fa-solid fa-recycle"></i> Setor Sampah
            </a>
            <a href="{{ route('hadiah-sampah.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100">
                <i class="fa-solid fa-gift"></i> Tukar Hadiah
            </a>
        </div>
    </div>

    <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wider text-emerald-700">Panduan Bank Sampah</p>
                <h2 class="mt-1 text-xl font-black text-emerald-950">Jadwal layanan: Rabu 16.00-18.00 dan Minggu 08.00-11.00</h2>
                <div class="mt-4 grid gap-3 text-sm text-emerald-900 md:grid-cols-2">
                    <div class="rounded-2xl bg-white/70 p-4">
                        <p class="font-black">Saat petugas ada</p>
                        <p class="mt-1">Datang ke pos Bank Sampah, sampah ditimbang petugas, lalu petugas memverifikasi berat aktual.</p>
                    </div>
                    <div class="rounded-2xl bg-white/70 p-4">
                        <p class="font-black">Setor mandiri</p>
                        <p class="mt-1">Beri label nama + RT, timbang jika tersedia, foto bukti sampah berlabel, lalu ajukan lewat aplikasi.</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-emerald-800 shadow-sm">
                Sampah wajib dipilah, bersih, dan kering.
            </div>
        </div>
    </section>

    @if($canManage)
        <div class="grid gap-4 md:grid-cols-5">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Saldo Beredar</p>
                <p class="mt-2 text-2xl font-black text-slate-900">Rp {{ number_format($statistik['total_saldo_beredar'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Setoran Bulan Ini</p>
                <p class="mt-2 text-2xl font-black text-emerald-700">Rp {{ number_format($statistik['total_setoran_bulan_ini'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Warga Aktif</p>
                <p class="mt-2 text-2xl font-black text-blue-700">{{ $statistik['total_warga_aktif'] }} orang</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Penarikan Menunggu</p>
                <p class="mt-2 text-2xl font-black text-amber-700">{{ $statistik['penarikan_menunggu'] }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Kas Bank Sampah</p>
                <p class="mt-2 text-2xl font-black text-green-700">Rp {{ number_format($statistik['kas_bank_sampah'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('setoran-sampah.index') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Kelola Setoran</a>
            <a href="{{ route('penarikan-sampah.index') }}" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200">Kelola Penarikan</a>
            <a href="{{ route('hadiah-sampah.index') }}" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200">Kelola Hadiah</a>
            <a href="{{ route('penjualan-sampah.index') }}" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200">Penjualan ke Pengepul</a>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-black text-slate-900">Setoran Menunggu Verifikasi</h2>
                <a href="{{ route('setoran-sampah.index', ['status' => 'menunggu']) }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">Lihat semua</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Warga</th>
                            <th class="px-4 py-3">Jenis</th>
                            <th class="px-4 py-3">Estimasi</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($setoranMenunggu as $setoran)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $setoran->warga->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $setoran->jenisSampah->nama }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ number_format($setoran->estimasi_berat, 2, ',', '.') }} {{ $setoran->jenisSampah->satuan_label }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $setoran->tanggal_setor->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('setoran-sampah.index', ['status' => 'menunggu']) }}" class="font-bold text-emerald-700">Proses</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada setoran menunggu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="rounded-3xl bg-gradient-to-br from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-700/20">
            <p class="text-sm font-bold text-emerald-100">Saldo Bank Sampah Saya</p>
            <p class="mt-2 text-4xl font-black">Rp {{ number_format($saldoSaya->saldo, 0, ',', '.') }}</p>
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-white/15 p-4">
                    <p class="text-xs font-bold text-emerald-100">Total Setor</p>
                    <p class="mt-1 font-black">Rp {{ number_format($saldoSaya->total_setor, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 p-4">
                    <p class="text-xs font-bold text-emerald-100">Total Tarik</p>
                    <p class="mt-1 font-black">Rp {{ number_format($saldoSaya->total_tarik, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 p-4">
                    <p class="text-xs font-bold text-emerald-100">Total Tukar</p>
                    <p class="mt-1 font-black">Rp {{ number_format($saldoSaya->total_tukar, 0, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <div class="grid gap-3 sm:grid-cols-3">
            <a href="{{ route('setoran-sampah.create') }}" class="rounded-2xl border border-emerald-200 bg-white p-5 text-center font-bold text-emerald-800 shadow-sm hover:bg-emerald-50">Setor Sampah</a>
            <a href="{{ route('penarikan-sampah.create') }}" class="rounded-2xl border border-emerald-200 bg-white p-5 text-center font-bold text-emerald-800 shadow-sm hover:bg-emerald-50">Tarik Saldo</a>
            <a href="{{ route('hadiah-sampah.index') }}" class="rounded-2xl border border-emerald-200 bg-white p-5 text-center font-bold text-emerald-800 shadow-sm hover:bg-emerald-50">Tukar Hadiah</a>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">Riwayat Transaksi Terbaru</h2>
            <div class="space-y-3">
                @forelse($riwayatSaya as $transaksi)
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-4">
                        <div>
                            <p class="font-bold text-slate-800">{{ str($transaksi->kategori)->replace('_', ' ')->headline() }}</p>
                            <p class="text-xs text-slate-500">{{ $transaksi->created_at->translatedFormat('d M Y H:i') }}</p>
                        </div>
                        <p class="font-black {{ $transaksi->tipe === 'kredit' ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ $transaksi->tipe === 'kredit' ? '+' : '-' }} Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}
                        </p>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada transaksi.</p>
                @endforelse
            </div>
        </section>
    @endif
</div>
@endsection
