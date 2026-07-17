@extends('layouts.app')

@section('title', 'Kelola Koperasi')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-indigo-700">Verifikasi simpan pinjam warga</p>
            <h1 class="text-2xl font-black text-slate-900">Kelola Koperasi</h1>
            <p class="mt-1 text-sm text-slate-500">Bendahara dapat memproses anggota, simpanan, pinjaman, dan angsuran sesuai wilayahnya.</p>
        </div>
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm font-bold text-indigo-800">
            <i class="fa-solid fa-shield-halved mr-2"></i> Data dibatasi sesuai RT/RW akun pengurus.
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Anggota</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ $members->count() }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $members->where('status', 'pending')->count() }} menunggu verifikasi</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Simpanan Pending</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ $pendingSimpanans->count() }}</p>
            <p class="mt-1 text-xs text-slate-500">Bukti transfer perlu dicek</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pinjaman Pending</p>
            <p class="mt-2 text-3xl font-black text-amber-700">{{ $pendingPinjamans->count() }}</p>
            <p class="mt-1 text-xs text-slate-500">Butuh keputusan pengurus</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Angsuran Pending</p>
            <p class="mt-2 text-3xl font-black text-blue-700">{{ $pendingAngsurans->count() }}</p>
            <p class="mt-1 text-xs text-slate-500">Mengurangi sisa pinjaman saat valid</p>
        </div>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ tab: 'anggota' }">
        <div class="mb-6 flex flex-wrap gap-2">
            <button type="button" x-on:click="tab = 'anggota'" :class="tab === 'anggota' ? 'bg-indigo-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-xl px-4 py-2 text-sm font-bold">Anggota</button>
            <button type="button" x-on:click="tab = 'simpanan'" :class="tab === 'simpanan' ? 'bg-indigo-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-xl px-4 py-2 text-sm font-bold">Simpanan</button>
            <button type="button" x-on:click="tab = 'pinjaman'" :class="tab === 'pinjaman' ? 'bg-indigo-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-xl px-4 py-2 text-sm font-bold">Pinjaman</button>
            <button type="button" x-on:click="tab = 'angsuran'" :class="tab === 'angsuran' ? 'bg-indigo-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-xl px-4 py-2 text-sm font-bold">Angsuran</button>
        </div>

        <div x-show="tab === 'anggota'">
            <h2 class="mb-4 text-lg font-black text-slate-900">Verifikasi Anggota Koperasi</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">No Anggota</th>
                            <th class="px-4 py-3">Warga</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($members as $member)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $member->member_number }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ $member->user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $member->user->rt?->nama ?? 'RT belum terset' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $member->status === 'aktif' ? 'bg-emerald-50 text-emerald-700' : ($member->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                        {{ str($member->status)->headline() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        @if($member->status !== 'aktif')
                                            <form method="POST" action="{{ route('admin.koperasi.approve-member', $member) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="aktif">
                                                <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">Aktifkan</button>
                                            </form>
                                        @endif
                                        @if($member->status !== 'ditolak')
                                            <form method="POST" action="{{ route('admin.koperasi.approve-member', $member) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="ditolak">
                                                <button class="rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100">Tolak</button>
                                            </form>
                                        @endif
                                        @if($member->status === 'aktif')
                                            <form method="POST" action="{{ route('admin.koperasi.approve-member', $member) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="nonaktif">
                                                <button class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200">Nonaktifkan</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada anggota koperasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="tab === 'simpanan'" x-cloak>
            <h2 class="mb-4 text-lg font-black text-slate-900">Simpanan Menunggu Verifikasi</h2>
            <div class="space-y-3">
                @forelse($pendingSimpanans as $simpanan)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-black text-slate-900">{{ $simpanan->user->name }} · {{ str($simpanan->type)->headline() }}</p>
                                <p class="text-sm text-slate-500">{{ $simpanan->transaction_date->translatedFormat('d M Y') }} · Rp {{ number_format($simpanan->amount, 0, ',', '.') }}</p>
                                @if($simpanan->proof_path)
                                    <a href="{{ Storage::url($simpanan->proof_path) }}" target="_blank" class="mt-1 inline-block text-sm font-bold text-indigo-700">Lihat bukti transfer</a>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.koperasi.approve-simpanan', $simpanan) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="terverifikasi">
                                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Verifikasi</button>
                                </form>
                                <form method="POST" action="{{ route('admin.koperasi.approve-simpanan', $simpanan) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="ditolak">
                                    <input type="hidden" name="rejected_reason" value="Bukti simpanan tidak valid">
                                    <button class="rounded-xl bg-red-50 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-100">Tolak</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-8 text-center text-sm text-slate-500">Tidak ada simpanan pending.</p>
                @endforelse
            </div>
        </div>

        <div x-show="tab === 'pinjaman'" x-cloak>
            <h2 class="mb-4 text-lg font-black text-slate-900">Pengajuan Pinjaman</h2>
            <div class="space-y-3">
                @forelse($pendingPinjamans as $pinjaman)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div>
                                <p class="font-black text-slate-900">{{ $pinjaman->user->name }}</p>
                                <p class="text-sm text-slate-500">Pokok Rp {{ number_format($pinjaman->amount, 0, ',', '.') }} · Jasa Rp {{ number_format($pinjaman->service_fee_amount, 0, ',', '.') }} · {{ $pinjaman->tenor_months }} bulan</p>
                                <p class="mt-1 text-sm font-bold text-amber-700">Total pengembalian Rp {{ number_format($pinjaman->remaining_amount, 0, ',', '.') }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.koperasi.approve-pinjaman', $pinjaman) }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="disetujui">
                                    <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" class="max-w-52 rounded-xl border border-slate-200 px-3 py-2 text-xs">
                                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('admin.koperasi.approve-pinjaman', $pinjaman) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="ditolak">
                                    <input type="hidden" name="rejected_reason" value="Pengajuan belum memenuhi syarat">
                                    <button class="rounded-xl bg-red-50 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-100">Tolak</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-8 text-center text-sm text-slate-500">Tidak ada pengajuan pinjaman pending.</p>
                @endforelse
            </div>
        </div>

        <div x-show="tab === 'angsuran'" x-cloak>
            <h2 class="mb-4 text-lg font-black text-slate-900">Angsuran Menunggu Verifikasi</h2>
            <div class="space-y-3">
                @forelse($pendingAngsurans as $angsuran)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-black text-slate-900">{{ $angsuran->pinjaman->user->name }}</p>
                                <p class="text-sm text-slate-500">{{ $angsuran->paid_at?->translatedFormat('d M Y') }} · Rp {{ number_format($angsuran->amount, 0, ',', '.') }}</p>
                                @if($angsuran->proof_path)
                                    <a href="{{ Storage::url($angsuran->proof_path) }}" target="_blank" class="mt-1 inline-block text-sm font-bold text-indigo-700">Lihat bukti angsuran</a>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.koperasi.approve-angsuran', $angsuran) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="terverifikasi">
                                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Verifikasi</button>
                                </form>
                                <form method="POST" action="{{ route('admin.koperasi.approve-angsuran', $angsuran) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="ditolak">
                                    <input type="hidden" name="rejected_reason" value="Bukti angsuran tidak valid">
                                    <button class="rounded-xl bg-red-50 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-100">Tolak</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-8 text-center text-sm text-slate-500">Tidak ada angsuran pending.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
