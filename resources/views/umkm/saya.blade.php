@extends('layouts.app')

@section('title', 'UMKM Saya')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Kelola usaha dan katalog produk</p>
            <h1 class="text-2xl font-black text-slate-900">UMKM Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau status persetujuan dan perbarui informasi usaha Anda.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('umkm.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Lihat Direktori</a>
            <a href="{{ route('umkm.create') }}" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800"><i class="fa-solid fa-plus mr-1"></i> Daftarkan Usaha</a>
        </div>
    </div>

    @forelse($umkms as $umkm)
        <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" x-data="{ addProduct: {{ $errors->any() && old('_umkm_id') == $umkm->id && ! old('_produk_id') ? 'true' : 'false' }} }">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 gap-4">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-slate-100">
                        @if($umkm->foto_usaha)
                            <img src="{{ route('umkm.foto', $umkm) }}" alt="Foto {{ $umkm->nama_usaha }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-store text-2xl"></i></div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $umkm->status_color }}">{{ $umkm->status_label }}</span>
                        <h2 class="mt-2 truncate text-xl font-black text-slate-900">{{ $umkm->nama_usaha }}</h2>
                        <p class="text-sm text-slate-500">{{ $umkm->kategori_label }} · {{ $umkm->rt?->name ?? 'Lingkup RW' }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('umkm.show', $umkm) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Detail</a>
                    <a href="{{ route('umkm.edit', $umkm) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-100">Edit</a>
                </div>
            </div>

            <div class="space-y-5 p-6">
                @if($umkm->status === 'pending')
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"><p class="font-black">Menunggu pemeriksaan pengurus</p><p class="mt-1">Usaha belum tampil di direktori sampai disetujui.</p></div>
                @elseif($umkm->status === 'rejected')
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-black">Pendaftaran perlu diperbaiki</p><p class="mt-1">{{ $umkm->catatan_pengurus ?: 'Silakan periksa dan perbarui informasi usaha.' }}</p><a href="{{ route('umkm.edit', $umkm) }}" class="mt-3 inline-flex font-black underline">Perbaiki dan ajukan ulang</a></div>
                @endif

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-black text-slate-900">Produk & Jasa</h3>
                        <p class="text-sm text-slate-500">{{ $umkm->produkUmkms->count() }} item telah ditambahkan.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($umkm->status === 'approved')
                            <button type="button" x-on:click="addProduct = true" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800"><i class="fa-solid fa-plus mr-1"></i> Tambah Produk</button>
                            <form method="POST" action="{{ route('umkm.nonaktifkan', $umkm) }}" onsubmit="return confirm('Nonaktifkan usaha ini dari direktori?')">
                                @csrf
                                @method('PATCH')
                                <button class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Nonaktifkan</button>
                            </form>
                        @elseif($umkm->status === 'nonaktif')
                            <form method="POST" action="{{ route('umkm.aktifkan-kembali', $umkm) }}">
                                @csrf
                                @method('PATCH')
                                <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Aktifkan Kembali</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @forelse($umkm->produkUmkms as $produk)
                        <section class="overflow-hidden rounded-2xl border border-slate-200 {{ $produk->is_available ? 'bg-white' : 'bg-slate-50' }}">
                            <div class="flex gap-4 p-4">
                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                    @if($produk->foto)
                                        <img src="{{ route('produk-umkm.foto', $produk) }}" alt="Foto {{ $produk->nama_produk }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-bag-shopping text-2xl"></i></div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="truncate font-black text-slate-900">{{ $produk->nama_produk }}</h4>
                                        <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold {{ $produk->is_available ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $produk->is_available ? 'Tersedia' : 'Tidak tersedia' }}</span>
                                    </div>
                                    <p class="mt-1 text-sm font-bold text-emerald-700">{{ is_null($produk->harga) ? 'Harga sesuai kesepakatan' : 'Rp '.number_format($produk->harga, 0, ',', '.').' '.($produk->satuan_harga ?? '') }}</p>
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $produk->deskripsi ?: 'Tidak ada deskripsi.' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 border-t border-slate-100 p-3">
                                <form method="POST" action="{{ route('produk-umkm.toggle', $produk) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200">{{ $produk->is_available ? 'Tandai Habis' : 'Tandai Tersedia' }}</button>
                                </form>
                                <details class="group">
                                    <summary class="cursor-pointer list-none rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100">Edit</summary>
                                    <form method="POST" action="{{ route('produk-umkm.update', $produk) }}" enctype="multipart/form-data" class="mt-3 space-y-3 rounded-2xl border border-blue-100 bg-blue-50 p-4 md:min-w-80">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_umkm_id" value="{{ $umkm->id }}">
                                        <input type="hidden" name="_produk_id" value="{{ $produk->id }}">
                                        <input type="text" name="nama_produk" value="{{ old('_produk_id') == $produk->id ? old('nama_produk') : $produk->nama_produk }}" required maxlength="255" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Nama produk">
                                        <textarea name="deskripsi" rows="2" maxlength="500" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Deskripsi">{{ old('_produk_id') == $produk->id ? old('deskripsi') : $produk->deskripsi }}</textarea>
                                        <div class="grid grid-cols-2 gap-2"><input type="number" name="harga" value="{{ old('_produk_id') == $produk->id ? old('harga') : $produk->harga }}" min="0" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Harga"><input type="text" name="satuan_harga" value="{{ old('_produk_id') == $produk->id ? old('satuan_harga') : $produk->satuan_harga }}" maxlength="50" class="w-full rounded-xl border-slate-200 text-sm" placeholder="per porsi"></div>
                                        <input type="file" name="foto" accept="image/jpeg,image/png,image/jpg" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                        @if(old('_produk_id') == $produk->id && $errors->any())<p class="text-xs font-semibold text-red-600">Periksa kembali data produk.</p>@endif
                                        <button class="w-full rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700">Simpan Produk</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('produk-umkm.destroy', $produk) }}" onsubmit="return confirm('Hapus produk ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100">Hapus</button>
                                </form>
                            </div>
                        </section>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 md:col-span-2">Belum ada produk atau jasa.</div>
                    @endforelse
                </div>
            </div>

            <div x-cloak x-show="addProduct" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-on:click.self="addProduct = false">
                <form method="POST" action="{{ route('produk-umkm.store', $umkm) }}" enctype="multipart/form-data" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl">
                    @csrf
                    <input type="hidden" name="_umkm_id" value="{{ $umkm->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $umkm->nama_usaha }}</p><h3 class="mt-1 text-xl font-black text-slate-900">Tambah Produk / Jasa</h3></div>
                        <button type="button" x-on:click="addProduct = false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="mt-5 space-y-4">
                        <div><label class="text-sm font-bold text-slate-700">Nama Produk</label><input type="text" name="nama_produk" value="{{ old('_umkm_id') == $umkm->id && ! old('_produk_id') ? old('nama_produk') : '' }}" required maxlength="255" class="mt-2 w-full rounded-xl border-slate-200 text-sm">@if(old('_umkm_id') == $umkm->id && ! old('_produk_id')) @error('nama_produk')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror @endif</div>
                        <div><label class="text-sm font-bold text-slate-700">Deskripsi</label><textarea name="deskripsi" rows="3" maxlength="500" class="mt-2 w-full rounded-xl border-slate-200 text-sm">{{ old('_umkm_id') == $umkm->id && ! old('_produk_id') ? old('deskripsi') : '' }}</textarea></div>
                        <div class="grid grid-cols-2 gap-3"><div><label class="text-sm font-bold text-slate-700">Harga</label><input type="number" name="harga" value="{{ old('_umkm_id') == $umkm->id && ! old('_produk_id') ? old('harga') : '' }}" min="0" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="Opsional"></div><div><label class="text-sm font-bold text-slate-700">Satuan Harga</label><input type="text" name="satuan_harga" value="{{ old('_umkm_id') == $umkm->id && ! old('_produk_id') ? old('satuan_harga') : '' }}" maxlength="50" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="per porsi"></div></div>
                        <div><label class="text-sm font-bold text-slate-700">Foto Produk</label><input type="file" name="foto" accept="image/jpeg,image/png,image/jpg" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">@if(old('_umkm_id') == $umkm->id && ! old('_produk_id')) @error('foto')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror @endif</div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2"><button type="button" x-on:click="addProduct = false" class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">Batal</button><button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Simpan Produk</button></div>
                </form>
            </div>
        </article>
    @empty
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600"><i class="fa-solid fa-store"></i></span>
            <h2 class="mt-4 font-bold text-slate-800">Anda belum mendaftarkan usaha</h2>
            <p class="mt-1 text-sm text-slate-500">Daftarkan usaha agar warga satu RW lebih mudah menemukan produk atau jasa Anda.</p>
            <a href="{{ route('umkm.create') }}" class="mt-5 inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Daftarkan Sekarang</a>
        </div>
    @endforelse
</div>
@endsection
