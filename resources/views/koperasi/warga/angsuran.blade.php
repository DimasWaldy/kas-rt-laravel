@extends('layouts.app')

@section('title', 'Bayar Angsuran')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-blue-700">Koperasi Smart RW</p>
        <h1 class="text-2xl font-black text-slate-900">Bayar Angsuran</h1>
        <p class="mt-1 text-sm text-slate-500">Angsuran mengurangi sisa pinjaman setelah diverifikasi bendahara.</p>
    </div>

    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    <section class="rounded-3xl bg-gradient-to-br from-blue-700 to-indigo-600 p-6 text-white shadow-lg shadow-blue-700/20">
        <p class="text-sm font-bold text-blue-100">Sisa Pinjaman</p>
        <p class="mt-2 text-4xl font-black">Rp {{ number_format($pinjaman->remaining_amount, 0, ',', '.') }}</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-white/15 p-4">
                <p class="text-xs font-bold text-blue-100">Pokok</p>
                <p class="mt-1 font-black">Rp {{ number_format($pinjaman->amount, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-white/15 p-4">
                <p class="text-xs font-bold text-blue-100">Jasa</p>
                <p class="mt-1 font-black">Rp {{ number_format($pinjaman->service_fee_amount, 0, ',', '.') }}</p>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('koperasi.store-angsuran', $pinjaman) }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label for="amount" class="text-sm font-bold text-slate-700">Nominal Angsuran</label>
            <input id="amount" name="amount" type="number" value="{{ old('amount', $pinjaman->remaining_amount) }}" min="10000" max="{{ $pinjaman->remaining_amount }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
            @error('amount')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="paid_at" class="text-sm font-bold text-slate-700">Tanggal Pembayaran</label>
            <input id="paid_at" name="paid_at" type="date" value="{{ old('paid_at', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
            @error('paid_at')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="proof_file" class="text-sm font-bold text-slate-700">Bukti Transfer</label>
            <input id="proof_file" name="proof_file" type="file" accept=".jpg,.jpeg,.png,.pdf" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            @error('proof_file')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('koperasi.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Batal</a>
            <button class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white hover:bg-blue-800">Kirim Angsuran</button>
        </div>
    </form>
</div>
@endsection
