@extends('layouts.app')

@section('title', 'Buat Tagihan Baru')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="rounded-3xl bg-white shadow-sm p-6">
        <h1 class="text-2xl font-bold text-slate-900">Buat Tagihan Baru</h1>
        <p class="mt-2 text-sm text-slate-600">Tambahkan tagihan iuran untuk kepala keluarga.</p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-3xl bg-white shadow-sm p-6">
        <form action="{{ route('tagihan.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700">Kepala Keluarga <span class="text-red-500">*</span></label>
                <select name="user_id" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has('user_id') ? 'border-red-500' : '' }}" required>
                    <option value="">Pilih Kepala Keluarga</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->rt?->name ?? 'RT belum ditentukan' }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Bulan <span class="text-red-500">*</span></label>
                    <select name="bulan" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has('bulan') ? 'border-red-500' : '' }}" required>
                        <option value="">Pilih Bulan</option>
                        @foreach($bulanList as $numBulan => $namaBulan)
                            <option value="{{ $numBulan }}" {{ old('bulan') == $numBulan ? 'selected' : '' }}>
                                {{ $namaBulan }}
                            </option>
                        @endforeach
                    </select>
                    @error('bulan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Tahun <span class="text-red-500">*</span></label>
                    <select name="tahun" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has('tahun') ? 'border-red-500' : '' }}" required>
                        <option value="">Pilih Tahun</option>
                        @foreach($tahunList as $tahun)
                            <option value="{{ $tahun }}" {{ old('tahun') == $tahun ? 'selected' : '' }}>
                                {{ $tahun }}
                            </option>
                        @endforeach
                    </select>
                    @error('tahun')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Jumlah Tagihan <span class="text-red-500">*</span></label>
                <div class="relative mt-2">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-700 font-semibold">Rp</span>
                    <input type="number" name="total" value="{{ old('total') }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pl-10 text-slate-900 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has('total') ? 'border-red-500' : '' }}" placeholder="0" min="1000" step="1000" required>
                </div>
                @error('total')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Catatan (Opsional)</label>
                <textarea name="note" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:ring-blue-200" placeholder="Tambahkan catatan jika ada...">{{ old('note') }}</textarea>
                @error('note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 justify-end pt-4">
                <a href="{{ route('tagihan.admin') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">
                    Buat Tagihan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
