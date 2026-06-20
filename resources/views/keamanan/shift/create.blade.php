@extends('layouts.app')

@section('title', 'Tambah Shift Satpam')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('keamanan.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali</a>
        <h1 class="mt-3 text-2xl font-black text-slate-900">Tambah Shift Satpam</h1>
        <p class="text-sm text-slate-500">Catat jadwal piket keamanan RW.</p>
    </div>

    <form method="POST" action="{{ route('keamanan.shift.store') }}" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Nama Satpam</label>
            <input type="text" name="nama_satpam" value="{{ old('nama_satpam') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            @error('nama_satpam')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Kontak Satpam</label>
            <input type="text" name="kontak_satpam" value="{{ old('kontak_satpam') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            @error('kontak_satpam')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Shift</label>
                <select name="shift" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    @foreach(['pagi' => 'Pagi', 'siang' => 'Siang', 'malam' => 'Malam'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('shift') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('shift')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Jam mulai</label>
                <input type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                @error('jam_mulai')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Jam selesai</label>
                <input type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                @error('jam_selesai')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Tanggal</label>
            <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            @error('tanggal')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('keamanan.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-2xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">Simpan Shift</button>
        </div>
    </form>
</div>
@endsection
