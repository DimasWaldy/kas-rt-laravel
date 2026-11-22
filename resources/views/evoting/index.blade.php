@extends('layouts.app')

@section('title', 'E-Voting Pemilu RT/RW')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-purple-100 px-2.5 py-1 text-xs font-black text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                    <i class="fa-solid fa-check-to-slot"></i> E-Demokrasi Warga
                </span>
                <span class="text-xs font-bold text-slate-400">•</span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">1 KTP = 1 Suara Sah</span>
            </div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">E-Voting Pemilu RT / RW</h1>
            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Pesta demokrasi pemilihan pimpinan lingkungan berbasis verifikasi fisik e-KTP secara Langsung, Umum, Bebas, Rahasia, Jujur, dan Adil.</p>
        </div>

        @if(auth()->user()->hasPermission('manage-voting'))
            <a href="{{ route('admin.evoting.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-purple-800">
                <i class="fa-solid fa-box-archive"></i>
                Kelola Pemilihan
            </a>
        @endif
    </div>

    {{-- e-KTP Verification Status Banner --}}
    @if($hasVerifiedKtp)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-2xs dark:border-emerald-900/50 dark:bg-emerald-950/30">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                        <i class="fa-solid fa-id-card-clip text-lg"></i>
                    </div>
                    <div>
                        <p class="font-bold text-emerald-950 dark:text-emerald-200">e-KTP Fisik Terverifikasi (Hak Suara Aktif)</p>
                        <p class="text-xs text-emerald-700 dark:text-emerald-400">Identitas fisik e-KTP Anda telah tervalidasi via NFC/RFID. Anda berhak memberikan 1 suara sah pada pemilihan wilayah Anda.</p>
                    </div>
                </div>
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-emerald-200/80 px-3 py-1 text-xs font-black text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                    <i class="fa-solid fa-shield-check"></i> Terverifikasi
                </span>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-5 shadow-2xs dark:border-amber-800/60 dark:bg-amber-950/40">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-200/80 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="font-bold text-amber-950 dark:text-amber-200">Hak Suara Terkunci: Wajib Verifikasi Fisik e-KTP</p>
                        <p class="mt-0.5 text-xs text-amber-900/90 dark:text-amber-300/90">Demi mencegah kecurangan dan bot akun ganda, sistem E-Voting mensyaratkan verifikasi fisik e-KTP melalui Tap NFC Smartphone atau Reader RT.</p>
                    </div>
                </div>
                <a href="{{ route('warga.rfid.show') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-amber-800">
                    <i class="fa-solid fa-wifi"></i>
                    <span>Verifikasi e-KTP Sekarang</span>
                </a>
            </div>
        </div>
    @endif

    {{-- Daftar Pemilihan --}}
    @if($pemilihans->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-800 dark:bg-slate-900">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-purple-50 text-2xl text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                <i class="fa-solid fa-check-to-slot"></i>
            </span>
            <h2 class="mt-4 font-bold text-slate-800 dark:text-slate-200">Belum Ada Agenda Pemilihan</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pemilihan pimpinan RT atau RW di lingkungan Anda akan ditampilkan di sini saat masa pemilu dibuka.</p>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($pemilihans as $pemilihan)
                @php
                    $isVoted = $pemilihan->hasUserVoted(auth()->id());
                    $isOngoing = $pemilihan->isOngoing();
                    $isFinished = $pemilihan->isFinished();
                    $isUpcoming = $pemilihan->isUpcoming();
                @endphp
                <article class="flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <div class="space-y-4">
                        {{-- Badges --}}
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg {{ $pemilihan->scope_type === 'rw' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }} px-2.5 py-1 text-[11px] font-bold">
                                <i class="fa-solid fa-map-pin"></i>
                                {{ $pemilihan->scope_type === 'rw' ? 'Tingkat RW' : ($pemilihan->rt ? 'RT '.$pemilihan->rt->name : 'Tingkat RT') }}
                            </span>

                            @if($isOngoing)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <span class="h-2 w-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                    Sedang Berlangsung
                                </span>
                            @elseif($isFinished)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    <i class="fa-solid fa-flag-checkered"></i> Selesai
                                </span>
                            @elseif($isUpcoming)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-[11px] font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                    <i class="fa-solid fa-clock"></i> Akan Datang
                                </span>
                            @endif
                        </div>

                        {{-- Title & Desc --}}
                        <div>
                            <h2 class="text-lg font-black tracking-tight text-slate-900 hover:text-purple-700 dark:text-slate-100 dark:hover:text-purple-400">
                                <a href="{{ route('evoting.show', $pemilihan->slug) }}">{{ $pemilihan->title }}</a>
                            </h2>
                            @if($pemilihan->description)
                                <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $pemilihan->description }}</p>
                            @endif
                        </div>

                        {{-- Info Details --}}
                        <div class="space-y-1.5 rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400"><i class="fa-solid fa-calendar-check mr-1.5"></i>Periode:</span>
                                <span class="font-bold">{{ $pemilihan->start_date->translatedFormat('d M') }} - {{ $pemilihan->end_date->translatedFormat('d M Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400"><i class="fa-solid fa-users mr-1.5"></i>Paslon Terdaftar:</span>
                                <span class="font-bold text-purple-700 dark:text-purple-400">{{ $pemilihan->kandidats->count() }} Pasangan Calon</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400"><i class="fa-solid fa-box-ballot mr-1.5"></i>Suara Masuk:</span>
                                <span class="font-bold">{{ $pemilihan->voters->count() }} Suara</span>
                            </div>
                        </div>

                        {{-- Status Hak Suara User --}}
                        <div class="pt-1">
                            @if($isVoted)
                                <div class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-100/80 px-3 py-2 text-xs font-black text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    <i class="fa-solid fa-circle-check"></i> Hak Suara Telah Digunakan
                                </div>
                            @elseif(!$hasVerifiedKtp)
                                <div class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-100/80 px-3 py-2 text-xs font-black text-amber-900 dark:bg-amber-950/60 dark:text-amber-300">
                                    <i class="fa-solid fa-lock"></i> Hak Suara Terkunci (Wajib e-KTP)
                                </div>
                            @elseif($isOngoing)
                                <div class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-purple-100/80 px-3 py-2 text-xs font-black text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                                    <i class="fa-solid fa-envelope-open"></i> Bilik Suara Terbuka (Belum Memilih)
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CTA --}}
                    <div class="mt-6 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <a href="{{ route('evoting.show', $pemilihan->slug) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl {{ $isOngoing && !$isVoted && $hasVerifiedKtp ? 'bg-purple-700 hover:bg-purple-800 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700' }} px-4 py-2.5 text-xs font-bold transition">
                            @if($isOngoing && !$isVoted && $hasVerifiedKtp)
                                <i class="fa-solid fa-check-to-slot"></i> Buka Bilik Suara (Coblos)
                            @else
                                <i class="fa-solid fa-chart-pie"></i> Lihat Detail & Quick Count
                            @endif
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
