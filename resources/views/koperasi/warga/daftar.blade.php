@extends('layouts.app')

@section('title', 'Daftar Koperasi')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-indigo-700">Koperasi Smart RW</p>
        <h1 class="text-2xl font-black text-slate-900">Daftar Anggota Koperasi</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar dulu sebagai anggota. Bendahara akan memverifikasi sebelum Anda bisa menabung atau mengajukan pinjaman.</p>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    <section class="overflow-hidden rounded-3xl border border-indigo-100 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-indigo-700 to-blue-600 p-6 text-white">
            <p class="text-sm font-bold text-indigo-100">Simpan pinjam warga</p>
            <h2 class="mt-2 text-3xl font-black">Koperasi untuk kebutuhan kecil warga, dikelola transparan.</h2>
            <div class="mt-5 grid gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-2xl bg-white/15 p-4"><p class="font-black">Simpanan</p><p class="mt-1 text-indigo-50">Pokok, wajib, dan sukarela.</p></div>
                <div class="rounded-2xl bg-white/15 p-4"><p class="font-black">Pinjaman</p><p class="mt-1 text-indigo-50">Jasa flat 2% per bulan.</p></div>
                <div class="rounded-2xl bg-white/15 p-4"><p class="font-black">Verifikasi</p><p class="mt-1 text-indigo-50">Diproses bendahara/pengurus.</p></div>
            </div>
        </div>

        <form method="POST" action="{{ route('koperasi.store-daftar') }}" class="space-y-5 p-6">
            @csrf
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Setelah klik daftar, status Anda menjadi <strong>menunggu verifikasi</strong>. Menu transaksi baru aktif setelah bendahara menyetujui keanggotaan.
            </div>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-800 sm:w-auto">
                <i class="fa-solid fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>
    </section>
</div>
@endsection
