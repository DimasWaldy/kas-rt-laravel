@extends('layouts.app')

@section('title', 'Buat Pemilihan Baru')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.evoting.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-purple-700 dark:text-slate-400 dark:hover:text-purple-300">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Pemilihan
            </a>
            <h1 class="mt-2 text-2xl font-black text-slate-900 dark:text-slate-100">Buat Agenda Pemilihan Baru</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Atur judul pemilu, lingkup wilayah pemilih, dan tanggal periode pencoblosan.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-xs font-bold text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:p-8" x-data="{ scope: '{{ old('scope_type', 'rt') }}' }">
        <form method="POST" action="{{ route('admin.evoting.store') }}" class="space-y-6">
            @csrf

            {{-- Judul Pemilihan --}}
            <div class="space-y-1.5">
                <label for="title" class="text-xs font-bold text-slate-700 dark:text-slate-300">Judul Agenda Pemilihan <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    name="title"
                    id="title"
                    value="{{ old('title') }}"
                    required
                    placeholder="Contoh: Pemilihan Ketua RT 01 Periode 2026-2031"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 focus:border-purple-600 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
            </div>

            {{-- Lingkup Wilayah --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Tingkat Wilayah Pemilihan <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-xs font-bold transition" :class="scope === 'rt' ? 'border-purple-600 bg-purple-50 text-purple-900 dark:bg-purple-950/60 dark:text-purple-200' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-400'">
                            <input type="radio" name="scope_type" value="rt" x-model="scope" class="hidden">
                            <i class="fa-solid fa-house-chimney"></i> Tingkat RT
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-xs font-bold transition" :class="scope === 'rw' ? 'border-purple-600 bg-purple-50 text-purple-900 dark:bg-purple-950/60 dark:text-purple-200' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-400'">
                            <input type="radio" name="scope_type" value="rw" x-model="scope" class="hidden">
                            <i class="fa-solid fa-building"></i> Tingkat RW
                        </label>
                    </div>
                </div>

                <div class="space-y-1.5" x-show="scope === 'rt'">
                    <label for="rt_id" class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih RT Pemilihan <span class="text-rose-500">*</span></label>
                    <select name="rt_id" id="rt_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 focus:border-purple-600 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">-- Pilih RT --</option>
                        @foreach($rts as $rt)
                            <option value="{{ $rt->id }}" @selected(old('rt_id', auth()->user()->rt_id) == $rt->id)>RT {{ $rt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5" x-show="scope === 'rw'" style="display: none;">
                    <label for="rw_id" class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih RW Pemilihan <span class="text-rose-500">*</span></label>
                    <select name="rw_id" id="rw_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 focus:border-purple-600 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">-- Pilih RW --</option>
                        @foreach($rws as $rw)
                            <option value="{{ $rw->id }}" @selected(old('rw_id') == $rw->id)>RW {{ $rw->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Periode Pemilihan --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="start_date" class="text-xs font-bold text-slate-700 dark:text-slate-300">Waktu Mulai Pencoblosan <span class="text-rose-500">*</span></label>
                    <input
                        type="datetime-local"
                        name="start_date"
                        id="start_date"
                        value="{{ old('start_date', now()->format('Y-m-d\TH:i')) }}"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 focus:border-purple-600 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >
                </div>

                <div class="space-y-1.5">
                    <label for="end_date" class="text-xs font-bold text-slate-700 dark:text-slate-300">Waktu Selesai Pencoblosan <span class="text-rose-500">*</span></label>
                    <input
                        type="datetime-local"
                        name="end_date"
                        id="end_date"
                        value="{{ old('end_date', now()->addDays(3)->format('Y-m-d\T23:59')) }}"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 focus:border-purple-600 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >
                </div>
            </div>

            {{-- Deskripsi / Tata Tertib --}}
            <div class="space-y-1.5">
                <label for="description" class="text-xs font-bold text-slate-700 dark:text-slate-300">Keterangan / Tata Tertib Pemilihan (Opsional)</label>
                <textarea
                    name="description"
                    id="description"
                    rows="3"
                    placeholder="Ketentuan pemilih, tata cara, dan catatan penting bagi warga pemilih..."
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 focus:border-purple-600 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('admin.evoting.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-xs font-bold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-6 py-3 text-xs font-bold text-white shadow-xs transition hover:bg-purple-800">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>Simpan & Tambah Paslon</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
