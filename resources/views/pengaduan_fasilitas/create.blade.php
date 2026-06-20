@extends('layouts.app')

@section('title', 'Buat Pengaduan Fasilitas')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('pengaduan-fasilitas.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali</a>
        <h1 class="mt-3 text-2xl font-black text-slate-900">Buat Pengaduan Fasilitas</h1>
        <p class="text-sm text-slate-500">Isi laporan singkat agar pengurus bisa mengecek dan menindaklanjuti fasilitas bermasalah.</p>
    </div>

    <form method="POST" action="{{ route('pengaduan-fasilitas.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Fasilitas</label>
            <select name="fasilitas_id" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="">Pilih fasilitas</option>
                @foreach($fasilitas as $item)
                    <option value="{{ $item->id }}" @selected((string) old('fasilitas_id', $selectedFasilitasId) === (string) $item->id)>{{ $item->nama }} - {{ $item->lokasi_lengkap ?: 'Lokasi belum diisi' }}</option>
                @endforeach
            </select>
            @error('fasilitas_id')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Jenis Masalah</label>
            <select name="jenis_masalah" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @foreach(['rusak' => 'Rusak', 'mati' => 'Mati/Tidak Berfungsi', 'kotor' => 'Kotor', 'hilang' => 'Hilang', 'lainnya' => 'Lainnya'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('jenis_masalah') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('jenis_masalah')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Deskripsi</label>
            <textarea name="deskripsi" rows="5" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm leading-relaxed focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="Contoh: Lampu jalan depan pos satpam mati sejak tadi malam.">{{ old('deskripsi') }}</textarea>
            @error('deskripsi')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Foto Bukti</label>
            <input type="file" name="foto" accept="image/*" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
            <p class="mt-1 text-xs text-slate-500">Opsional. Format JPG/PNG maksimal 2MB.</p>
            @error('foto')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('pengaduan-fasilitas.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-2xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">Kirim Laporan</button>
        </div>
    </form>
</div>
@endsection
