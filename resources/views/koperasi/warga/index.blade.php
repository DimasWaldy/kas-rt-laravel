@extends('layouts.app')

@section('title', 'Koperasi Saya')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-indigo-700">Koperasi Smart RW</p>
            <h1 class="text-2xl font-black text-slate-900">Koperasi Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau simpanan, pinjaman aktif, sisa pinjaman, dan riwayat angsuran.</p>
        </div>
        @if($memberStatus === 'aktif')
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('koperasi.simpan') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">
                    <i class="fa-solid fa-piggy-bank"></i> Tambah Simpanan
                </a>
                @if($pinjamanAktif->count() === 0)
                    <a href="{{ route('koperasi.pinjam') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-800">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Ajukan Pinjaman
                    </a>
                @endif
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    @if($memberStatus !== 'aktif')
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
            <p class="text-sm font-bold uppercase tracking-wider text-amber-700">Status keanggotaan</p>
            <h2 class="mt-1 text-xl font-black text-amber-950">{{ str($memberStatus)->headline() }}</h2>
            <p class="mt-2 text-sm text-amber-900">
                Keanggotaan Anda belum aktif, jadi transaksi simpanan/pinjaman masih dikunci. Silakan tunggu verifikasi bendahara atau hubungi pengurus RT.
            </p>
        </section>
    @else
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-3xl bg-gradient-to-br from-indigo-700 to-blue-600 p-6 text-white shadow-lg shadow-indigo-700/20 lg:col-span-2">
                <p class="text-sm font-bold text-indigo-100">Total Simpanan Terverifikasi</p>
                <p class="mt-2 text-4xl font-black">Rp {{ number_format($simpananTotal, 0, ',', '.') }}</p>
                <p class="mt-4 max-w-2xl text-sm text-indigo-50">Hanya simpanan yang sudah diverifikasi pengurus yang dihitung sebagai saldo koperasi aktif.</p>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Status Anggota</p>
                <p class="mt-2 text-2xl font-black text-emerald-700">Aktif</p>
                <p class="mt-2 text-sm text-slate-500">Anda bisa menambah simpanan dan mengajukan pinjaman.</p>
            </section>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-black text-slate-900">Pinjaman Aktif</h2>
                @if($pinjamanAktif->count() === 0)
                    <a href="{{ route('koperasi.pinjam') }}" class="text-sm font-bold text-indigo-700 hover:text-indigo-900">Ajukan pinjaman</a>
                @endif
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                @forelse($pinjamanAktif as $pinjam)
                    @php
                        $totalPinjaman = $pinjam->amount + $pinjam->service_fee_amount;
                        $sudahDibayar = $pinjam->angsurans->where('status', 'terverifikasi')->sum('amount');
                    @endphp
                    <article class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="w-full">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ str($pinjam->status)->replace('_', ' ')->headline() }}</p>
                                <p class="mt-1 text-sm font-bold text-slate-500">Sisa Pinjaman</p>
                                <p class="text-2xl font-black text-slate-900">Rp {{ number_format($pinjam->remaining_amount, 0, ',', '.') }}</p>
                                <p class="mt-1 text-sm text-slate-500">Tenor {{ $pinjam->tenor_months }} bulan · jasa Rp {{ number_format($pinjam->service_fee_amount, 0, ',', '.') }}</p>

                                <div class="mt-4 grid gap-2 text-xs sm:grid-cols-3">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="font-bold text-slate-400">Total Pinjaman</p>
                                        <p class="mt-1 font-black text-slate-800">Rp {{ number_format($totalPinjaman, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-xl bg-emerald-50 p-3">
                                        <p class="font-bold text-emerald-600">Sudah Dibayar</p>
                                        <p class="mt-1 font-black text-emerald-800">Rp {{ number_format($sudahDibayar, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-xl bg-amber-50 p-3">
                                        <p class="font-bold text-amber-600">Angsuran Pending</p>
                                        <p class="mt-1 font-black text-amber-800">Rp {{ number_format($pinjam->angsurans->where('status', 'pending')->sum('amount'), 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                            @if($pinjam->status === 'disetujui')
                                <a href="{{ route('koperasi.angsuran', $pinjam) }}" class="shrink-0 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Bayar</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500 md:col-span-2">Belum ada pinjaman aktif.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">Riwayat Angsuran Terbaru</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Tanggal Bayar</th>
                            <th class="px-4 py-3">Nominal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Diverifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($riwayatAngsuran as $angsuran)
                            <tr>
                                <td class="px-4 py-3 text-slate-600">{{ $angsuran->paid_at?->translatedFormat('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3 font-bold text-slate-900">Rp {{ number_format($angsuran->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $angsuran->status === 'terverifikasi' ? 'bg-emerald-50 text-emerald-700' : ($angsuran->status === 'ditolak' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                        {{ str($angsuran->status)->headline() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $angsuran->verified_at?->translatedFormat('d M Y H:i') ?? 'Belum diverifikasi' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada riwayat angsuran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">Riwayat Simpanan Terbaru</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Jenis</th>
                            <th class="px-4 py-3">Nominal</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($riwayatSimpanan as $simpan)
                            <tr>
                                <td class="px-4 py-3 text-slate-600">{{ $simpan->transaction_date->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ str($simpan->type)->headline() }}</td>
                                <td class="px-4 py-3 font-bold text-slate-900">Rp {{ number_format($simpan->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $simpan->status === 'terverifikasi' ? 'bg-emerald-50 text-emerald-700' : ($simpan->status === 'ditolak' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                        {{ str($simpan->status)->headline() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada simpanan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
