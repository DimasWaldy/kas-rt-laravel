@extends('layouts.app')

@section('title', 'Edit Tagihan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="rounded-3xl bg-white shadow-sm p-6">
        <h1 class="text-2xl font-bold text-slate-900">Edit Tagihan</h1>
        <p class="mt-2 text-sm text-slate-600">Perbarui informasi tagihan iuran.</p>
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
        <form action="{{ route('tagihan.update', $tagihan) }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="space-y-4 p-4 bg-slate-50 rounded-2xl">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Kepala Keluarga</p>
                    <p class="text-lg font-bold text-slate-900 mt-1">{{ $tagihan->user->name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Bulan/Tahun</p>
                        <p class="text-lg font-bold text-slate-900 mt-1">
                            {{ $bulanList[$tagihan->bulan] }} {{ $tagihan->tahun }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Status</p>
                        <p class="text-lg font-bold mt-1">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tagihan->status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ ucwords(str_replace('_', ' ', $tagihan->status)) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Jumlah Tagihan <span class="text-red-500">*</span></label>
                <div class="relative mt-2">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-700 font-semibold">Rp</span>
                    <input type="number" name="total" value="{{ old('total', $tagihan->total) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pl-10 text-slate-900 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has('total') ? 'border-red-500' : '' }}" placeholder="0" min="1000" step="1000" required>
                </div>
                @error('total')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Catatan (Opsional)</label>
                <textarea name="note" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:ring-blue-200" placeholder="Tambahkan catatan jika ada...">{{ old('note', $tagihan->note) }}</textarea>
                @error('note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 justify-end pt-4">
                <a href="{{ route('tagihan.admin') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
