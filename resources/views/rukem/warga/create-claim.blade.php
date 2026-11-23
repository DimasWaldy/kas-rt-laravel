@extends('layouts.app')

@section('title', 'Lapor Berita Duka & Permohonan Santunan')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    {{-- Breadcrumb & Header --}}
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
        <a href="{{ route('rukem.index') }}" class="hover:text-rose-600 dark:hover:text-rose-400">Rukun Kematian</a>
        <i class="fa-solid fa-chevron-right text-3xs"></i>
        <span class="text-slate-800 dark:text-slate-200">Lapor Berita Duka</span>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 sm:p-8">
        <div class="flex items-center gap-4 border-b border-slate-100 pb-5 dark:border-slate-800">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                <i class="fa-solid fa-dove text-2xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-slate-100">Lapor Berita Duka Cita & Pengajuan Santunan</h1>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Isi formulir pemberitahuan duka cita warga lingkungan untuk pengurusan pemakaman dan penyaluran santunan rukun kematian.</p>
            </div>
        </div>

        <form action="{{ route('rukem.store-claim') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <div class="space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                    <i class="fa-solid fa-user"></i> 1. Identitas Almarhum / Almarhumah
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="deceased_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Nama Lengkap Almarhum / Almarhumah <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="deceased_name" name="deceased_name" value="{{ old('deceased_name') }}" placeholder="Contoh: H. Ahmad Subarjo" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-2xs focus:border-rose-600 focus:ring-1 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" required>
                        @error('deceased_name')
                            <p class="mt-1 text-2xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nik_deceased" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            NIK Almarhum (Opsional)
                        </label>
                        <input type="text" id="nik_deceased" name="nik_deceased" value="{{ old('nik_deceased') }}" maxlength="16" placeholder="16 digit NIK jika ada" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-mono text-slate-900 shadow-2xs focus:border-rose-600 focus:ring-1 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('nik_deceased')
                            <p class="mt-1 text-2xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="relationship" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Hubungan dengan Pelapor <span class="text-rose-500">*</span>
                        </label>
                        <select id="relationship" name="relationship" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-2xs focus:border-rose-600 focus:ring-1 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" required>
                            <option value="">-- Pilih Hubungan --</option>
                            <option value="orang_tua" {{ old('relationship') === 'orang_tua' ? 'selected' : '' }}>Orang Tua (Ayah / Ibu)</option>
                            <option value="pasangan" {{ old('relationship') === 'pasangan' ? 'selected' : '' }}>Pasangan (Suami / Istri)</option>
                            <option value="anak" {{ old('relationship') === 'anak' ? 'selected' : '' }}>Anak Kandung</option>
                            <option value="saudara" {{ old('relationship') === 'saudara' ? 'selected' : '' }}>Saudara Kandung</option>
                            <option value="famili_lain" {{ old('relationship') === 'famili_lain' ? 'selected' : '' }}>Famili / Keluarga Lainnya</option>
                            <option value="tetangga" {{ old('relationship') === 'tetangga' ? 'selected' : '' }}>Tetangga Lingkungan</option>
                        </select>
                        @error('relationship')
                            <p class="mt-1 text-2xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="space-y-4 border-t border-slate-100 pt-5 dark:border-slate-800">
                <h2 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                    <i class="fa-solid fa-monument"></i> 2. Waktu & Lokasi Pemakaman
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="death_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Tanggal Wafat <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="death_date" name="death_date" value="{{ old('death_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-2xs focus:border-rose-600 focus:ring-1 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" required>
                        @error('death_date')
                            <p class="mt-1 text-2xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="funeral_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Rencana Waktu Pemakaman
                        </label>
                        <input type="datetime-local" id="funeral_date" name="funeral_date" value="{{ old('funeral_date') }}" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-2xs focus:border-rose-600 focus:ring-1 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('funeral_date')
                            <p class="mt-1 text-2xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="cemetery_location" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Lokasi Pemakaman / TPU
                        </label>
                        <input type="text" id="cemetery_location" name="cemetery_location" value="{{ old('cemetery_location') }}" placeholder="Contoh: TPU Karet Bivak / Pemakaman Umum Lingkungan RT 04" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-2xs focus:border-rose-600 focus:ring-1 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('cemetery_location')
                            <p class="mt-1 text-2xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Alamat Rumah Duka & Keterangan Tambahan
                        </label>
                        <textarea id="description" name="description" rows="3" placeholder="Contoh: Rumah duka beralamat di Jl. Anggrek No. 12 RT 03. Rencana disholatkan ba'da Dzuhur di Masjid Al-Ikhlas." class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs focus:border-rose-600 focus:ring-1 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-2xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="space-y-4 border-t border-slate-100 pt-5 dark:border-slate-800">
                <h2 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                    <i class="fa-solid fa-file-circle-check"></i> 3. Berkas Pendukung (Surat Kematian)
                </h2>

                <div>
                    <label for="proof" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Unggah Surat Keterangan Kematian (RS / Kelurahan / RT)
                    </label>
                    <input type="file" id="proof" name="proof" accept="image/*,application/pdf" class="mt-1.5 block w-full text-xs text-slate-500 file:mr-3 file:rounded-xl file:border-0 file:bg-rose-50 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-rose-700 hover:file:bg-rose-100 dark:file:bg-rose-950/60 dark:file:text-rose-400">
                    <p class="mt-1 text-2xs text-slate-400">Format: JPG, PNG, WEBP, atau PDF (maks. 5MB). Dapat disusulkan jika surat resmi masih dalam proses penerbitan.</p>
                    @error('proof')
                        <p class="mt-1 text-2xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
                <a href="{{ route('rukem.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-rose-700">
                    <i class="fa-solid fa-paper-plane"></i>
                    Kirim Laporan Duka
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
