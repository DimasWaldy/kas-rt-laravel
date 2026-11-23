@extends('layouts.app')

@section('title', 'Kelola Rukun Kematian (Rukem) & Kas Duka')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{ activeTab: '{{ request('tab', 'claims') }}' }">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-black text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                    <i class="fa-solid fa-hands-holding-child"></i> Panel Pengurus
                </span>
                <span class="text-xs font-bold text-slate-400">•</span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Rukun Kematian (Rukem)</span>
            </div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">Kelola Rukun Kematian & Kas Sosial</h1>
            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Verifikasi laporan duka, persetujuan dan pencairan santunan kematian, serta pembukuan iuran kas rukem.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('rukem.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80">
                <i class="fa-solid fa-arrow-left"></i>
                Portal Warga
            </a>

            @if(auth()->user()->hasPermission('export-rukem'))
                <a href="{{ route('admin.rukem.export') }}" target="_blank" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-600 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 shadow-2xs transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50">
                    <i class="fa-solid fa-print"></i>
                    Cetak / Ekspor Laporan
                </a>
            @endif
        </div>
    </div>

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
            <p class="mt-1 text-2xs font-semibold text-slate-500 dark:text-slate-400">Kas terpisah: Iuran - Santunan</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Laporan Butuh Tindakan</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                    <i class="fa-solid fa-bell"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">{{ $pendingClaimsCount }}</p>
            <p class="mt-1 text-2xs font-semibold text-rose-600 dark:text-rose-400">Klaim duka perlu verifikasi/persetujuan</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Iuran Menunggu Verifikasi</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">{{ $pendingIuranCount }}</p>
            <p class="mt-1 text-2xs font-semibold text-slate-500 dark:text-slate-400">Menunggu validasi bukti transfer</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Santunan Disalurkan</span>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">Rp {{ number_format($totalSantunanKeluar, 0, ',', '.') }}</p>
            <p class="mt-1 text-2xs font-semibold text-slate-500 dark:text-slate-400">Dari {{ $activeMembersCount }} anggota aktif</p>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <button type="button" @click="activeTab = 'claims'" :class="activeTab === 'claims' ? 'border-amber-600 text-amber-600 dark:border-amber-400 dark:text-amber-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" class="flex items-center gap-2 border-b-2 py-3 text-sm font-bold transition">
                <i class="fa-solid fa-dove"></i>
                Laporan Duka & Santunan
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-2xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $claims->total() }}</span>
            </button>

            <button type="button" @click="activeTab = 'iurans'" :class="activeTab === 'iurans' ? 'border-amber-600 text-amber-600 dark:border-amber-400 dark:text-amber-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" class="flex items-center gap-2 border-b-2 py-3 text-sm font-bold transition">
                <i class="fa-solid fa-wallet"></i>
                Verifikasi Iuran
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-2xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $iurans->total() }}</span>
            </button>

            <button type="button" @click="activeTab = 'members'" :class="activeTab === 'members' ? 'border-amber-600 text-amber-600 dark:border-amber-400 dark:text-amber-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" class="flex items-center gap-2 border-b-2 py-3 text-sm font-bold transition">
                <i class="fa-solid fa-users"></i>
                Daftar Anggota
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-2xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $members->total() }}</span>
            </button>

            <button type="button" @click="activeTab = 'bantuans'" :class="activeTab === 'bantuans' ? 'border-amber-600 text-amber-600 dark:border-amber-400 dark:text-amber-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" class="flex items-center gap-2 border-b-2 py-3 text-sm font-bold transition">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Riwayat Penyaluran
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-2xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $bantuans->total() }}</span>
            </button>
        </nav>
    </div>

    {{-- TAB 1: Laporan Duka & Santunan --}}
    <div x-show="activeTab === 'claims'" class="space-y-4">
        {{-- Status Filter Badges --}}
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.rukem.index', ['tab' => 'claims']) }}" class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ !$claimStatusFilter ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                Semua Status
            </a>
            <a href="{{ route('admin.rukem.index', ['tab' => 'claims', 'claim_status' => 'submitted']) }}" class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $claimStatusFilter === 'submitted' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300' }}">
                Menunggu Verifikasi
            </a>
            <a href="{{ route('admin.rukem.index', ['tab' => 'claims', 'claim_status' => 'verified']) }}" class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $claimStatusFilter === 'verified' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300' }}">
                Terverifikasi
            </a>
            <a href="{{ route('admin.rukem.index', ['tab' => 'claims', 'claim_status' => 'approved']) }}" class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $claimStatusFilter === 'approved' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300' }}">
                Disetujui
            </a>
            <a href="{{ route('admin.rukem.index', ['tab' => 'claims', 'claim_status' => 'done']) }}" class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $claimStatusFilter === 'done' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300' }}">
                Selesai Disalurkan
            </a>
            <a href="{{ route('admin.rukem.index', ['tab' => 'claims', 'claim_status' => 'rejected']) }}" class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $claimStatusFilter === 'rejected' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300' }}">
                Ditolak
            </a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Nama Almarhum</th>
                            <th class="px-5 py-3.5">Pelapor & RT</th>
                            <th class="px-5 py-3.5">Hubungan</th>
                            <th class="px-5 py-3.5">Tanggal Wafat</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Santunan</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($claims as $claim)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">
                                    {{ $claim->deceased_name }}
                                    @if($claim->cemetery_location)
                                        <span class="block text-2xs font-normal text-slate-400">TPU: {{ $claim->cemetery_location }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $claim->user?->name }}</span>
                                    <span class="block text-2xs text-slate-400">{{ $claim->user?->rt ? 'RT ' . $claim->user->rt->name : '-' }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs">{{ ucfirst(str_replace('_', ' ', $claim->relationship)) }}</td>
                                <td class="px-5 py-4 text-xs">{{ \Carbon\Carbon::parse($claim->death_date)->translatedFormat('d M Y') }}</td>
                                <td class="px-5 py-4">
                                    @if($claim->status === 'submitted')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-2xs font-bold text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                            <i class="fa-solid fa-paper-plane"></i> Menunggu Verifikasi
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
                                            <i class="fa-solid fa-circle-check"></i> Selesai
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
                                    <a href="{{ route('admin.rukem.show-claim', $claim) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-amber-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                        <i class="fa-solid fa-sliders"></i> Tinjau & Proses
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-xs text-slate-500">Tidak ada data laporan duka yang sesuai filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($claims->hasPages())
                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $claims->appends(['tab' => 'claims', 'claim_status' => $claimStatusFilter])->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 2: Verifikasi Iuran --}}
    <div x-show="activeTab === 'iurans'" class="space-y-4" style="display: none;">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Warga Penyetor</th>
                            <th class="px-5 py-3.5">Periode</th>
                            <th class="px-5 py-3.5">Nominal</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Bukti Transfer</th>
                            <th class="px-5 py-3.5 text-right">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($iurans as $iuran)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-4">
                                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $iuran->user?->name }}</span>
                                    <span class="block text-2xs text-slate-400">{{ $iuran->user?->rt ? 'RT ' . $iuran->user->rt->name : '-' }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs font-semibold">
                                    {{ \Carbon\Carbon::createFromDate($iuran->period_year, $iuran->period_month, 1)->translatedFormat('F Y') }}
                                </td>
                                <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">
                                    Rp {{ number_format($iuran->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($iuran->status === 'verified')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-2xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            <i class="fa-solid fa-check"></i> Terverifikasi
                                        </span>
                                    @elseif($iuran->status === 'pending')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-2xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                            <i class="fa-solid fa-clock"></i> Pending
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
                                        <span class="text-slate-400">Tunai</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if($iuran->status === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('admin.rukem.verify-iuran', $iuran) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="verified">
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-2xs font-bold text-white shadow-xs hover:bg-emerald-700">
                                                    <i class="fa-solid fa-check"></i> Terima
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.rukem.verify-iuran', $iuran) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-2.5 py-1 text-2xs font-bold text-white shadow-xs hover:bg-rose-700">
                                                    <i class="fa-solid fa-xmark"></i> Tolak
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-2xs text-slate-400">Telah diverifikasi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-slate-500">Belum ada catatan setoran iuran rukem.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($iurans->hasPages())
                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $iurans->appends(['tab' => 'iurans'])->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 3: Daftar Anggota --}}
    <div x-show="activeTab === 'members'" class="space-y-4" style="display: none;">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Nomor Anggota</th>
                            <th class="px-5 py-3.5">Nama Warga</th>
                            <th class="px-5 py-3.5">Wilayah RT</th>
                            <th class="px-5 py-3.5">Tanggal Bergabung</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($members as $m)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-4 font-mono text-xs font-bold text-slate-900 dark:text-slate-100">
                                    {{ $m->member_number ?? '-' }}
                                </td>
                                <td class="px-5 py-4 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $m->user?->name }}
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    {{ $m->user?->rt ? 'RT ' . $m->user->rt->name : '-' }}
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    {{ $m->joined_at ? \Carbon\Carbon::parse($m->joined_at)->translatedFormat('d M Y') : '-' }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($m->status === 'active')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-2xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            <i class="fa-solid fa-circle-check"></i> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-2xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            <i class="fa-solid fa-circle-minus"></i> {{ ucfirst($m->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <form action="{{ route('admin.rukem.member-status', $m) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $m->status === 'active' ? 'inactive' : 'active' }}">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                            {{ $m->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-slate-500">Belum ada anggota rukun kematian terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($members->hasPages())
                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $members->appends(['tab' => 'members'])->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 4: Riwayat Penyaluran --}}
    <div x-show="activeTab === 'bantuans'" class="space-y-4" style="display: none;">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Penerima / Ahli Waris</th>
                            <th class="px-5 py-3.5">Almarhum/ah</th>
                            <th class="px-5 py-3.5">Nominal Santunan</th>
                            <th class="px-5 py-3.5">Tanggal Penyerahan</th>
                            <th class="px-5 py-3.5">Petugas Penyerah</th>
                            <th class="px-5 py-3.5 text-right">Kwitansi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($bantuans as $bantuan)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-4 font-bold text-slate-900 dark:text-slate-100">
                                    {{ $bantuan->recipient_name }}
                                    @if($bantuan->recipient_phone)
                                        <span class="block font-mono text-2xs text-slate-400">{{ $bantuan->recipient_phone }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs font-semibold">
                                    Alm. {{ $bantuan->claim?->deceased_name }}
                                </td>
                                <td class="px-5 py-4 font-black text-emerald-700 dark:text-emerald-400">
                                    Rp {{ number_format($bantuan->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    {{ \Carbon\Carbon::parse($bantuan->given_at)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    {{ $bantuan->giver?->name ?? 'Pengurus' }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if($bantuan->proof_path)
                                        <a href="{{ route('admin.rukem.bantuan-bukti', $bantuan) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-emerald-700 hover:underline dark:text-emerald-400">
                                            <i class="fa-solid fa-receipt"></i> Kwitansi
                                        </a>
                                    @else
                                        <span class="text-2xs text-slate-400">Tanpa Berkas</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-slate-500">Belum ada riwayat santunan yang disalurkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($bantuans->hasPages())
                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $bantuans->appends(['tab' => 'bantuans'])->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
