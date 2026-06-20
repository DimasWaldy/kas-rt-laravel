@extends('layouts.app')

@section('title', 'Direktori UMKM')

@section('content')
@php
    $kategoriLabels = [
        'makanan_minuman' => 'Makanan & Minuman',
        'jasa' => 'Jasa',
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
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Belanja dan dukung usaha tetangga</p>
            <h1 class="text-2xl font-black text-slate-900">Direktori UMKM Warga</h1>
            <p class="mt-1 text-sm text-slate-500">Temukan produk dan jasa warga lintas RT dalam satu RW. Hubungi pemilik langsung melalui WhatsApp.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->hasPermission('daftar-umkm'))
                <a href="{{ route('umkm.saya') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100"><i class="fa-solid fa-shop"></i> UMKM Saya</a>
                <a href="{{ route('umkm.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800"><i class="fa-solid fa-plus"></i> Daftarkan Usaha</a>
            @endif
        </div>
    </div>

    @if($canManage)
        <div class="rounded-3xl border {{ $pendingCount ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <p class="font-black text-slate-900">Panel Persetujuan UMKM</p>
                    <p class="text-sm text-slate-500">{{ $pendingCount }} usaha menunggu pemeriksaan.</p>
                </div>
                @if($pendingCount)
                    <span class="flex h-10 min-w-10 items-center justify-center rounded-full bg-amber-500 px-3 text-sm font-black text-white">{{ $pendingCount }}</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($statusTabs as $value => $label)
                    <a href="{{ route('umkm.index', ['status' => $value]) }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === $value ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Kategori Usaha</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('umkm.index') }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $kategori === '' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Semua</a>
                @foreach($categories as $category)
                    <a href="{{ route('umkm.index', ['kategori' => $category]) }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $kategori === $category ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $kategoriLabels[$category] }}</a>
                @endforeach
            </div>
        </div>
    @endif

    @if($umkms->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600"><i class="fa-solid fa-store-slash"></i></span>
            <h2 class="mt-4 font-bold text-slate-800">Belum ada UMKM</h2>
            <p class="mt-1 text-sm text-slate-500">Usaha warga sesuai filter akan tampil di sini.</p>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($umkms as $umkm)
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg" x-data="{ rejectOpen: {{ $errors->has('catatan_pengurus') && old('umkm_id') == $umkm->id ? 'true' : 'false' }} }">
                    <div class="relative h-48 bg-slate-100">
                        @if($umkm->foto_usaha)
                            <img src="{{ route('umkm.foto', $umkm) }}" alt="Foto {{ $umkm->nama_usaha }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-store text-5xl"></i></div>
                        @endif
                        @if($canManage)
                            <span class="absolute left-4 top-4 rounded-full border px-3 py-1.5 text-xs font-bold {{ $umkm->status_color }}">{{ $umkm->status_label }}</span>
                        @endif
                    </div>

                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700">{{ $umkm->kategori_label }}</span>
                                <h2 class="mt-3 truncate text-lg font-black text-slate-900">{{ $umkm->nama_usaha }}</h2>
                            </div>
                            <span class="shrink-0 text-xs font-bold text-slate-500">{{ $umkm->rt?->name ?? 'RW' }}</span>
                        </div>
                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $umkm->deskripsi }}</p>
                        <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
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

                        <a href="{{ route('umkm.show', $umkm) }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Lihat Detail <i class="fa-solid fa-arrow-right text-xs"></i></a>
                    </div>

                    <div x-cloak x-show="rejectOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-on:click.self="rejectOpen = false">
                        <form method="POST" action="{{ route('umkm.reject', $umkm) }}" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="umkm_id" value="{{ $umkm->id }}">
                            <h3 class="text-lg font-black text-slate-900">Tolak {{ $umkm->nama_usaha }}?</h3>
                            <p class="mt-1 text-sm text-slate-500">Jelaskan bagian yang harus diperbaiki pemilik.</p>
                            <textarea name="catatan_pengurus" rows="4" minlength="5" maxlength="255" required class="mt-4 w-full rounded-xl border-slate-200 text-sm focus:border-red-500 focus:ring-red-500">{{ old('umkm_id') == $umkm->id ? old('catatan_pengurus') : '' }}</textarea>
                            @if(old('umkm_id') == $umkm->id) @error('catatan_pengurus')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror @endif
                            <div class="mt-5 flex justify-end gap-2">
                                <button type="button" x-on:click="rejectOpen = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Batal</button>
                                <button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700">Tolak UMKM</button>
                            </div>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        @if($umkms->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $umkms->links() }}</div>
        @endif
    @endif
</div>
@endsection
