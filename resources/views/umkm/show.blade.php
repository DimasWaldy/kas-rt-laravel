@extends('layouts.app')

@section('title', 'Detail UMKM')

@section('content')
@php
    $canEdit = auth()->id() === $umkm->pemilik_id || auth()->user()->hasPermission('manage-umkm');
    $hariLabels = \App\Models\JamOperasionalUmkm::HARI;
    $hariIni = \App\Models\JamOperasionalUmkm::getHariIni();
    $jadwalByHari = $umkm->jamOperasional->keyBy('hari');
    $isBuka = $umkm->isBukaSekarang();
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('umkm.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke direktori</a>
        @if($canEdit)
            <a href="{{ route('umkm.edit', $umkm) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-pen"></i> Edit Usaha</a>
        @endif
    </div>

    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="relative h-72 bg-slate-100 sm:h-96">
            @if($umkm->foto_usaha)
                <img src="{{ route('umkm.foto', $umkm) }}" alt="Foto {{ $umkm->nama_usaha }}" class="h-full w-full object-cover">
            @else
                <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-store text-7xl"></i></div>
            @endif
            @if($umkm->status !== 'approved' || auth()->user()->hasPermission('manage-umkm'))
                <span class="absolute left-5 top-5 rounded-full border px-4 py-2 text-xs font-bold {{ $umkm->status_color }}">{{ $umkm->status_label }}</span>
            @endif
        </div>

        <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">{{ $umkm->kategori_label }}</span>
                <h1 class="mt-4 text-3xl font-black text-slate-900">{{ $umkm->nama_usaha }}</h1>
                <p class="mt-2 text-sm font-semibold text-slate-500">Milik {{ $umkm->pemilik?->name }} &middot; {{ $umkm->rt?->name ?? 'Lingkup RW' }}</p>
                <p class="mt-6 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $umkm->deskripsi }}</p>

                @if($umkm->status === 'rejected' && $umkm->catatan_pengurus)
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-black">Catatan pengurus</p><p class="mt-1">{{ $umkm->catatan_pengurus }}</p></div>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <dl class="space-y-4 text-sm">
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Alamat</dt><dd class="mt-1 font-semibold text-slate-700">{{ $umkm->alamat_lokasi ?: 'Hubungi pemilik untuk lokasi' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">WhatsApp</dt><dd class="mt-1 font-semibold text-slate-700">{{ $umkm->nomor_whatsapp }}</dd></div>
                    </dl>
                </div>
                <div class="rounded-2xl border p-4 {{ $isBuka ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider {{ $isBuka ? 'text-emerald-600' : 'text-slate-400' }}">Status saat ini</p>
                            <p class="mt-1 font-black {{ $isBuka ? 'text-emerald-800' : 'text-slate-700' }}">{{ $isBuka ? 'Buka Sekarang' : 'Tutup' }}</p>
                        </div>
                        <span class="h-3 w-3 rounded-full {{ $isBuka ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    </div>
                </div>
                @if($umkm->status === 'approved')
                    <a href="{{ $umkm->whatsapp_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-green-600 px-5 py-4 text-sm font-black text-white shadow-lg shadow-green-100 hover:bg-green-700"><i class="fa-brands fa-whatsapp text-xl"></i> Hubungi via WhatsApp</a>
                @endif
            </aside>
        </div>
    </article>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Waktu pelayanan</p>
                <h2 class="text-xl font-black text-slate-900">Jam Operasional</h2>
            </div>
            <span class="w-fit rounded-full px-3 py-1.5 text-xs font-black {{ $isBuka ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $isBuka ? 'Buka Sekarang' : 'Tutup' }}</span>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
            @foreach($hariLabels as $nomorHari => $labelHari)
                @php($jadwal = $jadwalByHari->get($nomorHari))
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 last:border-b-0 {{ $hariIni === $nomorHari ? 'bg-emerald-50' : 'bg-white' }}">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-black {{ $hariIni === $nomorHari ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $nomorHari }}</span>
                        <div>
                            <p class="text-sm font-black {{ $hariIni === $nomorHari ? 'text-emerald-900' : 'text-slate-800' }}">{{ $labelHari }}</p>
                            @if($hariIni === $nomorHari)
                                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Hari ini</p>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm font-bold {{ $jadwal?->is_tutup ? 'text-slate-400' : 'text-slate-700' }}">{{ $jadwal?->jam_teks ?? 'Belum diatur' }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Katalog</p>
            <h2 class="text-xl font-black text-slate-900">Produk & Jasa Tersedia</h2>
        </div>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($umkm->produkUmkms as $produk)
                <article class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="h-40 bg-slate-100">
                        @if($produk->foto)
                            <img src="{{ route('produk-umkm.foto', $produk) }}" alt="Foto {{ $produk->nama_produk }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-bag-shopping text-4xl"></i></div>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="font-black text-slate-900">{{ $produk->nama_produk }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $produk->deskripsi ?: 'Tidak ada deskripsi.' }}</p>
                        <p class="mt-3 font-black text-emerald-700">{{ is_null($produk->harga) ? 'Hubungi penjual' : 'Rp '.number_format($produk->harga, 0, ',', '.').' '.($produk->satuan_harga ?? '') }}</p>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 sm:col-span-2 lg:col-span-3">Pemilik belum menambahkan produk atau jasa.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
