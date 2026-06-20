@extends('layouts.app')

@section('title', 'Detail Shift Satpam')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <a href="{{ route('keamanan.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali</a>
        <h1 class="mt-3 text-2xl font-black text-slate-900">Shift {{ $shift->nama_satpam }}</h1>
        <p class="text-sm text-slate-500">{{ $shift->tanggal->format('d M Y') }} · {{ $shift->jam_mulai->format('H:i') }}-{{ $shift->jam_selesai->format('H:i') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="font-black text-slate-900">Log Patroli</h2>
            <div class="mt-4 space-y-3">
                @forelse($shift->logPatrolis->sortByDesc('waktu_patroli') as $log)
                    <div class="rounded-2xl border {{ $log->ada_kejadian ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }} p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="font-bold text-slate-800">{{ $log->waktu_patroli->format('d M Y H:i') }}</p>
                            <span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $log->ada_kejadian ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700' }}">{{ $log->ada_kejadian ? 'Ada kejadian' : 'Aman' }}</span>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $log->catatan ?: 'Tidak ada catatan khusus.' }}</p>
                        <p class="mt-2 text-xs text-slate-400">Dicatat oleh {{ $log->pencatat?->name ?? '-' }}</p>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada log patroli untuk shift ini.</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black text-slate-900">Info Shift</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-400">Satpam</dt><dd class="text-slate-700">{{ $shift->nama_satpam }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Kontak</dt><dd class="text-slate-700">{{ $shift->kontak_satpam ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Shift</dt><dd class="text-slate-700">{{ $shift->shift_label }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Pencatat</dt><dd class="text-slate-700">{{ $shift->pencatat?->name ?? '-' }}</dd></div>
                </dl>
            </div>

            <form method="POST" action="{{ route('keamanan.patroli.store', $shift) }}" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                <h2 class="font-black text-slate-900">Tambah Log Patroli</h2>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Waktu patroli</label>
                    <input type="datetime-local" name="waktu_patroli" value="{{ old('waktu_patroli', now()->format('Y-m-d\\TH:i')) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                    @error('waktu_patroli')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Catatan</label>
                    <textarea name="catatan" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ old('catatan') }}</textarea>
                    @error('catatan')<p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="ada_kejadian" value="1" class="rounded border-slate-300 text-emerald-700" @checked(old('ada_kejadian'))>
                    Ada kejadian khusus
                </label>
                <button class="w-full rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Simpan Log</button>
            </form>
        </aside>
    </div>
</div>
@endsection
