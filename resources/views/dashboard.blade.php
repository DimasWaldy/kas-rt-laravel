@extends('layouts.app')

@section('title', 'Ringkasan Statistik')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .font-inter {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
</style>
@endpush

@section('content')
<div class="font-inter">

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
            : 'Lengkapi profil, KK/KTP, serta domisili Anda melalui halaman profil agar pengurus RT/Admin/Superadmin dapat memverifikasi akun Anda sekaligus.';
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
        $statusTitle = 'Akun aktif';
        $statusMessage = 'Lengkapi data profil Anda agar layanan warga berjalan lancar.';
        $statusCtaUrl = route('profile.edit');
        $statusCtaLabel = 'Kelola profil';
    }

    $statusCardClasses = match($statusTone) {
        'amber' => 'border-amber-200 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10',
        'indigo' => 'border-indigo-200 bg-indigo-50 dark:border-indigo-500/30 dark:bg-indigo-500/10',
                'emerald' => 'border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30',
                default => 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900',
    };
    $statusIconClasses = match($statusTone) {
        'amber' => 'text-amber-600 dark:text-amber-400',
        'indigo' => 'text-indigo-600 dark:text-indigo-400',
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        default => 'text-slate-500 dark:text-slate-400',
    };
    $statusButtonClasses = match($statusTone) {
        'amber' => 'bg-amber-600 hover:bg-amber-700',
        'indigo' => 'bg-indigo-600 hover:bg-indigo-700',
        'emerald' => 'bg-emerald-600 hover:bg-emerald-700',
        default => 'bg-slate-700 hover:bg-slate-800',
    };
@endphp
<div class="mb-6 rounded-2xl border {{ $statusCardClasses }} p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="flex items-start gap-3">
            <i class="fa-solid {{ $statusIcon }} {{ $statusIconClasses }} mt-1 text-2xl"></i>
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $statusTitle }}</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $statusMessage }}</p>
                @if($statusCtaUrl)
                    <a href="{{ $statusCtaUrl }}" class="mt-3 inline-flex items-center gap-2 rounded-xl {{ $statusButtonClasses }} px-4 py-2.5 text-sm font-bold text-white shadow-xs transition">
                        <i class="fa-solid {{ $statusTone === 'amber' ? 'fa-pen-to-square' : 'fa-house' }}"></i>
                        {{ $statusCtaLabel }}
                    </a>
                @endif
                @if($accountStatus === 'aktif' && ! $needsDomicile)
                    @php
                        $ktpRfidCard = \App\Models\RfidCard::where('user_id', auth()->id())->with('verifiedRt:id,name')->first();
                    @endphp
                    <div class="mt-3">
                        @if($ktpRfidCard?->ktp_verified_at)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">
                                <i class="fa-solid fa-id-card"></i>
                                KTP Terdaftar di {{ $ktpRfidCard->verifiedRt?->name ?? 'RT' }} &amp; Sistem (RFID)
                            </span>
                        @else
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                <p class="font-bold"><i class="fa-solid fa-id-card mr-1"></i> KTP belum diverifikasi via RFID</p>
                                <p class="mt-0.5">Layanan dasar tetap aktif. Bawa KTP ke petugas RT/Admin untuk tap verifikasi agar terdaftar di RT &amp; dapat mendaftar Koperasi serta Absensi RFID.</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        <div class="shrink-0 rounded-xl border border-black/5 bg-white/60 px-4 py-3 dark:border-white/10 dark:bg-white/5">
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">Status Iuran</p>
                    <p class="mt-1 text-sm font-bold capitalize text-slate-700 dark:text-slate-100">{{ str_replace('_', ' ', $userStatus ?? 'Belum Ada Tagihan') }}</p>
            <a href="{{ route('tagihan.index') }}" class="mt-1 inline-block text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">Detail Tagihan</a>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isGlobalOperator() && auth()->user()->isAktif() && auth()->user()->hasPermission('admin-only'))
<div class="mb-8 flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-950/40 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Administrasi Sistem</p>
        <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Manajemen Akun</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Buat akun pengurus dan tempatkan sesuai wilayah tugas. Akun warga mengikuti alur data rumah dan KK.</p>
    </div>
    <a href="{{ route('admin.accounts.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
        <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Buka Manajemen Akun
    </a>
</div>
@endif

{{-- =============================================
     SECTION 1: KEUANGAN KESELURUHAN
     ============================================= --}}
@php
    $u = auth()->user();
    $canViewFinance = $u->canViewFinance();
    $isLockedAccount = ! $u->isAktif() || ($u->role_name === 'warga' && $u->onboarding_domisili && ! $u->rumah_id);
@endphp

@if($canViewFinance && ! $isLockedAccount)
<div class="mb-2">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">
        {{ $u->isRwOfficial() ? 'Rekap Keuangan RW' : ($u->role_name === 'warga' ? 'Ringkasan Keuangan RT' : 'Keuangan RT') }}
    </p>
</div>
{{-- ===== MAIN BALANCE CARD ===== --}}
@php
    $saldo = ($kasMasuk ?? 0) - ($kasKeluar ?? 0);
@endphp
<div class="balance-card mb-6 p-6 md:p-8">
    <div class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <i class="fa-solid fa-wallet text-white"></i>
                </span>
                <p class="text-sm font-medium text-white/80">Saldo Kas {{ $u->isRwOfficial() ? 'RW' : 'RT' }} Saat Ini</p>
            </div>
            <p class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
            <p class="mt-2 text-xs font-medium text-white/70">Saldo = Kas Masuk − Kas Keluar (seluruh waktu)</p>
        </div>
        <div class="flex flex-wrap gap-4">
            <div class="rounded-2xl bg-white/10 px-4 py-3 text-center backdrop-blur-sm">
                <p class="text-[10px] font-medium uppercase tracking-wider text-white/70">Total Masuk</p>
                <p class="mt-1 text-sm font-bold text-white">Rp {{ number_format($kasMasuk ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-white/10 px-4 py-3 text-center backdrop-blur-sm">
                <p class="text-[10px] font-medium uppercase tracking-wider text-white/70">Total Keluar</p>
                <p class="mt-1 text-sm font-bold text-white">Rp {{ number_format($kasKeluar ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</div>
{{-- ===== FINANCIAL STATISTICS CARDS ===== --}}
<div class="grid grid-cols-2 gap-4 md:grid-cols-4 mb-6">
    <div class="card-fintech p-4">
        <div class="icon-tile bg-green-50">
            <i class="fa-solid fa-arrow-down-left text-green-600"></i>
        </div>
        <p class="mt-3 text-xs font-medium text-slate-400">Total Pemasukan</p>
        <p class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100 truncate">Rp {{ number_format($kasMasuk ?? 0, 0, ',', '.') }}</p>
    </div>
    <div class="card-fintech p-4">
        <div class="icon-tile bg-red-50">
            <i class="fa-solid fa-arrow-up-right text-red-500"></i>
        </div>
        <p class="mt-3 text-xs font-medium text-slate-400">Total Pengeluaran</p>
        <p class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100 truncate">Rp {{ number_format($kasKeluar ?? 0, 0, ',', '.') }}</p>
    </div>
    <div class="card-fintech p-4">
        <div class="icon-tile bg-emerald-50">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
        </div>
        <p class="mt-3 text-xs font-medium text-slate-400">Iuran Lunas</p>
        <p class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100 truncate">{{ $totalPaidTagihan ?? 0 }} tagihan</p>
    </div>
    <div class="card-fintech p-4">
        <div class="icon-tile {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'bg-amber-50' : 'bg-slate-50' }}">
            <i class="fa-solid fa-clock {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-amber-600' : 'text-slate-400' }}"></i>
        </div>
        <p class="mt-3 text-xs font-medium text-slate-400">Iuran Belum Bayar</p>
        <p class="mt-1 text-lg font-bold truncate {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-slate-100' }}">{{ $totalUnpaidTagihan ?? 0 }} tagihan</p>
    </div>
</div>
@else
@if(! $isLockedAccount)
<div class="mb-2">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Ringkasan Tagihan</p>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="card-fintech p-4">
        <div class="icon-tile bg-emerald-50">
            <i class="fa-solid fa-receipt text-emerald-600"></i>
        </div>
        <p class="mt-3 text-xs font-medium text-slate-400">Tagihan Bulan Ini</p>
        <p class="mt-1 text-lg font-bold capitalize text-slate-900 dark:text-slate-100 truncate">{{ str_replace('_', ' ', $userStatus ?? 'Belum Ada Tagihan') }}</p>
    </div>
    <div class="card-fintech p-4">
        <div class="icon-tile {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'bg-amber-50' : 'bg-slate-50' }}">
            <i class="fa-solid fa-clock {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-amber-600' : 'text-slate-400' }}"></i>
        </div>
        <p class="mt-3 text-xs font-medium text-slate-400">Iuran Belum Bayar</p>
        <p class="mt-1 text-lg font-bold truncate {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-slate-100' }}">{{ $totalUnpaidTagihan ?? 0 }} tagihan</p>
    </div>
</div>
@endif
@endif

{{-- ===== QUICK ACTIONS ===== --}}
@if(! $isLockedAccount)
@php
    $quickActions = [
        ['label' => 'Bayar Iuran', 'route' => 'tagihan.index', 'icon' => 'fa-wallet', 'visible' => ! $u->isRwOfficial()],
        ['label' => 'Laporan Kas', 'route' => 'laporan-kas.index', 'icon' => 'fa-chart-pie', 'visible' => $u->canViewFinance()],
        ['label' => 'Verifikasi Warga', 'route' => 'verifikasi-warga.index', 'icon' => 'fa-user-check', 'visible' => $u->canManageWarga()],
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
        ['label' => 'Direktori RW', 'route' => 'direktori-rw.index', 'icon' => 'fa-address-book', 'visible' => $u->hasPermission('view-direktori-rw')],
    ];
    $quickActions = array_values(array_filter($quickActions, function ($qa) {
        return $qa['visible'] && Route::has($qa['route']);
    }));
@endphp
@if(count($quickActions) > 0)
{{-- ==========================================
     VERSI DESKTOP: MENU CEPAT
     ========================================== --}}
<div class="hidden md:block">
    <div class="card-fintech p-5 mb-6">
        <p class="mb-4 text-sm font-semibold text-slate-800 dark:text-slate-100">Menu Cepat</p>
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach($quickActions as $qa)
                <a href="{{ route($qa['route']) }}" class="quick-action flex flex-col items-center gap-2 rounded-xl p-3">
                    <span class="icon-tile bg-emerald-50"><i class="fa-solid {{ $qa['icon'] }} text-emerald-600"></i></span>
                    <span class="text-center text-xs font-semibold text-slate-700 dark:text-slate-200 leading-tight">{{ $qa['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- ==========================================
     VERSI MOBILE / ANDROID: TAMPILAN BPJS JMO
     ========================================== --}}
<div class="block md:hidden mb-6" x-data="{ showMorePrograms: false }">
    {{-- Section 1: Program Layanan --}}
    <div class="mb-5">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Program Layanan</h3>
        </div>

        <div class="space-y-2.5">
            {{-- Card 1: Iuran & Kas Warga (Style JHT) --}}
            <a href="{{ route('tagihan.index') }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-xs transition active:scale-[0.98] dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-200/50 dark:border-amber-500/20 text-xl">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">Iuran Warga RT</h4>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">Anda sudah terdaftar di layanan ini</p>
                    </div>
                </div>
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-xs shadow-xs">
                    <i class="fa-solid fa-check"></i>
                </span>
            </a>

            {{-- Card 2: Administrasi & Surat (Style JKK) --}}
            @if(Route::has('surat.index'))
            <a href="{{ route('surat.index') }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-xs transition active:scale-[0.98] dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-500 border border-blue-200/50 dark:border-blue-500/20 text-xl">
                        <i class="fa-solid fa-file-shield"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">Administrasi Surat Digital</h4>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">Anda sudah terdaftar di layanan ini</p>
                    </div>
                </div>
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-xs shadow-xs">
                    <i class="fa-solid fa-check"></i>
                </span>
            </a>
            @endif

            {{-- Program Tambahan (Expandable) --}}
            <div x-show="showMorePrograms" x-cloak class="space-y-2.5 pt-0.5" x-transition>
                @if(Route::has('bank-sampah.index'))
                <a href="{{ route('bank-sampah.index') }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-xs transition active:scale-[0.98] dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-500/10 text-teal-600 border border-teal-200/50 dark:border-teal-500/20 text-xl">
                            <i class="fa-solid fa-recycle"></i>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">Bank Sampah Digital</h4>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">Anda sudah terdaftar di layanan ini</p>
                        </div>
                    </div>
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-xs shadow-xs">
                        <i class="fa-solid fa-check"></i>
                    </span>
                </a>
                @endif

                @if(Route::has('posyandu.index'))
                <a href="{{ route('posyandu.index') }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-xs transition active:scale-[0.98] dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-pink-500/10 text-pink-500 border border-pink-200/50 dark:border-pink-500/20 text-xl">
                            <i class="fa-solid fa-baby"></i>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">Posyandu &amp; Balita</h4>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">Anda sudah terdaftar di layanan ini</p>
                        </div>
                    </div>
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-xs shadow-xs">
                        <i class="fa-solid fa-check"></i>
                    </span>
                </a>
                @endif

                @if(Route::has('koperasi.index'))
                <a href="{{ route('koperasi.index') }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-xs transition active:scale-[0.98] dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 border border-amber-200/50 dark:border-amber-500/20 text-xl">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">Koperasi Simpan Pinjam</h4>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">Anda sudah terdaftar di layanan ini</p>
                        </div>
                    </div>
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-xs shadow-xs">
                        <i class="fa-solid fa-check"></i>
                    </span>
                </a>
                @endif
            </div>

            {{-- Tombol Program Lainnya (Style JMO) --}}
            <button type="button" x-on:click="showMorePrograms = !showMorePrograms" class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-emerald-100 bg-emerald-50/70 py-2.5 text-center text-xs font-bold text-emerald-800 transition active:scale-[0.99] dark:border-emerald-800/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                <span x-text="showMorePrograms ? 'Tutup Program' : 'Program Lainnya'">Program Lainnya</span>
                <i class="fa-solid text-[10px]" :class="showMorePrograms ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
    </div>

    {{-- Section 2: Layanan Lainnya (Grid 4 Kolom Style JMO) --}}
    <div>
        <div class="mb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Layanan Lainnya</h3>
        </div>

        <div class="grid grid-cols-4 gap-y-4 gap-x-2 rounded-2xl border border-slate-100 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            @foreach($quickActions as $qa)
                @php
                    $mobileIconColor = match($qa['icon']) {
                        'fa-wallet', 'fa-money-bill-trend-up' => 'text-amber-500',
                        'fa-chart-pie', 'fa-boxes-stacked' => 'text-emerald-600',
                        'fa-user-check', 'fa-users', 'fa-address-book' => 'text-indigo-500',
                        'fa-money-bill-transfer' => 'text-rose-500',
                        'fa-list-check', 'fa-clipboard-list' => 'text-blue-500',
                        'fa-hand-holding-heart', 'fa-hand-holding' => 'text-teal-600',
                        'fa-bullhorn' => 'text-teal-500',
                        'fa-building-shield', 'fa-shield-halved' => 'text-cyan-600',
                        'fa-store' => 'text-emerald-600',
                        'fa-envelope-open-text' => 'text-emerald-500',
                        'fa-calendar-days' => 'text-violet-500',
                        'fa-baby' => 'text-pink-500',
                        'fa-recycle' => 'text-teal-500',
                        default => 'text-emerald-600',
                    };
                @endphp
                <a href="{{ route($qa['route']) }}" class="flex flex-col items-center gap-1.5 p-1 transition active:scale-95 group">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-100 bg-slate-50/80 shadow-2xs group-hover:scale-105 transition dark:border-slate-700/80 dark:bg-slate-800">
                        <i class="fa-solid {{ $qa['icon'] }} text-lg {{ $mobileIconColor }}"></i>
                    </div>
                    <span class="text-center text-[10.5px] leading-tight font-medium text-slate-700 dark:text-slate-300 line-clamp-2">
                        {{ $qa['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif
@endif
{{-- =============================================
     SECTION 2: DATA WARGA
     ============================================= --}}
<div class="mb-2">
    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Data warga</p>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
        <div class="bg-indigo-50 w-9 h-9 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </div>
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ auth()->user()->isRwOfficial() ? 'Total Warga RW' : 'Warga RT' }}</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100 mt-0.5">{{ $totalWarga ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">orang terdaftar</p>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
        <div class="bg-amber-50 w-9 h-9 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/></svg>
        </div>
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Rumah terdaftar</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100 mt-0.5">{{ $totalKK ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">unit hunian</p>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
        <div class="bg-emerald-50 dark:bg-emerald-500/10 w-9 h-9 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
        </div>
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Rumah aktif</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100 mt-0.5">{{ $kepalaKeluargaAktif ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">dari {{ $totalKK ?? 0 }} rumah</p>
    </div>

    {{-- Card Belum Bayar - border merah kalau ada tunggakan --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border p-5 {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'border-red-200' : 'border-slate-200 dark:border-slate-700' }}">
        <div class="{{ ($totalUnpaidTagihan ?? 0) > 0 ? 'bg-red-50' : 'bg-slate-50 dark:bg-slate-800' }} w-9 h-9 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-red-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5"/></svg>
        </div>
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Belum bayar</p>
        <p class="text-2xl font-semibold mt-0.5 {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-red-600' : 'text-slate-900 dark:text-slate-100' }}">{{ $totalUnpaidTagihan ?? 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">tagihan tertunggak</p>
    </div>

</div>

{{-- =============================================
     SECTION 3: DETAIL BULAN INI + AKTIVITAS
     ============================================= --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">

    {{-- Kolom kiri: ringkasan bulan ini + top rumah --}}
    <div class="flex flex-col gap-6">

        {{-- Ringkasan bulan ini + alert pending --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Ringkasan bulan ini</p>
                <span class="text-xs font-medium bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 px-2.5 py-1 rounded-full">
                    {{ now()->translatedFormat('F Y') }}
                </span>
            </div>

            <div class="space-y-3 mb-4">
                <div>
                    <div class="flex justify-between items-center text-xs mb-1">
                        <span class="text-slate-500 dark:text-slate-400">Pendapatan masuk</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">Rp {{ number_format($monthlyRevenue ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        @php
                            $maxVal = max($monthlyRevenue ?? 0, 1);
                            $pctMasuk = min(100, round((($monthlyRevenue ?? 0) / $maxVal) * 100));
                        @endphp
                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $pctMasuk }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center text-xs mb-1">
                        <span class="text-slate-500 dark:text-slate-400">Tagihan lunas</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $totalPaidTagihan ?? 0 }} tagihan</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        @php
                            $totalTagihan = ($totalPaidTagihan ?? 0) + ($totalUnpaidTagihan ?? 0);
                            $pctLunas = $totalTagihan > 0 ? min(100, round((($totalPaidTagihan ?? 0) / $totalTagihan) * 100)) : 0;
                        @endphp
                        <div class="h-full bg-blue-500 rounded-full" style="width: {{ $pctLunas }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center text-xs mb-1">
                        <span class="text-slate-500 dark:text-slate-400">Tagihan belum bayar</span>
                        <span class="font-medium {{ ($totalUnpaidTagihan ?? 0) > 0 ? 'text-red-600' : 'text-slate-800 dark:text-slate-200' }}">{{ $totalUnpaidTagihan ?? 0 }} tagihan</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        @php
                            $pctUnpaid = $totalTagihan > 0 ? min(100, round((($totalUnpaidTagihan ?? 0) / $totalTagihan) * 100)) : 0;
                        @endphp
                        <div class="h-full bg-red-400 rounded-full" style="width: {{ $pctUnpaid }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Alert bukti transfer pending --}}
            @if(auth()->user()->canManageFinance() && ($pendingTransferCount ?? 0) > 0)
                <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-xs font-semibold text-amber-800">{{ $pendingTransferCount }} bukti transfer menunggu verifikasi</p>
                        <p class="text-xs text-amber-700 mt-0.5">Segera tindaklanjuti agar pembayaran warga tercatat.</p>
                    </div>
                </div>
            @endif

            {{-- Shortcut Iuran Bulanan untuk Ketua RT & Bendahara --}}
            @if(in_array(auth()->user()->role_name, ['ketua_rt', 'rt', 'ketua-rt']) || auth()->user()->canManageFinance())
                <a href="{{ route('iuran-bulanan.index') }}"
                   class="flex items-center justify-between gap-3 rounded-xl border border-teal-200 bg-teal-50 dark:bg-teal-900/20 dark:border-teal-700 p-3 transition hover:bg-teal-100 dark:hover:bg-teal-900/40">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-800">
                            <svg class="h-4 w-4 text-teal-700 dark:text-teal-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold text-teal-800 dark:text-teal-200">Iuran Bulanan</p>
                            <p class="text-[11px] text-teal-600 dark:text-teal-400">Generate & kelola tagihan warga</p>
                        </div>
                    </div>
                    <svg class="h-4 w-4 shrink-0 text-teal-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endif
        </div>

        {{-- Top 5 rumah --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-4">Top 5 Rumah - total iuran</p>

            @forelse($topKKIuran as $topKK)
                <div class="flex items-center justify-between gap-3 py-2.5 {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-800' : '' }}">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold shrink-0
                            {{ $loop->first ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500 dark:text-slate-400' }}">
                            {{ $loop->iteration }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $topKK['kepala_keluarga'] }}</p>
                            <p class="text-xs text-slate-400">Rumah {{ $topKK['no_kk'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 shrink-0">
                        Rp {{ number_format($topKK['total_iuran'], 0, ',', '.') }}
                    </p>
                </div>
            @empty
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400">Belum ada data iuran.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Kolom kanan: grafik + log aktivitas --}}
    <div class="flex flex-col gap-6">

        {{-- Chart tren pembayaran --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Tren pembayaran</p>
                    <p class="text-xs text-slate-400 mt-0.5">Kas masuk & keluar per hari</p>
                </div>
                <span class="text-xs font-medium bg-slate-100 text-slate-600 dark:text-slate-300 px-2.5 py-1 rounded-full">Bulan ini</span>
            </div>
            <div class="h-56">
                <canvas id="kasChart" class="w-full h-full"></canvas>
            </div>
        </div>

        @if(auth()->user()->role_name !== 'warga')
            {{-- Aktivitas terakhir --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-4">Aktivitas terakhir</p>

                @forelse($recentAuditLogs as $log)
                    <div class="py-2.5 {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-800' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-snug">{{ $log['event'] }}</p>
                            <span class="text-xs text-slate-400 shrink-0 mt-0.5">{{ $log['created_at_human'] }}</span>
                        </div>
                        @if($log['notes'])
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $log['notes'] }}</p>
                        @endif
                        <p class="text-xs text-slate-400 mt-1">Oleh: {{ $log['user_name'] ?? 'Sistem' }}</p>
                    </div>
                @empty
                    <div class="text-center py-6">
                        <p class="text-sm text-slate-400">Belum ada aktivitas tercatat.</p>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Tampilan Alternatif untuk Warga: Panduan Pembayaran --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Panduan Pembayaran</p>
                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>

                <div class="space-y-4">
                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-800">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Transfer</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                            Silakan transfer ke rekening <strong>BCA 1234-567-890</strong> a/n Bendahara RT. Pastikan simpan bukti transfer untuk diunggah di menu Tagihan.
                        </p>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-800">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Offline</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                            Pembayaran tunai dapat diserahkan langsung kepada Bendahara atau Ketua RT pada jam kerja pengurus (19:00 - 21:00).
                        </p>
                    </div>

                    <a href="{{ route('tagihan.index') }}" class="flex items-center justify-center gap-2 w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-xs shadow-indigo-100">
                        <span>Cek Tagihan Sekarang</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>

{{-- =============================================
     CHART.JS
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
                    backgroundColor: '#22c55e',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.65,
                    categoryPercentage: 0.55
                },
                {
                    label: 'Kas Keluar',
                    data: keluar,
                    backgroundColor: '#f87171',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.65,
                    categoryPercentage: 0.55
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 11, weight: '500' },
                        color: '#64748b',
                        padding: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const val = context.parsed.y ?? 0;
                            return ' Rp ' + val.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        font: { size: 10 },
                        color: '#94a3b8'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    border: { display: false, dash: [4, 4] },
                    ticks: {
                        callback: function (value) {
                            if (value === 0) return 'Rp 0';
                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        font: { size: 10 },
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
