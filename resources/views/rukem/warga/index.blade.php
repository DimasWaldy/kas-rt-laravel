@extends('layouts.app')

@section('title', 'Rukun Kematian (Rukem) & Berita Duka')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{ activeTab: 'berita', showIuranModal: false }">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-black text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                    <i class="fa-solid fa-hand-holding-heart"></i> Sosial & Kemanusiaan
                </span>
                <span class="text-xs font-bold text-slate-400">•</span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Rukun Kematian (Rukem)</span>
            </div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">Rukun Kematian & Berita Duka</h1>
            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Pemberitahuan duka cita lingkungan, pengelolaan dana sosial rukun kematian, dan santunan kepedulian warga.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if(auth()->user()->hasPermission('manage-rukem'))
                <a href="{{ route('admin.rukem.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80">
                    <i class="fa-solid fa-hands-holding-child text-amber-600"></i>
                    Kelola Rukem
                </a>
            @endif

            @if(auth()->user()->hasPermission('submit-rukem'))
                <button type="button" @click="showIuranModal = true" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-600 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 shadow-2xs transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50">
                    <i class="fa-solid fa-wallet"></i>
                    Bayar Iuran Duka
                </button>
                <a href="{{ route('rukem.create-claim') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-rose-700">
                    <i class="fa-solid fa-bullhorn"></i>
                    Lapor Berita Duka
                </a>
            @endif
        </div>
    </div>

    {{-- Membership Banner --}}
    @if(!$member)
        <div class="relative overflow-hidden rounded-2xl border border-rose-200 bg-linear-to-r from-rose-50 to-orange-50 p-5 shadow-2xs dark:border-rose-900/50 dark:from-rose-950/40 dark:to-orange-950/30">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300">
                        <i class="fa-solid fa-shield-heart text-xl"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-slate-100">Anda Belum Terdaftar sebagai Anggota Rukem</h2>
                        <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">Bergabunglah dalam program Rukun Kematian lingkungan untuk saling tolong menolong, pemulasaran jenazah, dan santunan duka saat ada keluarga yang berpulang.</p>
                    </div>
                </div>
                <form action="{{ route('rukem.store-daftar') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-rose-700">
                        <i class="fa-solid fa-user-plus"></i>
                        Daftar Anggota Sekarang
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 shadow-2xs dark:border-emerald-900/40 dark:bg-emerald-950/30">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-emerald-900 dark:text-emerald-200">Kartu Anggota Rukem Aktif:</span>
                    <span class="font-mono text-xs font-black text-emerald-700 dark:text-emerald-400">{{ $member->member_number }}</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-500">• Terdaftar sejak {{ $member->joined_at ? \Carbon\Carbon::parse($member->joined_at)->translatedFormat('d M Y') : '-' }}</span>
                </div>
            </div>
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-2xs font-black uppercase text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                <i class="fa-solid fa-circle-check"></i> {{ ucfirst($member->status) }}
            </span>
        </div>
    @endif

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Saldo Kas Rukem</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <i class="fa-solid fa-vault"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">Rp {{ number_format($saldoKasRukem, 0, ',', '.') }}</p>
            <p class="mt-1 text-2xs font-semibold text-slate-500 dark:text-slate-400">Dana sosial siap salur wilayah</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Iuran Duka</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">Rp {{ number_format($totalIuranTerkumpul, 0, ',', '.') }}</p>
            <p class="mt-1 text-2xs font-semibold text-slate-500 dark:text-slate-400">Penerimaan iuran terverifikasi</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Santunan Disalurkan</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">Rp {{ number_format($totalSantunanDisalurkan, 0, ',', '.') }}</p>
            <p class="mt-1 text-2xs font-semibold text-slate-500 dark:text-slate-400">Total bantuan duka tersampaikan</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Anggota Aktif</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">{{ $totalAnggotaAktif }} <span class="text-sm font-semibold text-slate-500">Warga</span></p>
            <p class="mt-1 text-2xs font-semibold text-slate-500 dark:text-slate-400">{{ $totalLaporanDuka }} total catatan duka</p>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <button type="button" @click="activeTab = 'berita'" :class="activeTab === 'berita' ? 'border-rose-600 text-rose-600 dark:border-rose-400 dark:text-rose-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" class="flex items-center gap-2 border-b-2 py-3 text-sm font-bold transition">
                <i class="fa-solid fa-bullhorn"></i>
                Berita Duka Lingkungan
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-2xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $beritaDuka->total() }}</span>
            </button>

            <button type="button" @click="activeTab = 'laporan-saya'" :class="activeTab === 'laporan-saya' ? 'border-rose-600 text-rose-600 dark:border-rose-400 dark:text-rose-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" class="flex items-center gap-2 border-b-2 py-3 text-sm font-bold transition">
                <i class="fa-solid fa-file-lines"></i>
                Laporan Duka Saya
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-2xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $myClaims->count() }}</span>
            </button>

            <button type="button" @click="activeTab = 'iuran-saya'" :class="activeTab === 'iuran-saya' ? 'border-rose-600 text-rose-600 dark:border-rose-400 dark:text-rose-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" class="flex items-center gap-2 border-b-2 py-3 text-sm font-bold transition">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Riwayat Iuran Saya
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-2xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $myIurans->count() }}</span>
            </button>
        </nav>
    </div>

    {{-- TAB 1: Berita Duka Lingkungan --}}
    <div x-show="activeTab === 'berita'" class="space-y-4">
        @if($beritaDuka->count() > 0)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($beritaDuka as $duka)
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-rose-300 hover:shadow-xs dark:border-slate-800 dark:bg-slate-900 dark:hover:border-rose-800/60">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <i class="fa-solid fa-location-dot text-rose-500"></i> {{ $duka->user?->rt ? 'RT ' . $duka->user->rt->name : 'Warga RT' }}
                                </span>
                                @if($duka->status === 'done')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <i class="fa-solid fa-circle-check"></i> Santunan Diserahkan
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-2xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                        <i class="fa-solid fa-clock"></i> Proses Bantuan
                                    </span>
                                @endif
                            </div>

                            <div class="mt-4 flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                                    <i class="fa-solid fa-dove text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Alm. {{ $duka->deceased_name }}</h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Keluarga dari {{ $duka->user?->name }} ({{ ucfirst(str_replace('_', ' ', $duka->relationship)) }})</p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2 border-t border-slate-100 pt-3 text-xs text-slate-600 dark:border-slate-800 dark:text-slate-400">
                                <div class="flex items-center gap-2">
                                    <i class="fa-regular fa-calendar-xmark w-4 text-slate-400"></i>
                                    <span>Wafat: <strong>{{ \Carbon\Carbon::parse($duka->death_date)->translatedFormat('l, d F Y') }}</strong></span>
                                </div>
                                @if($duka->funeral_date)
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-clock w-4 text-slate-400"></i>
                                        <span>Pemakaman: <strong>{{ \Carbon\Carbon::parse($duka->funeral_date)->translatedFormat('d M Y, H:i') }} WIB</strong></span>
                                    </div>
                                @endif
                                @if($duka->cemetery_location)
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-monument w-4 text-slate-400"></i>
                                        <span>Lokasi: <strong>{{ $duka->cemetery_location }}</strong></span>
                                    </div>
                                @endif
                                @if($duka->description)
                                    <p class="mt-2 rounded-lg bg-slate-50 p-2 text-2xs italic text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                                        "{{ Str::limit($duka->description, 100) }}"
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800">
                            <a href="{{ route('rukem.show-claim', $duka) }}" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                <i class="fa-solid fa-circle-info"></i> Detail Takziah & Santunan
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $beritaDuka->links() }}
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center dark:border-slate-700">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                    <i class="fa-solid fa-hands-praying text-2xl"></i>
                </div>
                <h3 class="mt-3 text-base font-bold text-slate-900 dark:text-slate-100">Tidak Ada Berita Duka Aktif</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Alhamdulillah, lingkungan RT dan RW saat ini dalam keadaan aman, sehat, dan kondusif.</p>
            </div>
        @endif
    </div>

    {{-- TAB 2: Laporan Duka Saya --}}
    <div x-show="activeTab === 'laporan-saya'" class="space-y-4" style="display: none;">
        @if($myClaims->count() > 0)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                            <tr>
                                <th class="px-5 py-3.5">Nama Almarhum/ah</th>
                                <th class="px-5 py-3.5">Hubungan</th>
                                <th class="px-5 py-3.5">Tanggal Wafat</th>
                                <th class="px-5 py-3.5">Status Pengajuan</th>
                                <th class="px-5 py-3.5">Santunan</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach($myClaims as $claim)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">
                                        {{ $claim->deceased_name }}
                                        @if($claim->nik_deceased)
                                            <span class="block font-mono text-2xs font-normal text-slate-400">NIK: {{ $claim->nik_deceased }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-xs">{{ ucfirst(str_replace('_', ' ', $claim->relationship)) }}</td>
                                    <td class="px-5 py-4 text-xs">{{ \Carbon\Carbon::parse($claim->death_date)->translatedFormat('d M Y') }}</td>
                                    <td class="px-5 py-4">
                                        @if($claim->status === 'submitted')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-2xs font-bold text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                                <i class="fa-solid fa-paper-plane"></i> Laporan Masuk
                                            </span>
                                        @elseif($claim->status === 'verified')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2.5 py-0.5 text-2xs font-bold text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300">
                                                <i class="fa-solid fa-shield-check"></i> Terverifikasi
                                            </span>
                                        @elseif($claim->status === 'approved')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-2xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                                <i class="fa-solid fa-check"></i> Disetujui
                                            </span>
                                        @elseif($claim->status === 'done')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-2xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                <i class="fa-solid fa-circle-check"></i> Selesai Disalurkan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-2xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                                <i class="fa-solid fa-xmark"></i> Ditolak
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-xs font-semibold">
                                        @if($claim->bantuan)
                                            <span class="text-emerald-700 dark:text-emerald-400">Rp {{ number_format($claim->bantuan->amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('rukem.show-claim', $claim) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                            Lihat Tracking
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700">
                <i class="fa-solid fa-file-circle-check mx-auto text-3xl text-slate-400"></i>
                <h3 class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Pengajuan Laporan Duka</h3>
                <p class="mt-1 text-xs text-slate-500">Jika ada keluarga atau tetangga yang berpulang, Anda dapat mengirimkan laporan duka untuk santunan lingkungan.</p>
                <div class="mt-4">
                    <a href="{{ route('rukem.create-claim') }}" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-700">
                        <i class="fa-solid fa-bullhorn"></i> Lapor Berita Duka
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- TAB 3: Riwayat Iuran Saya --}}
    <div x-show="activeTab === 'iuran-saya'" class="space-y-4" style="display: none;">
        @if($myIurans->count() > 0)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                            <tr>
                                <th class="px-5 py-3.5">Periode</th>
                                <th class="px-5 py-3.5">Nominal</th>
                                <th class="px-5 py-3.5">Tanggal Bayar</th>
                                <th class="px-5 py-3.5">Status Verifikasi</th>
                                <th class="px-5 py-3.5">Bukti Transfer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach($myIurans as $iuran)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">
                                        {{ \Carbon\Carbon::createFromDate($iuran->period_year, $iuran->period_month, 1)->translatedFormat('F Y') }}
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-slate-900 dark:text-slate-100">
                                        Rp {{ number_format($iuran->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        {{ $iuran->paid_at ? \Carbon\Carbon::parse($iuran->paid_at)->translatedFormat('d M Y') : '-' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($iuran->status === 'verified')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-2xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                <i class="fa-solid fa-check"></i> Terverifikasi ({{ $iuran->verifier?->name ?? 'Pengurus' }})
                                            </span>
                                        @elseif($iuran->status === 'pending')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-2xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                                <i class="fa-solid fa-clock"></i> Menunggu Verifikasi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-2xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                                <i class="fa-solid fa-xmark"></i> Ditolak
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        @if($iuran->proof_path)
                                            <a href="{{ route('rukem.iuran-bukti', $iuran) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-emerald-700 hover:underline dark:text-emerald-400">
                                                <i class="fa-solid fa-receipt"></i> Lihat Bukti
                                            </a>
                                        @else
                                            <span class="text-slate-400">Tunai / Tanpa Berkas</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700">
                <i class="fa-solid fa-receipt mx-auto text-3xl text-slate-400"></i>
                <h3 class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Riwayat Pembayaran Iuran</h3>
                <p class="mt-1 text-xs text-slate-500">Mulai setorkan iuran sukarela atau wajib rukun kematian untuk kas sosial lingkungan.</p>
                <div class="mt-4">
                    <button type="button" @click="showIuranModal = true" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700">
                        <i class="fa-solid fa-wallet"></i> Bayar Iuran Sekarang
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- MODAL BAYAR IURAN RUKEM --}}
    <div x-show="showIuranModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showIuranModal = false"></div>

            <div class="relative transform overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg dark:border-slate-800 dark:bg-slate-900">
                <form action="{{ route('rukem.store-iuran') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100" id="modal-title">
                                <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i>
                                Setor Iuran Kas Rukun Kematian
                            </h3>
                            <button type="button" @click="showIuranModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Iuran sosial duka dialokasikan khusus untuk operasional pemakaman dan santunan ahli waris warga yang berpulang.</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="period_month" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Bulan Periode <span class="text-rose-500">*</span></label>
                                <select id="period_month" name="period_month" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" required>
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label for="period_year" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Tahun <span class="text-rose-500">*</span></label>
                                <input type="number" id="period_year" name="period_year" value="{{ now()->year }}" min="2020" max="2035" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" required>
                            </div>
                        </div>

                        <div>
                            <label for="amount" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Nominal Iuran (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" id="amount" name="amount" value="20000" min="1000" step="1000" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" required>
                            <p class="mt-1 text-2xs text-slate-500">Rekomendasi iuran sukarela / bulanan: Rp 20.000 atau sesuai kesepakatan RT.</p>
                        </div>

                        <div>
                            <label for="proof" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Unggah Bukti Transfer / Resi</label>
                            <input type="file" id="proof" name="proof" accept="image/*" class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950/60 dark:file:text-emerald-400">
                            <p class="mt-1 text-2xs text-slate-400">Format: JPG, PNG, WEBP (maks. 3MB). Kosongkan jika diserahkan tunai ke bendahara.</p>
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Catatan Tambahan (Opsional)</label>
                            <textarea id="notes" name="notes" rows="2" placeholder="Contoh: Titip iuran via transfer BCA atas nama Pak RT" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                        <button type="button" @click="showIuranModal = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700">
                            <i class="fa-solid fa-paper-plane"></i> Kirim Setoran Iuran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
