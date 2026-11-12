@extends('layouts.app')

@section('title', 'Verifikasi KTP RFID Warga')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap gap-4 text-sm font-semibold">
        <a href="{{ route('verifikasi-warga.index') }}" class="text-emerald-700">&larr; Verifikasi Warga</a>
        <span class="text-slate-300">|</span>
        <a href="{{ route('admin.warga.index') }}" class="text-slate-600 hover:text-emerald-700">Data Warga</a>
    </div>

    <header class="rounded-3xl bg-emerald-800 p-6 text-white sm:p-8">
        <p class="text-sm text-emerald-100">RT / ADMIN / VERIFIKASI KTP RFID</p>
        <h1 class="mt-2 text-2xl font-bold">Verifikasi KTP Warga via RFID</h1>
        <p class="mt-2 text-sm text-emerald-100">Setiap warga yang akunnya sudah disetujui wajib tap KTP lewat reader ESP32 agar UID KTP-nya terdaftar di RT dan seluruh sistem (berlaku untuk Daftar Koperasi &amp; Absensi Kegiatan).</p>
    </header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    @if(session('rfid_token'))
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
            <h2 class="font-bold">Token alat verifikasi KTP — hanya ditampilkan sekali</h2>
            <p class="mt-2 text-sm">Salin ke konfigurasi ESP32 (portal <code>SmartRW-RFID</code>) sebagai Bearer token. Membuka sesi baru membatalkan token lama.</p>
            <code class="mt-3 block break-all select-all rounded-lg bg-white p-3 text-sm">{{ session('rfid_token') }}</code>
            <p class="mt-3 text-sm">Endpoint POST: <code class="break-all">{{ route('rfid.tap') }}</code></p>
        </section>
    @endif

    <div x-data="rfidMonitor" data-status-url="{{ route('admin.warga.rfid.status') }}" class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Sesi alat verifikasi KTP</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300" x-text="state ? (state.open ? 'Terbuka hingga ' + state.expires_at + ' WIB' : 'Sesi ditutup / belum dibuka') : 'Memuat status…'"></p>
                    <p class="mt-1 text-xs text-slate-500" x-text="state?.last_seen_at ? 'Tap berhasil terakhir: ' + state.last_seen_at + ' WIB' : 'Belum ada tap berhasil pada sesi ini.'"></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.warga.rfid.open') }}" x-on:submit="if (!confirm('Buka sesi 6 jam dan batalkan token lama?')) $event.preventDefault()">
                        @csrf <button class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-bold text-white">Buka / perbarui sesi</button>
                    </form>
                    <form method="POST" action="{{ route('admin.warga.rfid.close') }}">
                        @csrf <button class="rounded-xl border border-red-300 px-4 py-2 text-sm font-bold text-red-700 dark:text-red-300">Tutup sesi</button>
                    </form>
                </div>
            </div>
            <p class="mt-4 text-sm text-slate-500">Warga yang belum pernah punya kartu RFID: klik <strong>Aktifkan Tap KTP</strong> pada daftar warga di bawah, lalu tap KTP 1 kali ke reader. Jika warga sudah punya kartu dari kegiatan, cukup tap langsung ke alat saat sesi ini terbuka.</p>
            <div x-cloak x-show="state?.enrollment" class="mt-4 rounded-xl bg-amber-50 p-4 text-amber-950">
                <p class="font-semibold" x-text="state?.enrollment ? 'Menunggu tap KTP: ' + state.enrollment.name : ''"></p>
                <p class="text-sm" x-text="state?.enrollment?.expired ? 'Waktu habis. Batalkan atau klik Aktifkan Tap KTP lagi.' : 'Cocokkan KTP fisik dengan data akun warga, lalu tempel KTP ke reader ESP32.'"></p>
                <form method="POST" action="{{ route('admin.warga.rfid.cancel-enrollment') }}" class="mt-2">
                    @csrf <button class="text-sm font-bold underline">Batalkan mode tap</button>
                </form>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">KTP warga terdaftar di RT &amp; Sistem</h2>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800" x-text="(state?.count ?? 0) + ' KTP terdaftar'"></span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Diperbarui otomatis setiap 5 detik. Menampilkan 100 warga terbaru yang KTP-nya sudah terverifikasi RFID.</p>
            <p role="alert" class="mt-3 text-sm text-red-600" x-text="error"></p>
            <p x-show="state && state.count === 0" class="py-6 text-sm text-slate-500">Belum ada warga yang KTP-nya diverifikasi lewat RFID.</p>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-700" aria-live="polite">
                <template x-for="row in (state?.attendees ?? [])" :key="row.id">
                    <div class="flex flex-wrap justify-between gap-2 py-3">
                        <p class="font-semibold text-slate-800 dark:text-slate-100" x-text="row.name"></p>
                        <p class="text-sm text-slate-500" x-text="row.time + ' WIB'"></p>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Warga terverifikasi akun dalam wilayah</h2>
        <p class="mt-1 text-sm text-slate-500">Hanya menampilkan warga dengan akun aktif dan sudah terverifikasi oleh Admin/Superadmin/RT.</p>
        <form method="GET" class="mt-4 flex gap-2">
            <label for="warga-rfid-search" class="sr-only">Cari nama warga</label>
            <input id="warga-rfid-search" name="q" value="{{ $search }}" maxlength="100" placeholder="Cari nama warga…" class="min-w-0 flex-1 rounded-xl border-slate-300 dark:bg-slate-800 dark:text-white">
            <button class="rounded-xl bg-slate-700 px-4 py-2 text-sm font-bold text-white">Cari</button>
        </form>
        <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-700">
            @forelse($users as $user)
                <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div>
                        <p class="font-semibold text-slate-800 dark:text-slate-100">
                            {{ $user->name }} <span class="text-xs text-slate-500">#{{ $user->id }}</span>
                            @if($user->rt?->name)
                                <span class="ml-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $user->rt->name }}</span>
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            @if($user->rfidCard?->ktp_verified_at)
                                <span class="font-semibold text-emerald-700">KTP terdaftar di {{ $user->rfidCard->verifiedRt?->name ?? $user->rt?->name ?? 'RT' }} &amp; Sistem</span>
                            @elseif($user->rfidCard)
                                Kartu terdaftar di modul lain (belum verifikasi KTP RT — minta warga tap KTP saat sesi aktif)
                            @else
                                Belum verifikasi KTP via RFID
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($user->rfidCard)
                            <form method="POST" action="{{ route('admin.warga.rfid.revoke') }}" x-data x-on:submit="if (!confirm('Cabut KTP / kartu RFID warga ini dari RT dan sistem?')) $event.preventDefault()">
                                @csrf <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 dark:text-red-300">Cabut KTP / Kartu</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.warga.rfid.enroll') }}">
                                @csrf <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">Aktifkan Tap KTP</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-6 text-sm text-slate-500">Tidak ada warga terverifikasi yang sesuai pencarian.</p>
            @endforelse
        </div>
        {{ $users->links() }}
    </section>
</div>
@endsection

