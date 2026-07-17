@extends('layouts.app')

@section('title', 'Tambah Simpanan')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-emerald-700">Koperasi Smart RW</p>
        <h1 class="text-2xl font-black text-slate-900">Tambah Simpanan</h1>
        <p class="mt-1 text-sm text-slate-500">Upload bukti transfer. Saldo baru masuk setelah diverifikasi bendahara.</p>
    </div>

    <form method="POST" action="{{ route('koperasi.store-simpanan') }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label for="type" class="text-sm font-bold text-slate-700">Jenis Simpanan</label>
            <select id="type" name="type" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="wajib" @selected(old('type') === 'wajib')>Simpanan Wajib</option>
                <option value="pokok" @selected(old('type') === 'pokok')>Simpanan Pokok</option>
                <option value="sukarela" @selected(old('type') === 'sukarela')>Simpanan Sukarela</option>
            </select>
            @error('type')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="amount" class="text-sm font-bold text-slate-700">Nominal Simpanan</label>
            <input id="amount" name="amount" type="number" value="{{ old('amount') }}" min="10000" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Minimal 10000">
            @error('amount')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="transaction_date" class="text-sm font-bold text-slate-700">Tanggal Transfer</label>
            <input id="transaction_date" name="transaction_date" type="date" value="{{ old('transaction_date', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('transaction_date')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="proof_file" class="text-sm font-bold text-slate-700">Bukti Transfer</label>
            <input id="proof_file" name="proof_file" type="file" accept=".jpg,.jpeg,.png,.pdf" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-slate-500">Format JPG, PNG, atau PDF. Maksimal 2 MB.</p>
            @error('proof_file')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('koperasi.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Batal</a>
            <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Kirim Simpanan</button>
        </div>
    </form>
</div>
@endsection
