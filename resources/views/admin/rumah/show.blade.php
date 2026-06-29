@extends('layouts.app')

@section('title', 'Detail Rumah')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Detail Unit Hunian</p>
                <h1 class="mt-2 text-2xl font-black text-slate-900">{{ $rumah->label }}</h1>
                <p class="mt-2 text-sm text-slate-500">RT/RW {{ $rumah->rt ?? '-' }}/{{ $rumah->rw ?? '-' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.rumah.edit', $rumah) }}" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Edit Rumah</a>
                <a href="{{ route('admin.rumah.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Kembali</a>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Status Rumah</p>
            <p class="mt-2 text-2xl font-black capitalize {{ $rumah->status === 'aktif' ? 'text-emerald-700' : ($rumah->status === 'kosong' ? 'text-slate-600' : 'text-rose-600') }}">{{ $rumah->status }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Jumlah Warga</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ $rumah->warga->count() }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Jumlah KK</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ $rumah->warga->filter(fn ($user) => $user->warga?->status_dalam_kk === 'kepala_keluarga')->count() }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">PJ Iuran</p>
            <p class="mt-2 text-base font-black text-slate-900">{{ $rumah->penanggungJawab?->name ?? 'Belum ditentukan' }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <section class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-black text-slate-900">Warga di Rumah Ini</h2>
                <p class="mt-1 text-sm text-slate-500">Gunakan aksi pindah jika warga sudah tidak tinggal di unit ini.</p>
            </div>

            <div class="space-y-3">
                @forelse($rumah->warga as $warga)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-black text-slate-900">{{ $warga->name }}</p>
                                    @if($warga->is_kepala_keluarga)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">Kepala KK</span>
                                    @endif
                                    @if($warga->is_penanggung_jawab_rumah)
                                        <span class="rounded-full bg-lime-100 px-2.5 py-1 text-[11px] font-bold text-lime-700">PJ Iuran</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $warga->email }} | KK: {{ $warga->no_kk ?? '-' }} | HP: {{ $warga->phone ?? '-' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.warga.edit', $warga) }}" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">Edit Warga</a>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.rumah.warga.move', [$rumah, $warga]) }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_auto_auto] md:items-center">
                            @csrf
                            <select name="target_rumah_id" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                                <option value="">Keluarkan dari rumah / belum ditentukan</option>
                                @foreach($rumahOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                <input type="checkbox" name="make_penanggung_jawab" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                Jadikan PJ di rumah tujuan
                            </label>
                            <button class="rounded-2xl border border-emerald-200 px-4 py-3 text-xs font-black text-emerald-800 hover:bg-emerald-50">Pindahkan</button>
                        </form>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-8 text-center text-sm font-semibold text-slate-500">Belum ada warga di rumah ini.</div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-900">Tagihan Periode Ini</h2>
                <p class="mt-1 text-sm text-slate-500">{{ \Carbon\Carbon::create(null, $bulan)->translatedFormat('F') }} {{ $tahun }}</p>
                <div class="mt-4 space-y-3">
                    @forelse($tagihans as $tagihan)
                        <div class="rounded-2xl bg-emerald-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-emerald-950">{{ $tagihan->display_title }}</p>
                                    <p class="mt-1 text-xs text-emerald-700">{{ $tagihan->status_label }}</p>
                                </div>
                                <p class="font-black text-emerald-800">Rp {{ number_format($tagihan->total, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">Belum ada tagihan bulan ini.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-amber-100 bg-amber-50 p-6">
                <h2 class="text-sm font-black text-amber-900">Catatan Konsep</h2>
                <p class="mt-2 text-sm leading-6 text-amber-800">Rumah aktif dan memiliki PJ akan menerima tagihan iuran. Rumah kosong/nonaktif tidak seharusnya dipakai sebagai tujuan tagihan baru.</p>
            </div>
        </aside>
    </div>
</div>
@endsection
