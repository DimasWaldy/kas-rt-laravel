@extends('layouts.app')

@section('title', 'Keamanan RW')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Jadwal satpam dan log patroli</p>
            <h1 class="text-2xl font-black text-slate-900">Keamanan RW</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau shift satpam, log patroli, dan catatan kejadian lingkungan.</p>
        </div>
        <a href="{{ route('keamanan.shift.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
            <i class="fa-solid fa-plus"></i> Tambah Shift
        </a>
    </div>

    <form method="GET" action="{{ route('keamanan.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_1fr_auto]">
        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Tanggal mulai</label>
            <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai->toDateString() }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Tanggal selesai</label>
            <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai->toDateString() }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
        </div>
        <div class="flex items-end">
            <button class="w-full rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
        </div>
    </form>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="font-black text-slate-900">Jadwal Shift</h2>
            <div class="mt-4 space-y-3">
                @forelse($shifts as $shift)
                    <a href="{{ route('keamanan.shift.show', $shift) }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-emerald-300 hover:bg-emerald-50/40 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-black text-slate-900">{{ $shift->nama_satpam }}</p>
                            <p class="text-sm text-slate-500">{{ $shift->tanggal->format('d M Y') }} · {{ $shift->jam_mulai->format('H:i') }}-{{ $shift->jam_selesai->format('H:i') }} · {{ $shift->shift_label }}</p>
                        </div>
                        <div class="text-sm font-bold text-emerald-700">{{ $shift->log_patrolis_count ?? $shift->logPatrolis->count() }} log patroli</div>
                    </a>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada jadwal shift pada rentang tanggal ini.</p>
                @endforelse
            </div>
        </section>

        <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-black text-slate-900">Log Patroli Terbaru</h2>
            <div class="mt-4 space-y-3">
                @forelse($logs->take(8) as $log)
                    <div class="rounded-2xl border {{ $log->ada_kejadian ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }} p-4">
                        <p class="text-sm font-bold text-slate-800">{{ $log->waktu_patroli->format('d M Y H:i') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $log->jadwalShift?->nama_satpam ?? '-' }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $log->catatan ?: 'Tidak ada catatan khusus.' }}</p>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada log patroli.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
@endsection
