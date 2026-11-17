@extends('layouts.app')

@section('title', 'Kartu KTP & RFID Warga')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header Banner Konsisten --}}
    <div class="rounded-3xl border border-emerald-100 bg-linear-to-r/srgb from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-100 dark:border-emerald-900/40">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Kewargaan &amp; Identitas Digital</p>
                <h1 class="mt-2 text-2xl font-black md:text-3xl">Kartu KTP &amp; Verifikasi RFID</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50">
                    Pantau status keterdaftaran fisik e-KTP Anda ke mesin pembaca RFID RT untuk akses otomatis ke layanan lingkungan.
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-black text-emerald-800 shadow-xs transition hover:bg-emerald-50">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>
    </div>

    {{-- Main Grid: Digital Card & Instructions --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Left: Digital Card Preview & Realtime Status --}}
        <div class="space-y-6 lg:col-span-5">
            {{-- Visual Smart Card --}}
            <div class="relative overflow-hidden rounded-3xl border border-slate-700/60 bg-linear-to-br/srgb from-slate-950 via-emerald-950 to-slate-900 p-6 text-white shadow-xl shadow-emerald-950/30">
                {{-- Decorative pattern --}}
                <div class="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-emerald-500/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-44 w-44 rounded-full bg-teal-500/10 blur-2xl"></div>

                <div class="relative z-10 flex flex-col justify-between space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-emerald-400">Smart RW Citizen ID</p>
                            <p class="text-xs font-bold text-white/90">KARTU WARGA &amp; E-KTP</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-emerald-300 backdrop-blur-xs">
                            <i class="fa-solid fa-wifi rotate-90 text-sm"></i>
                        </div>
                    </div>

                    {{-- Chip visual --}}
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-12 rounded-lg border border-amber-300/40 bg-linear-to-br from-amber-200 via-amber-400 to-amber-600 shadow-sm flex items-center justify-center">
                            <div class="h-5 w-8 rounded border border-amber-700/30 opacity-70"></div>
                        </div>
                        <span class="text-[11px] font-mono tracking-widest text-white/60">CONTACTLESS NFC / RFID</span>
                    </div>

                    <div>
                        <p class="text-base font-extrabold uppercase tracking-wide text-white">{{ $user->name }}</p>
                        <p class="mt-0.5 text-xs text-slate-300">
                            {{ $user->rt?->name ?? 'RT Belum Ditentukan' }} · {{ $user->rw?->name ?? 'RW' }}
                            @if($user->rumah)
                                · Rumah {{ $user->rumah->label }}
                            @endif
                        </p>
                    </div>

                    <div class="pt-3 border-t border-white/15 flex items-center justify-between text-xs">
                        <div>
                            <p class="text-[10px] uppercase text-white/60">Status KTP RFID</p>
                            @if($card?->ktp_verified_at)
                                <span class="inline-flex items-center gap-1.5 font-bold text-emerald-400">
                                    <i class="fa-solid fa-circle-check"></i> Terdaftar Resmi
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 font-bold text-amber-400">
                                    <i class="fa-solid fa-clock"></i> Belum Di-Tap
                                </span>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] uppercase text-white/60">Sistem</p>
                            <span class="font-mono text-xs text-white/80">RT-KTP-PASS</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Alat Reader RT --}}
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Status Reader RT</h3>
                @if($activeSession)
                    <div class="mt-3 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        <span class="mt-0.5 flex h-3 w-3 shrink-0 rounded-full bg-emerald-500 animate-pulse"></span>
                        <div class="text-xs leading-relaxed">
                            <p class="font-bold text-emerald-900 dark:text-emerald-100">Sesi Alat Reader RT Sedang Aktif</p>
                            <p class="mt-0.5 text-emerald-700 dark:text-emerald-300">
                                Petugas RT sedang membuka sesi tap (berlaku s/d {{ $activeSession->expires_at->timezone('Asia/Jakarta')->format('H:i') }} WIB). Anda bisa langsung mendatangi petugas untuk tap e-KTP fisik Anda sekarang.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <span class="mt-1 flex h-2.5 w-2.5 shrink-0 rounded-full bg-slate-400"></span>
                        <div class="text-xs leading-relaxed">
                            <p class="font-bold text-slate-900 dark:text-slate-100">Sesi Alat Belum Dibuka</p>
                            <p class="mt-0.5 text-slate-500 dark:text-slate-400">
                                Sesi reader diaktifkan oleh pengurus saat Anda hadir di pos/kantor RT untuk melakukan tap verifikasi e-KTP.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Panduan Lengkap & Manfaat --}}
        <div class="space-y-6 lg:col-span-7">
            {{-- Panduan 4 Langkah --}}
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <i class="fa-solid fa-list-ol text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-slate-100">Cara Melakukan Verifikasi KTP via RFID</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Hanya perlu dilakukan 1 kali untuk selamanya.</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">1</span>
                        <div class="text-sm">
                            <p class="font-bold text-slate-900 dark:text-slate-100">Siapkan Fisik e-KTP Anda</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Semua e-KTP resmi Indonesia telah memiliki chip RFID nirkabel di dalamnya.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">2</span>
                        <div class="text-sm">
                            <p class="font-bold text-slate-900 dark:text-slate-100">Kunjungi Pos RT atau Temui Pengurus</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Sampaikan kepada pengurus RT bahwa Anda ingin melakukan aktivasi tap kartu e-KTP.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">3</span>
                        <div class="text-sm">
                            <p class="font-bold text-slate-900 dark:text-slate-100">Pengurus Memilih Akun Anda</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pengurus membuka menu verifikasi RFID dan mengaktifkan mode penempelan kartu selama 2 menit.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">4</span>
                        <div class="text-sm">
                            <p class="font-bold text-slate-900 dark:text-slate-100">Tempelkan e-KTP ke Reader ESP32</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tempelkan e-KTP ke atas sensor reader selama 1 detik sampai terdengar bunyi konfirmasi. KTP Anda langsung aktif di sistem!</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Manfaat Layanan --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <h3 class="mt-3 text-sm font-bold text-slate-900 dark:text-slate-100">Pendaftaran Koperasi Warga</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Syarat wajib pendaftaran anggota Koperasi Simpan Pinjam RT/RW untuk verifikasi identitas peminjam.
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <i class="fa-solid fa-clipboard-user"></i>
                    </div>
                    <h3 class="mt-3 text-sm font-bold text-slate-900 dark:text-slate-100">Absensi Otomatis Kegiatan</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Cukup tap KTP saat rapat lingkungan, gotong royong, atau pos ronda tanpa antre tanda tangan manual.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
