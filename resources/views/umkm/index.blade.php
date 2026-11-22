@extends('layouts.app')

@section('title', 'Katalog & Etalase UMKM Warga')

@section('content')
@php
    $kategoriLabels = [
        'makanan_minuman' => 'Makanan & Minuman',
        'jasa' => 'Jasa & Laundry',
        'kerajinan' => 'Kerajinan',
        'sembako' => 'Sembako',
        'fashion' => 'Fashion',
        'pertanian' => 'Pertanian',
        'lainnya' => 'Lainnya',
    ];
    $statusTabs = [
        '' => 'Semua',
        'pending' => 'Menunggu',
        'approved' => 'Aktif',
        'rejected' => 'Ditolak',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header Banner --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <i class="fa-solid fa-store"></i> Ekonomi Warga
                </span>
                <span class="text-xs font-bold text-slate-400">•</span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Order Langsung via WhatsApp</span>
            </div>
            <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-slate-100 sm:text-3xl">Katalog & Etalase UMKM Warga</h1>
            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Dukung usaha tetangga sendiri! Pesan aneka makanan, minuman, jasa, sembako, dan kerajinan tangan warga sekitar secara cepat.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(auth()->user()->hasPermission('daftar-umkm'))
                <a href="{{ route('umkm.saya') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-800 shadow-2xs transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                    <i class="fa-solid fa-shop"></i> UMKM Saya
                </a>
                <a href="{{ route('umkm.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-800">
                    <i class="fa-solid fa-plus"></i> Daftarkan Usaha
                </a>
            @endif
        </div>
    </div>

    {{-- 3 KPI Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
            <p class="text-[13px] font-semibold text-slate-500 dark:text-slate-400">Etalase Produk Siap Pesan</p>
            <p class="mt-2 text-2xl font-black tracking-tight text-emerald-700 dark:text-emerald-400 sm:text-3xl">{{ $totalProduk ?? 0 }} Produk</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
            <p class="text-[13px] font-semibold text-slate-500 dark:text-slate-400">Toko / Usaha Terverifikasi</p>
            <p class="mt-2 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">{{ $stats['terdaftar'] ?? 0 }} Toko</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
            <p class="text-[13px] font-semibold text-slate-500 dark:text-slate-400">Kategori Usaha</p>
            <p class="mt-2 text-2xl font-black tracking-tight text-teal-600 dark:text-teal-400 sm:text-3xl">{{ $stats['kategori'] ?? 0 }} Bidang</p>
        </div>
    </div>

    {{-- Search & Tab Control --}}
    <div class="space-y-4 rounded-3xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        {{-- Search Form --}}
        <form method="GET" action="{{ route('umkm.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            @if($tab)
                <input type="hidden" name="tab" value="{{ $tab }}">
            @endif
            @if($kategori)
                <input type="hidden" name="kategori" value="{{ $kategori }}">
            @endif
            @if($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif

            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari produk (cth: Nasi Goreng, Laundry, Keripik...) atau nama toko..."
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-10 pr-4 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-600 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-800">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Cari</span>
                </button>
                @if($search !== '' || $kategori !== '')
                    <a href="{{ route('umkm.index', $tab ? ['tab' => $tab] : []) }}" class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>

        {{-- Tab Switcher --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <a href="{{ route('umkm.index', array_filter(array_merge(request()->query(), ['tab' => '']))) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black transition {{ $tab === '' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Semua</span>
                </a>
                <a href="{{ route('umkm.index', array_merge(request()->query(), ['tab' => 'etalase'])) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black transition {{ $tab === 'etalase' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span>Etalase Produk Warga</span>
                </a>
                <a href="{{ route('umkm.index', array_merge(request()->query(), ['tab' => 'usaha'])) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black transition {{ $tab === 'usaha' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    <i class="fa-solid fa-store"></i>
                    <span>Daftar Toko & Usaha</span>
                </a>
            </div>

            @if($canManage)
                <div class="flex items-center gap-1.5 text-xs">
                    <span class="font-bold text-slate-400">Filter Status:</span>
                    @foreach($statusTabs as $value => $label)
                        <a href="{{ route('umkm.index', array_merge(request()->query(), ['status' => $value, 'tab' => 'usaha'])) }}" class="rounded-lg px-2.5 py-1 text-2xs font-bold transition {{ $status === $value ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Kategori Pills --}}
        <div class="border-t border-slate-100 pt-3 dark:border-slate-800">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
                <span class="shrink-0 font-bold uppercase text-[10px] tracking-wider text-slate-400">Kategori:</span>
                <a href="{{ route('umkm.index', array_filter(array_merge(request()->query(), ['kategori' => '']))) }}" class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-bold transition {{ $kategori === '' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400' }}">
                    Semua
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('umkm.index', array_merge(request()->query(), ['kategori' => $cat])) }}" class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-bold transition {{ $kategori === $cat ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ $kategoriLabels[$cat] ?? ucfirst($cat) }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- KONTEN ETALASE PRODUK WARGA --}}
    @if($tab === 'etalase' || ($tab === '' && $produks->isNotEmpty()))
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-slate-100">Etalase Produk Warga</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pesan makanan, minuman, jasa, dan aneka produk lokal langsung via WhatsApp pemilik.</p>
                </div>
                <span class="rounded-lg bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                    {{ $produks->total() }} Produk Siap Order
                </span>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($produks as $produk)
                    @php
                        $umkm = $produk->umkm;
                        $isBuka = $umkm->isBukaSekarang();
                    @endphp
                    <article class="flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-2xs transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 group">
                        <div>
                            {{-- Foto Produk --}}
                            <div class="relative h-48 w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                                @if($produk->foto)
                                    <img src="{{ route('produk-umkm.foto', $produk) }}" alt="{{ $produk->nama_produk }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-slate-300 dark:text-slate-600">
                                        <i class="fa-solid fa-bag-shopping text-5xl"></i>
                                    </div>
                                @endif

                                {{-- Badge Kategori --}}
                                <span class="absolute left-3 top-3 rounded-lg bg-slate-900/70 px-2.5 py-1 text-[10px] font-black text-white backdrop-blur-xs">
                                    {{ $kategoriLabels[$umkm->kategori] ?? ucfirst($umkm->kategori) }}
                                </span>

                                {{-- Badge Status Buka --}}
                                <span class="absolute right-3 top-3 rounded-full px-2.5 py-0.5 text-[10px] font-black {{ $isBuka ? 'bg-emerald-600 text-white' : 'bg-slate-800/80 text-slate-300' }} backdrop-blur-xs">
                                    {{ $isBuka ? 'Buka' : 'Tutup' }}
                                </span>
                            </div>

                            {{-- Rincian Produk --}}
                            <div class="p-5 space-y-3">
                                <div>
                                    <h3 class="text-base font-black tracking-tight text-slate-900 line-clamp-1 dark:text-slate-100" title="{{ $produk->nama_produk }}">
                                        {{ $produk->nama_produk }}
                                    </h3>
                                    @if($produk->deskripsi)
                                        <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                            {{ $produk->deskripsi }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Harga --}}
                                <div class="rounded-2xl bg-emerald-50/70 p-3 dark:bg-emerald-950/40">
                                    <span class="text-[10px] font-bold text-emerald-800/80 dark:text-emerald-300">Harga Warga:</span>
                                    <p class="text-base font-black text-emerald-700 dark:text-emerald-400">
                                        {{ $produk->harga_formatted }}
                                    </p>
                                </div>

                                {{-- Info Usaha Penjual --}}
                                <div class="space-y-1 text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center justify-between">
                                        <span class="truncate font-bold text-slate-700 dark:text-slate-300">
                                            <i class="fa-solid fa-store mr-1 text-emerald-600"></i>{{ $umkm->nama_usaha }}
                                        </span>
                                        <span class="shrink-0 text-2xs font-semibold">{{ $umkm->rt?->name ?? 'RW' }}</span>
                                    </div>
                                    <div class="truncate text-2xs text-slate-400">
                                        <i class="fa-solid fa-user mr-1"></i>{{ $umkm->pemilik?->name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="p-5 pt-0 space-y-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                            {{-- Tombol Pesan Sekarang via WhatsApp --}}
                            <a
                                href="{{ $produk->whatsapp_order_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-xs font-black text-white shadow-xs transition hover:bg-emerald-700 active:scale-98"
                            >
                                <i class="fa-brands fa-whatsapp text-lg"></i>
                                <span>Pesan Sekarang via WhatsApp</span>
                            </a>

                            {{-- Link Detail Toko --}}
                            <a href="{{ route('umkm.show', $umkm) }}" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white py-2 text-2xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                <span>Lihat Profil Usaha</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($produks->hasPages())
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    {{ $produks->links() }}
                </div>
            @endif
        </div>
    @elseif($tab === 'etalase' && $produks->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-800 dark:bg-slate-900">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                <i class="fa-solid fa-bag-shopping"></i>
            </span>
            <h2 class="mt-4 font-bold text-slate-800 dark:text-slate-200">Belum Ada Produk di Etalase</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                @if($search !== '' || $kategori !== '')
                    Tidak ada produk yang sesuai dengan kata kunci pencarian atau filter Anda.
                @else
                    Produk jualan dari UMKM warga yang telah terverifikasi akan tampil di sini.
                @endif
            </p>
        </div>
    @endif

    {{-- KONTEN DAFTAR TOKO / USAHA WARGA --}}
    @if($tab === 'usaha' || $tab === '')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-slate-100">Direktori Toko & Usaha Warga</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Daftar tempat usaha, warung, dan penyedia jasa resmi warga lingkungan.</p>
                </div>
                <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    {{ $umkms->total() }} Usaha Terdaftar
                </span>
            </div>

            @if($umkms->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-800 dark:bg-slate-900">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                        <i class="fa-solid fa-store-slash"></i>
                    </span>
                    <h2 class="mt-4 font-bold text-slate-800 dark:text-slate-200">Belum Ada Toko UMKM</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Toko dan usaha warga sesuai filter akan tampil di sini.</p>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($umkms as $umkm)
                        @php($isBuka = $umkm->isBukaSekarang())
                        <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xs transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900" x-data="{ rejectOpen: {{ $errors->has('catatan_pengurus') && old('umkm_id') == $umkm->id ? 'true' : 'false' }} }">
                            <div class="relative h-48 bg-slate-100 dark:bg-slate-800">
                                @if($umkm->foto_usaha)
                                    <img src="{{ route('umkm.foto', $umkm) }}" alt="Foto {{ $umkm->nama_usaha }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-slate-300 dark:text-slate-600"><i class="fa-solid fa-store text-5xl"></i></div>
                                @endif
                                @if($canManage)
                                    <span class="absolute left-4 top-4 rounded-full border px-3 py-1.5 text-xs font-bold {{ $umkm->status_color }}">{{ $umkm->status_label }}</span>
                                @endif
                            </div>

                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{{ $umkm->kategori_label }}</span>
                                        <h3 class="mt-3 truncate text-lg font-black text-slate-900 dark:text-slate-100">{{ $umkm->nama_usaha }}</h3>
                                    </div>
                                    <span class="shrink-0 text-xs font-bold text-slate-500 dark:text-slate-400">{{ $umkm->rt?->name ?? 'RW' }}</span>
                                </div>
                                <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $umkm->deskripsi }}</p>
                                <div class="mt-4 flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/60">
                                    <p class="min-w-0 truncate text-xs font-semibold text-slate-600 dark:text-slate-300"><i class="fa-regular fa-clock mr-1 text-emerald-600"></i>{{ $umkm->jam_operasional_ringkas }}</p>
                                    @if($umkm->status === 'approved')
                                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black {{ $isBuka ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                            {{ $isBuka ? 'Buka' : 'Tutup' }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-4 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                    <span><i class="fa-solid fa-user mr-1 text-emerald-600"></i>{{ $umkm->pemilik?->name }}</span>
                                    <span>{{ $umkm->produk_umkms_count }} produk</span>
                                </div>

                                @if($canManage && $umkm->status === 'pending')
                                    <div class="mt-5 grid grid-cols-2 gap-2">
                                        <form method="POST" action="{{ route('umkm.approve', $umkm) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-800">Setujui</button>
                                        </form>
                                        <button type="button" x-on:click="rejectOpen = true" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 hover:bg-red-100">Tolak</button>
                                    </div>
                                @endif

                                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @if($umkm->status === 'approved')
                                        <a href="{{ $umkm->whatsapp_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700 shadow-2xs">
                                            <i class="fa-brands fa-whatsapp text-sm"></i>
                                            <span>Chat WA</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('umkm.show', $umkm) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        <span>Lihat Toko</span>
                                        <i class="fa-solid fa-arrow-right text-2xs"></i>
                                    </a>
                                </div>
                            </div>

                            <div x-cloak x-show="rejectOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-on:click.self="rejectOpen = false">
                                <form method="POST" action="{{ route('umkm.reject', $umkm) }}" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="umkm_id" value="{{ $umkm->id }}">
                                    <h3 class="text-lg font-black text-slate-900 dark:text-slate-100">Tolak {{ $umkm->nama_usaha }}?</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Jelaskan bagian yang harus diperbaiki pemilik.</p>
                                    <textarea name="catatan_pengurus" rows="4" minlength="5" maxlength="255" required class="mt-4 w-full rounded-xl border-slate-200 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">{{ old('umkm_id') == $umkm->id ? old('catatan_pengurus') : '' }}</textarea>
                                    @if(old('umkm_id') == $umkm->id) @error('catatan_pengurus')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror @endif
                                    <div class="mt-5 flex justify-end gap-2">
                                        <button type="button" x-on:click="rejectOpen = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">Batal</button>
                                        <button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700">Tolak UMKM</button>
                                    </div>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($umkms->hasPages())
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">{{ $umkms->links() }}</div>
                @endif
            @endif
        </div>
    @endif
</div>
@endsection
