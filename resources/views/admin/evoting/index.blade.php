@extends('layouts.app')

@section('title', 'Kelola E-Voting Pemilihan')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-purple-100 px-2.5 py-1 text-xs font-black text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                    <i class="fa-solid fa-box-archive"></i> Panel Pengurus
                </span>
                <span class="text-xs font-bold text-slate-400">•</span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Penyelenggara Pemilu</span>
            </div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">Kelola Pemilihan RT & RW</h1>
            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Kelola agenda pemilu, daftar kandidat paslon, dan pemantauan suara warga secara aman.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('evoting.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                <i class="fa-solid fa-eye"></i>
                Bilik Suara Warga
            </a>
            <a href="{{ route('admin.evoting.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-purple-800">
                <i class="fa-solid fa-plus"></i>
                Buat Pemilihan Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-bold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
            <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if($pemilihans->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-800 dark:bg-slate-900">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-purple-50 text-2xl text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                <i class="fa-solid fa-box-archive"></i>
            </span>
            <h2 class="mt-4 font-bold text-slate-800 dark:text-slate-200">Belum Ada Pemilihan</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Klik tombol "Buat Pemilihan Baru" untuk memulai agenda pemilihan di lingkungan Anda.</p>
            <div class="mt-6">
                <a href="{{ route('admin.evoting.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-purple-800">
                    <i class="fa-solid fa-plus"></i> Mulai Pemilihan Baru
                </a>
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50/75 text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-4 font-bold">Agenda Pemilihan</th>
                            <th class="px-6 py-4 font-bold">Tingkat / Wilayah</th>
                            <th class="px-6 py-4 font-bold">Periode Pemilihan</th>
                            <th class="px-6 py-4 font-bold">Kandidat</th>
                            <th class="px-6 py-4 font-bold">Suara Masuk</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($pemilihans as $pemilihan)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-4">
                                    <p class="font-black text-slate-900 dark:text-slate-100">{{ $pemilihan->title }}</p>
                                    <p class="mt-0.5 text-2xs text-slate-400">Dibuat: {{ $pemilihan->created_at->translatedFormat('d M Y') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-2xs font-bold {{ $pemilihan->scope_type === 'rw' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                        {{ $pemilihan->scope_type === 'rw' ? 'Tingkat RW' : ($pemilihan->rt ? 'RT '.$pemilihan->rt->name : 'Tingkat RT') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">
                                    <p class="font-semibold">{{ $pemilihan->start_date->translatedFormat('d M Y H:i') }}</p>
                                    <p class="text-2xs text-slate-400">s/d {{ $pemilihan->end_date->translatedFormat('d M Y H:i') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-black text-purple-700 dark:text-purple-400">{{ $pemilihan->kandidats->count() }} Paslon</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-black text-slate-900 dark:text-slate-100">{{ $pemilihan->voters->count() }} Suara</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($pemilihan->status === 'aktif' && $pemilihan->isOngoing())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-2xs font-black text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 animate-pulse"></span> Aktif
                                        </span>
                                    @elseif($pemilihan->status === 'selesai' || $pemilihan->isFinished())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-2xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            Selesai
                                        </span>
                                    @elseif($pemilihan->status === 'draft')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-2xs font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                            Draft
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-2xs font-bold text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                            {{ ucfirst($pemilihan->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.evoting.show', $pemilihan->slug) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-100 dark:bg-purple-950/60 dark:text-purple-300 dark:hover:bg-purple-900">
                                        <i class="fa-solid fa-gear"></i>
                                        <span>Kelola</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($pemilihans->hasPages())
                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $pemilihans->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
