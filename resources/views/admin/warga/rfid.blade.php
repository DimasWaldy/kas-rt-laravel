@extends('layouts.app')

@section('title', 'Verifikasi KTP RFID Warga')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap gap-4 text-sm font-semibold">
        <a href="{{ route('verifikasi-warga.index') }}" class="text-emerald-700">&larr; Verifikasi Warga</a>
        <span class="text-slate-300">|</span>
        <a href="{{ route('admin.warga.index') }}" class="text-slate-600 hover:text-emerald-700">Data Warga</a>
        @if(auth()->user()?->hasPermission('manage-fasilitas'))
            <span class="text-slate-300">|</span>
            <a href="{{ route('keamanan.gerbang.show') }}" class="text-emerald-700 hover:text-emerald-800">Gerbang RFID &rarr;</a>
        @endif
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

    {{-- Card Panduan Operasional Petugas (Bisa Dibuka/Tutup) --}}
    <section x-data="{ showGuide: false, guideTab: 'alur' }" class="rounded-3xl border border-emerald-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-4 p-5 sm:p-6 bg-emerald-50/60 dark:bg-emerald-950/20 border-b border-emerald-100 dark:border-emerald-900/40">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-xs">
                    <i class="fa-solid fa-book-bookmark text-base"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Panduan Petugas &amp; Setup Reader RFID</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Petunjuk langkah pendaftaran kartu warga, konfigurasi alat ESP32, dan uji coba simulasi.</p>
                </div>
            </div>
            <button type="button" @click="showGuide = !showGuide" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-white px-3.5 py-2 text-xs font-bold text-emerald-800 shadow-2xs transition hover:bg-emerald-50 dark:border-emerald-700 dark:bg-slate-800 dark:text-emerald-300">
                <span x-text="showGuide ? 'Sembunyikan Panduan' : 'Buka Panduan Petugas'">Buka Panduan Petugas</span>
                <i class="fa-solid text-[10px] transition-transform" :class="showGuide ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>

        <div x-show="showGuide" x-cloak x-transition class="p-5 sm:p-6 space-y-5">
            {{-- Tab navigasi panduan --}}
            <div class="flex border-b border-slate-200 dark:border-slate-700 gap-4 text-xs font-bold">
                <button type="button" @click="guideTab = 'alur'" class="pb-2.5 transition" :class="guideTab === 'alur' ? 'border-b-2 border-emerald-600 text-emerald-700 dark:text-emerald-400 font-extrabold' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400'">
                    <i class="fa-solid fa-list-check mr-1.5"></i> 1. Alur Verifikasi Warga
                </button>
                <button type="button" @click="guideTab = 'setup'" class="pb-2.5 transition" :class="guideTab === 'setup' ? 'border-b-2 border-emerald-600 text-emerald-700 dark:text-emerald-400 font-extrabold' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400'">
                    <i class="fa-solid fa-microchip mr-1.5"></i> 2. Setup Alat Reader ESP32
                </button>
                <button type="button" @click="guideTab = 'simulasi'" class="pb-2.5 transition" :class="guideTab === 'simulasi' ? 'border-b-2 border-emerald-600 text-emerald-700 dark:text-emerald-400 font-extrabold' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400'">
                    <i class="fa-solid fa-terminal mr-1.5"></i> 3. Uji Coba Tanpa Alat (Simulasi)
                </button>
            </div>

            {{-- Isi Tab 1: Alur --}}
            <div x-show="guideTab === 'alur'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                    <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 font-bold text-xs mb-1">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-700 text-white text-[10px]">1</span>
                        <span>Buka Sesi Reader</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Klik tombol <strong>Buka / perbarui sesi</strong> di bawah. Sesi alat akan aktif selama 6 jam untuk menerima penempelan kartu warga di pos RT.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                    <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 font-bold text-xs mb-1">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-700 text-white text-[10px]">2</span>
                        <span>Pilih Warga &amp; Enroll</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Cari nama warga di daftar tabel, lalu klik tombol hijau <strong>Aktifkan Tap KTP</strong>. Mode pendaftaran akan siaga selama 2 menit.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                    <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 font-bold text-xs mb-1">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-700 text-white text-[10px]">3</span>
                        <span>Warga Tap KTP</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Arahkan warga menempelkan fisik e-KTP ke sensor ESP32 selama 1 detik. Sistem otomatis mengaitkan UID kartu ke akun warga secara permanen.
                    </p>
                </div>
            </div>

            {{-- Isi Tab 2: Setup Hardware --}}
            <div x-show="guideTab === 'setup'" x-cloak class="space-y-3 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40 space-y-2">
                    <p class="font-bold text-slate-800 dark:text-slate-100 text-sm">Langkah Menghubungkan Reader ESP32 ke Jaringan RT:</p>
                    <ol class="list-decimal list-inside space-y-1.5 text-slate-600 dark:text-slate-300">
                        <li>Nyalakan reader ESP32 dengan menyambungkan kabel daya USB (charger HP atau adaptor 5V).</li>
                        <li>Hubungkan HP atau laptop petugas ke hotspot Wi-Fi bawaan alat: <code>SmartRW-RFID</code>.</li>
                        <li>Buka browser dan masuk ke portal konfigurasi Wi-Fi alat (otomatis muncul captive portal).</li>
                        <li>Pilih nama Wi-Fi pos RT / modem Anda, lalu masukkan kata sandi Wi-Fi pos RT.</li>
                        <li>Pada kolom <strong>Bearer Token</strong>, tempelkan token yang didapatkan saat Anda mengklik tombol <em>Buka Sesi</em> di halaman ini.</li>
                        <li>Simpan konfigurasi. Alat reader akan restart dan otomatis terhubung online ke server Smart RW!</li>
                    </ol>
                </div>
            </div>

            {{-- Isi Tab 3: Simulasi --}}
            <div x-show="guideTab === 'simulasi'" x-cloak class="space-y-3 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40 space-y-2">
                    <p class="font-bold text-slate-800 dark:text-slate-100 text-sm">Uji Coba Aktivasi Tanpa Alat Fisik (Simulasi API):</p>
                    <p>Jika alat fisik ESP32 belum terpasang, petugas dapat menguji langsung melalui terminal atau cURL:</p>
                    <div class="rounded-xl bg-slate-900 p-3 text-emerald-400 font-mono text-[11px] overflow-x-auto select-all">
                        curl -X POST {{ route('rfid.tap') }} \<br>
                        &nbsp;&nbsp;-H "Authorization: Bearer &lt;MASUKKAN_TOKEN_SESI&gt;" \<br>
                        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
                        &nbsp;&nbsp;-d '{"uid": "A1B2C3D4"}'
                    </div>
                    <p class="text-[11px] text-slate-500">Ganti <code>&lt;MASUKKAN_TOKEN_SESI&gt;</code> dengan token sesi yang muncul saat Anda mengklik tombol <em>Buka / perbarui sesi</em>.</p>
                </div>
            </div>
        </div>
    </section>

    <div x-data="rfidMonitor" data-status-url="{{ route('admin.warga.rfid.status') }}" class="space-y-6">
        <x-rfid-live-panel />
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
            <div x-cloak x-show="state?.notice" class="mt-4 rounded-xl border-2 border-rose-300 bg-rose-50 p-4 text-rose-950 dark:border-rose-800 dark:bg-rose-950/70 dark:text-rose-100">
                <div class="flex items-center gap-2 font-bold text-rose-800 dark:text-rose-200">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Peringatan Duplikasi Kartu RFID</span>
                </div>
                <p class="mt-1 text-sm text-rose-900 dark:text-rose-200" x-text="state?.notice"></p>
            </div>
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
            <p class="mt-1 text-xs text-slate-500">Diperbarui otomatis setiap 1,5 detik. Menampilkan 100 warga terbaru yang KTP-nya sudah terverifikasi RFID.</p>
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
                            <form
                                method="POST"
                                action="{{ route('admin.warga.rfid.revoke') }}"
                                x-data="{
                                    holding: false,
                                    timer: null,
                                    progress: 0,
                                    start() {
                                        this.holding = true;
                                        this.progress = 100;
                                        this.timer = setTimeout(() => {
                                            if (this.holding) {
                                                this.$el.submit();
                                            }
                                        }, 1800);
                                    },
                                    cancel() {
                                        if (this.holding) {
                                            this.holding = false;
                                            this.progress = 0;
                                            clearTimeout(this.timer);
                                        }
                                    }
                                }"
                            >
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <button
                                    type="button"
                                    @pointerdown="start()"
                                    @pointerup="cancel()"
                                    @pointerleave="cancel()"
                                    @keydown.enter.prevent="if (confirm('Cabut KTP / kartu RFID warga ini dari RT dan sistem?')) $el.closest('form').submit()"
                                    class="relative overflow-hidden rounded-xl border border-red-200 bg-white px-3.5 py-2 text-xs font-bold text-red-700 shadow-2xs transition hover:border-red-300 active:scale-97 select-none dark:border-red-800/60 dark:bg-slate-800 dark:text-red-400"
                                    title="Tahan tombol selama 2 detik untuk mencabut kartu"
                                >
                                    {{-- Hold Progress Fill Bar --}}
                                    <div
                                        class="absolute inset-0 bg-red-600/20 dark:bg-red-500/20 pointer-events-none"
                                        :class="holding ? 'hold-confirm-bar' : 'hold-confirm-bar resetting'"
                                        :style="'width: ' + progress + '%'"
                                    ></div>
                                    <span class="relative z-10 flex items-center gap-1.5">
                                        <i class="fa-solid fa-trash-can text-xs" :class="holding ? 'animate-pulse text-red-600' : ''"></i>
                                        <span x-text="holding ? 'Tahan untuk mencabut...' : 'Cabut KTP / Kartu'">Cabut KTP / Kartu</span>
                                    </span>
                                </button>
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

