@extends('layouts.app')

@section('title', $pemilihan->title)

@section('content')
<div class="mx-auto max-w-7xl space-y-8" x-data="bilikSuara()">
    {{-- Header Banner --}}
    <div class="relative overflow-hidden rounded-3xl bg-linear-to-r from-purple-900 via-indigo-900 to-slate-900 p-6 text-white shadow-lg sm:p-8">
        <div class="relative z-10 max-w-3xl space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-500/20 px-3 py-1 text-xs font-black text-purple-200 backdrop-blur-xs">
                    <i class="fa-solid fa-check-to-slot"></i> Bilik Suara Digital
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200">
                    <i class="fa-solid fa-map-pin"></i>
                    {{ $pemilihan->scope_type === 'rw' ? 'Wilayah RW' : ($pemilihan->rt ? 'Wilayah RT '.$pemilihan->rt->name : 'Wilayah RT') }}
                </span>
                @if($pemilihan->isOngoing())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-black text-emerald-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Pemilihan Sedang Berlangsung
                    </span>
                @elseif($pemilihan->isFinished())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-500/30 px-3 py-1 text-xs font-bold text-slate-300">
                        <i class="fa-solid fa-flag-checkered"></i> Pemilihan Selesai (Hasil Resmi)
                    </span>
                @endif
            </div>

            <h1 class="text-2xl font-black tracking-tight text-white sm:text-4xl">{{ $pemilihan->title }}</h1>
            
            @if($pemilihan->description)
                <p class="text-xs leading-relaxed text-slate-300 sm:text-sm">{{ $pemilihan->description }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-4 pt-2 text-xs text-purple-200">
                <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-check text-purple-400"></i>
                    <span>Mulai: <strong>{{ $pemilihan->start_date->translatedFormat('d M Y H:i') }} WIB</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-xmark text-purple-400"></i>
                    <span>Selesai: <strong>{{ $pemilihan->end_date->translatedFormat('d M Y H:i') }} WIB</strong></span>
                </div>
            </div>
        </div>

        {{-- Asas Luber Jurdil Watermark / Badge --}}
        <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-white/10 pt-4 text-[11px] font-bold text-purple-200">
            <span class="text-purple-300"><i class="fa-solid fa-shield-halved mr-1"></i> Asas Pemilu:</span>
            <span class="rounded bg-white/10 px-2 py-0.5">Langsung</span>
            <span class="rounded bg-white/10 px-2 py-0.5">Umum</span>
            <span class="rounded bg-white/10 px-2 py-0.5">Bebas</span>
            <span class="rounded bg-white/10 px-2 py-0.5">Rahasia (Secret Ballot)</span>
            <span class="rounded bg-white/10 px-2 py-0.5">Jujur</span>
            <span class="rounded bg-white/10 px-2 py-0.5">Adil</span>
        </div>
    </div>

    {{-- Status Alert Box --}}
    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-950 shadow-xs dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                <i class="fa-solid fa-circle-check text-lg"></i>
            </div>
            <div>
                <p class="font-bold">Pencoblosan Berhasil!</p>
                <p class="text-xs text-emerald-800 dark:text-emerald-300">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-300 bg-rose-50 p-4 text-rose-950 shadow-xs dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-200 text-rose-800 dark:bg-rose-900 dark:text-rose-200">
                <i class="fa-solid fa-circle-xmark text-lg"></i>
            </div>
            <div>
                <p class="font-bold">Pengajuan Gagal</p>
                <p class="text-xs text-rose-800 dark:text-rose-300">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if($hasVoted)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-5 dark:border-emerald-900/60 dark:bg-emerald-950/30">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                    <i class="fa-solid fa-envelope-circle-check text-xl"></i>
                </div>
                <div>
                    <h2 class="font-black text-emerald-950 dark:text-emerald-200">Suara Anda Telah Tercatat Sah</h2>
                    <p class="mt-1 text-xs leading-relaxed text-emerald-800 dark:text-emerald-300">
                        Terima kasih atas partisipasi aktif Anda. Surat suara Anda telah dimasukkan ke kotak suara digital secara <strong>terenkripsi dan anonim</strong> tanpa korelasi identitas pribadi. Anda dapat memantau pergerakan Quick Count di bawah ini.
                    </p>
                </div>
            </div>
        </div>
    @elseif(! $eligibility['eligible'] && ($eligibility['needs_rfid'] ?? false))
        <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-5 dark:border-amber-800/60 dark:bg-amber-950/40">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-200/80 text-amber-900 dark:bg-amber-900 dark:text-amber-300">
                        <i class="fa-solid fa-lock text-xl"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-amber-950 dark:text-amber-200">Hak Suara Terkunci (Wajib Verifikasi e-KTP)</h2>
                        <p class="mt-1 text-xs leading-relaxed text-amber-900/90 dark:text-amber-300/90">
                            Untuk menjaga pemilu warga yang jujur, transparan, dan bebas akun manipulasi, Anda wajib memverifikasi kepemilikan fisik e-KTP melalui <strong>Tap NFC Smartphone</strong> atau <strong>Mesin Reader RT</strong> sebelum mencoblos.
                        </p>
                    </div>
                </div>
                <a href="{{ route('warga.rfid.show') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-700 px-5 py-3 text-xs font-bold text-white shadow-xs transition hover:bg-amber-800">
                    <i class="fa-solid fa-wifi"></i>
                    <span>Tap e-KTP Sekarang (NFC / RFID)</span>
                </a>
            </div>
        </div>
    @elseif(! $eligibility['eligible'])
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-info text-base text-slate-400"></i>
                <p>{{ $eligibility['reason'] }}</p>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-purple-200 bg-purple-50/80 p-4 dark:border-purple-900/50 dark:bg-purple-950/30">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                    <i class="fa-solid fa-hand-pointer text-base"></i>
                </div>
                <div>
                    <p class="font-bold text-purple-950 dark:text-purple-200">Bilik Suara Siap Digunakan</p>
                    <p class="text-xs text-purple-800 dark:text-purple-300">Silakan pilih salah satu pasangan calon di bawah ini. Pastikan Anda telah meninjau visi dan misi sebelum mencoblos.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- KANDIDAT PASLON / BILIK SUARA --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-slate-100 sm:text-2xl">Daftar Pasangan Calon</h2>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Pelajari profil, visi, dan misi tiap kandidat sebelum menentukan pilihan Anda.</p>
            </div>
            <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                {{ $pemilihan->kandidats->count() }} Paslon
            </span>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($pemilihan->kandidats as $kandidat)
                <div class="flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900" x-data="{ expanded: false }">
                    <div class="space-y-4">
                        {{-- Header Nomor Urut & Foto --}}
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-purple-700 text-xl font-black text-white shadow-xs">
                                {{ sprintf('%02d', $kandidat->nomor_urut) }}
                            </div>

                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-800 dark:bg-slate-800">
                                @if($kandidat->foto_path)
                                    <img src="{{ asset('storage/'.$kandidat->foto_path) }}" alt="{{ $kandidat->nama_calon }}" class="h-full w-full object-cover">
                                @else
                                    <i class="fa-solid fa-user-tie text-3xl text-purple-400"></i>
                                @endif
                            </div>
                        </div>

                        {{-- Nama & Slogan --}}
                        <div>
                            <span class="text-[11px] font-black uppercase tracking-wider text-purple-600 dark:text-purple-400">Calon Ketua & Wakil</span>
                            <h3 class="text-lg font-black text-slate-900 dark:text-slate-100">{{ $kandidat->nama_calon }}</h3>
                            @if($kandidat->nama_wakil)
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-400">& {{ $kandidat->nama_wakil }}</p>
                            @endif

                            @if($kandidat->slogan)
                                <p class="mt-2 rounded-xl bg-purple-50/80 px-3 py-1.5 text-xs font-semibold italic text-purple-900 dark:bg-purple-950/40 dark:text-purple-300">
                                    "{{ $kandidat->slogan }}"
                                </p>
                            @endif
                        </div>

                        {{-- Visi & Misi Toggle --}}
                        @if($kandidat->visi || $kandidat->misi)
                            <div class="border-t border-slate-100 pt-3 dark:border-slate-800">
                                <button type="button" @click="expanded = !expanded" class="flex w-full items-center justify-between text-xs font-bold text-slate-600 hover:text-purple-700 dark:text-slate-400 dark:hover:text-purple-300">
                                    <span>Visi & Misi Kandidat</span>
                                    <i class="fa-solid fa-chevron-down text-2xs transition-transform" :class="expanded ? 'rotate-180' : ''"></i>
                                </button>

                                <div x-show="expanded" x-collapse class="mt-3 space-y-3 rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
                                    @if($kandidat->visi)
                                        <div>
                                            <p class="font-black text-purple-800 dark:text-purple-300">Visi:</p>
                                            <p class="mt-0.5 leading-relaxed text-slate-600 dark:text-slate-400">{{ $kandidat->visi }}</p>
                                        </div>
                                    @endif
                                    @if($kandidat->misi)
                                        <div>
                                            <p class="font-black text-purple-800 dark:text-purple-300">Misi:</p>
                                            <p class="mt-0.5 whitespace-pre-line leading-relaxed text-slate-600 dark:text-slate-400">{{ $kandidat->misi }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Action Button Coblos --}}
                    <div class="mt-6 border-t border-slate-100 pt-4 dark:border-slate-800">
                        @if($eligibility['eligible'] && ! $hasVoted)
                            <button
                                type="button"
                                @click="openConfirmModal({{ $kandidat->id }}, {{ $kandidat->nomor_urut }}, '{{ addslashes($kandidat->nama_calon) }}')"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-purple-700 px-4 py-3 text-sm font-black text-white shadow-xs transition hover:bg-purple-800 active:scale-98"
                            >
                                <i class="fa-solid fa-check-to-slot"></i>
                                <span>Coblos Paslon {{ $kandidat->nomor_urut }}</span>
                            </button>
                        @elseif($hasVoted)
                            <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-100 px-4 py-3 text-xs font-bold text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                <i class="fa-solid fa-check"></i> Sudah Menggunakan Suara
                            </button>
                        @elseif($eligibility['needs_rfid'] ?? false)
                            <a href="{{ route('warga.rfid.show') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-amber-600/90 px-4 py-3 text-xs font-bold text-white transition hover:bg-amber-700">
                                <i class="fa-solid fa-lock"></i> Tap e-KTP untuk Membuka
                            </a>
                        @else
                            <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-100 px-4 py-3 text-xs font-bold text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                <i class="fa-solid fa-lock"></i> Bilik Suara Ditutup
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- QUICK COUNT REAL-TIME SECTION --}}
    <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:p-8" id="quick-count-section">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-purple-100 px-2 py-0.5 text-xs font-black text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                        <i class="fa-solid fa-chart-simple"></i> Live Quick Count
                    </span>
                    <span class="text-xs font-semibold text-slate-400">Pembaruan Otomatis</span>
                </div>
                <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-slate-100 sm:text-2xl">Perolehan Suara & Partisipasi Pemilih</h2>
            </div>

            <button type="button" @click="fetchQuickCount()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <i class="fa-solid fa-rotate-right" :class="isRefreshing ? 'animate-spin' : ''"></i>
                <span>Refresh Suara</span>
            </button>
        </div>

        {{-- 3 KPI Cards --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Suara Masuk</p>
                <p class="mt-1 text-2xl font-black text-purple-700 dark:text-purple-400" x-text="quickData.total_suara + ' Suara'">{{ $totalSuara }} Suara</p>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">DPT (Daftar Pemilih Tetap)</p>
                <p class="mt-1 text-2xl font-black text-slate-900 dark:text-slate-100">{{ $totalDpt }} Warga</p>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Tingkat Partisipasi</p>
                <p class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $partisipasi }}%</p>
            </div>
        </div>

        {{-- Progress Bars Per Paslon --}}
        <div class="mt-8 space-y-5">
            <template x-for="item in quickData.kandidats" :key="item.id">
                <div class="space-y-2 rounded-2xl border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-700 text-xs font-black text-white" x-text="String(item.nomor_urut).padStart(2, '0')"></span>
                            <div>
                                <span class="font-bold text-slate-900 dark:text-slate-100" x-text="item.nama_calon"></span>
                                <span x-show="item.nama_wakil" class="text-xs text-slate-500" x-text="' & ' + item.nama_wakil"></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span x-show="item.is_leading && quickData.total_suara > 0" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-2xs font-black text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                <i class="fa-solid fa-crown text-amber-500"></i> Unggul
                            </span>
                            <span class="text-xs font-semibold text-slate-500" x-text="item.total_suara + ' suara'"></span>
                            <span class="text-base font-black text-purple-700 dark:text-purple-400" x-text="item.persentase + '%'"></span>
                        </div>
                    </div>

                    {{-- Bar --}}
                    <div class="h-3.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                        <div
                            class="h-full rounded-full bg-linear-to-r from-purple-600 to-indigo-600 transition-all duration-700 ease-out"
                            :style="'width: ' + item.persentase + '%'"
                        ></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- MODAL KONFIRMASI PENCOBLOSAN (ALPINE.JS) --}}
    <div
        x-show="showModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
        @keydown.escape.window="showModal = false"
    >
        <div
            x-show="showModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.away="showModal = false"
            class="w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900"
        >
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                    <i class="fa-solid fa-check-to-slot text-3xl"></i>
                </div>
                <h3 class="mt-4 text-xl font-black text-slate-900 dark:text-slate-100">Konfirmasi Pilihan Suara</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pilihan Anda dijamin rahasia (secret ballot) dan tidak dapat diubah setelah dicoblos.</p>
            </div>

            <div class="my-5 rounded-2xl border border-purple-200 bg-purple-50 p-4 text-center dark:border-purple-900 dark:bg-purple-950/40">
                <span class="text-xs font-black uppercase text-purple-600 dark:text-purple-400">Pasangan Calon No. <span x-text="selectedUrut"></span></span>
                <p class="mt-1 text-base font-black text-purple-950 dark:text-purple-200" x-text="selectedName"></p>
            </div>

            <form method="POST" action="{{ route('evoting.vote', $pemilihan->slug) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="kandidat_id" :value="selectedId">

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-purple-700 py-3 text-sm font-black text-white shadow-xs transition hover:bg-purple-800">
                    <i class="fa-solid fa-check"></i>
                    <span>Ya, Coblos Paslon Ini</span>
                </button>

                <button type="button" @click="showModal = false" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-100 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Batal & Tinjau Ulang
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function bilikSuara() {
    return {
        showModal: false,
        selectedId: null,
        selectedUrut: null,
        selectedName: '',
        isRefreshing: false,
        quickData: {
            total_suara: {{ $totalSuara }},
            kandidats: @json($quickCount),
        },
        openConfirmModal(id, urut, name) {
            this.selectedId = id;
            this.selectedUrut = urut;
            this.selectedName = name;
            this.showModal = true;
        },
        async fetchQuickCount() {
            this.isRefreshing = true;
            try {
                const response = await fetch('{{ route("evoting.quick-count", $pemilihan->slug) }}');
                if (response.ok) {
                    const data = await response.json();
                    this.quickData.total_suara = data.total_suara;
                    this.quickData.kandidats = data.kandidats;
                }
            } catch (e) {
                console.error(e);
            } finally {
                setTimeout(() => { this.isRefreshing = false; }, 400);
            }
        },
        init() {
            // Auto refresh quick count every 15s if ongoing
            @if($pemilihan->isOngoing())
                setInterval(() => {
                    this.fetchQuickCount();
                }, 15000);
            @endif
        }
    }
}
</script>
@endsection
