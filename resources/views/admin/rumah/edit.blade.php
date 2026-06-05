@extends('layouts.app')

@section('title', 'Edit Rumah')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-black text-slate-900">Edit Rumah / Unit Hunian</h1>
        <p class="mt-2 text-sm text-slate-500">Atur alamat, status rumah, dan penanggung jawab iuran dengan jelas.</p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.rumah.update', $rumah) }}" class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
        @csrf
        @method('PATCH')

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="block text-sm font-bold text-slate-700">Kode Rumah</label>
                <input type="text" name="kode_rumah" value="{{ old('kode_rumah', $rumah->kode_rumah) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700">Status Rumah</label>
                <select name="status" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200" required>
                    <option value="aktif" {{ old('status', $rumah->status) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="kosong" {{ old('status', $rumah->status) === 'kosong' ? 'selected' : '' }}>Kosong</option>
                    <option value="nonaktif" {{ old('status', $rumah->status) === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-slate-700">Alamat</label>
                <input type="text" name="alamat" value="{{ old('alamat', $rumah->alamat) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700">RT</label>
                <input type="text" name="rt" value="{{ old('rt', $rumah->rt) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700">RW</label>
                <input type="text" name="rw" value="{{ old('rw', $rumah->rw) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-slate-700">Penanggung Jawab Iuran</label>
                <select name="penanggung_jawab_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                    <option value="">Belum ditentukan</option>
                    @foreach($rumah->warga as $warga)
                        <option value="{{ $warga->id }}" {{ old('penanggung_jawab_id', $rumah->penanggung_jawab_id) == $warga->id ? 'selected' : '' }}>
                            {{ $warga->name }}{{ $warga->is_kepala_keluarga ? ' - Kepala KK' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-slate-500">Jika status rumah dibuat kosong/nonaktif, PJ iuran otomatis dikosongkan.</p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <a href="{{ route('admin.rumah.show', $rumah) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</a>
            <button class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Simpan Rumah</button>
        </div>
    </form>
</div>
@endsection
