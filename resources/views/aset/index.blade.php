@extends('layouts.app')

@section('title', 'Inventaris Aset RT')

@section('content')
@php
    $kategoriLabels = [
        'furniture' => 'Furniture',
        'elektronik' => 'Elektronik',
        'tenda_dan_terpal' => 'Tenda & Terpal',
        'kebersihan' => 'Kebersihan',
        'olahraga' => 'Olahraga',
        'lainnya' => 'Lainnya',
    ];
    $kondisiLabels = [
        'baik' => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat' => 'Rusak Berat',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Inventaris lingkungan</p>
            <h1 class="text-2xl font-black text-slate-900">Inventaris Aset RT</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola barang RT dan ajukan peminjaman aset yang tersedia.</p>
        </div>

        @if(auth()->user()->hasPermission('manage-aset'))
            <a href="{{ route('aset.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
                <i class="fa-solid fa-plus"></i> Tambah Aset
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('aset.index') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Kategori</p>
            <div class="flex flex-wrap gap-2">
                <button type="submit" name="kategori" value="" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $filters['kategori'] === '' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua
                </button>
                @foreach($categories as $category)
                    <button type="submit" name="kategori" value="{{ $category }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $filters['kategori'] === $category ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $kategoriLabels[$category] ?? str($category)->headline() }}
                    </button>
                @endforeach
            </div>
        </div>

        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Kondisi</p>
            <div class="flex flex-wrap gap-2">
                <button type="submit" name="kondisi" value="" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $filters['kondisi'] === '' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua
                </button>
                @foreach($conditions as $condition)
                    <button type="submit" name="kondisi" value="{{ $condition }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $filters['kondisi'] === $condition ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $kondisiLabels[$condition] ?? str($condition)->headline() }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach(['' => 'Semua Status', '1' => 'Aktif', '0' => 'Nonaktif'] as $value => $label)
                <button type="submit" name="is_active" value="{{ $value }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $filters['is_active'] === $value ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    @if($asets->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400"><i class="fa-solid fa-box-open"></i></span>
            <h2 class="mt-4 font-bold text-slate-800">Belum ada aset</h2>
            <p class="mt-1 text-sm text-slate-500">Aset RT yang sudah didata akan tampil di sini.</p>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($asets as $aset)
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="relative h-48 bg-slate-100">
                        @if($aset->foto)
                            <img src="{{ route('aset.foto', $aset) }}" alt="Foto {{ $aset->nama }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-boxes-stacked text-5xl"></i></div>
                        @endif
                        <span class="absolute left-4 top-4 rounded-full border px-3 py-1.5 text-xs font-bold {{ $aset->kondisi_color }}">{{ $aset->kondisi_label }}</span>
                        @unless($aset->is_active)
                            <span class="absolute right-4 top-4 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-500">Nonaktif</span>
                        @endunless
                    </div>

                    <div class="p-5">
                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700">
                            {{ $aset->kategori_label }}
                        </span>
                        <h2 class="mt-3 line-clamp-2 text-lg font-black text-slate-900">{{ $aset->nama }}</h2>

                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <p class="flex items-center gap-2"><i class="fa-solid fa-layer-group w-4 text-emerald-600"></i><span>{{ $aset->jumlah_tersedia }} / {{ $aset->jumlah_total }} unit tersedia</span></p>
                            <p class="flex items-center gap-2"><i class="fa-solid fa-location-dot w-4 text-emerald-600"></i><span>{{ $aset->lokasi_penyimpanan ?: 'Lokasi belum diisi' }}</span></p>
                        </div>

                        <div class="mt-5 grid gap-2 sm:grid-cols-2">
                            @if(auth()->user()->hasPermission('pinjam-aset') && $aset->jumlah_tersedia > 0 && $aset->is_active && $aset->kondisi !== 'rusak_berat')
                                <a href="{{ route('peminjaman-aset.create', ['aset_id' => $aset->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-800">
                                    Pinjam
                                </a>
                            @endif
                            <a href="{{ route('aset.show', $aset) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100">
                                Detail <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if($asets->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $asets->links() }}</div>
        @endif
    @endif
</div>
@endsection
