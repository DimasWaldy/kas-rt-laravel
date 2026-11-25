@extends('layouts.app')

@section('title', 'Papan Pengumuman RT/RW')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <i class="fa-solid fa-bullhorn"></i> Informasi Warga
                </span>
            </div>
            <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-slate-100 sm:text-3xl">Papan Pengumuman Digital RT &amp; RW</h1>
            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Pengumuman resmi, himbauan darurat, jadwal fogging, pemadaman, dan informasi lingkungan wilayah Anda.</p>
        </div>

        @if($canManage)
            <a href="{{ route('pengumuman.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-emerald-800">
                <i class="fa-solid fa-plus"></i>
                <span>Buat Pengumuman</span>
            </a>
        @endif
    </div>

    {{-- 3 KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
            <p class="text-[13px] font-semibold text-slate-500 dark:text-slate-400">Pengumuman Aktif</p>
            <p class="mt-2 text-2xl font-black tracking-tight text-emerald-700 dark:text-emerald-400 sm:text-3xl">{{ $counts['aktif'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
            <p class="text-[13px] font-semibold text-slate-500 dark:text-slate-400">Darurat &amp; Penting</p>
            <p class="mt-2 text-2xl font-black tracking-tight text-amber-600 dark:text-amber-400 sm:text-3xl">{{ $counts['darurat'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900/60">
            <p class="text-[13px] font-semibold text-slate-500 dark:text-slate-400">Disematkan (Pin)</p>
            <p class="mt-2 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">{{ $counts['disematkan'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Filter & Search --}}
    <form method="GET" action="{{ route('pengumuman.index') }}" class="space-y-4 rounded-3xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari judul atau isi pengumuman..."
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-xs font-semibold text-slate-900 focus:border-emerald-600 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
            </div>

            <select name="kategori" class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <option value="">Semua Kategori</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected($kategori === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="prioritas" class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <option value="">Semua Prioritas</option>
                @foreach($priorities as $key => $label)
                    <option value="{{ $key }}" @selected($prioritas === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-800">
                    <i class="fa-solid fa-filter"></i>
                    <span>Terapkan</span>
                </button>
                @if($search !== '' || $kategori !== '' || $prioritas !== '' || $status !== '')
                    <a href="{{ route('pengumuman.index') }}" class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
            @foreach(['' => 'Semua', 'aktif' => 'Sedang Tayang', 'arsip' => 'Arsip / Draf'] as $val => $lbl)
                <button type="submit" name="status" value="{{ $val }}" class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition {{ $status === $val ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    {{ $lbl }}
                </button>
            @endforeach
        </div>
    </form>

    {{-- Daftar Pengumuman --}}
    @if($pengumumans->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-800 dark:bg-slate-900">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                <i class="fa-solid fa-bullhorn"></i>
            </span>
            <h2 class="mt-4 font-bold text-slate-800 dark:text-slate-200">Belum Ada Pengumuman</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pengumuman terbaru dari pengurus RT dan RW akan muncul di papan informasi ini.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($pengumumans as $item)
                @php
                    $canEditItem = $canManage && ($isRwOfficial || $item->rt_id === auth()->user()->rt_id);
                    $borderClass = match ($item->prioritas) {
                        'darurat' => 'border-red-200 dark:border-red-900/60',
                        'penting' => 'border-amber-200 dark:border-amber-900/60',
                        default => 'border-slate-200 dark:border-slate-800',
                    };
                @endphp
                <article class="rounded-3xl border {{ $borderClass }} bg-white p-6 shadow-xs dark:bg-slate-900">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3 min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border px-3 py-1 text-[11px] font-black {{ $item->prioritas_badge_class }}">
                                    {{ $item->prioritas_label }}
                                </span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $item->kategori_label }}
                                </span>
                                <span class="rounded-full {{ $item->isRtScoped() ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' }} px-3 py-1 text-[11px] font-bold">
                                    {{ $item->scope_label }}
                                </span>
                                @if($item->is_pinned)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-black text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                        <i class="fa-solid fa-thumbtack"></i> Disematkan
                                    </span>
                                @endif
                                @if($canManage)
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $item->is_public ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                        {{ $item->is_public ? 'Publik & Dashboard' : 'Internal Warga' }}
                                    </span>
                                    @if(! $item->is_published)
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200">Draf / Diarsipkan</span>
                                    @elseif($item->isExpired())
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200">Kedaluwarsa</span>
                                    @endif
                                @endif
                            </div>

                            <h2 class="text-xl font-black text-slate-900 dark:text-slate-100">{{ $item->judul }}</h2>
                            <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $item->isi }}</p>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 text-xs text-slate-400">
                                <span><i class="fa-solid fa-user-pen mr-1 text-emerald-600"></i>{{ $item->creator?->name ?? 'Pengurus' }}</span>
                                <span><i class="fa-regular fa-clock mr-1 text-emerald-600"></i>{{ $item->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                @if($item->tanggal_berakhir)
                                    <span><i class="fa-regular fa-calendar-xmark mr-1 text-amber-500"></i>Berlaku s/d {{ $item->tanggal_berakhir->translatedFormat('d M Y, H:i') }} WIB</span>
                                @endif
                            </div>
                        </div>

                        @if($canEditItem)
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('pengumuman.toggle-pin', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        <i class="fa-solid fa-thumbtack mr-1"></i>
                                        <span>{{ $item->is_pinned ? 'Lepas Pin' : 'Sematkan' }}</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('pengumuman.toggle-publish', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        <span>{{ $item->is_published ? 'Arsipkan' : 'Terbitkan' }}</span>
                                    </button>
                                </form>

                                <a href="{{ route('pengumuman.edit', $item) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    <i class="fa-solid fa-pen mr-1"></i>Edit
                                </a>

                                <form method="POST" action="{{ route('pengumuman.destroy', $item) }}" onsubmit="return confirm('Hapus pengumuman ini secara permanen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300">
                                        <i class="fa-solid fa-trash mr-1"></i>Hapus
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if($pengumumans->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                {{ $pengumumans->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
