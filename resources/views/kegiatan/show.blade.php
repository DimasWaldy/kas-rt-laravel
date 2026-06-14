@extends('layouts.app')

@section('title', 'Detail Kegiatan RW')

@section('content')
<div class="mx-auto max-w-6xl space-y-6" x-data="{ showCancel: {{ $errors->has('catatan_pembatalan') ? 'true' : 'false' }} }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('kegiatan.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke daftar</a>
        @if(auth()->user()->hasPermission('manage-kegiatan'))
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()->isGlobalOperator() || $kegiatan->created_by === auth()->id())
                    <a href="{{ route('kegiatan.edit', $kegiatan) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-pen mr-1"></i> Edit</a>
                @endif
                @if(! in_array($kegiatan->effective_status, ['dibatalkan', 'selesai'], true))
                    <button type="button" @click="showCancel = !showCancel" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-100"><i class="fa-solid fa-ban mr-1"></i> Batalkan</button>
                @endif
            </div>
        @endif
    </div>

    @if($kegiatan->foto || ($kegiatan->foto_dokumentasi && $kegiatan->effective_status !== 'akan_datang'))
        <section class="grid gap-5 {{ $kegiatan->foto && $kegiatan->foto_dokumentasi && $kegiatan->effective_status !== 'akan_datang' ? 'lg:grid-cols-2' : '' }}">
            @if($kegiatan->foto)
                <figure class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <img src="{{ route('kegiatan.foto', $kegiatan) }}" alt="Poster {{ $kegiatan->nama }}" class="h-72 w-full object-cover">
                    <figcaption class="p-4 text-sm font-bold text-slate-600"><i class="fa-solid fa-bullhorn mr-2 text-emerald-600"></i>Poster / Gambar Ajakan</figcaption>
                </figure>
            @endif
            @if($kegiatan->foto_dokumentasi && $kegiatan->effective_status !== 'akan_datang')
                <figure class="overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-sm">
                    <img src="{{ route('kegiatan.dokumentasi', $kegiatan) }}" alt="Dokumentasi {{ $kegiatan->nama }}" class="h-72 w-full object-cover">
                    <figcaption class="p-4 text-sm font-bold text-emerald-700"><i class="fa-solid fa-camera mr-2"></i>Dokumentasi Kegiatan</figcaption>
                </figure>
            @endif
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <span class="inline-flex rounded-full border px-3 py-1.5 text-xs font-bold {{ $kegiatan->status_color }}">{{ $kegiatan->status_label }}</span>
                <h1 class="mt-4 text-2xl font-black text-slate-900 sm:text-3xl">{{ $kegiatan->nama }}</h1>

                <div class="mt-6 grid gap-4 rounded-2xl bg-slate-50 p-5 text-sm sm:grid-cols-2">
                    <div class="flex gap-3"><i class="fa-regular fa-calendar mt-1 text-emerald-600"></i><div><p class="text-xs text-slate-400">Waktu mulai</p><p class="font-bold text-slate-800">{{ $kegiatan->tanggal_mulai->translatedFormat('l, d F Y') }}</p><p class="text-slate-500">{{ $kegiatan->tanggal_mulai->format('H:i') }} WIB</p></div></div>
                    <div class="flex gap-3"><i class="fa-solid fa-location-dot mt-1 text-emerald-600"></i><div><p class="text-xs text-slate-400">Lokasi</p><p class="font-bold text-slate-800">{{ $kegiatan->lokasi ?: 'Lokasi menyusul' }}</p></div></div>
                    @if($kegiatan->tanggal_selesai)<div class="flex gap-3"><i class="fa-regular fa-clock mt-1 text-blue-600"></i><div><p class="text-xs text-slate-400">Waktu selesai</p><p class="font-bold text-slate-800">{{ $kegiatan->tanggal_selesai->translatedFormat('d F Y, H:i') }} WIB</p></div></div>@endif
                    <div class="flex gap-3"><i class="fa-solid fa-user-tie mt-1 text-blue-600"></i><div><p class="text-xs text-slate-400">Dibuat oleh</p><p class="font-bold text-slate-800">{{ $kegiatan->creator->name }}</p></div></div>
                </div>

                <div class="mt-7"><h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Deskripsi Kegiatan</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $kegiatan->deskripsi ?: 'Belum ada deskripsi kegiatan.' }}</p></div>

                @if($kegiatan->status === 'dibatalkan')
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5"><h2 class="font-bold text-red-900">Kegiatan Dibatalkan</h2><p class="mt-2 whitespace-pre-line text-sm text-red-800">{{ $kegiatan->catatan_pembatalan }}</p></div>
                @endif
            </article>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3"><div><h2 class="font-bold text-slate-900">Daftar Hadir</h2><p class="text-xs text-slate-500">{{ $kegiatan->hadirs->count() }} warga telah mengonfirmasi</p></div><span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600"><i class="fa-solid fa-users"></i></span></div>
                <div class="mt-5 divide-y divide-slate-100">
                    @forelse($kegiatan->hadirs->sortByDesc('konfirmasi_at') as $hadir)
                        <div class="flex items-center gap-3 py-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700">{{ substr($hadir->user->name, 0, 1) }}</span><div class="min-w-0"><p class="truncate text-sm font-bold text-slate-800">{{ $hadir->user->name }}</p><p class="text-xs text-slate-400">{{ $hadir->konfirmasi_at?->translatedFormat('d M Y, H:i') }}</p>@if($hadir->catatan)<p class="mt-1 text-xs text-slate-500">{{ $hadir->catatan }}</p>@endif</div></div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">Belum ada warga yang konfirmasi hadir.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold text-slate-900">Informasi Biaya</h2>
                <dl class="mt-4 space-y-4 text-sm"><div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs text-slate-400">Estimasi</dt><dd class="mt-1 text-lg font-black text-slate-800">Rp {{ number_format($kegiatan->estimasi_biaya, 0, ',', '.') }}</dd></div><div class="rounded-xl bg-emerald-50 p-4"><dt class="text-xs text-emerald-600">Realisasi</dt><dd class="mt-1 text-lg font-black text-emerald-800">Rp {{ number_format($kegiatan->realisasi_biaya, 0, ',', '.') }}</dd></div></dl>
                <p class="mt-4 text-xs leading-relaxed text-slate-400">Nominal ini bersifat informatif dan tidak terhubung otomatis ke kas.</p>
            </section>

            <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
                @if($sudahHadir)
                    <div class="text-center text-emerald-800"><i class="fa-solid fa-circle-check text-3xl"></i><p class="mt-3 font-bold">Anda sudah konfirmasi hadir</p></div>
                @elseif(! in_array($kegiatan->effective_status, ['dibatalkan', 'selesai'], true))
                    <h2 class="font-bold text-emerald-950">Akan hadir?</h2><p class="mt-1 text-sm text-emerald-800">Konfirmasi membantu pengurus menyiapkan kegiatan.</p>
                    <form method="POST" action="{{ route('kegiatan.hadir', $kegiatan) }}" class="mt-4 space-y-3">@csrf<textarea name="catatan" rows="2" maxlength="255" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm" placeholder="Catatan opsional"></textarea><button class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-800"><i class="fa-solid fa-user-check mr-1"></i> Konfirmasi Hadir</button></form>
                @else
                    <p class="text-center text-sm font-semibold text-slate-600">Konfirmasi kehadiran sudah ditutup.</p>
                @endif
            </section>

            @if(auth()->user()->hasPermission('manage-kegiatan'))
                <section x-cloak x-show="showCancel" x-transition class="rounded-3xl border border-red-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-red-900">Batalkan Kegiatan</h2><p class="mt-1 text-xs text-red-600">Alasan pembatalan akan terlihat oleh warga.</p>
                    <form method="POST" action="{{ route('kegiatan.batalkan', $kegiatan) }}" class="mt-4 space-y-3">@csrf @method('PATCH')<textarea name="catatan_pembatalan" rows="4" required minlength="10" class="w-full rounded-xl border border-red-200 px-3 py-2 text-sm" placeholder="Tuliskan alasan pembatalan minimal 10 karakter">{{ old('catatan_pembatalan') }}</textarea>@error('catatan_pembatalan')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror<button class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700">Konfirmasi Pembatalan</button></form>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
