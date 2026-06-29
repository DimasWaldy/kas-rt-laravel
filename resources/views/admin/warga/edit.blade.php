@extends('layouts.app')

@section('title', 'Edit Profil Warga')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="rounded-3xl bg-white shadow-sm p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Edit Profil Warga</h1>
            <p class="mt-2 text-sm text-slate-500">Perbarui data warga, KK, dan rumah/unit hunian untuk dasar tagihan iuran.</p>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.warga.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nama</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200" required>
                </div>

                <div class="lg:col-span-2 rounded-3xl border border-emerald-100 bg-emerald-50/60 p-5">
                    <h2 class="text-sm font-bold text-emerald-900">Data Rumah / Unit Hunian</h2>
                    <p class="mt-1 text-xs text-emerald-700">Tagihan iuran dibuat per rumah. Gunakan penanggung jawab untuk menentukan akun yang menerima tagihan.</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Pilih Rumah yang Sudah Ada</label>
                            <select name="rumah_id" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                                <option value="">Buat rumah baru / belum ditentukan</option>
                                @foreach($rumahs as $rumah)
                                    <option value="{{ $rumah->id }}" {{ old('rumah_id', $user->rumah_id) == $rumah->id ? 'selected' : '' }}>{{ $rumah->label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Kode Rumah Baru</label>
                            <input type="text" name="rumah_kode" value="{{ old('rumah_kode') }}" placeholder="Contoh: A-01" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Alamat Rumah Baru</label>
                            <input type="text" name="rumah_alamat" value="{{ old('rumah_alamat') }}" placeholder="Contoh: Jl. Melati No. 1" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">No. KK</label>
                    <input type="text" name="no_kk" value="{{ old('no_kk', $user->warga?->kartuKeluarga?->no_kk) }}" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" autocomplete="off" placeholder="16 digit angka" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="numeric" pattern="[0-9]{10,13}" maxlength="13" autocomplete="tel" placeholder="10-13 digit angka" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                </div>

                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_kepala_keluarga" value="1" {{ old('is_kepala_keluarga', $user->warga?->status_dalam_kk === 'kepala_keluarga') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Kepala Keluarga
                    </label>
                </div>

                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_penanggung_jawab_rumah" value="1" {{ old('is_penanggung_jawab_rumah', $user->is_penanggung_jawab_rumah) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Penanggung Jawab Iuran Rumah
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('admin.warga.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
