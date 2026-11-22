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
            {{-- 3D Flip Smart Card --}}
            <div x-data="{ isFlipped: false }" class="space-y-3">
                <div
                    class="perspective-1000 group cursor-pointer select-none"
                    tabindex="0"
                    role="button"
                    aria-label="Kartu Digital KTP RFID Warga. Tekan untuk membalik tampilan 3D kartu."
                    @click="isFlipped = !isFlipped"
                    @keydown.enter="isFlipped = !isFlipped"
                    @keydown.space.prevent="isFlipped = !isFlipped"
                >
                    <div
                        class="transform-style-3d relative min-h-[240px] w-full rounded-3xl shadow-2xl transition-transform duration-700 ease-in-out"
                        :class="{ 'rotate-y-180': isFlipped }"
                    >
                        {{-- SISI DEPAN (FRONT) --}}
                        <div class="backface-hidden relative overflow-hidden rounded-3xl border border-emerald-500/30 bg-linear-to-br/srgb from-slate-950 via-emerald-950 to-slate-900 p-6 text-white shadow-xl shadow-emerald-950/40">
                            {{-- Decorative glows & watermark pattern --}}
                            <div class="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-emerald-500/15 blur-2xl"></div>
                            <div class="pointer-events-none absolute -bottom-12 -left-12 h-44 w-44 rounded-full bg-teal-500/15 blur-2xl"></div>
                            <div class="pointer-events-none absolute inset-0 opacity-[0.03] bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>

                            <div class="relative z-10 flex flex-col justify-between space-y-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-emerald-400">Smart RW Citizen ID</p>
                                        <p class="text-xs font-bold text-white/90">KARTU WARGA &amp; E-KTP</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[9px] font-bold text-emerald-300 backdrop-blur-xs transition group-hover:bg-white/20">
                                            <i class="fa-solid fa-arrows-rotate text-[8px]"></i> 3D Flip
                                        </span>
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-emerald-300 backdrop-blur-xs">
                                            <i class="fa-solid fa-wifi rotate-90 text-sm"></i>
                                        </div>
                                    </div>
                                </div>

                                {{-- Chip visual --}}
                                <div class="flex items-center gap-3">
                                    <div class="relative flex h-9 w-12 items-center justify-center rounded-lg border border-amber-300/60 bg-linear-to-br from-amber-200 via-amber-400 to-amber-600 shadow-md">
                                        <div class="h-5 w-8 rounded border border-amber-700/40 opacity-80"></div>
                                        <div class="absolute inset-0 rounded-lg bg-linear-to-t from-black/10 to-transparent pointer-events-none"></div>
                                    </div>
                                    <span class="font-mono text-[11px] tracking-widest text-white/70">CONTACTLESS NFC / RFID</span>
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

                                <div class="flex items-center justify-between border-t border-white/15 pt-3 text-xs">
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

                        {{-- SISI BELAKANG (BACK) --}}
                        <div class="backface-hidden rotate-y-180 absolute inset-0 flex h-full w-full flex-col justify-between overflow-hidden rounded-3xl border border-emerald-500/30 bg-linear-to-br/srgb from-slate-950 via-slate-900 to-emerald-950 p-6 text-white shadow-xl shadow-emerald-950/40">
                            {{-- Magnetic Stripe --}}
                            <div class="-mx-6 -mt-3 flex h-10 items-center border-y border-white/10 bg-slate-950 px-6">
                                <div class="h-1.5 w-full bg-linear-to-r from-slate-800 via-slate-700 to-slate-900 rounded-xs opacity-60"></div>
                            </div>

                            {{-- Card Details on Back --}}
                            <div class="mt-2 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between rounded-xl bg-white/5 p-2.5 backdrop-blur-xs">
                                    <div>
                                        <p class="text-[10px] uppercase font-bold text-emerald-400 tracking-wider">UID Chip Sensor</p>
                                        <p class="font-mono text-xs font-semibold text-slate-200">
                                            @if($card?->uid_hash)
                                                {{ substr($card->uid_hash, 0, 16) }}••••
                                            @else
                                                <span class="text-amber-300">Menunggu Penempelan Kartu</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] uppercase font-bold text-slate-400">Waktu Verifikasi</p>
                                        <p class="text-xs text-slate-200">
                                            {{ $card?->ktp_verified_at ? $card->ktp_verified_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') : 'Belum Terdaftar' }}
                                        </p>
                                    </div>
                                </div>

                                <p class="text-[9.5px] leading-relaxed text-slate-400">
                                    Kartu Identitas Digital Warga berbasis e-KTP fisik. Digunakan sebagai akses nirkabel kegiatan lingkungan, absensi rapat, pos ronda, dan verifikasi Koperasi Warga di {{ $user->rw?->name ?? 'RW' }}.
                                </p>
                            </div>

                            {{-- Footer Barcode Sim & Security Hologram --}}
                            <div class="flex items-end justify-between border-t border-white/10 pt-2.5">
                                <div class="space-y-1">
                                    <div class="flex h-5 items-center gap-0.5 opacity-70">
                                        <span class="h-full w-0.5 bg-white"></span>
                                        <span class="h-full w-1 bg-white"></span>
                                        <span class="h-full w-0.5 bg-white"></span>
                                        <span class="h-full w-1.5 bg-white"></span>
                                        <span class="h-full w-0.5 bg-white"></span>
                                        <span class="h-full w-1 bg-white"></span>
                                        <span class="h-full w-0.5 bg-white"></span>
                                        <span class="h-full w-2 bg-white"></span>
                                        <span class="h-full w-0.5 bg-white"></span>
                                        <span class="h-full w-1 bg-white"></span>
                                        <span class="h-full w-0.5 bg-white"></span>
                                        <span class="h-full w-1.5 bg-white"></span>
                                        <span class="h-full w-0.5 bg-white"></span>
                                        <span class="h-full w-1 bg-white"></span>
                                    </div>
                                    <p class="font-mono text-[9px] text-white/50 tracking-wider">SMART-RW-SECURE-ID</p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[9px] font-bold text-emerald-300 backdrop-blur-xs">
                                    <i class="fa-solid fa-arrows-rotate text-[8px]"></i> Balik ke Muka
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Balik Kartu Interaktif --}}
                <button
                    type="button"
                    @click="isFlipped = !isFlipped"
                    class="btn-tactile flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200/90 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-emerald-300"
                >
                    <i class="fa-solid fa-arrows-rotate text-emerald-600 transition-transform duration-500" :class="{ 'rotate-180': isFlipped }"></i>
                    <span x-text="isFlipped ? 'Putar ke Muka Depan' : 'Putar Kartu 3D (Lihat Sisi Belakang)'"></span>
                </button>
            </div>

            {{-- Panel Aktivasi Tap e-KTP Mandiri Warga (NFC HP & RFID Reader) --}}
            <div
                x-data="wargaRfidTap({
                    isEnrolled: {{ ($isEnrolled ?? false) ? 'true' : 'false' }},
                    remainingSeconds: {{ $remainingSeconds ?? 0 }},
                    isVerified: {{ ($card && $card->ktp_verified_at) ? 'true' : 'false' }},
                    csrfToken: '{{ csrf_token() }}',
                    openUrl: '{{ route('warga.rfid.open') }}',
                    cancelUrl: '{{ route('warga.rfid.cancel') }}',
                    statusUrl: '{{ route('warga.rfid.status') }}',
                    tapNfcUrl: '{{ route('warga.rfid.tap-nfc') }}'
                })"
                x-init="init()"
                class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                {{-- Header Status --}}
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl transition-colors"
                            :class="isVerified ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : (isEnrolling || isNfcScanning ? 'bg-amber-100 text-amber-700 animate-pulse dark:bg-amber-950/60 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300')"
                        >
                            <i class="fa-solid text-base" :class="isVerified ? 'fa-id-card-clip' : (isNfcScanning ? 'fa-mobile-screen-button' : (isEnrolling ? 'fa-tower-broadcast' : 'fa-wifi'))"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                <span x-show="isVerified">e-KTP Fisik Terverifikasi</span>
                                <span x-show="!isVerified && isNfcScanning">Sensor NFC HP Siap</span>
                                <span x-show="!isVerified && !isNfcScanning && isEnrolling">Sesi Reader RT Aktif</span>
                                <span x-show="!isVerified && !isNfcScanning && !isEnrolling">Verifikasi e-KTP Mandiri</span>
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                <span x-show="isVerified">Kartu fisik Anda telah terhubung secara permanen.</span>
                                <span x-show="!isVerified && isNfcScanning">Tempelkan e-KTP ke punggung bodi HP Anda.</span>
                                <span x-show="!isVerified && !isNfcScanning && isEnrolling">Tempelkan e-KTP ke alat reader RT di pos.</span>
                                <span x-show="!isVerified && !isNfcScanning && !isEnrolling">Bisa lewat sensor NFC HP atau alat reader RT.</span>
                            </p>
                        </div>
                    </div>

                    {{-- Timer Badge saat Enrolling RT Reader --}}
                    <template x-if="!isVerified && isEnrolling">
                        <div class="flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1 font-mono text-xs font-black text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                            <i class="fa-regular fa-clock text-[10px]"></i>
                            <span x-text="formattedRemaining"></span>
                        </div>
                    </template>
                </div>

                {{-- Alert Khusus: Kartu Sudah Terdaftar di Akun Warga Lain --}}
                <div x-show="conflictNotice" x-cloak class="mt-4 rounded-2xl border-2 border-rose-400 bg-linear-to-r from-rose-50 via-red-50 to-amber-50 p-4 text-xs shadow-md shadow-rose-100 dark:border-rose-700 dark:from-rose-950/80 dark:via-red-950/70 dark:to-amber-950/60 dark:text-rose-100">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-xs">
                            <i class="fa-solid fa-triangle-exclamation text-base animate-bounce"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h4 class="font-extrabold text-sm text-rose-900 dark:text-rose-200">Kartu Sudah Terdaftar di Akun Warga Lain!</h4>
                                <button type="button" @click="conflictNotice = null" class="text-rose-500 hover:text-rose-700 p-1" title="Tutup Notifikasi">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <p class="mt-1 text-[11.5px] leading-relaxed font-medium text-rose-800 dark:text-rose-200" x-text="conflictNotice"></p>
                            <div class="mt-2.5 flex items-center gap-2 text-[11px] font-bold text-rose-700 dark:text-rose-300">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Satu fisik kartu e-KTP hanya dapat terdaftar untuk satu orang warga.</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alert Error jika ada --}}
                <div x-show="errorMessage" x-cloak class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-200">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                        <span x-text="errorMessage"></span>
                    </div>
                </div>

                {{-- Alert Sukses Instan saat Tap Berhasil --}}
                <div x-show="justVerified" x-cloak class="mt-4 rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-semibold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-100">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-circle-check mt-0.5 text-base text-emerald-600"></i>
                        <div>
                            <p class="font-bold text-sm">Verifikasi Berhasil!</p>
                            <p class="mt-0.5 text-emerald-700 dark:text-emerald-300">e-KTP fisik Anda telah berhasil terdaftar dan aktif secara resmi di RT dan sistem!</p>
                        </div>
                    </div>
                </div>

                {{-- Kondisi 1: KTP Sudah Terverifikasi --}}
                <template x-if="isVerified && !justVerified">
                    <div class="mt-4 rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-4 text-xs text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                <span class="font-bold">Status: Terdaftar Resmi</span>
                            </div>
                            <span class="rounded-full bg-emerald-600/10 px-2.5 py-0.5 font-mono text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                                1x Seumur Hidup
                            </span>
                        </div>
                        <p class="mt-2 text-[11px] leading-relaxed text-emerald-800/80 dark:text-emerald-300/80">
                            Kartu e-KTP Anda dapat digunakan untuk absensi kehadiran rapat RT dan syarat verifikasi program Koperasi.
                        </p>
                    </div>
                </template>

                {{-- Navigasi Tab Pilihan Metode (NFC HP vs Reader RT) jika Belum Terverifikasi --}}
                <div x-show="!isVerified" class="mt-4">
                    <div class="flex rounded-2xl bg-slate-100 p-1 dark:bg-slate-800">
                        <button
                            type="button"
                            @click="activeTab = 'nfc'"
                            class="btn-tactile flex-1 rounded-xl py-2 text-center text-xs font-bold transition-all"
                            :class="activeTab === 'nfc' ? 'bg-white text-emerald-700 shadow-xs dark:bg-slate-900 dark:text-emerald-400' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                        >
                            <i class="fa-solid fa-mobile-screen-button mr-1.5"></i>
                            <span>Sensor NFC HP</span>
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'rfid'"
                            class="btn-tactile flex-1 rounded-xl py-2 text-center text-xs font-bold transition-all"
                            :class="activeTab === 'rfid' ? 'bg-white text-emerald-700 shadow-xs dark:bg-slate-900 dark:text-emerald-400' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                        >
                            <i class="fa-solid fa-tower-broadcast mr-1.5"></i>
                            <span>Reader Alat RT</span>
                        </button>
                    </div>

                    {{-- METODE 1: SENSOR NFC HANDPHONE --}}
                    <div x-show="activeTab === 'nfc'" class="mt-4 space-y-3">
                        <template x-if="hasNfcSupport">
                            <div class="space-y-3">
                                {{-- Saat Scanning NFC Aktif --}}
                                <div x-show="isNfcScanning" class="relative flex flex-col items-center justify-center overflow-hidden rounded-2xl border border-emerald-300 bg-linear-to-b from-emerald-500/10 via-teal-500/10 to-transparent py-8 text-center dark:border-emerald-700 dark:from-emerald-950/40">
                                    <div class="relative flex h-24 w-24 items-center justify-center">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-30"></span>
                                        <span class="absolute inline-flex h-16 w-16 animate-pulse rounded-full bg-teal-400/50 opacity-40"></span>
                                        <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/30">
                                            <i class="fa-solid fa-nfc-symbol text-2xl animate-pulse"></i>
                                        </div>
                                    </div>
                                    <p class="mt-4 text-xs font-black text-slate-900 dark:text-slate-100">Sensor NFC Handphone Aktif!</p>
                                    <p class="mt-1 max-w-xs px-4 text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                        Tempelkan fisik e-KTP Anda ke bodi belakang HP (dekat kamera belakang) sampai HP bergetar.
                                    </p>

                                    <button
                                        type="button"
                                        @click="stopNfcScan()"
                                        class="btn-tactile mt-5 inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white/90 px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        <i class="fa-solid fa-xmark text-slate-400"></i> Hentikan Scan NFC
                                    </button>
                                </div>

                                {{-- Saat Idle Belum Scan --}}
                                <div x-show="!isNfcScanning" class="space-y-3">
                                    <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-4 text-xs text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                                        <div class="flex items-center gap-2 font-bold">
                                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                            <span>Sensor NFC Ponsel Terdeteksi Aktif</span>
                                        </div>
                                        <p class="mt-1.5 text-[11px] leading-relaxed text-emerald-800 dark:text-emerald-300">
                                            Anda bisa tap kartu e-KTP langsung di ponsel ini tanpa perlu alat reader fisik RT atau datang ke pos.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="startNfcScan()"
                                        :disabled="isLoading"
                                        class="btn-tactile flex w-full items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-emerald-600 to-teal-600 px-5 py-3 text-xs font-black text-white shadow-md shadow-emerald-600/20 transition hover:from-emerald-700 hover:to-teal-700 disabled:opacity-50"
                                    >
                                        <span x-show="!isLoading" class="flex items-center gap-2">
                                            <i class="fa-solid fa-mobile-screen-button text-sm"></i>
                                            <span>Mulai Scan e-KTP via NFC HP Sekarang</span>
                                        </span>
                                        <span x-show="isLoading" class="flex items-center gap-2" x-cloak>
                                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                                            <span>Menghubungkan Sensor NFC...</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Jika Browser/Device Tidak Support Web NFC --}}
                        <template x-if="!hasNfcSupport">
                            <div class="space-y-3">
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 dark:border-amber-800/80 dark:bg-amber-950/40 dark:text-amber-200">
                                    <div class="flex items-start gap-2.5">
                                        <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600 shrink-0"></i>
                                        <div>
                                            <p class="font-bold">Web NFC Tidak Terdeteksi di Perangkat Ini</p>
                                            <p class="mt-1 text-[11px] leading-relaxed text-amber-800 dark:text-amber-300">
                                                Fitur pembaca chip NFC lewat browser saat ini hanya didukung di <strong>Google Chrome Android</strong> (Apple membatasi akses NFC di browser iOS Safari).
                                            </p>
                                            <p class="mt-2 text-[11px] text-amber-800 dark:text-amber-300">
                                                Silakan beralih ke tab <strong>Reader Alat RT</strong> untuk tap lewat sensor box di pos RT, atau buka web ini di HP Android ber-NFC.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="activeTab = 'rfid'"
                                    class="btn-tactile flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:border-emerald-300 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    <i class="fa-solid fa-arrow-right text-emerald-600"></i>
                                    <span>Beralih ke Metode Reader Alat RT</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- METODE 2: READER ALAT RT (ESP32) --}}
                    <div x-show="activeTab === 'rfid'" class="mt-4 space-y-3">
                        {{-- Kondisi 2: Sedang Enrolling (Sesi Aktif Menunggu Tap) --}}
                        <template x-if="isEnrolling">
                            <div class="space-y-4">
                                {{-- Radar Wave Visualizer --}}
                                <div class="relative flex flex-col items-center justify-center overflow-hidden rounded-2xl border border-amber-200/80 bg-linear-to-b from-amber-500/5 via-amber-500/10 to-transparent py-7 dark:border-amber-800/60 dark:from-amber-950/30">
                                    <div class="relative flex h-20 w-20 items-center justify-center">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-30"></span>
                                        <span class="absolute inline-flex h-14 w-14 animate-pulse rounded-full bg-amber-400/50 opacity-40"></span>
                                        <div class="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-600 text-white shadow-lg shadow-amber-600/30">
                                            <i class="fa-solid fa-tower-broadcast text-xl animate-bounce"></i>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs font-bold text-slate-800 dark:text-slate-100">Tempelkan e-KTP ke Sensor ESP32 Sekarang</p>
                                    <p class="mt-1 text-center text-[11px] text-slate-500 dark:text-slate-400 max-w-xs px-4">
                                        Dekatkan fisik e-KTP Anda ke atas kotak alat reader RT selama 1 detik hingga terdengar bunyi konfirmasi beep.
                                    </p>
                                </div>

                                {{-- Tombol Batal Sesi --}}
                                <button
                                    type="button"
                                    @click="cancelSession()"
                                    :disabled="isLoading"
                                    class="btn-tactile flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                >
                                    <i class="fa-solid fa-xmark text-slate-400"></i>
                                    <span>Batalkan Sesi Tap Mandiri</span>
                                </button>
                            </div>
                        </template>

                        {{-- Kondisi 3: Belum Terverifikasi & Belum Enrolling (Idle) --}}
                        <template x-if="!isEnrolling">
                            <div class="space-y-3">
                                <div class="rounded-2xl border border-slate-200/70 bg-slate-50/70 p-4 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
                                    <p class="font-bold text-slate-800 dark:text-slate-200">Cara Tap Lewat Alat RT:</p>
                                    <ol class="mt-1.5 list-decimal list-inside space-y-1 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">
                                        <li>Pastikan Anda berada di dekat alat reader RT (di pos/kantor RT atau alat yang terhubung).</li>
                                        <li>Klik tombol hijau di bawah untuk mengaktifkan sesi tap (3 menit).</li>
                                        <li>Tempelkan fisik e-KTP Anda ke alat sensor reader ESP32. Sistem langsung memverifikasi kartu Anda secara instan!</li>
                                    </ol>
                                </div>

                                <button
                                    type="button"
                                    @click="openSession()"
                                    :disabled="isLoading"
                                    class="btn-tactile flex w-full items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-emerald-600 to-green-600 px-5 py-3 text-xs font-black text-white shadow-md shadow-emerald-600/20 transition hover:from-emerald-700 hover:to-green-700 focus:outline-hidden disabled:opacity-50"
                                >
                                    <span x-show="!isLoading" class="flex items-center gap-2">
                                        <i class="fa-solid fa-wifi text-sm"></i>
                                        <span>Buka Sesi Tap Alat Reader RT (3 Menit)</span>
                                    </span>
                                    <span x-show="isLoading" class="flex items-center gap-2" x-cloak>
                                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                                        <span>Membuka Sesi Tap...</span>
                                    </span>
                                </button>
                            </div>
                        </template>
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
                            <p class="font-bold text-slate-900 dark:text-slate-100">Pilih Metode: Sensor NFC HP atau Reader RT</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Gunakan sensor NFC langsung di ponsel pintar Anda (Chrome Android) atau pilih metode reader fisik di pos RT.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">3</span>
                        <div class="text-sm">
                            <p class="font-bold text-slate-900 dark:text-slate-100">Tempelkan e-KTP ke Sensor</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tempelkan fisik e-KTP ke bodi belakang HP Anda atau ke atas sensor alat reader ESP32 di pos RT.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">4</span>
                        <div class="text-sm">
                            <p class="font-bold text-slate-900 dark:text-slate-100">Terdaftar Otomatis dalam 1 Detik</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Sistem langsung memverifikasi kartu Anda seketika. Kartu digital otomatis berstatus Terdaftar Resmi seumur hidup!</p>
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

            {{-- Tanya Jawab Seputar Verifikasi KTP (FAQ) --}}
            <div x-data="{ activeFaq: null }" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <i class="fa-solid fa-circle-question text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Tanya Jawab (FAQ) Verifikasi e-KTP</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Informasi penting seputar keamanan dan penggunaan kartu e-KTP fisik.</p>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800 space-y-1">
                    {{-- FAQ 1 --}}
                    <div class="pt-3">
                        <button type="button" @click="activeFaq = activeFaq === 1 ? null : 1" class="btn-tactile flex w-full items-center justify-between py-2 text-left text-xs font-bold text-slate-800 transition hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400">
                            <span>Apakah data pribadi saya di e-KTP aman?</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200" :class="activeFaq === 1 ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>
                        <div class="grid transition-[grid-template-rows] duration-220 ease-[cubic-bezier(0.23,1,0.32,1)]" :class="activeFaq === 1 ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                            <div class="overflow-hidden">
                                <div class="pb-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed transition-opacity duration-200" :class="activeFaq === 1 ? 'opacity-100' : 'opacity-0'">
                                    Sangat aman. Reader RFID RT hanya membaca kode serial unik nirkabel (UID chip) yang tertanam di kartu KTP Anda sebagai pengenal. Sistem sama sekali tidak mengakses isi chip rahasia seperti sidik jari maupun data biometrik kependudukan.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FAQ 2 --}}
                    <div class="pt-3">
                        <button type="button" @click="activeFaq = activeFaq === 2 ? null : 2" class="btn-tactile flex w-full items-center justify-between py-2 text-left text-xs font-bold text-slate-800 transition hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400">
                            <span>Mengapa verifikasi harus lewat tap kartu di alat RT?</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200" :class="activeFaq === 2 ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>
                        <div class="grid transition-[grid-template-rows] duration-220 ease-[cubic-bezier(0.23,1,0.32,1)]" :class="activeFaq === 2 ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                            <div class="overflow-hidden">
                                <div class="pb-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed transition-opacity duration-200" :class="activeFaq === 2 ? 'opacity-100' : 'opacity-0'">
                                    Proses tap memastikan bahwa pemilik akun benar-benar memiliki fisik e-KTP yang sah dan hadir di lingkungan RT. Setelah terdaftar, e-KTP Anda berfungsi sebagai kunci kartu pintar untuk absensi kegiatan warga tanpa tanda tangan manual dan syarat masuk ke program Koperasi Warga.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FAQ 3 --}}
                    <div class="pt-3">
                        <button type="button" @click="activeFaq = activeFaq === 3 ? null : 3" class="btn-tactile flex w-full items-center justify-between py-2 text-left text-xs font-bold text-slate-800 transition hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400">
                            <span>Bagaimana jika e-KTP saya hilang atau diganti?</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200" :class="activeFaq === 3 ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>
                        <div class="grid transition-[grid-template-rows] duration-220 ease-[cubic-bezier(0.23,1,0.32,1)]" :class="activeFaq === 3 ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                            <div class="overflow-hidden">
                                <div class="pb-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed transition-opacity duration-200" :class="activeFaq === 3 ? 'opacity-100' : 'opacity-0'">
                                    Jika e-KTP Anda hilang, segera lapor ke pengurus RT. Pengurus dapat mencabut kartu lama Anda dari sistem dalam 1 klik melalui menu admin agar tidak disalahgunakan, kemudian mendaftarkan kartu e-KTP baru Anda.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FAQ 4 --}}
                    <div class="pt-3">
                        <button type="button" @click="activeFaq = activeFaq === 4 ? null : 4" class="btn-tactile flex w-full items-center justify-between py-2 text-left text-xs font-bold text-slate-800 transition hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400">
                            <span>Apakah verifikasi tap KTP harus diulang berkala?</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200" :class="activeFaq === 4 ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>
                        <div class="grid transition-[grid-template-rows] duration-220 ease-[cubic-bezier(0.23,1,0.32,1)]" :class="activeFaq === 4 ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                            <div class="overflow-hidden">
                                <div class="pb-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed transition-opacity duration-200" :class="activeFaq === 4 ? 'opacity-100' : 'opacity-0'">
                                    Tidak. Verifikasi tap KTP hanya dilakukan 1 kali saja untuk selamanya. Setelah terverifikasi, status aktif Anda melekat permanen di sistem RT.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function wargaRfidTap(config) {
    return {
        isEnrolled: config.isEnrolled,
        remainingSeconds: config.remainingSeconds,
        isVerified: config.isVerified,
        isLoading: false,
        errorMessage: null,
        conflictNotice: null,
        justVerified: false,
        pollTimer: null,
        countdownTimer: null,
        activeTab: ('NDEFReader' in window) ? 'nfc' : 'rfid',
        hasNfcSupport: ('NDEFReader' in window),
        isNfcScanning: false,
        nfcAbortController: null,

        get isEnrolling() {
            return this.isEnrolled && this.remainingSeconds > 0;
        },

        get formattedRemaining() {
            const m = Math.floor(this.remainingSeconds / 60);
            const s = this.remainingSeconds % 60;
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },

        init() {
            this.hasNfcSupport = ('NDEFReader' in window);
            if (!this.hasNfcSupport && this.activeTab === 'nfc') {
                this.activeTab = 'rfid';
            }
            if (this.isEnrolling) {
                this.activeTab = 'rfid';
                this.startCountdown();
                this.startPolling();
            }
        },

        async startNfcScan() {
            if (!('NDEFReader' in window)) {
                this.errorMessage = 'Perangkat atau browser Anda tidak mendukung Web NFC. Silakan gunakan tab Reader Alat RT.';
                this.activeTab = 'rfid';
                return;
            }

            this.errorMessage = null;
            this.isLoading = true;

            try {
                this.nfcAbortController = new AbortController();
                const ndef = new NDEFReader();
                await ndef.scan({ signal: this.nfcAbortController.signal });

                this.isNfcScanning = true;
                this.isLoading = false;

                ndef.onreading = async (event) => {
                    if (navigator.vibrate) {
                        try { navigator.vibrate([150, 60, 150]); } catch (e) {}
                    }
                    const rawSerial = event.serialNumber || '';
                    const cleanUid = rawSerial.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();

                    if (!cleanUid) {
                        this.errorMessage = 'Chip e-KTP tidak terbaca jelas. Mohon tempelkan kembali ke bodi belakang HP.';
                        return;
                    }

                    await this.submitNfcUid(cleanUid);
                };

                ndef.onreadingerror = () => {
                    this.errorMessage = 'Sinyal e-KTP tidak terbaca. Pastikan kartu menempel rapat di bodi belakang HP.';
                };
            } catch (err) {
                this.isLoading = false;
                this.isNfcScanning = false;
                if (err.name === 'NotAllowedError') {
                    this.errorMessage = 'Izin akses NFC ditolak. Izinkan izin NFC di perizinan browser Anda.';
                } else if (err.name === 'NotSupportedError') {
                    this.errorMessage = 'Fitur NFC tidak aktif atau tidak didukung di perangkat ini.';
                } else if (err.name !== 'AbortError') {
                    this.errorMessage = err.message || 'Gagal mengaktifkan sensor NFC.';
                }
            }
        },

        stopNfcScan() {
            if (this.nfcAbortController) {
                this.nfcAbortController.abort();
                this.nfcAbortController = null;
            }
            this.isNfcScanning = false;
        },

        async submitNfcUid(uid) {
            this.isLoading = true;
            this.errorMessage = null;
            this.conflictNotice = null;

            try {
                const response = await fetch(config.tapNfcUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({ uid })
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.message && (data.message.includes('terdaftar di akun') || data.message.includes('warga lain'))) {
                        this.conflictNotice = data.message;
                        this.errorMessage = null;
                    } else {
                        this.errorMessage = data.message || 'Gagal memverifikasi chip e-KTP.';
                    }
                    throw new Error(data.message || 'Gagal memverifikasi chip e-KTP.');
                }

                this.stopNfcScan();
                this.isVerified = true;
                this.justVerified = true;
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } catch (err) {
                if (!this.conflictNotice) {
                    this.errorMessage = err.message;
                }
            } finally {
                this.isLoading = false;
            }
        },

        async openSession() {
            this.isLoading = true;
            this.errorMessage = null;
            this.conflictNotice = null;

            try {
                const response = await fetch(config.openUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal membuka sesi tap.');
                }

                this.isEnrolled = true;
                this.remainingSeconds = data.remaining_seconds || 180;
                this.startCountdown();
                this.startPolling();
            } catch (err) {
                this.errorMessage = err.message;
            } finally {
                this.isLoading = false;
            }
        },

        async cancelSession() {
            this.isLoading = true;
            this.errorMessage = null;
            this.conflictNotice = null;

            try {
                const response = await fetch(config.cancelUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });

                this.stopTimers();
                this.isEnrolled = false;
                this.remainingSeconds = 0;
            } catch (err) {
                this.errorMessage = err.message;
            } finally {
                this.isLoading = false;
            }
        },

        startCountdown() {
            if (this.countdownTimer) clearInterval(this.countdownTimer);
            this.countdownTimer = setInterval(() => {
                if (this.remainingSeconds > 0) {
                    this.remainingSeconds--;
                } else {
                    this.stopTimers();
                    this.isEnrolled = false;
                }
            }, 1000);
        },

        startPolling() {
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.pollTimer = setInterval(async () => {
                try {
                    const response = await fetch(config.statusUrl, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.notice) {
                            this.conflictNotice = data.notice;
                            this.errorMessage = null;
                            if (navigator.vibrate) {
                                try { navigator.vibrate([200, 100, 200]); } catch (e) {}
                            }
                        }
                        if (data.is_verified) {
                            this.stopTimers();
                            this.isVerified = true;
                            this.isEnrolled = false;
                            this.conflictNotice = null;
                            this.justVerified = true;
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else if (!data.is_enrolling) {
                            this.stopTimers();
                            this.isEnrolled = false;
                            this.remainingSeconds = 0;
                        }
                    }
                } catch (e) {
                    // silent fail on network blip
                }
            }, 1500);
        },

        stopTimers() {
            if (this.countdownTimer) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
            }
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        }
    };
}
</script>
@endsection
