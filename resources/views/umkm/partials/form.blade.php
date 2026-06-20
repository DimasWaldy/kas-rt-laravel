@php
    $editing = isset($umkm);
    $kategoriLabels = [
        'makanan_minuman' => 'Makanan & Minuman',
        'jasa' => 'Jasa',
        'kerajinan' => 'Kerajinan',
        'sembako' => 'Sembako',
        'fashion' => 'Fashion',
        'pertanian' => 'Pertanian',
        'lainnya' => 'Lainnya',
    ];
    $initialPreview = $editing && $umkm->foto_usaha ? route('umkm.foto', $umkm) : null;
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="nama_usaha" class="text-sm font-bold text-slate-700">Nama Usaha</label>
        <input id="nama_usaha" type="text" name="nama_usaha" value="{{ old('nama_usaha', $umkm->nama_usaha ?? '') }}" maxlength="255" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Dapur Bu Sari">
        @error('nama_usaha')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="kategori" class="text-sm font-bold text-slate-700">Kategori</label>
        <select id="kategori" name="kategori" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Pilih kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" @selected(old('kategori', $umkm->kategori ?? '') === $category)>{{ $kategoriLabels[$category] }}</option>
            @endforeach
        </select>
        @error('kategori')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="nomor_whatsapp" class="text-sm font-bold text-slate-700">Nomor WhatsApp</label>
        <input id="nomor_whatsapp" type="text" name="nomor_whatsapp" value="{{ old('nomor_whatsapp', $umkm->nomor_whatsapp ?? '') }}" minlength="10" maxlength="15" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: 081234567890">
        @error('nomor_whatsapp')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="deskripsi" class="text-sm font-bold text-slate-700">Deskripsi Usaha</label>
        <textarea id="deskripsi" name="deskripsi" rows="5" minlength="20" maxlength="1000" required class="mt-2 w-full rounded-xl border-slate-200 text-sm leading-6 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Jelaskan produk atau jasa yang ditawarkan.">{{ old('deskripsi', $umkm->deskripsi ?? '') }}</textarea>
        @error('deskripsi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="alamat_lokasi" class="text-sm font-bold text-slate-700">Alamat / Lokasi Usaha</label>
        <input id="alamat_lokasi" type="text" name="alamat_lokasi" value="{{ old('alamat_lokasi', $umkm->alamat_lokasi ?? '') }}" maxlength="255" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Opsional jika usaha tidak punya lokasi tetap">
        @error('alamat_lokasi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="jam_operasional" class="text-sm font-bold text-slate-700">Jam Operasional</label>
        <input id="jam_operasional" type="text" name="jam_operasional" value="{{ old('jam_operasional', $umkm->jam_operasional ?? '') }}" maxlength="100" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: 08:00-17:00 Senin-Sabtu">
        @error('jam_operasional')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2" x-data="{ preview: @js($initialPreview) }">
        <label for="foto_usaha" class="text-sm font-bold text-slate-700">Foto Usaha</label>
        <input id="foto_usaha" type="file" name="foto_usaha" accept="image/jpeg,image/png,image/jpg" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" x-on:change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : @js($initialPreview)">
        <p class="mt-1 text-xs text-slate-500">JPG atau PNG, maksimal 2MB.</p>
        @error('foto_usaha')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        <template x-if="preview">
            <img x-bind:src="preview" alt="Preview foto usaha" class="mt-4 h-56 w-full rounded-2xl object-cover">
        </template>
    </div>
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ $editing ? route('umkm.show', $umkm) : route('umkm.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
    <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">{{ $submitLabel }}</button>
</div>
