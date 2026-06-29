@extends('layouts.app')

@section('title', 'Posyandu & KMS')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('manage-posyandu');
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-rose-600">Pemantauan tumbuh kembang balita</p>
            <h1 class="text-2xl font-black text-slate-900">Posyandu Smart RW</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau berat badan menurut umur menggunakan kurva WHO usia 0-60 bulan.</p>
        </div>
        @if($canManage)
            <a href="{{ route('posyandu.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white hover:bg-rose-700"><i class="fa-solid fa-plus"></i> Tambah Balita</a>
        @endif
    </div>

    <section class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Terlihat</p><p class="mt-2 text-3xl font-black text-slate-900">{{ $stats['total'] }}</p></div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Balita Aktif</p><p class="mt-2 text-3xl font-black text-emerald-800">{{ $stats['aktif'] }}</p></div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-amber-600">Perlu Perhatian BB/U</p><p class="mt-2 text-3xl font-black text-amber-800">{{ $stats['perlu_perhatian'] }}</p></div>
    </section>

    <form method="GET" action="{{ route('posyandu.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_12rem_12rem_auto]">
        <input name="search" value="{{ $filters['search'] }}" placeholder="Cari nama, NIK, KK, atau orang tua..." class="rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
        @if($rts->isNotEmpty())
            <select name="rt_id" class="rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
                <option value="">Semua RT</option>
                @foreach($rts as $rt)<option value="{{ $rt->id }}" @selected($filters['rt_id'] === $rt->id)>{{ $rt->name }}</option>@endforeach
            </select>
        @endif
        <select name="is_active" class="rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
            <option value="">Semua status</option>
            <option value="1" @selected($filters['is_active'] === '1')>Aktif</option>
            <option value="0" @selected($filters['is_active'] === '0')>Nonaktif</option>
        </select>
        <button class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">Terapkan</button>
    </form>

    @if($balitas->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-2xl text-rose-500"><i class="fa-solid fa-baby"></i></span>
            <h2 class="mt-4 font-bold text-slate-800">Belum ada data balita</h2>
            <p class="mt-1 text-sm text-slate-500">Data sesuai wilayah dan filter akan tampil di sini.</p>
        </div>
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($balitas as $balita)
                @php
                    $last = $balita->pemeriksaanTerakhir;
                    [$statusLabel, $statusClass] = match($last?->status_bb_u) {
                        'berat_sangat_kurang' => ['Berat sangat kurang', 'bg-red-50 text-red-700 border-red-200'],
                        'berat_kurang' => ['Berat kurang', 'bg-amber-50 text-amber-700 border-amber-200'],
                        'normal' => ['Normal', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                        'di_atas_rentang_normal' => ['Di atas rentang normal', 'bg-blue-50 text-blue-700 border-blue-200'],
                        default => ['Belum diperiksa', 'bg-slate-50 text-slate-600 border-slate-200'],
                    };
                @endphp
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $balita->jenis_kelamin === 'laki_laki' ? 'bg-blue-50 text-blue-600' : 'bg-rose-50 text-rose-600' }}"><i class="fa-solid fa-child-reaching text-xl"></i></span>
                        <div class="flex flex-wrap justify-end gap-2">
                            <span class="rounded-full border px-3 py-1 text-[10px] font-black {{ $statusClass }}">{{ $statusLabel }}</span>
                            @unless($balita->is_active)<span class="rounded-full bg-slate-200 px-3 py-1 text-[10px] font-black text-slate-600">Nonaktif</span>@endunless
                        </div>
                    </div>
                    <h2 class="mt-4 text-lg font-black text-slate-900">{{ $balita->nama }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $balita->jenis_kelamin_label }} &middot; {{ $balita->usia_sekarang_bulan }} bulan &middot; {{ $balita->rt->name }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-4 text-sm">
                        <div><p class="text-xs text-slate-400">Berat terakhir</p><p class="mt-1 font-black text-slate-800">{{ $last ? number_format($last->berat_kg, 2, ',', '.') . ' kg' : '-' }}</p></div>
                        <div><p class="text-xs text-slate-400">Z-score BB/U</p><p class="mt-1 font-black text-slate-800">{{ $last ? number_format($last->z_score_bb_u, 2, ',', '.') : '-' }}</p></div>
                    </div>
                    <p class="mt-4 truncate text-xs text-slate-500"><i class="fa-solid fa-user-group mr-1 text-rose-500"></i>{{ $balita->orangTua?->name ?? $balita->nama_ibu ?? 'Orang tua belum dihubungkan' }}</p>
                    <a href="{{ route('posyandu.show', $balita) }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 hover:bg-rose-100">Lihat KMS <i class="fa-solid fa-chart-line"></i></a>
                </article>
            @endforeach
        </div>
        @if($balitas->hasPages())<div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $balitas->links() }}</div>@endif
    @endif
</div>
@endsection
