@extends('layouts.app')

@section('title', 'Detail Berita Duka: ' . $claim->deceased_name)

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    {{-- Breadcrumb --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
            <a href="{{ route('rukem.index') }}" class="hover:text-rose-600 dark:hover:text-rose-400">Rukun Kematian</a>
            <i class="fa-solid fa-chevron-right text-3xs"></i>
            <span class="text-slate-800 dark:text-slate-200">Detail Laporan Duka</span>
        </div>

        @if($canManage)
            <a href="{{ route('admin.rukem.show-claim', $claim) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 shadow-2xs hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                <i class="fa-solid fa-pen-to-square"></i> Panel Pengurus
            </a>
        @endif
    </div>

    {{-- Main Status Header Card --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 p-6 dark:border-slate-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                        <i class="fa-solid fa-dove text-2xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-2xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                <i class="fa-solid fa-location-dot text-rose-500"></i> {{ $claim->rt ? 'RT ' . $claim->rt->name : 'RT Lingkungan' }}
                            </span>
                            <span class="text-xs text-slate-400">•</span>
                            <span class="text-xs text-slate-500">Pelapor: <strong>{{ $claim->user?->name }}</strong></span>
                        </div>
                        <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-slate-100">Alm. {{ $claim->deceased_name }}</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Wafat pada hari {{ \Carbon\Carbon::parse($claim->death_date)->translatedFormat('l, d F Y') }}
                        </p>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="shrink-0">
                    @if($claim->status === 'submitted')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-blue-100 px-3 py-1.5 text-xs font-bold text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                            <i class="fa-solid fa-paper-plane"></i> Laporan Masuk
                        </span>
                    @elseif($claim->status === 'verified')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300">
                            <i class="fa-solid fa-shield-check"></i> Terverifikasi Lingkungan
                        </span>
                    @elseif($claim->status === 'approved')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                            <i class="fa-solid fa-check"></i> Santunan Disetujui
                        </span>
                    @elseif($claim->status === 'done')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i> Santunan Selesai Disalurkan
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                            <i class="fa-solid fa-circle-xmark"></i> Ditolak
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Progress Timeline Stepper --}}
        @if($claim->status !== 'rejected')
            <div class="bg-slate-50/60 px-6 py-6 dark:bg-slate-800/40">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    {{-- Step 1 --}}
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-2xs">
                            <i class="fa-solid fa-check text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100">1. Laporan Masuk</p>
                            <p class="text-3xs text-slate-500">{{ $claim->created_at->translatedFormat('d M H:i') }}</p>
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ in_array($claim->status, ['verified', 'approved', 'done']) ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400' }} shadow-2xs">
                            @if(in_array($claim->status, ['verified', 'approved', 'done']))
                                <i class="fa-solid fa-check text-xs"></i>
                            @else
                                <span class="text-xs font-bold">2</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100">2. Verifikasi Data</p>
                            <p class="text-3xs text-slate-500">{{ $claim->verified_at ? $claim->verified_at->translatedFormat('d M H:i') : 'Menunggu' }}</p>
                        </div>
                    </div>

                    {{-- Step 3 --}}
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ in_array($claim->status, ['approved', 'done']) ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400' }} shadow-2xs">
                            @if(in_array($claim->status, ['approved', 'done']))
                                <i class="fa-solid fa-check text-xs"></i>
                            @else
                                <span class="text-xs font-bold">3</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100">3. Persetujuan</p>
                            <p class="text-3xs text-slate-500">{{ $claim->approved_at ? $claim->approved_at->translatedFormat('d M H:i') : 'Menunggu' }}</p>
                        </div>
                    </div>

                    {{-- Step 4 --}}
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $claim->status === 'done' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400' }} shadow-2xs">
                            @if($claim->status === 'done')
                                <i class="fa-solid fa-check text-xs"></i>
                            @else
                                <span class="text-xs font-bold">4</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100">4. Penyerahan Dana</p>
                            <p class="text-3xs text-slate-500">{{ $claim->bantuan ? \Carbon\Carbon::parse($claim->bantuan->given_at)->translatedFormat('d M Y') : 'Menunggu' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="border-t border-rose-200 bg-rose-50/80 p-4 dark:border-rose-900/60 dark:bg-rose-950/40">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    <div>
                        <p class="text-xs font-bold text-rose-900 dark:text-rose-200">Pengajuan Laporan Duka Ditolak</p>
                        <p class="mt-0.5 text-xs text-rose-700 dark:text-rose-300">Alasan penolakan: {{ $claim->rejected_reason ?? 'Informasi atau berkas tidak sesuai.' }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Card 1: Informasi Duka & Pemakaman --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100">
                <i class="fa-solid fa-monument text-rose-600"></i>
                Informasi Pemakaman & Rumah Duka
            </h2>

            <dl class="mt-4 divide-y divide-slate-100 text-xs text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Nama Almarhum</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">{{ $claim->deceased_name }}</dd>
                </div>
                @if($claim->nik_deceased)
                    <div class="flex justify-between py-2.5">
                        <dt class="font-semibold text-slate-500">NIK Almarhum</dt>
                        <dd class="font-mono text-slate-700 dark:text-slate-300">{{ $claim->nik_deceased }}</dd>
                    </div>
                @endif
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Hubungan Pelapor</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">{{ ucfirst(str_replace('_', ' ', $claim->relationship)) }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Tanggal Wafat</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">{{ \Carbon\Carbon::parse($claim->death_date)->translatedFormat('l, d F Y') }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Waktu Pemakaman</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">
                        {{ $claim->funeral_date ? \Carbon\Carbon::parse($claim->funeral_date)->translatedFormat('l, d F Y - H:i') . ' WIB' : 'Menunggu kepastian' }}
                    </dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Lokasi TPU / Makam</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">{{ $claim->cemetery_location ?? 'Belum ditentukan' }}</dd>
                </div>
                @if($claim->description)
                    <div class="py-2.5">
                        <dt class="font-semibold text-slate-500">Alamat Duka / Keterangan</dt>
                        <dd class="mt-1 rounded-xl bg-slate-50 p-3 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $claim->description }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Surat Keterangan Kematian</dt>
                    <dd>
                        @if($claim->proof_path)
                            <a href="{{ route('rukem.claim-bukti', $claim) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-rose-600 hover:underline dark:text-rose-400">
                                <i class="fa-solid fa-file-pdf"></i> Lihat Berkas Surat
                            </a>
                        @else
                            <span class="text-slate-400">Belum diunggah</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Card 2: Santunan & Bantuan Kas Rukem --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100">
                <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i>
                Status Santunan Kas Rukun Kematian
            </h2>

            @if($claim->bantuan)
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Nominal Santunan Duka</span>
                        <span class="text-lg font-black text-emerald-700 dark:text-emerald-400">
                            Rp {{ number_format($claim->bantuan->amount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <dl class="mt-4 divide-y divide-slate-100 text-xs text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                    <div class="flex justify-between py-2.5">
                        <dt class="font-semibold text-slate-500">Penerima / Ahli Waris</dt>
                        <dd class="font-bold text-slate-900 dark:text-slate-100">{{ $claim->bantuan->recipient_name }}</dd>
                    </div>
                    @if($claim->bantuan->recipient_phone)
                        <div class="flex justify-between py-2.5">
                            <dt class="font-semibold text-slate-500">Kontak Penerima</dt>
                            <dd class="font-mono text-slate-700 dark:text-slate-300">{{ $claim->bantuan->recipient_phone }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between py-2.5">
                        <dt class="font-semibold text-slate-500">Tanggal Diserahkan</dt>
                        <dd class="font-bold text-slate-900 dark:text-slate-100">{{ \Carbon\Carbon::parse($claim->bantuan->given_at)->translatedFormat('d F Y') }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="font-semibold text-slate-500">Petugas Penyerah</dt>
                        <dd class="font-bold text-slate-900 dark:text-slate-100">{{ $claim->bantuan->giver?->name ?? 'Pengurus RT/RW' }}</dd>
                    </div>
                    @if($claim->bantuan->proof_path)
                        <div class="flex items-center justify-between py-2.5">
                            <dt class="font-semibold text-slate-500">Kwitansi / Tanda Terima</dt>
                            <dd>
                                <a href="{{ route('admin.rukem.bantuan-bukti', $claim->bantuan) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-emerald-700 hover:underline dark:text-emerald-400">
                                    <i class="fa-solid fa-receipt"></i> Unduh / Lihat Kwitansi
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if($claim->bantuan->note)
                        <div class="py-2.5">
                            <dt class="font-semibold text-slate-500">Catatan Penyerahan</dt>
                            <dd class="mt-1 text-slate-700 dark:text-slate-300">{{ $claim->bantuan->note }}</dd>
                        </div>
                    @endif
                </dl>
            @else
                <div class="mt-6 rounded-2xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                        <i class="fa-solid fa-hourglass-half text-xl"></i>
                    </div>
                    <h3 class="mt-3 text-xs font-bold text-slate-800 dark:text-slate-200">Santunan Dalam Proses</h3>
                    <p class="mt-1 text-2xs text-slate-500">
                        @if($claim->status === 'submitted')
                            Laporan sedang ditinjau dan menunggu verifikasi kelayakan oleh pengurus RT/RW.
                        @elseif($claim->status === 'verified')
                            Laporan telah diverifikasi dan siap disetujui untuk pencairan dana santunan kas duka.
                        @elseif($claim->status === 'approved')
                            Santunan telah disetujui dan bendahara sedang menyiapkan penyerahan dana kepada keluarga/ahli waris.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
