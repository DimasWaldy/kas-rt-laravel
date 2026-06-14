@php
    $tanggalMulai = old('tanggal_mulai', $kegiatan?->tanggal_mulai?->format('Y-m-d\TH:i'));
    $tanggalSelesai = old('tanggal_selesai', $kegiatan?->tanggal_selesai?->format('Y-m-d\TH:i'));
@endphp

<div>
    <label for="nama" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Nama Kegiatan</label>
    <input id="nama" name="nama" value="{{ old('nama', $kegiatan?->nama) }}" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Kerja Bakti Lingkungan RW 05">
    @error('nama')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="deskripsi" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Deskripsi</label>
    <textarea id="deskripsi" name="deskripsi" rows="5" maxlength="2000" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Jelaskan tujuan dan rangkaian kegiatan.">{{ old('deskripsi', $kegiatan?->deskripsi) }}</textarea>
    @error('deskripsi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="tanggal_mulai" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Tanggal Mulai</label>
        <input id="tanggal_mulai" name="tanggal_mulai" type="datetime-local" value="{{ $tanggalMulai }}" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        @error('tanggal_mulai')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="tanggal_selesai" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Tanggal Selesai <span class="font-normal normal-case text-slate-400">(opsional)</span></label>
        <input id="tanggal_selesai" name="tanggal_selesai" type="datetime-local" value="{{ $tanggalSelesai }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        @error('tanggal_selesai')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="lokasi" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Lokasi</label>
    <input id="lokasi" name="lokasi" value="{{ old('lokasi', $kegiatan?->lokasi) }}" maxlength="255" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Balai RW 05">
    @error('lokasi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="foto" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Poster / Gambar Ajakan <span class="font-normal normal-case text-slate-400">(opsional)</span></label>
    <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png" class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm">
    <p class="mt-2 text-xs text-slate-400">Dipakai sebagai poster atau ajakan sebelum kegiatan. JPG/JPEG/PNG, maksimal 2 MB.</p>
    @error('foto')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
</div>

@if($kegiatan && now()->greaterThanOrEqualTo($kegiatan->tanggal_mulai))
    <div>
        <label for="foto_dokumentasi" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Dokumentasi Kegiatan <span class="font-normal normal-case text-slate-400">(opsional)</span></label>
        <input id="foto_dokumentasi" name="foto_dokumentasi" type="file" accept=".jpg,.jpeg,.png" class="w-full rounded-xl border border-dashed border-emerald-300 bg-emerald-50 px-4 py-5 text-sm">
        <p class="mt-2 text-xs text-emerald-700">Unggah foto saat kegiatan berlangsung atau setelah selesai. Foto baru akan mengganti dokumentasi sebelumnya.</p>
        @error('foto_dokumentasi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
@endif

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="estimasi_biaya" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Estimasi Biaya</label>
        <div class="relative"><span class="absolute left-4 top-3 text-sm font-bold text-slate-400">Rp</span><input id="estimasi_biaya" name="estimasi_biaya" type="number" min="0" step="1" value="{{ old('estimasi_biaya', $kegiatan?->estimasi_biaya ?? 0) }}" class="w-full rounded-xl border border-slate-200 py-3 pl-12 pr-4 text-sm focus:border-emerald-500 focus:ring-emerald-500"></div>
        @error('estimasi_biaya')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="realisasi_biaya" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Realisasi Biaya</label>
        <div class="relative"><span class="absolute left-4 top-3 text-sm font-bold text-slate-400">Rp</span><input id="realisasi_biaya" name="realisasi_biaya" type="number" min="0" step="1" value="{{ old('realisasi_biaya', $kegiatan?->realisasi_biaya ?? 0) }}" class="w-full rounded-xl border border-slate-200 py-3 pl-12 pr-4 text-sm focus:border-emerald-500 focus:ring-emerald-500"></div>
        @error('realisasi_biaya')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<p class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-xs leading-relaxed text-blue-800"><i class="fa-solid fa-circle-info mr-1"></i> Biaya kegiatan hanya berupa informasi dan tidak otomatis memotong kas RT maupun RW.</p>
