@extends('layouts.app')

@section('title', 'Edit Profil Warga')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="rounded-3xl bg-white shadow-sm p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Edit Profil Warga</h1>
            <p class="mt-2 text-sm text-slate-500">Perbarui data warga seperti nomor telepon, nomor KK, dan alamat RT/RW.</p>
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
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-blue-500 focus:ring-blue-200" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-blue-500 focus:ring-blue-200" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">No. KK</label>
                    <input type="text" name="no_kk" value="{{ old('no_kk', $user->no_kk) }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">RT</label>
                    <input type="text" name="rt" value="{{ old('rt', $user->rt) }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">RW</label>
                    <input type="text" name="rw" value="{{ old('rw', $user->rw) }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Jumlah Anggota Keluarga</label>
                    <input type="number" name="jumlah_anggota_keluarga" value="{{ old('jumlah_anggota_keluarga', $user->jumlah_anggota_keluarga) }}" min="0" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                </div>

                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_kepala_keluarga" value="1" {{ old('is_kepala_keluarga', $user->is_kepala_keluarga) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Kepala Keluarga
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('admin.warga.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
