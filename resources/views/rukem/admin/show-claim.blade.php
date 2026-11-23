@extends('layouts.app')

@section('title', 'Tinjau Laporan Duka & Santunan: ' . $claim->deceased_name)

@section('content')
<div class="mx-auto max-w-5xl space-y-6" x-data="{ showBantuanModal: false, showRejectModal: false, rejectAction: 'verify' }">
    {{-- Breadcrumb --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
            <a href="{{ route('admin.rukem.index') }}" class="hover:text-amber-600 dark:hover:text-amber-400">Kelola Rukem</a>
            <i class="fa-solid fa-chevron-right text-3xs"></i>
            <span class="text-slate-800 dark:text-slate-200">Tinjau Laporan Duka</span>
        </div>

        <a href="{{ route('admin.rukem.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
        </a>
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
                            Wafat: {{ \Carbon\Carbon::parse($claim->death_date)->translatedFormat('l, d F Y') }} • Laporan masuk: {{ $claim->created_at->translatedFormat('d M Y, H:i') }}
                        </p>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="shrink-0">
                    @if($claim->status === 'submitted')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-blue-100 px-3 py-1.5 text-xs font-bold text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                            <i class="fa-solid fa-paper-plane"></i> Menunggu Verifikasi
                        </span>
                    @elseif($claim->status === 'verified')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300">
                            <i class="fa-solid fa-shield-check"></i> Terverifikasi
                        </span>
                    @elseif($claim->status === 'approved')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                            <i class="fa-solid fa-check"></i> Disetujui (Siap Dicairkan)
                        </span>
                    @elseif($claim->status === 'done')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i> Santunan Telah Diserahkan
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                            <i class="fa-solid fa-circle-xmark"></i> Ditolak
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Action Bar for Officials --}}
        <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50/75 p-5 dark:bg-slate-800/40">
            <div class="text-xs text-slate-500 dark:text-slate-400">
                @if($claim->status === 'submitted')
                    <span>Tinjau kebenaran berita duka sebelum melakukan verifikasi publik.</span>
                @elseif($claim->status === 'verified')
                    <span>Laporan telah terverifikasi. Pengurus dapat menyetujui nominal santunan duka.</span>
                @elseif($claim->status === 'approved')
                    <span>Santunan telah disetujui. Silakan input bukti serah terima dana duka.</span>
                @elseif($claim->status === 'done')
                    <span class="font-bold text-emerald-700 dark:text-emerald-400">Penyaluran santunan telah selesai dan tercatat resmi di buku kas sosial Rukem.</span>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Action 1: Verifikasi Berita Duka --}}
                @if($claim->status === 'submitted' && auth()->user()->hasPermission('manage-rukem'))
                    <form action="{{ route('admin.rukem.verify-claim', $claim) }}" method="POST" class="inline-block">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="verified">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700">
                            <i class="fa-solid fa-check"></i> Verifikasi Laporan Duka
                        </button>
                    </form>

                    <button type="button" @click="rejectAction = 'verify'; showRejectModal = true" class="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-white px-4 py-2 text-xs font-bold text-rose-700 shadow-2xs transition hover:bg-rose-50 dark:border-rose-900 dark:bg-slate-800 dark:text-rose-400">
                        <i class="fa-solid fa-xmark"></i> Tolak Laporan
                    </button>
                @endif

                {{-- Action 2: Persetujuan Santunan --}}
                @if($claim->status === 'verified' && auth()->user()->hasPermission('approve-rukem'))
                    <form action="{{ route('admin.rukem.approve-claim', $claim) }}" method="POST" class="inline-block">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-amber-700">
                            <i class="fa-solid fa-thumbs-up"></i> Setujui Santunan Duka
                        </button>
                    </form>

                    <button type="button" @click="rejectAction = 'approve'; showRejectModal = true" class="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-white px-4 py-2 text-xs font-bold text-rose-700 shadow-2xs transition hover:bg-rose-50 dark:border-rose-900 dark:bg-slate-800 dark:text-rose-400">
                        <i class="fa-solid fa-xmark"></i> Tolak Pengajuan
                    </button>
                @endif

                {{-- Action 3: Penyaluran Dana Santunan --}}
                @if(in_array($claim->status, ['verified', 'approved']) && auth()->user()->hasPermission('approve-rukem'))
                    <button type="button" @click="showBantuanModal = true" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-700">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Serahkan / Cairkan Santunan
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Card 1: Data Almarhum & Pemakaman --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100">
                <i class="fa-solid fa-user-check text-rose-600"></i>
                Detail Almarhum & Pemakaman
            </h2>

            <dl class="mt-4 divide-y divide-slate-100 text-xs text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Nama Almarhum</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">{{ $claim->deceased_name }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">NIK Almarhum</dt>
                    <dd class="font-mono text-slate-700 dark:text-slate-300">{{ $claim->nik_deceased ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Pelapor / Pengaju</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">{{ $claim->user?->name }} ({{ ucfirst(str_replace('_', ' ', $claim->relationship)) }})</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Tanggal Wafat</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">{{ \Carbon\Carbon::parse($claim->death_date)->translatedFormat('l, d F Y') }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Rencana Pemakaman</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">
                        {{ $claim->funeral_date ? \Carbon\Carbon::parse($claim->funeral_date)->translatedFormat('d M Y - H:i') . ' WIB' : '-' }}
                    </dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Lokasi TPU / Makam</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100">{{ $claim->cemetery_location ?? '-' }}</dd>
                </div>
                @if($claim->description)
                    <div class="py-2.5">
                        <dt class="font-semibold text-slate-500">Alamat Rumah Duka</dt>
                        <dd class="mt-1 rounded-xl bg-slate-50 p-2.5 text-xs text-slate-800 dark:bg-slate-800 dark:text-slate-200">{{ $claim->description }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between py-2.5">
                    <dt class="font-semibold text-slate-500">Surat Keterangan Kematian</dt>
                    <dd>
                        @if($claim->proof_path)
                            <a href="{{ route('rukem.claim-bukti', $claim) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-rose-600 hover:underline dark:text-rose-400">
                                <i class="fa-solid fa-file-pdf"></i> Buka Berkas Surat
                            </a>
                        @else
                            <span class="text-slate-400">Tidak ada berkas diunggah</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Card 2: Status Pencairan & Penyerahan Santunan --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100">
                <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i>
                Catatan Santunan & Penyerahan Dana
            </h2>

            @if($claim->bantuan)
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Nominal Disalurkan</span>
                        <span class="text-xl font-black text-emerald-700 dark:text-emerald-400">
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
                        <dd class="font-bold text-slate-900 dark:text-slate-100">{{ $claim->bantuan->giver?->name }}</dd>
                    </div>
                    @if($claim->bantuan->proof_path)
                        <div class="flex items-center justify-between py-2.5">
                            <dt class="font-semibold text-slate-500">Bukti Kwitansi / Serah Terima</dt>
                            <dd>
                                <a href="{{ route('admin.rukem.bantuan-bukti', $claim->bantuan) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-emerald-700 hover:underline dark:text-emerald-400">
                                    <i class="fa-solid fa-receipt"></i> Buka Kwitansi
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if($claim->bantuan->note)
                        <div class="py-2.5">
                            <dt class="font-semibold text-slate-500">Catatan Tambahan</dt>
                            <dd class="mt-1 text-slate-700 dark:text-slate-300">{{ $claim->bantuan->note }}</dd>
                        </div>
                    @endif
                </dl>
            @else
                <div class="mt-6 rounded-2xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                        <i class="fa-solid fa-hand-holding-dollar text-xl"></i>
                    </div>
                    <h3 class="mt-3 text-xs font-bold text-slate-800 dark:text-slate-200">Santunan Belum Disalurkan</h3>
                    <p class="mt-1 text-2xs text-slate-500">
                        Setelah laporan diverifikasi dan disetujui, gunakan tombol <strong>"Serahkan / Cairkan Santunan"</strong> untuk mencatat pencairan dana kas duka ke ahli waris.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL PENYERAHAN / PENCAIRAN SANTUNAN --}}
    <div x-show="showBantuanModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="bantuan-modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showBantuanModal = false"></div>

            <div class="relative transform overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg dark:border-slate-800 dark:bg-slate-900">
                <form action="{{ route('admin.rukem.disalurkan-bantuan', $claim) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100" id="bantuan-modal-title">
                                <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i>
                                Form Penyaluran Santunan Kas Rukun Kematian
                            </h3>
                            <button type="button" @click="showBantuanModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-2xs text-slate-500">Pencairan santunan akan memotong saldo kas sosial rukem dan menandai laporan duka selesai.</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <div>
                            <label for="amount" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Nominal Santunan Duka (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" id="amount" name="amount" value="{{ old('amount', 500000) }}" min="10000" step="10000" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" required>
                            <p class="mt-1 text-2xs text-slate-400">Standar santunan duka: Rp 500.000 atau sesuai alokasi kas RT.</p>
                        </div>

                        <div>
                            <label for="recipient_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Nama Penerima / Ahli Waris <span class="text-rose-500">*</span></label>
                            <input type="text" id="recipient_name" name="recipient_name" value="{{ old('recipient_name', $claim->user?->name) }}" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" required>
                        </div>

                        <div>
                            <label for="recipient_phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300">No. Kontak Penerima (Opsional)</label>
                            <input type="text" id="recipient_phone" name="recipient_phone" value="{{ old('recipient_phone') }}" placeholder="Contoh: 081234567890" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-mono text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        </div>

                        <div>
                            <label for="given_at" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Tanggal Penyerahan Dana <span class="text-rose-500">*</span></label>
                            <input type="date" id="given_at" name="given_at" value="{{ old('given_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" required>
                        </div>

                        <div>
                            <label for="bantuan_proof" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Bukti Kwitansi / Foto Serah Terima</label>
                            <input type="file" id="bantuan_proof" name="proof" accept="image/*,application/pdf" class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950/60 dark:file:text-emerald-400">
                        </div>

                        <div>
                            <label for="bantuan_note" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Catatan / Keterangan Penyaluran</label>
                            <textarea id="bantuan_note" name="note" rows="2" placeholder="Contoh: Diserahkan langsung di rumah duka oleh Ketua RT dan Bendahara" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-2xs focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                        <button type="button" @click="showBantuanModal = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700">
                            <i class="fa-solid fa-check"></i> Simpan & Cairkan Dana
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL PENOLAKAN LAPORAN / KLAIM --}}
    <div x-show="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="reject-modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showRejectModal = false"></div>

            <div class="relative transform overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md dark:border-slate-800 dark:bg-slate-900">
                <form :action="rejectAction === 'verify' ? '{{ route('admin.rukem.verify-claim', $claim) }}' : '{{ route('admin.rukem.approve-claim', $claim) }}'" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="rejected">

                    <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100" id="reject-modal-title">
                            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                            Tolak Laporan / Pengajuan Duka
                        </h3>
                        <p class="mt-1 text-2xs text-slate-500">Berikan alasan jelas penolakan agar pelapor memahami kendala verifikasi.</p>
                    </div>

                    <div class="p-5">
                        <label for="rejected_reason" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Alasan Penolakan <span class="text-rose-500">*</span></label>
                        <textarea id="rejected_reason" name="rejected_reason" rows="3" placeholder="Contoh: Berkas surat kematian tidak valid atau data warga tidak tercatat di wilayah ini." class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-2xs focus:border-rose-600 focus:ring-1 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" required></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                        <button type="button" @click="showRejectModal = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-700">
                            <i class="fa-solid fa-ban"></i> Konfirmasi Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
