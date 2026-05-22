@extends('layouts.app')

@section('title', 'Tambah Warga Baru')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="rounded-3xl bg-white shadow-sm p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Tambah Warga Baru</h1>
            <p class="mt-2 text-sm text-slate-500">Isi form di bawah untuk menambahkan warga baru ke sistem.</p>
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

        <form action="{{ route('admin.warga.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has('name') ? 'border-red-500' : '' }}" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has('email') ? 'border-red-500' : '' }}" required>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-blue-500 focus:ring-blue-200 {{ $errors->has('password') ? 'border-red-500' : '' }}" required>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">No. KK</label>
                    <input type="text" name="no_kk" value="{{ old('no_kk') }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                    @error('no_kk')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">RT</label>
                    <input type="text" name="rt" value="{{ old('rt') }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                    @error('rt')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">RW</label>
                    <input type="text" name="rw" value="{{ old('rw') }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                    @error('rw')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Jumlah Anggota Keluarga</label>
                    <input type="number" name="jumlah_anggota_keluarga" value="{{ old('jumlah_anggota_keluarga') }}" min="0" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-blue-500 focus:ring-blue-200">
                    @error('jumlah_anggota_keluarga')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_kepala_keluarga" value="1" {{ old('is_kepala_keluarga') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Kepala Keluarga
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('admin.warga.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">Tambah Warga</button>
            </div>
        </form>
    </div>
</div>
@endsection
