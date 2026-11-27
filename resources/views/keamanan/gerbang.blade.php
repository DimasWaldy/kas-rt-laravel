@extends('layouts.app')

@section('title', 'Gerbang RFID Masuk & Keluar')

@section('content')
@php
    $isOpen = $session && $session->expires_at->isFuture();
    $currentGateMode = $session?->gate_mode ?? 'auto';
@endphp
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3 text-sm font-semibold">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('keamanan.index') }}" class="text-emerald-700 hover:text-emerald-800">&larr; Keamanan RW</a>
            @if(auth()->user()?->hasPermission('manage-warga'))
                <span class="text-slate-300">|</span>
                <a href="{{ route('admin.warga.rfid.show') }}" class="text-slate-600 hover:text-emerald-700">Verifikasi KTP RFID</a>
            @endif
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
            <i class="fa-solid fa-id-card"></i>
            <span>{{ number_format($verifiedKtpCount) }} e-KTP Terverifikasi Siap Tap</span>
        </span>
    </div>

    <header class="rounded-3xl bg-emerald-800 p-6 text-white sm:p-8">
        <p class="text-sm text-emerald-100">KEAMANAN RW / GERBANG OTOMATIS RFID + SERVO</p>
        <h1 class="mt-2 text-2xl font-black sm:text-3xl">Kontrol Gerbang Masuk &amp; Keluar e-KTP</h1>
        <p class="mt-2 max-w-3xl text-sm text-emerald-100">
            Warga yang e-KTP-nya sudah terdaftar dan terverifikasi dapat langsung menempelkan kartu ke reader ESP32 untuk membuka palang servo gerbang secara otomatis dan mencatat log keluar-masuk real-time.
        </p>
    </header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    @if(session('rfid_token'))
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
            <h2 class="font-bold">Token Sesi Gerbang RFID — hanya ditampilkan sekali</h2>
            <p class="mt-1 text-sm">Opsional jika ESP32 menggunakan <code>Bearer {{ config('services.rfid.device_key', 'smartrw-rfid-device') }}</code>, karena Device Key permanen otomatis mengenali mode gerbang.</p>
            <code class="mt-3 block break-all select-all rounded-lg bg-white p-3 text-sm">{{ session('rfid_token') }}</code>
            <p class="mt-2 text-xs">Endpoint khusus gerbang: <code class="break-all">{{ route('rfid.gate.tap') }}</code></p>
        </section>
    @endif

    <div
        x-data="rfidMonitor"
        data-status-url="{{ route('keamanan.gerbang.status') }}"
        class="space-y-6"
    >
        <x-rfid-live-panel />

        {{-- Statistik Gerbang Hari Ini --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-xs dark:border-emerald-900/50 dark:bg-slate-900">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Masuk Hari Ini</p>
                <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white" x-text="state?.stats?.masuk_today ?? {{ $stats['masuk_today'] }}"></p>
                <p class="mt-1 text-xs text-slate-500">Tap diizinkan berstatus masuk</p>
            </div>
            <div class="rounded-2xl border border-sky-200 bg-white p-5 shadow-xs dark:border-sky-900/50 dark:bg-slate-900">
                <p class="text-xs font-bold uppercase tracking-wider text-sky-700 dark:text-sky-400">Keluar Hari Ini</p>
                <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white" x-text="state?.stats?.keluar_today ?? {{ $stats['keluar_today'] }}"></p>
                <p class="mt-1 text-xs text-slate-500">Tap diizinkan berstatus keluar</p>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-white p-5 shadow-xs dark:border-rose-900/50 dark:bg-slate-900">
                <p class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Ditolak Hari Ini</p>
                <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white" x-text="state?.stats?.ditolak_today ?? {{ $stats['ditolak_today'] }}"></p>
                <p class="mt-1 text-xs text-slate-500">Kartu belum terdaftar / luar wilayah</p>
            </div>
        </div>

        {{-- Kontrol Sesi & Arah Gerbang --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Status Reader Gerbang</h2>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                            :class="(state ? state.open : {{ $isOpen ? 'true' : 'false' }}) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                            x-text="(state ? state.open : {{ $isOpen ? 'true' : 'false' }}) ? 'Mode Gerbang Aktif' : 'Standby / Belum Diaktifkan'"
                        ></span>
                    </div>
                    <p
                        class="mt-1 text-sm text-slate-600 dark:text-slate-300"
                        x-text="state ? (state.open ? 'Aktif hingga ' + state.expires_at + ' WIB' : 'Klik Aktifkan Mode Gerbang agar /api/rfid/tap memproses gerbang masuk/keluar.') : 'Memuat status…'"
                    ></p>
                    <p
                        class="mt-1 text-xs text-slate-500"
                        x-text="state?.last_seen_at ? 'Tap gerbang terakhir: ' + state.last_seen_at + ' WIB' : 'Belum ada tap gerbang pada sesi ini.'"
                    ></p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('keamanan.gerbang.open') }}">
                        @csrf
                        <input type="hidden" name="gate_mode" :value="state?.gate_mode ?? '{{ $currentGateMode }}'">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-800">
                            <i class="fa-solid fa-power-off"></i>
                            <span x-text="(state ? state.open : {{ $isOpen ? 'true' : 'false' }}) ? 'Perpanjang Sesi 7 Hari' : 'Aktifkan Mode Gerbang'"></span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('keamanan.gerbang.close') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-50 dark:border-rose-800 dark:text-rose-300">
                            Nonaktifkan
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-5 border-t border-slate-100 pt-5 dark:border-slate-800">
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Pilih Arah Reader Gerbang</p>
                <div class="grid gap-3 md:grid-cols-3">
                    <form method="POST" action="{{ route('keamanan.gerbang.mode') }}">
                        @csrf
                        <input type="hidden" name="gate_mode" value="auto">
                        <button
                            type="submit"
                            class="w-full rounded-2xl border-2 p-4 text-left transition"
                            :class="(state?.gate_mode ?? '{{ $currentGateMode }}') === 'auto'
                                ? 'border-emerald-600 bg-emerald-50/70 text-emerald-950 dark:bg-emerald-950/30 dark:text-emerald-100'
                                : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200'"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-black">Otomatis Masuk ↔ Keluar</span>
                                <i class="fa-solid fa-right-left text-emerald-600"></i>
                            </div>
                            <p class="mt-1 text-xs opacity-75">Tap pertama tercatat MASUK, tap berikutnya otomatis KELUAR (1 alat dua arah).</p>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('keamanan.gerbang.mode') }}">
                        @csrf
                        <input type="hidden" name="gate_mode" value="masuk">
                        <button
                            type="submit"
                            class="w-full rounded-2xl border-2 p-4 text-left transition"
                            :class="(state?.gate_mode ?? '{{ $currentGateMode }}') === 'masuk'
                                ? 'border-emerald-600 bg-emerald-50/70 text-emerald-950 dark:bg-emerald-950/30 dark:text-emerald-100'
                                : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200'"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-black">Khusus Palang Masuk</span>
                                <i class="fa-solid fa-arrow-right-to-bracket text-emerald-600"></i>
                            </div>
                            <p class="mt-1 text-xs opacity-75">Semua tap valid dicatat MASUK dengan buzzer 2x bunyi cepat + buka servo.</p>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('keamanan.gerbang.mode') }}">
                        @csrf
                        <input type="hidden" name="gate_mode" value="keluar">
                        <button
                            type="submit"
                            class="w-full rounded-2xl border-2 p-4 text-left transition"
                            :class="(state?.gate_mode ?? '{{ $currentGateMode }}') === 'keluar'
                                ? 'border-sky-600 bg-sky-50/70 text-sky-950 dark:bg-sky-950/30 dark:text-sky-100'
                                : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200'"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-black">Khusus Palang Keluar</span>
                                <i class="fa-solid fa-arrow-right-from-bracket text-sky-600"></i>
                            </div>
                            <p class="mt-1 text-xs opacity-75">Semua tap valid dicatat KELUAR dengan buzzer 1x bunyi panjang + buka servo.</p>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        {{-- Riwayat Log Gerbang Real-time --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Riwayat Tap Gerbang Terbaru</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Diperbarui otomatis setiap 1,5 detik tanpa perlu refresh halaman (50 log terakhir).</p>
                </div>
                <p role="alert" class="text-xs font-semibold text-rose-600" x-text="error"></p>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead>
                        <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 pr-4">Waktu</th>
                            <th class="py-3 pr-4">Warga</th>
                            <th class="py-3 pr-4">Kartu UID</th>
                            <th class="py-3 pr-4">Arah</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" aria-live="polite">
                        <template x-if="!state?.logs || state.logs.length === 0">
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-slate-500">Belum ada aktivitas tap kartu di gerbang.</td>
                            </tr>
                        </template>
                        <template x-for="log in (state?.logs ?? [])" :key="log.id">
                            <tr>
                                <td class="whitespace-nowrap py-3 pr-4 font-mono text-xs text-slate-600 dark:text-slate-300" x-text="log.time + ' WIB'"></td>
                                <td class="py-3 pr-4">
                                    <p class="font-bold text-slate-900 dark:text-slate-100" x-text="log.user_name"></p>
                                    <p class="text-xs text-slate-500" x-show="log.rt_name" x-text="log.rt_name"></p>
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4 font-mono text-xs text-slate-600 dark:text-slate-300" x-text="log.uid_masked"></td>
                                <td class="whitespace-nowrap py-3 pr-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase"
                                        :class="log.arah === 'masuk'
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : (log.arah === 'keluar' ? 'bg-sky-100 text-sky-800' : 'bg-rose-100 text-rose-800')"
                                        x-text="log.arah"
                                    ></span>
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold"
                                        :class="log.status === 'diizinkan' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                        x-text="log.status === 'diizinkan' ? 'Servo Dibuka' : 'Ditolak'"
                                    ></span>
                                </td>
                                <td class="py-3 text-xs text-slate-600 dark:text-slate-300" x-text="log.keterangan"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
