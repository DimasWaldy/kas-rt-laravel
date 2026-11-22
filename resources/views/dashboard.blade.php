@extends('layouts.app')

@section('title', 'Ringkasan Statistik')

@push('styles')
<style>
    .civic-card {
        transition: transform 150ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 150ms cubic-bezier(0.16, 1, 0.3, 1), border-color 150ms ease-out;
    }
    .civic-card:hover {
        transform: translateY(-1px);
    }
    .civic-card:active {
        transform: scale(0.99);
    }
    .btn-tactile {
        transition: transform 120ms cubic-bezier(0.16, 1, 0.3, 1), background-color 120ms ease-out, box-shadow 120ms ease-out;
    }
    .btn-tactile:active {
        transform: scale(0.97);
    }
</style>
@endpush

@section('content')
<div class="space-y-6">

{{-- =============================================
     WARGA STATUS & IDENTITAS CIVIC
     ============================================= --}}
@if(auth()->user()->role_name === 'warga')
@php
    $accountStatus = auth()->user()->status_akun;
    $needsDomicile = auth()->user()->onboarding_domisili && ! auth()->user()->rumah_id;
    $domisiliStatus = $needsDomicile ? (auth()->user()->warga?->status_domisili) : null;

    if ($accountStatus === 'ditolak') {
        $statusTone = 'amber';
        $statusIcon = 'fa-circle-exclamation';
        $statusTitle = 'Pengajuan perlu perbaikan';
        $statusMessage = 'Pengurus menolak pengajuan Anda. Buka halaman status pendaftaran untuk melihat alasan dan mengirim perbaikan.';
        $statusCtaUrl = route('pendaftaran.show');
        $statusCtaLabel = 'Periksa status & perbaiki';
    } elseif ($accountStatus === 'pending_verifikasi') {
        $hasSubmittedProfileDomicile = auth()->user()->warga?->status_domisili === 'pending' && auth()->user()->rt_id;
        $statusTone = 'indigo';
        $statusIcon = 'fa-hourglass-half';
        $statusTitle = 'Pendaftaran Anda sedang diperiksa';
        $statusMessage = $hasSubmittedProfileDomicile
            ? 'Profil, dokumen, dan domisili Anda sudah dikirim. Menunggu verifikasi dari pengurus RT/Admin/Superadmin.'
            : 'Lengkapi profil, KK/KTP, serta domisili Anda melalui halaman profil agar pengurus dapat memverifikasi akun Anda sekaligus.';
        $statusCtaUrl = route('profile.edit');
        $statusCtaLabel = $hasSubmittedProfileDomicile ? 'Lihat Profil Saya' : 'Lengkapi Profil & Domisili';
    } elseif ($needsDomicile) {
        if ($domisiliStatus === 'perlu_perbaikan') {
            $statusTone = 'amber';
            $statusIcon = 'fa-triangle-exclamation';
            $statusTitle = 'Domisili perlu perbaikan';
            $statusMessage = auth()->user()->warga?->catatan_domisili;
            $statusCtaUrl = route('profile.edit');
            $statusCtaLabel = 'Perbaiki domisili';
        } elseif ($domisiliStatus === 'pending') {
            $statusTone = 'emerald';
            $statusIcon = 'fa-hourglass-half';
            $statusTitle = 'Menunggu persetujuan domisili';
            $statusMessage = 'Akun sudah aktif, tetapi rumah belum terhubung. Fitur layanan dan data rumah tetap terkunci sampai pengajuan domisili disetujui.';
            $statusCtaUrl = route('profile.edit');
            $statusCtaLabel = 'Lihat status domisili';
        } else {
            $statusTone = 'emerald';
            $statusIcon = 'fa-house-circle-check';
            $statusTitle = 'Lengkapi domisili';
            $statusMessage = 'Lengkapi rumah tempat Anda tinggal dari halaman profil. Pengurus akan memeriksa sebelum akses data rumah dibuka.';
            $statusCtaUrl = route('profile.edit');
            $statusCtaLabel = 'Lengkapi domisili';
        }
    } else {
        $statusTone = 'slate';
        $statusIcon = 'fa-circle-check';
        $statusTitle = 'Akun aktif & terverifikasi';
        $statusMessage = 'Akses layanan warga terbuka. Simpan nomor kontak dan data KK Anda agar tetap mutakhir.';
        $statusCtaUrl = route('profile.edit');
        $statusCtaLabel = 'Kelola profil';
    }

    $statusCardBorder = match($statusTone) {
        'amber' => 'border-amber-200/80 bg-amber-50/70 dark:border-amber-500/20 dark:bg-amber-950/20',
        'indigo' => 'border-indigo-200/80 bg-indigo-50/70 dark:border-indigo-500/20 dark:bg-indigo-950/20',
        'emerald' => 'border-emerald-200/80 bg-emerald-50/60 dark:border-emerald-500/20 dark:bg-emerald-950/20',
        default => 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900',
    };
    $statusBadge = match($statusTone) {
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
        'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-200',
        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
        default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    };
    $statusBtn = match($statusTone) {
        'amber' => 'bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20',
        'indigo' => 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-600/20',
        'emerald' => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/20',
        default => 'bg-slate-800 hover:bg-slate-900 text-white shadow-slate-900/20 dark:bg-slate-700 dark:hover:bg-slate-600',
    };
@endphp
<div x-data="{ showRfidGuide: false }" class="rounded-3xl border {{ $statusCardBorder }} p-6 sm:p-7 shadow-xs">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $statusBadge }} text-xl shadow-2xs">
                <i class="fa-solid {{ $statusIcon }}"></i>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ $statusTitle }}</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $statusBadge }}">
                        {{ auth()->user()->rt ? 'RT '.str_pad(auth()->user()->rt->nomor ?? '0', 2, '0', STR_PAD_LEFT) : 'Warga RW' }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-300 leading-relaxed max-w-2xl">{{ $statusMessage }}</p>
                
                <div class="mt-4 flex flex-wrap items-center gap-2.5">
                    @if($statusCtaUrl)
                        <a href="{{ $statusCtaUrl }}" class="btn-tactile inline-flex items-center gap-2 rounded-xl {{ $statusBtn }} px-4 py-2 text-xs font-bold shadow-xs">
                            <i class="fa-solid {{ $statusTone === 'amber' ? 'fa-pen-to-square' : 'fa-house' }}"></i>
                            <span>{{ $statusCtaLabel }}</span>
                        </a>
                    @endif
                    <a href="{{ route('warga.rfid.show') }}" class="btn-tactile inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i class="fa-solid fa-id-card text-emerald-600 dark:text-emerald-400"></i>
                        <span>Verifikasi KTP (RFID)</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Widget Status Tagihan Warga --}}
        <div class="shrink-0 rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-2xs dark:border-slate-700/60 dark:bg-slate-800/80 min-w-[200px]">
            <span class="text-[11px] font-medium text-slate-400 dark:text-slate-400 block">Status Iuran Anda</span>
            <div class="mt-1 flex items-center gap-2">
                <span class="inline-block h-2 w-2 rounded-full {{ ($userStatus ?? '') === 'lunas' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                <span class="text-sm font-bold capitalize text-slate-800 dark:text-slate-100">{{ str_replace('_', ' ', $userStatus ?? 'Belum Ada Tagihan') }}</span>
            </div>
            <a href="{{ route('tagihan.index') }}" class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">
                <span>Rincian Tagihan</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>

    {{-- Kartu Status RFID Banner --}}
    @if($accountStatus === 'aktif' && ! $needsDomicile)
        @php
            $ktpRfidCard = \App\Models\RfidCard::where('user_id', auth()->id())->with('verifiedRt:id,name')->first();
        @endphp
        <div class="mt-5 pt-4 border-t border-slate-200/60 dark:border-slate-800">
            @if($ktpRfidCard?->ktp_verified_at)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/70 px-4 py-3 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-emerald-900 dark:text-emerald-100">KTP Terdaftar di {{ $ktpRfidCard->verifiedRt?->name ?? 'RT' }} &amp; Sistem (RFID Aktif)</p>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-300">Siap digunakan untuk Absensi Kegiatan dan pendaftaran Koperasi Warga.</p>
                        </div>
                    </div>
                    <a href="{{ route('warga.rfid.show') }}" class="btn-tactile inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-800">
                        <i class="fa-solid fa-qrcode"></i>
                        <span>Kartu Digital</span>
                    </a>
                </div>
            @else
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/70 px-4 py-3 dark:border-amber-900/60 dark:bg-amber-950/30">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs mt-0.5">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-amber-900 dark:text-amber-100"><i class="fa-solid fa-circle-exclamation mr-1"></i> KTP belum diverifikasi via RFID</p>
                            <p class="text-[11px] text-amber-800 dark:text-amber-300">Layanan dasar tetap aktif. Cukup 1x tap e-KTP fisik di alat pos RT untuk mengaktifkan akses Koperasi &amp; Absensi Kegiatan.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" @click="showRfidGuide = true" class="btn-tactile inline-flex items-center gap-1.5 rounded-xl border border-amber-300/80 bg-white px-3.5 py-2 text-xs font-bold text-amber-900 shadow-2xs hover:bg-amber-50 dark:border-amber-700 dark:bg-slate-800 dark:text-amber-200">
                            <i class="fa-solid fa-circle-question text-amber-600"></i>
                            <span>Panduan Tap</span>
                        </button>
                        <a href="{{ route('warga.rfid.show') }}" class="btn-tactile inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-amber-700">
                            <i class="fa-solid fa-wifi"></i>
                            <span>Lihat Cara Tap</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Modal Panduan Tap KTP (Alpine.js) --}}
    <div x-show="showRfidGuide" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-transition.opacity>
        <div @click.away="showRfidGuide = false" class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900 dark:text-white" x-transition.scale.95>
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <i class="fa-solid fa-id-card text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Panduan Verifikasi e-KTP</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">1x tap untuk verifikasi identitas permanen</p>
                    </div>
                </div>
                <button type="button" @click="showRfidGuide = false" class="btn-tactile rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200 active:scale-92 transition" aria-label="Tutup panduan">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="mt-4 space-y-3 text-xs text-slate-600 dark:text-slate-300">
                <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-slate-800/60">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-[11px] font-bold text-white">1</span>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-slate-100 text-xs">Siapkan e-KTP Asli Anda</p>
                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Semua e-KTP nasional sudah memiliki chip contactless RFID resmi Kemendagri di dalamnya.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-slate-800/60">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-[11px] font-bold text-white">2</span>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-slate-100 text-xs">Temui Pengurus / Datangi Pos RT</p>
                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Bawa fisik e-KTP saat sesi alat reader RT dibuka (cek status di halaman Kartu Digital).</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-slate-800/60">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-[11px] font-bold text-white">3</span>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-slate-100 text-xs">Pengurus Mengaktifkan Mode Tap</p>
                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Pengurus memilih nama Anda di sistem dan mengaktifkan mode penempelan kartu 2 menit.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-slate-800/60">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-[11px] font-bold text-white">4</span>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-slate-100 text-xs">Tempelkan e-KTP ke Sensor</p>
                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Tempelkan e-KTP pada alat reader selama 1 detik sampai berbunyi beep. Akun Anda langsung terverifikasi penuh!</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-3 text-[11px] text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                <i class="fa-solid fa-shield-halved mr-1 text-emerald-600"></i>
                <strong>Privasi Terjamin:</strong> Sistem hanya membaca kode serial nirkabel unik (UID) kartu untuk verifikasi, tanpa mengakses data sidik jari maupun data biometrik rahasia lainnya.
            </div>

            <div class="mt-5 flex items-center justify-end gap-2.5">
                <button type="button" @click="showRfidGuide = false" class="btn-tactile rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                    Tutup
                </button>
                <a href="{{ route('warga.rfid.show') }}" class="btn-tactile inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800 shadow-xs">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>Buka Halaman Kartu Warga</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endif

{{-- =============================================
     ADMIN QUICK CALLOUT
     ============================================= --}}
@if(auth()->user()->isGlobalOperator() && auth()->user()->isAktif() && auth()->user()->hasPermission('admin-only'))
<div class="flex flex-col gap-4 rounded-3xl border border-emerald-200/80 bg-emerald-50/50 p-5 dark:border-emerald-800/60 dark:bg-emerald-950/20 sm:flex-row sm:items-center sm:justify-between shadow-2xs">
    <div class="flex items-center gap-3.5">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-xs">
            <i class="fa-solid fa-users-gear text-lg"></i>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Manajemen Akun &amp; Wilayah</h2>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200">Operator</span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">Kelola penempatan pengurus RT/RW dan verifikasi dokumen kependudukan warga.</p>
        </div>
    </div>
    <a href="{{ route('admin.accounts.create') }}" class="btn-tactile inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-800 shadow-xs">
        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
        <span>Kelola Akun Pengurus</span>
    </a>
</div>
@endif

{{-- =============================================
     SECTION 1: KEUANGAN KESELURUHAN (CIVIC LEDGER)
     ============================================= --}}
@php
    $u = auth()->user();
    $canViewFinance = $u->canViewFinance();
    $isLockedAccount = ! $u->isAktif() || ($u->role_name === 'warga' && $u->onboarding_domisili && ! $u->rumah_id);
    $saldo = ($kasMasuk ?? 0) - ($kasKeluar ?? 0);
@endphp

@if($canViewFinance && ! $isLockedAccount)
<div>
    <div class="mb-3 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                {{ $u->isRwOfficial() ? 'Rekap Keuangan RW' : ($u->role_name === 'warga' ? 'Transparansi Keuangan RT' : 'Laporan Keuangan RT') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Pembukuan kas warga dan pergerakan dana kas lingkungan</p>
        </div>
        @if(Route::has('laporan-kas.index'))
            <a href="{{ route('laporan-kas.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 inline-flex items-center gap-1">
                <span>Buka Buku Kas</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        @endif
    </div>

    {{-- Main Financial Card: Dignified Civic Balance --}}
    <div class="mb-4 overflow-hidden rounded-3xl bg-linear-to-br from-emerald-900 via-emerald-800 to-teal-900 p-6 sm:p-8 text-white shadow-xl shadow-emerald-950/10 ring-1 ring-white/10 relative">
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex items-center gap-2 text-emerald-200">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10">
                        <i class="fa-solid fa-wallet text-xs"></i>
                    </span>
                    <span class="text-xs font-semibold tracking-wide">Saldo Kas {{ $u->isRwOfficial() ? 'RW' : 'RT' }} Tersedia</span>
                </div>
                <div class="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight tabular-nums">
                    Rp {{ number_format($saldo, 0, ',', '.') }}
                </div>
                <p class="mt-1.5 text-xs text-emerald-200/80">Saldo riil dihitung dari total kas masuk dikurangi total kas keluar.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-md min-w-[130px] border border-white/10">
                    <span class="text-[11px] font-medium text-emerald-200 block">Total Masuk</span>
                    <span class="mt-0.5 text-base font-bold text-white tabular-nums block">Rp {{ number_format($kasMasuk ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-md min-w-[130px] border border-white/10">
                    <span class="text-[11px] font-medium text-emerald-200 block">Total Keluar</span>
                    <span class="mt-0.5 text-base font-bold text-white tabular-nums block">Rp {{ number_format($kasKeluar ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Financial Statistics Indicators --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Masuk</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 text-xs">
                    <i class="fa-solid fa-arrow-down"></i>
                </span>
            </div>
            <div class="mt-2 text-base font-bold text-slate-900 dark:text-slate-100 tabular-nums truncate">
                Rp {{ number_format($kasMasuk ?? 0, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Iuran &amp; donasi warga</p>
        </div>

        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Keluar</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400 text-xs">
                    <i class="fa-solid fa-arrow-up"></i>
                </span>
            </div>
            <div class="mt-2 text-base font-bold text-slate-900 dark:text-slate-100 tabular-nums truncate">
                Rp {{ number_format($kasKeluar ?? 0, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Operasional &amp; fasilitas</p>
        </div>

        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Iuran Lunas</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 text-xs">
                    <i class="fa-solid fa-check"></i>
                </span>
            </div>
            <div class="mt-2 text-base font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                {{ $totalPaidTagihan ?? 0 }} <span class="text-xs font-normal text-slate-400">tagihan</span>
            </div>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">Terkonfirmasi lunas</p>
        </div>

        <div class="civic-card rounded-2xl border {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'border-amber-200/90 bg-amber-50/30 dark:border-amber-900/40' : 'border-slate-200/80 bg-white' }} p-4 shadow-2xs dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-amber-800 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400' }}">Belum Bayar</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-slate-100 text-slate-500' }} text-xs">
                    <i class="fa-solid fa-clock"></i>
                </span>
            </div>
            <div class="mt-2 text-base font-bold {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-amber-900 dark:text-amber-200' : 'text-slate-900 dark:text-slate-100' }} tabular-nums">
                {{ $totalUnpaidTagihan ?? 0 }} <span class="text-xs font-normal text-slate-400">tagihan</span>
            </div>
            <p class="text-[11px] {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-400' }} mt-0.5">Menunggu pembayaran</p>
        </div>
    </div>
</div>
@else
@if(! $isLockedAccount)
<div>
    <div class="mb-3">
        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Ringkasan Tagihan Iuran</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">Status kewajiban iuran rutin warga untuk lingkungan</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Bulan Ini</span>
                    <h3 class="mt-1 text-lg font-bold capitalize text-slate-900 dark:text-slate-100">{{ str_replace('_', ' ', $userStatus ?? 'Belum Ada Tagihan') }}</h3>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <i class="fa-solid fa-receipt text-base"></i>
                </div>
            </div>
            <a href="{{ route('tagihan.index') }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400">
                <span>Buka Detail Tagihan</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Tertunggak</span>
                    <h3 class="mt-1 text-lg font-bold {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }} tabular-nums">
                        {{ $totalUnpaidTagihan ?? 0 }} <span class="text-xs font-normal text-slate-400">tagihan</span>
                    </h3>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' : 'bg-slate-50 text-slate-400' }}">
                    <i class="fa-solid fa-clock text-base"></i>
                </div>
            </div>
            <a href="{{ route('tagihan.index') }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400">
                <span>Periksa Daftar Iuran</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
</div>
@endif
@endif

{{-- ==========================================
     MENU CEPAT: RESPONSIVE CIVIC ACTIONS
     ========================================== --}}
@if(! $isLockedAccount)
@php
    $quickActions = [
        ['label' => 'Bayar Iuran', 'route' => 'tagihan.index', 'icon' => 'fa-wallet', 'visible' => ! $u->isRwOfficial()],
        ['label' => 'Laporan Kas', 'route' => 'laporan-kas.index', 'icon' => 'fa-chart-pie', 'visible' => $u->canViewFinance()],
        ['label' => 'Verifikasi Warga', 'route' => 'verifikasi-warga.index', 'icon' => 'fa-user-check', 'visible' => $u->canManageWarga()],
        ['label' => \App\Support\RfidAccess::canManageWargaKtp($u) ? 'Tap KTP (RFID)' : 'Verifikasi KTP (RFID)', 'route' => \App\Support\RfidAccess::canManageWargaKtp($u) ? 'admin.warga.rfid.show' : 'warga.rfid.show', 'icon' => 'fa-id-card', 'visible' => true],
        ['label' => 'Kas Masuk', 'route' => 'kas-masuk.index', 'icon' => 'fa-money-bill-trend-up', 'visible' => ! $u->isRwOfficial() && $u->canViewFinance()],
        ['label' => 'Kas Keluar', 'route' => 'kas-keluar.index', 'icon' => 'fa-money-bill-transfer', 'visible' => ! $u->isRwOfficial() && $u->canViewFinance()],
        ['label' => 'Iuran Bulanan', 'route' => 'iuran-bulanan.index', 'icon' => 'fa-list-check', 'visible' => $u->canManageFinance() || in_array($u->role_name, ['ketua_rt', 'rt', 'ketua-rt'], true)],
        ['label' => 'Iuran Khusus', 'route' => 'iuran-khusus.index', 'icon' => 'fa-hand-holding-heart', 'visible' => $u->canManageFinance()],
        ['label' => 'Aset RT', 'route' => 'aset.index', 'icon' => 'fa-boxes-stacked', 'visible' => $u->hasPermission('view-aset')],
        ['label' => 'Peminjaman Aset', 'route' => 'peminjaman-aset.index', 'icon' => 'fa-hand-holding', 'visible' => $u->hasPermission('pinjam-aset')],
        ['label' => 'Verifikasi Tagihan', 'route' => 'tagihan.admin', 'icon' => 'fa-clipboard-list', 'visible' => $u->canManageFinance()],
        ['label' => 'Koperasi', 'route' => 'koperasi.index', 'icon' => 'fa-wallet', 'visible' => $u->hasPermission('view-koperasi')],
        ['label' => 'Data Warga', 'route' => 'admin.warga.index', 'icon' => 'fa-users', 'visible' => $u->canManageWarga()],
        ['label' => 'Pengaduan', 'route' => 'pengaduan.index', 'icon' => 'fa-bullhorn', 'visible' => true],
        ['label' => 'Fasilitas', 'route' => 'fasilitas.index', 'icon' => 'fa-building-shield', 'visible' => $u->hasPermission('view-fasilitas')],
        ['label' => 'UMKM', 'route' => 'umkm.index', 'icon' => 'fa-store', 'visible' => $u->hasPermission('view-umkm')],
        ['label' => 'Keamanan', 'route' => 'keamanan.index', 'icon' => 'fa-shield-halved', 'visible' => $u->hasPermission('manage-fasilitas')],
        ['label' => 'Surat', 'route' => 'surat.index', 'icon' => 'fa-envelope-open-text', 'visible' => $u->canViewSurat()],
        ['label' => 'Kegiatan', 'route' => 'kegiatan.index', 'icon' => 'fa-calendar-days', 'visible' => $u->hasPermission('view-kegiatan')],
        ['label' => 'Posyandu', 'route' => 'posyandu.index', 'icon' => 'fa-baby', 'visible' => $u->hasPermission('view-posyandu')],
        ['label' => 'Bank Sampah', 'route' => 'bank-sampah.index', 'icon' => 'fa-recycle', 'visible' => $u->hasPermission('view-bank-sampah')],
        ['label' => 'E-Voting', 'route' => 'evoting.index', 'icon' => 'fa-check-to-slot', 'visible' => $u->hasPermission('view-voting')],
        ['label' => 'Direktori RW', 'route' => 'direktori-rw.index', 'icon' => 'fa-address-book', 'visible' => $u->hasPermission('view-direktori-rw')],
    ];
    $quickActions = array_values(array_filter($quickActions, function ($qa) {
        return $qa['visible'] && Route::has($qa['route']);
    }));
@endphp
@if(count($quickActions) > 0)
<div class="block lg:hidden">
    <div class="mb-3 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Menu Cepat</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Jalan pintas ke modul utama yang sering digunakan</p>
        </div>
    </div>

    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
        @foreach($quickActions as $qa)
            @php
                $colorAccent = match($qa['icon']) {
                    'fa-wallet', 'fa-money-bill-trend-up' => 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300',
                    'fa-chart-pie', 'fa-boxes-stacked' => 'text-teal-700 bg-teal-50 dark:bg-teal-950/40 dark:text-teal-300',
                    'fa-user-check', 'fa-users', 'fa-address-book' => 'text-indigo-700 bg-indigo-50 dark:bg-indigo-950/40 dark:text-indigo-300',
                    'fa-id-card' => 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300',
                    'fa-money-bill-transfer' => 'text-rose-700 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-300',
                    'fa-list-check', 'fa-clipboard-list' => 'text-blue-700 bg-blue-50 dark:bg-blue-950/40 dark:text-blue-300',
                    'fa-hand-holding-heart', 'fa-hand-holding' => 'text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300',
                    'fa-bullhorn' => 'text-cyan-700 bg-cyan-50 dark:bg-cyan-950/40 dark:text-cyan-300',
                    'fa-envelope-open-text' => 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300',
                    'fa-calendar-days' => 'text-violet-700 bg-violet-50 dark:bg-violet-950/40 dark:text-violet-300',
                    'fa-baby' => 'text-pink-700 bg-pink-50 dark:bg-pink-950/40 dark:text-pink-300',
                    'fa-recycle' => 'text-teal-700 bg-teal-50 dark:bg-teal-950/40 dark:text-teal-300',
                    'fa-check-to-slot' => 'text-purple-700 bg-purple-50 dark:bg-purple-950/40 dark:text-purple-300',
                    default => 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300',
                };
            @endphp
            <a href="{{ route($qa['route']) }}" class="civic-card flex flex-col items-center justify-center p-3 rounded-2xl border border-slate-200/80 bg-white text-center shadow-2xs hover:border-emerald-300/80 dark:border-slate-800 dark:bg-slate-900 group">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $colorAccent }} transition-transform duration-150 group-hover:scale-105">
                    <i class="fa-solid {{ $qa['icon'] }} text-base"></i>
                </div>
                <span class="mt-2 text-center text-[11px] font-semibold text-slate-700 dark:text-slate-300 line-clamp-2 leading-tight">
                    {{ $qa['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</div>
@endif
@endif

{{-- =============================================
     SECTION 2: DATA WARGA & KEPENDUDUKAN
     ============================================= --}}
<div>
    <div class="mb-3">
        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Kependudukan Lingkungan</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">Rekapitulasi warga dan hunian rumah resmi terdaftar</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ auth()->user()->isRwOfficial() ? 'Total Warga RW' : 'Warga RT' }}</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400 text-xs">
                    <i class="fa-solid fa-users"></i>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                {{ $totalWarga ?? 0 }}
            </div>
            <p class="text-xs text-slate-400 mt-0.5">jiwa tercatat</p>
        </div>

        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Rumah Terdaftar</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400 text-xs">
                    <i class="fa-solid fa-building-user"></i>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                {{ $totalKK ?? 0 }}
            </div>
            <p class="text-xs text-slate-400 mt-0.5">unit hunian fisik</p>
        </div>

        <div class="civic-card rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Rumah Berpenghuni</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 text-xs">
                    <i class="fa-solid fa-house-chimney-user"></i>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                {{ $kepalaKeluargaAktif ?? 0 }}
            </div>
            <p class="text-xs text-slate-400 mt-0.5">dari {{ $totalKK ?? 0 }} rumah terdata</p>
        </div>

        <div class="civic-card rounded-2xl border {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'border-rose-200/90 bg-rose-50/20 dark:border-rose-900/40' : 'border-slate-200/80 bg-white' }} p-4 sm:p-5 shadow-2xs dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' }}">Tagihan Tertunggak</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' : 'bg-slate-100 text-slate-500' }} text-xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }} tabular-nums">
                {{ $totalUnpaidTagihan ?? 0 }}
            </div>
            <p class="text-xs {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-rose-600/80 dark:text-rose-400/80' : 'text-slate-400' }} mt-0.5">perlu penagihan</p>
        </div>
    </div>
</div>

{{-- =============================================
     SECTION 3: DETAIL BULAN INI + AKTIVITAS
     ============================================= --}}
@php
    $humanizeEvent = function($event) {
        $labels = [
            'mass_tagihan_generated' => 'Penerbitan Tagihan Massal',
            'tagihan_generated' => 'Penerbitan Tagihan Iuran',
            'tagihan_paid' => 'Tagihan Terbayar Lunas',
            'tagihan_cancelled' => 'Pembatalan Tagihan',
            'pembayaran_diverifikasi' => 'Verifikasi Pembayaran Kas',
            'pembayaran_ditolak' => 'Penolakan Pembayaran Warga',
            'warga_diverifikasi' => 'Verifikasi Berkas Warga',
            'warga_ditolak' => 'Penolakan Berkas Warga',
            'perbaikan_diminta' => 'Permintaan Perbaikan Berkas',
            'warga_registered' => 'Pendaftaran Warga Baru',
            'rfid_assigned' => 'Aktivasi Kartu RFID',
            'rfid_revoked' => 'Pencabutan Kartu RFID',
            'kas_masuk_created' => 'Pencatatan Kas Masuk',
            'kas_keluar_created' => 'Pencatatan Kas Keluar',
            'user_created' => 'Pembuatan Akun Baru',
            'user_updated' => 'Pembaruan Data Akun',
            'user_deleted' => 'Penghapusan Akun',
            'rumah_created' => 'Pendaftaran Rumah Baru',
            'created' => 'Penambahan Data Baru',
            'updated' => 'Pembaruan Data',
            'deleted' => 'Penghapusan Data',
        ];
        return $labels[$event] ?? ucwords(str_replace(['_', '-'], ' ', $event));
    };

    $getEventIcon = function($event) {
        if (str_contains($event, 'tagihan') || str_contains($event, 'mass') || str_contains($event, 'iuran')) {
            return ['icon' => 'fa-file-invoice-dollar', 'color' => 'text-teal-600 bg-teal-50 dark:bg-teal-950/50 dark:text-teal-400 border-teal-200/60 dark:border-teal-800/50'];
        }
        if (str_contains($event, 'verifikasi') || str_contains($event, 'paid') || str_contains($event, 'lunas')) {
            return ['icon' => 'fa-circle-check', 'color' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/50'];
        }
        if (str_contains($event, 'warga') || str_contains($event, 'user') || str_contains($event, 'rfid')) {
            return ['icon' => 'fa-user-check', 'color' => 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950/50 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/50'];
        }
        if (str_contains($event, 'kas') || str_contains($event, 'uang')) {
            return ['icon' => 'fa-wallet', 'color' => 'text-sky-600 bg-sky-50 dark:bg-sky-950/50 dark:text-sky-400 border-sky-200/60 dark:border-sky-800/50'];
        }
        if (str_contains($event, 'batal') || str_contains($event, 'tolak') || str_contains($event, 'deleted')) {
            return ['icon' => 'fa-circle-xmark', 'color' => 'text-rose-600 bg-rose-50 dark:bg-rose-950/50 dark:text-rose-400 border-rose-200/60 dark:border-rose-800/50'];
        }
        return ['icon' => 'fa-clock-rotate-left', 'color' => 'text-slate-600 bg-slate-100 dark:bg-slate-800 dark:text-slate-400 border-slate-200/60 dark:border-slate-700/50'];
    };
@endphp

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- Kolom kiri: ringkasan bulan ini + top rumah --}}
    <div class="flex flex-col gap-6">

        {{-- Ringkasan bulan ini + alert pending --}}
        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/50 text-base shadow-2xs">
                            <i class="fa-solid fa-chart-pie"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Kinerja Kas Bulan Ini</h3>
                            <p class="text-xs text-slate-400">Target dan realisasi pembayaran warga</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 px-3 py-1 rounded-full border border-slate-200/60 dark:border-slate-700/60">
                        {{ now()->translatedFormat('F Y') }}
                    </span>
                </div>

                {{-- Hero Metric: Realisasi Kas --}}
                <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-800 mb-5">
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Realisasi Penerimaan Kas</span>
                        @php
                            $totalTagihan = ($totalPaidTagihan ?? 0) + ($totalUnpaidTagihan ?? 0);
                            $pctLunas = $totalTagihan > 0 ? min(100, round((($totalPaidTagihan ?? 0) / $totalTagihan) * 100)) : 0;
                            $pctUnpaid = 100 - $pctLunas;
                        @endphp
                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-0.5 rounded-full border border-emerald-200/60 dark:border-emerald-800/40">
                            {{ $pctLunas }}% Terbayar
                        </span>
                    </div>
                    <div class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tabular-nums tracking-tight">
                        Rp {{ number_format($monthlyRevenue ?? 0, 0, ',', '.') }}
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        Dari total {{ $totalTagihan }} tagihan aktif periode ini
                    </p>
                </div>

                {{-- Stacked Progress Bar of Collections --}}
                <div class="space-y-3 mb-5">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-medium text-slate-600 dark:text-slate-300">Penyelesaian Tagihan</span>
                        <span class="text-slate-500 dark:text-slate-400 tabular-nums font-semibold">{{ $totalPaidTagihan ?? 0 }} / {{ $totalTagihan }} Terbayar</span>
                    </div>

                    {{-- Unified segmented bar --}}
                    <div class="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden flex p-0.5 gap-0.5">
                        @if($pctLunas > 0)
                            <div class="h-full bg-emerald-500 rounded-l-full transition-all duration-700" style="width: {{ $pctLunas }}%" title="Lunas: {{ $totalPaidTagihan }} tagihan ({{ $pctLunas }}%)"></div>
                        @endif
                        @if($pctUnpaid > 0)
                            <div class="h-full bg-amber-400 {{ $pctLunas == 0 ? 'rounded-l-full' : '' }} rounded-r-full transition-all duration-700" style="width: {{ $pctUnpaid }}%" title="Belum Lunas: {{ $totalUnpaidTagihan }} tagihan ({{ $pctUnpaid }}%)"></div>
                        @endif
                        @if($totalTagihan == 0)
                            <div class="h-full bg-slate-200 dark:bg-slate-700 rounded-full w-full"></div>
                        @endif
                    </div>

                    {{-- Legend & Values breakdown --}}
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Tagihan Lunas</p>
                                <p class="text-xs font-bold text-slate-900 dark:text-slate-100 tabular-nums truncate">
                                    {{ $totalPaidTagihan ?? 0 }} tagihan <span class="text-[10px] text-slate-400 font-normal">({{ $pctLunas }}%)</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 shrink-0"></span>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Tagihan Belum Lunas</p>
                                <p class="text-xs font-bold {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-slate-100' }} tabular-nums truncate">
                                    {{ $totalUnpaidTagihan ?? 0 }} tagihan <span class="text-[10px] text-slate-400 font-normal">({{ $pctUnpaid }}%)</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alert bukti transfer pending --}}
                @if(auth()->user()->canManageFinance() && ($pendingTransferCount ?? 0) > 0)
                    <div class="flex items-center justify-between gap-3 bg-amber-50/90 border border-amber-200/80 rounded-2xl p-3.5 dark:bg-amber-950/30 dark:border-amber-900/50 mb-4 transition-all">
                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300 text-xs mt-0.5">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold text-amber-900 dark:text-amber-200">{{ $pendingTransferCount }} bukti transfer menunggu verifikasi</p>
                                <p class="text-[11px] text-amber-700 dark:text-amber-300 mt-0.5">Periksa bukti transfer warga agar kas tercatat tepat waktu.</p>
                            </div>
                        </div>
                        <a href="{{ route('tagihan.index') }}" class="btn-tactile shrink-0 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition shadow-2xs">
                            Verifikasi
                        </a>
                    </div>
                @endif
            </div>

            {{-- Shortcut Iuran Bulanan untuk Ketua RT & Bendahara --}}
            @if(in_array(auth()->user()->role_name, ['ketua_rt', 'rt', 'ketua-rt']) || auth()->user()->canManageFinance())
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('iuran-bulanan.index') }}"
                       class="btn-tactile group flex items-center justify-between gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/60 dark:bg-emerald-950/20 dark:border-emerald-800/50 p-3.5 transition hover:bg-emerald-100/70">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs group-hover:scale-105 transition-transform">
                                <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold text-emerald-900 dark:text-emerald-100">Kelola Tagihan Bulanan</p>
                                <p class="text-[11px] text-emerald-700 dark:text-emerald-300">Generate otomatis &amp; pantau pembayaran tiap KK</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-arrow-right text-xs text-emerald-700 dark:text-emerald-400 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            @endif
        </div>

        {{-- Top 5 Rumah --}}
        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/50 text-base shadow-2xs">
                        <i class="fa-solid fa-award"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Top 5 Rumah — Kontribusi Iuran Terbanyak</h3>
                        <p class="text-xs text-slate-400">Apresiasi warga paling tertib membayar iuran lingkungan</p>
                    </div>
                </div>
                <span class="text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 px-3 py-1 rounded-full border border-slate-200/60 dark:border-slate-700/60">
                    Leaderboard
                </span>
            </div>

            <div class="space-y-2.5">
                @forelse($topKKIuran as $topKK)
                    <div class="flex items-center justify-between gap-3 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-colors">
                        <div class="flex items-center gap-3.5 min-w-0">
                            @php
                                $rankStyle = match($loop->iteration) {
                                    1 => 'bg-amber-400 text-amber-950 ring-2 ring-amber-300/60 shadow-xs font-extrabold',
                                    2 => 'bg-slate-200 text-slate-800 ring-2 ring-slate-300/60 font-bold',
                                    3 => 'bg-amber-700 text-amber-100 ring-2 ring-amber-600/40 font-bold',
                                    default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 font-semibold',
                                };
                            @endphp
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0 {{ $rankStyle }}">
                                {{ $loop->iteration }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 truncate">
                                    {{ $topKK['kepala_keluarga'] }}
                                </p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-slate-200/70 dark:bg-slate-800 text-[10px] font-semibold text-slate-700 dark:text-slate-300">
                                        No. {{ $topKK['no_kk'] }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 hidden sm:inline">Warga Tertib</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs sm:text-sm font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                Rp {{ number_format($topKK['total_iuran'], 0, ',', '.') }}
                            </span>
                            <p class="text-[10px] text-slate-400">Total iuran</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="fa-solid fa-receipt text-3xl text-slate-300 mb-2 block"></i>
                        <p class="text-xs text-slate-400">Belum ada data iuran yang tercatat.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Kolom kanan: grafik + log aktivitas / panduan warga --}}
    <div class="flex flex-col gap-6">

        {{-- Chart tren pembayaran --}}
        <div class="civic-card rounded-3xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-400 border border-sky-200/50 dark:border-sky-800/50 text-base shadow-2xs">
                        <i class="fa-solid fa-chart-column"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Tren Kas Masuk &amp; Keluar</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Pergerakan arus kas harian di bulan berjalan</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-800/40 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Masuk: Rp {{ number_format(array_sum($dataMasuk ?? []), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-50 dark:bg-rose-950/40 border border-rose-200/60 dark:border-rose-800/40 text-[11px] font-semibold text-rose-700 dark:text-rose-300">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Keluar: Rp {{ number_format(array_sum($dataKeluar ?? []), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            <div class="h-64 relative">
                <canvas id="kasChart" class="w-full h-full"></canvas>
            </div>
        </div>

        @if(auth()->user()->role_name !== 'warga')
            {{-- Aktivitas terakhir --}}
            <div class="civic-card rounded-3xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/50 text-base shadow-2xs">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Log Aktivitas Terakhir</h3>
                            <p class="text-xs text-slate-400">Audit trail perubahan data oleh pengurus</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 px-3 py-1 rounded-full border border-slate-200/60 dark:border-slate-700/60">
                        Audit Trail
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($recentAuditLogs as $log)
                        @php
                            $eventConfig = $getEventIcon($log['event'] ?? '');
                            $humanTitle = $humanizeEvent($log['event'] ?? '');
                        @endphp
                        <div class="flex items-start gap-3 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-colors">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border text-xs mt-0.5 {{ $eventConfig['color'] }}">
                                <i class="fa-solid {{ $eventConfig['icon'] }}"></i>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-snug">
                                        {{ $humanTitle }}
                                    </p>
                                    <span class="text-[10px] font-medium text-slate-400 shrink-0 tabular-nums">
                                        {{ $log['created_at_human'] }}
                                    </span>
                                </div>
                                @if(!empty($log['notes']))
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                                        {{ $log['notes'] }}
                                    </p>
                                @endif
                                <div class="flex items-center gap-2 mt-1.5">
                                    <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 font-medium">
                                        <i class="fa-solid fa-user-pen text-[10px]"></i>
                                        <span>{{ $log['user_name'] ?? 'Sistem' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fa-solid fa-list-check text-3xl text-slate-300 mb-2 block"></i>
                            <p class="text-xs text-slate-400">Belum ada aktivitas tercatat minggu ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            {{-- Tampilan Alternatif untuk Warga: Panduan Pembayaran --}}
            <div class="civic-card rounded-3xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Panduan Pembayaran Iuran</h3>
                        <p class="text-xs text-slate-400">Pilihan kanal pembayaran resmi RT</p>
                    </div>
                    <i class="fa-solid fa-circle-question text-emerald-600 text-lg"></i>
                </div>

                <div class="space-y-3.5">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/70 rounded-2xl border border-slate-200/60 dark:border-slate-700/60">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fa-solid fa-building-columns text-emerald-600 text-xs"></i>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Transfer Bank Resmi</span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            Transfer ke rekening <strong>BCA 1234-567-890</strong> a/n Bendahara RT. Unggah foto bukti transfer di halaman tagihan agar diverifikasi pengurus.
                        </p>
                    </div>

                    <div class="p-4 bg-slate-50 dark:bg-slate-800/70 rounded-2xl border border-slate-200/60 dark:border-slate-700/60">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fa-solid fa-hand-holding-dollar text-emerald-600 text-xs"></i>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Setor Tunai Langsung</span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            Dapat diserahkan langsung ke Bendahara atau Ketua RT saat jam pelayanan (pukul 19.00 – 21.00 WIB) untuk dicatat langsung di sistem.
                        </p>
                    </div>

                    <a href="{{ route('tagihan.index') }}" class="btn-tactile flex items-center justify-center gap-2 w-full py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-2xl shadow-xs shadow-emerald-700/20">
                        <span>Lihat &amp; Bayar Tagihan Sekarang</span>
                        <i class="fa-solid fa-arrow-right text-[11px]"></i>
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>

{{-- =============================================
     CHART.JS SCRIPT
     ============================================= --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const ctx = document.getElementById('kasChart');
    if (!ctx) return;

    const tanggal  = @json($tanggal ?? []);
    const masuk    = @json($dataMasuk ?? []);
    const keluar   = @json($dataKeluar ?? []);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: tanggal,
            datasets: [
                {
                    label: 'Kas Masuk',
                    data: masuk,
                    backgroundColor: '#10b981',
                    hoverBackgroundColor: '#059669',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.65
                },
                {
                    label: 'Kas Keluar',
                    data: keluar,
                    backgroundColor: '#f43f5e',
                    hoverBackgroundColor: '#e11d48',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.65
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 11, weight: '600', family: 'Inter, system-ui, sans-serif' },
                        color: '#64748b',
                        padding: 12
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#f8fafc',
                    bodyColor: '#e2e8f0',
                    titleFont: { size: 12, weight: 'bold', family: 'Inter, system-ui, sans-serif' },
                    bodyFont: { size: 11, family: 'Inter, system-ui, sans-serif' },
                    padding: 10,
                    cornerRadius: 8,
                    boxPadding: 4,
                    callbacks: {
                        label: function (context) {
                            const val = context.parsed.y ?? 0;
                            return ' ' + context.dataset.label + ': Rp ' + val.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        maxRotation: 0,
                        minRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 8,
                        font: { size: 11, family: 'Inter, system-ui, sans-serif' },
                        color: '#94a3b8'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(241, 245, 249, 0.8)' },
                    border: { display: false, dash: [4, 4] },
                    ticks: {
                        callback: function (value) {
                            if (value === 0) return 'Rp 0';
                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(value % 1000000 === 0 ? 0 : 1) + ' jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + ' rb';
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        font: { size: 10, family: 'Inter, system-ui, sans-serif' },
                        color: '#94a3b8',
                        maxTicksLimit: 5
                    }
                }
            }
        }
    });
})();
</script>

</div>
@endsection
