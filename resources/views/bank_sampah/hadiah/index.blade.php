@extends('layouts.app')

@section('title', 'Katalog Hadiah Bank Sampah')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Reward Bank Sampah</p>
            <h1 class="text-2xl font-black text-slate-900">Katalog Hadiah</h1>
            <p class="mt-1 text-sm text-slate-500">Saldo Anda: <span class="font-black text-emerald-700">Rp {{ number_format($saldoSaya->saldo, 0, ',', '.') }}</span></p>
        </div>
        @if($canManage)
            <a href="{{ route('hadiah-sampah.create') }}" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Tambah Hadiah</a>
        @endif
    </div>

    @if($canManage && $penukaranMenunggu->isNotEmpty())
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="mb-3 font-black text-amber-900">Penukaran Menunggu Konfirmasi</h2>
            <div class="space-y-2">
                @foreach($penukaranMenunggu as $penukaran)
                    <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-bold text-slate-900">{{ $penukaran->warga->name }} menukar {{ $penukaran->hadiah->nama }}</p>
                            <p class="text-xs text-slate-500">Rp {{ number_format($penukaran->nilai_tukar_saat_itu, 0, ',', '.') }}</p>
                        </div>
                        <form method="POST" action="{{ route('hadiah-sampah.konfirmasi-tukar', $penukaran) }}">
                            @csrf
                            @method('PATCH')
                            <button class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Konfirmasi Diberikan</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse($hadiahs as $hadiah)
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" x-data="{ open: false }">
                <div class="relative h-48 bg-slate-100">
                    @if($hadiah->foto)
                        <img src="{{ route('hadiah-sampah.foto', $hadiah) }}" alt="Foto {{ $hadiah->nama }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-gift text-5xl"></i></div>
                    @endif
                    @if($hadiah->stok <= 0)
                        <span class="absolute left-4 top-4 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">Habis</span>
                    @elseif(! $hadiah->is_active)
                        <span class="absolute left-4 top-4 rounded-full bg-slate-700 px-3 py-1 text-xs font-bold text-white">Nonaktif</span>
                    @endif
                </div>
                <div class="p-5">
                    <h2 class="text-lg font-black text-slate-900">{{ $hadiah->nama }}</h2>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $hadiah->deskripsi ?: 'Tidak ada deskripsi.' }}</p>
                    <div class="mt-4 flex items-center justify-between">
                        <p class="font-black text-emerald-700">Rp {{ number_format($hadiah->nilai_tukar, 0, ',', '.') }}</p>
                        <p class="text-sm font-bold text-slate-500">Stok {{ $hadiah->stok }}</p>
                    </div>
                    @if(! $canManage)
                        <button type="button" x-on:click="open = true" @disabled($saldoSaya->saldo < $hadiah->nilai_tukar || ! $hadiah->isAvailable()) class="mt-5 w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                            Tukar Hadiah
                        </button>
                    @endif
                </div>

                <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <form method="POST" action="{{ route('hadiah-sampah.tukar', $hadiah) }}" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                        @csrf
                        <h3 class="text-lg font-black text-slate-900">Konfirmasi Tukar Hadiah</h3>
                        <p class="mt-2 text-sm text-slate-600">Tukar saldo Rp {{ number_format($hadiah->nilai_tukar, 0, ',', '.') }} untuk {{ $hadiah->nama }}?</p>
                        <label class="mt-4 block text-sm font-bold text-slate-700">Catatan</label>
                        <textarea name="catatan" rows="3" class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" x-on:click="open = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Batal</button>
                            <button class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-bold text-white">Ajukan Tukar</button>
                        </div>
                    </form>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500 md:col-span-2 xl:col-span-3">Belum ada hadiah.</div>
        @endforelse
    </div>
</div>
@endsection
