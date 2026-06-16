@extends('layouts.app')

@section('title', 'Detail Peminjaman Aset')

@section('content')
@php
    $isRw = $peminjamanAset->aset->isRwAsset();
    $canManage = auth()->user()->hasPermission($isRw ? 'manage-aset-rw' : 'manage-aset');
    $indexRoute = $isRw ? 'peminjaman-aset-rw.index' : 'peminjaman-aset.index';
    $asetShowRoute = $isRw ? 'aset-rw.show' : 'aset.show';
    $asetFotoRoute = $isRw ? 'aset-rw.foto' : 'aset.foto';
    $setujuiRoute = $isRw ? 'peminjaman-aset-rw.setujui' : 'peminjaman-aset.setujui';
    $tolakRoute = $isRw ? 'peminjaman-aset-rw.tolak' : 'peminjaman-aset.tolak';
    $dipinjamRoute = $isRw ? 'peminjaman-aset-rw.dipinjam' : 'peminjaman-aset.dipinjam';
    $kembaliRoute = $isRw ? 'peminjaman-aset-rw.kembali' : 'peminjaman-aset.kembali';
    $steps = ['diajukan', 'disetujui', 'dipinjam', 'dikembalikan'];
    $currentIndex = array_search($peminjamanAset->status, $steps, true);
@endphp

<div class="mx-auto max-w-6xl space-y-6" x-data="{ modal: '{{ $errors->has('catatan_pengurus') ? 'tolak' : '' }}' }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route($indexRoute) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke peminjaman</a>
        <span class="w-fit rounded-full border px-3 py-1.5 text-xs font-bold {{ $peminjamanAset->status_color }}">{{ $peminjamanAset->status_label }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm font-semibold text-emerald-700">Peminjaman Aset {{ $isRw ? 'RW' : 'RT' }}</p>
                <h1 class="mt-2 text-2xl font-black text-slate-900">{{ $peminjamanAset->aset->nama }}</h1>
                <p class="mt-2 text-sm text-slate-500">Diajukan oleh <strong>{{ $peminjamanAset->pemohon->name }}</strong></p>

                <dl class="mt-7 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal pinjam</dt><dd class="mt-1 font-bold text-slate-800">{{ $peminjamanAset->tanggal_mulai->translatedFormat('d F Y') }} - {{ $peminjamanAset->tanggal_selesai->translatedFormat('d F Y') }}</dd></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Durasi</dt><dd class="mt-1 font-bold text-slate-800">{{ $peminjamanAset->durasi_hari }} hari</dd></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Jumlah</dt><dd class="mt-1 font-bold text-slate-800">{{ $peminjamanAset->jumlah_dipinjam }} unit</dd></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Diproses oleh</dt><dd class="mt-1 font-bold text-slate-800">{{ $peminjamanAset->processor?->name ?: '-' }}</dd></div>
                    <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Keperluan</dt><dd class="mt-1 font-bold text-slate-800">{{ $peminjamanAset->keperluan }}</dd></div>
                </dl>

                @if($peminjamanAset->catatan_pemohon)
                    <div class="mt-5 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan pemohon</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $peminjamanAset->catatan_pemohon }}</p>
                    </div>
                @endif

                @if($peminjamanAset->catatan_pengurus)
                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Catatan pengurus</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-amber-900">{{ $peminjamanAset->catatan_pengurus }}</p>
                    </div>
                @endif
            </article>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold text-slate-900">Timeline Status</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-4">
                    @foreach($steps as $index => $step)
                        @php $done = $currentIndex !== false && $index <= $currentIndex; @endphp
                        <div class="rounded-2xl border p-4 {{ $done ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full {{ $done ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-500' }}"><i class="fa-solid fa-check text-xs"></i></span>
                            <p class="mt-3 text-sm font-bold {{ $done ? 'text-emerald-900' : 'text-slate-500' }}">{{ str($step)->headline() }}</p>
                        </div>
                    @endforeach
                </div>
                @if($peminjamanAset->status === 'ditolak')
                    <p class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">Pengajuan ini ditolak oleh pengurus.</p>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold text-slate-900">Info Aset</h2>
                @if($peminjamanAset->aset->foto)
                    <img src="{{ route($asetFotoRoute, $peminjamanAset->aset) }}" alt="Foto {{ $peminjamanAset->aset->nama }}" class="mt-4 h-44 w-full rounded-2xl object-cover">
                @endif
                <a href="{{ route($asetShowRoute, $peminjamanAset->aset) }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100">Lihat Detail Aset</a>
            </section>

            @if($canManage)
                <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
                    <h2 class="font-bold text-emerald-950">Aksi Pengurus</h2>
                    <p class="mt-1 text-xs text-emerald-700">Setiap aksi akan tercatat di status peminjaman.</p>

                    <div class="mt-4 space-y-2">
                        @if($peminjamanAset->status === 'diajukan')
                            <button type="button" @click="modal = 'setujui'" class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-800">Setujui</button>
                            <button type="button" @click="modal = 'tolak'" class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700">Tolak</button>
                        @elseif($peminjamanAset->status === 'disetujui')
                            <form method="POST" action="{{ route($dipinjamRoute, $peminjamanAset) }}">
                                @csrf
                                @method('PATCH')
                                <button class="w-full rounded-xl bg-amber-600 px-4 py-3 text-sm font-bold text-white hover:bg-amber-700">Konfirmasi Dipinjam</button>
                            </form>
                        @elseif($peminjamanAset->status === 'dipinjam')
                            <form method="POST" action="{{ route($kembaliRoute, $peminjamanAset) }}">
                                @csrf
                                @method('PATCH')
                                <button class="w-full rounded-xl bg-slate-800 px-4 py-3 text-sm font-bold text-white hover:bg-slate-900">Konfirmasi Dikembalikan</button>
                            </form>
                        @else
                            <p class="rounded-2xl bg-white/70 p-4 text-center text-sm font-semibold text-slate-600">Tidak ada aksi lanjutan.</p>
                        @endif
                    </div>
                </section>
            @endif
        </aside>
    </div>

    <div x-show="modal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none">
        <div @click.outside="modal = ''" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-black text-slate-900" x-text="modal === 'setujui' ? 'Setujui Peminjaman' : 'Tolak Peminjaman'"></h2>
                <button type="button" @click="modal = ''" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" :action="modal === 'setujui' ? '{{ route($setujuiRoute, $peminjamanAset) }}' : '{{ route($tolakRoute, $peminjamanAset) }}'" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="text-sm font-bold text-slate-700">Catatan Pengurus</label>
                    <textarea name="catatan_pengurus" rows="4" :required="modal === 'tolak'" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Wajib diisi jika menolak">{{ old('catatan_pengurus') }}</textarea>
                    @error('catatan_pengurus')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <button class="w-full rounded-xl px-4 py-3 text-sm font-bold text-white" :class="modal === 'setujui' ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-red-600 hover:bg-red-700'" x-text="modal === 'setujui' ? 'Setujui Peminjaman' : 'Tolak Peminjaman'"></button>
            </form>
        </div>
    </div>
</div>
@endsection
