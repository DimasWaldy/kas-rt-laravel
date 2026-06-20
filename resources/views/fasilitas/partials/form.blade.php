@php($editing = isset($fasilitas))

<div>
    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Nama Fasilitas</label>
    <input type="text" name="nama" value="{{ old('nama', $fasilitas->nama ?? '') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
    @error('nama')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Kategori</label>
        <select name="kategori" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            <option value="">Pilih kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" @selected(old('kategori', $fasilitas->kategori ?? '') === $category)>{{ (new \App\Models\Fasilitas(['kategori' => $category]))->kategori_label }}</option>
            @endforeach
        </select>
        @error('kategori')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Kondisi</label>
        <select name="kondisi" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            @foreach($conditions as $condition)
                <option value="{{ $condition }}" @selected(old('kondisi', $fasilitas->kondisi ?? 'baik') === $condition)>{{ (new \App\Models\Fasilitas(['kondisi' => $condition]))->kondisi_label }}</option>
            @endforeach
        </select>
        @error('kondisi')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
    </div>
</div>

@if($rts->isNotEmpty())
    <div>
        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Scope RT</label>
        <select name="rt_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            <option value="">Fasilitas RW / bersama lintas RT</option>
            @foreach($rts as $rt)
                <option value="{{ $rt->id }}" @selected((string) old('rt_id', $fasilitas->rt_id ?? '') === (string) $rt->id)>{{ $rt->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Kosongkan untuk fasilitas RW yang bisa dilihat lintas RT.</p>
        @error('rt_id')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
    </div>
@endif

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Blok / Titik Lokasi</label>
        <input type="text" name="lokasi_blok" value="{{ old('lokasi_blok', $fasilitas->lokasi_blok ?? '') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="Contoh: Blok C, Gerbang Utama">
        @error('lokasi_blok')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Foto</label>
        <input type="file" name="foto" accept="image/*" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
        @error('foto')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Deskripsi Lokasi</label>
    <textarea name="lokasi_deskripsi" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('lokasi_deskripsi', $fasilitas->lokasi_deskripsi ?? '') }}</textarea>
    @error('lokasi_deskripsi')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
</div>

<div>
    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Catatan</label>
    <textarea name="catatan" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('catatan', $fasilitas->catatan ?? '') }}</textarea>
    @error('catatan')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
</div>

<label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700">
    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-emerald-700" @checked(old('is_active', $fasilitas->is_active ?? true))>
    Fasilitas aktif dan tampil untuk warga
</label>

<div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
    <a href="{{ $editing ? route('fasilitas.show', $fasilitas) : route('fasilitas.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Batal</a>
    <button type="submit" class="rounded-2xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">{{ $submitLabel }}</button>
</div>
