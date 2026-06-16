@extends('layouts.app')

@section('title', 'Ajukan Peminjaman Aset')

@section('content')
@php
    $asetOptions = $asets->map(fn ($aset) => [
        'id' => $aset->id,
        'nama' => $aset->nama,
        'jumlah_tersedia' => $aset->jumlah_tersedia,
    ])->values();
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('aset.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke aset</a>

    <form method="POST" action="{{ route('peminjaman-aset.store') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" x-data="{ asetId: '{{ old('aset_id', $asetTerpilih?->id) }}', mulai: '{{ old('tanggal_mulai') }}', selesai: '{{ old('tanggal_selesai') }}', asets: @js($asetOptions), get selectedAset() { return this.asets.find((item) => String(item.id) === String(this.asetId)); }, get durasi() { if (!this.mulai || !this.selesai) return 0; const start = new Date(this.mulai); const end = new Date(this.selesai); const diff = Math.floor((end - start) / 86400000) + 1; return diff > 0 ? diff : 0; } }">
        @csrf
        <div>
            <p class="text-sm font-semibold text-emerald-700">Form peminjaman</p>
            <h1 class="text-2xl font-black text-slate-900">Ajukan Peminjaman Aset</h1>
            <p class="mt-1 text-sm text-slate-500">Pengurus RT akan memeriksa jadwal dan menyetujui pengajuan Anda.</p>
        </div>

        <div class="mt-7 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Aset</label>
                <select name="aset_id" x-model="asetId" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Pilih aset</option>
                    @foreach($asets as $aset)
                        <option value="{{ $aset->id }}" @selected((int) old('aset_id', $asetTerpilih?->id) === $aset->id)>
                            {{ $aset->nama }} - tersedia {{ $aset->jumlah_tersedia }} / {{ $aset->jumlah_total }}
                        </option>
                    @endforeach
                </select>
                @error('aset_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                <template x-if="selectedAset && selectedAset.jumlah_tersedia === 0">
                    <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800">Aset ini sedang tidak tersedia.</p>
                </template>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" x-model="mulai" value="{{ old('tanggal_mulai') }}" min="{{ now()->toDateString() }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('tanggal_mulai')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" x-model="selesai" value="{{ old('tanggal_selesai') }}" min="{{ old('tanggal_mulai', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('tanggal_selesai')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jumlah Dipinjam</label>
                <input type="number" name="jumlah_dipinjam" value="{{ old('jumlah_dipinjam', 1) }}" min="1" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('jumlah_dipinjam')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-2xl bg-emerald-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Durasi</p>
                <p class="mt-1 text-xl font-black text-emerald-900"><span x-text="durasi"></span> hari</p>
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Keperluan</label>
                <input type="text" name="keperluan" value="{{ old('keperluan') }}" required minlength="5" maxlength="255" placeholder="Contoh: acara kerja bakti RT" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('keperluan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Catatan Pemohon</label>
                <textarea name="catatan_pemohon" rows="4" maxlength="500" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Catatan tambahan opsional">{{ old('catatan_pemohon') }}</textarea>
                @error('catatan_pemohon')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('peminjaman-aset.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
            <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Kirim Pengajuan</button>
        </div>
    </form>
</div>
@endsection
