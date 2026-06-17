@extends('layouts.app')

@section('title', 'Tarik Saldo Bank Sampah')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-emerald-700">Bank Sampah RW</p>
        <h1 class="text-2xl font-black text-slate-900">Tarik Saldo</h1>
        <p class="mt-1 text-sm text-slate-500">Pengajuan akan dikonfirmasi petugas setelah pembayaran tunai dilakukan.</p>
    </div>

    <form method="POST" action="{{ route('penarikan-sampah.store') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ saldo: {{ $saldo->saldo }}, jumlah: '{{ old('jumlah', '') }}' }">
        @csrf

        <div class="mb-6 rounded-2xl bg-emerald-50 p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Saldo Saat Ini</p>
            <p class="mt-1 text-3xl font-black text-emerald-900">Rp {{ number_format($saldo->saldo, 0, ',', '.') }}</p>
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Jumlah Penarikan</label>
            <input type="number" name="jumlah" min="1000" max="{{ $saldo->saldo }}" x-model="jumlah" required class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <p x-show="Number(jumlah) > saldo" class="mt-2 text-xs font-bold text-red-600">Jumlah tidak boleh melebihi saldo.</p>
        </div>

        <div class="mt-5">
            <label class="mb-2 block text-sm font-bold text-slate-700">Catatan</label>
            <textarea name="catatan_warga" rows="4" class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('catatan_warga') }}</textarea>
        </div>

        <div class="mt-6 flex gap-3">
            <button :disabled="Number(jumlah) > saldo" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">Ajukan Penarikan</button>
            <a href="{{ route('bank-sampah.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Batal</a>
        </div>
    </form>
</div>
@endsection
