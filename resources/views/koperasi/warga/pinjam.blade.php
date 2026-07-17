@extends('layouts.app')

@section('title', 'Ajukan Pinjaman')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-indigo-700">Koperasi Smart RW</p>
        <h1 class="text-2xl font-black text-slate-900">Ajukan Pinjaman</h1>
        <p class="mt-1 text-sm text-slate-500">Simulasikan total pengembalian sebelum mengirim pengajuan ke bendahara.</p>
    </div>

    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]" x-data="{ amount: Number(@js(old('amount', 0))), tenor: Number(@js(old('tenor_months', 1))), simpananWajib: Number(@js($simpananWajibTerverifikasi)), format(value) { return new Intl.NumberFormat('id-ID').format(value || 0) }, get fee() { return Math.round(this.amount * 0.02 * this.tenor) }, get total() { return this.amount + this.fee }, get highLoanNeedsSaving() { return this.amount >= 1000000 && this.simpananWajib < 50000 } }">
        <form method="POST" action="{{ route('koperasi.store-pinjam') }}" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
                <p class="font-black">Ketentuan pinjaman</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Jasa pinjaman 2% flat per bulan.</li>
                    <li>Hanya boleh punya satu pinjaman aktif atau menunggu persetujuan.</li>
                    <li>Pinjaman Rp 1.000.000 atau lebih wajib punya simpanan wajib terverifikasi minimal Rp 50.000.</li>
                    <li>Pencairan menunggu persetujuan bendahara/pengurus.</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                <p class="font-black">Simpanan wajib terverifikasi Anda</p>
                <p class="mt-1 text-2xl font-black">Rp {{ number_format($simpananWajibTerverifikasi, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs font-semibold text-emerald-700">Syarat pinjaman Rp 1.000.000 atau lebih: minimal Rp 50.000.</p>
            </div>

            <div>
                <label for="amount" class="text-sm font-bold text-slate-700">Nominal Pinjaman</label>
                <input id="amount" name="amount" type="number" min="50000" required x-model.number="amount" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Minimal 50000">
                <p x-show="highLoanNeedsSaving" x-cloak class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">
                    Pinjaman Rp 1.000.000 atau lebih butuh simpanan wajib terverifikasi minimal Rp 50.000.
                </p>
                @error('amount')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="tenor_months" class="text-sm font-bold text-slate-700">Tenor</label>
                <select id="tenor_months" name="tenor_months" required x-model.number="tenor" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @for($i = 1; $i <= 24; $i++)
                        <option value="{{ $i }}">{{ $i }} bulan</option>
                    @endfor
                </select>
                @error('tenor_months')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('koperasi.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Batal</a>
                <button class="rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-800">Ajukan Pinjaman</button>
            </div>
        </form>

        <aside class="rounded-3xl bg-gradient-to-br from-indigo-700 to-blue-600 p-6 text-white shadow-lg shadow-indigo-700/20">
            <p class="text-sm font-bold text-indigo-100">Estimasi Pengembalian</p>
            <div class="mt-6 space-y-4">
                <div class="rounded-2xl bg-white/15 p-4">
                    <p class="text-xs font-bold text-indigo-100">Pokok</p>
                    <p class="mt-1 text-2xl font-black">Rp <span x-text="format(amount)"></span></p>
                </div>
                <div class="rounded-2xl bg-white/15 p-4">
                    <p class="text-xs font-bold text-indigo-100">Jasa 2% x tenor</p>
                    <p class="mt-1 text-2xl font-black">Rp <span x-text="format(fee)"></span></p>
                </div>
                <div class="rounded-2xl bg-white p-4 text-indigo-900">
                    <p class="text-xs font-bold text-indigo-500">Total Bayar</p>
                    <p class="mt-1 text-3xl font-black">Rp <span x-text="format(total)"></span></p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
