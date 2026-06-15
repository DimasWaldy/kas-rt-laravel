@extends('layouts.app')

@section('title', 'Iuran Khusus / Insidental')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <header class="flex flex-col gap-4 rounded-3xl bg-gradient-to-r from-emerald-800 to-green-600 p-6 text-white shadow-lg sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-100">Dana insidental RT</p>
            <h1 class="mt-2 text-2xl font-black sm:text-3xl">Iuran Khusus / Insidental</h1>
            <p class="mt-2 max-w-2xl text-sm text-emerald-50">Kelola iuran sosial, kematian, pembangunan, dan kebutuhan khusus lainnya tanpa mengubah iuran rutin.</p>
        </div>
        <a href="{{ route('iuran-khusus.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-black text-emerald-800 shadow-sm hover:bg-emerald-50">
            <i class="fa-solid fa-plus"></i> Buat Iuran Khusus
        </a>
    </header>

    @if($iuranKhusus->isEmpty())
        <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600"><i class="fa-solid fa-hand-holding-heart"></i></span>
            <h2 class="mt-4 text-lg font-black text-slate-800">Belum ada iuran khusus</h2>
            <p class="mt-1 text-sm text-slate-500">Buat batch pertama ketika RT memiliki kebutuhan insidental.</p>
        </section>
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($iuranKhusus as $item)
                @php
                    $persentase = $item->total_tagihan > 0
                        ? min(100, round(($item->total_lunas / $item->total_tagihan) * 100))
                        : 0;
                @endphp
                <article class="flex flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $item->jenis_color }}">{{ $item->jenis_label }}</span>
                        <span class="text-xs text-slate-400">{{ $item->created_at->translatedFormat('d M Y') }}</span>
                    </div>

                    <h2 class="mt-4 text-lg font-black text-slate-900">{{ $item->judul }}</h2>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $item->keterangan ?: 'Tidak ada keterangan tambahan.' }}</p>

                    <div class="mt-5 rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Nominal per warga</p>
                        <p class="mt-1 text-xl font-black text-slate-900">Rp {{ number_format($item->nominal_per_warga, 0, ',', '.') }}</p>
                    </div>

                    <div class="mt-5">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-600">
                            <span>{{ $item->total_lunas }} dari {{ $item->total_tagihan }} warga sudah bayar</span>
                            <span>{{ $persentase }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $persentase }}%"></div></div>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-slate-500">Total terkumpul</span>
                        <strong class="text-emerald-700">Rp {{ number_format($item->total_terkumpul, 0, ',', '.') }}</strong>
                    </div>

                    <a href="{{ route('iuran-khusus.show', $item) }}" class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100">
                        Lihat Detail <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </article>
            @endforeach
        </div>

        @if($iuranKhusus->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $iuranKhusus->links() }}</div>
        @endif
    @endif
</div>
@endsection
