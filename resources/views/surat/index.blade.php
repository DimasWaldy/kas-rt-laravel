@extends('layouts.app')

@section('title', 'Surat Menyurat')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header Banner Konsisten --}}
    <div class="rounded-3xl border border-emerald-100 bg-linear-to-r/srgb from-emerald-700 to-green-600 p-6 text-white shadow-lg shadow-emerald-100 dark:border-emerald-900/40">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-100">Administrasi Lingkungan RT &amp; RW</p>
                <h1 class="mt-2 text-2xl font-black md:text-3xl">Surat Menyurat Resmi</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50">
                    Ajukan surat pengantar secara mandiri, pantau alur persetujuan RT/RW, dan unduh dokumen resmi ber-QR Code.
                </p>
            </div>

            @if(auth()->user()->hasPermission('submit-surat'))
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-modal', 'form-surat')"
                    class="inline-flex shrink-0 min-h-12 items-center justify-center gap-2 rounded-2xl bg-white px-6 py-3 text-sm font-black text-emerald-800 shadow-xs transition hover:bg-emerald-50 active:scale-95"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>+ Ajukan Surat Baru</span>
                </button>
            @endif
        </div>
    </div>

    {{-- 3 KPI Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400 text-xl">
                <i class="fa-solid fa-hourglass-start"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 dark:text-slate-500">Menunggu RT</p>
                <p class="mt-1 text-2xl font-black text-slate-900 dark:text-slate-100">{{ $counts['menunggu_rt'] ?? 0 }} <span class="text-xs font-normal text-slate-400">surat</span></p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 text-xl">
                <i class="fa-solid fa-building-columns"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 dark:text-slate-500">Menunggu RW</p>
                <p class="mt-1 text-2xl font-black text-slate-900 dark:text-slate-100">{{ $counts['menunggu_rw'] ?? 0 }} <span class="text-xs font-normal text-slate-400">surat</span></p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 dark:text-slate-500">Siap Cetak / Selesai</p>
                <p class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $counts['selesai'] ?? 0 }} <span class="text-xs font-normal text-slate-400">surat</span></p>
            </div>
        </div>
    </div>

    @if(session('info'))
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-800 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-200">
            {{ session('info') }}
        </div>
    @endif

    {{-- Filter Status Pill Tabs --}}
    <form method="GET" action="{{ route('surat.index') }}" class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        @foreach(['' => 'Semua', 'submitted' => 'Masuk', 'verified_rt' => 'Verifikasi RT', 'approved_rt' => 'Proses RW', 'done' => 'Selesai', 'rejected' => 'Ditolak'] as $value => $label)
            <button
                type="submit"
                name="status"
                value="{{ $value }}"
                class="rounded-xl px-4 py-2 text-xs font-bold transition active:scale-95 {{ $status === $value ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </form>

    {{-- Table & Card List --}}
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-4">Pengajuan Surat</th>
                        <th class="px-5 py-4">Pemohon / Wilayah</th>
                        <th class="px-5 py-4">Status Persetujuan</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($surats as $surat)
                        @php
                            $statusClass = match($surat->status) {
                                'done' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
                                'rejected' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300',
                                'submitted' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
                                default => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-300',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition dark:hover:bg-slate-800/40">
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-900 dark:text-slate-100">{{ $surat->type_label }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $surat->subject }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $surat->submitted_at?->translatedFormat('d M Y, H:i') }} WIB</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $surat->user->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $surat->rt?->name ?? 'RT belum ditentukan' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                    @if($surat->status === 'done')
                                        <i class="fa-solid fa-circle-check text-[10px]"></i>
                                    @elseif($surat->status === 'rejected')
                                        <i class="fa-solid fa-circle-xmark text-[10px]"></i>
                                    @else
                                        <i class="fa-solid fa-circle-dot text-[8px] animate-pulse"></i>
                                    @endif
                                    {{ $surat->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('surat.show', $surat) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-emerald-700 shadow-2xs transition hover:bg-emerald-50 hover:border-emerald-300 dark:border-slate-700 dark:bg-slate-800 dark:text-emerald-300">
                                    <span>Lihat detail</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-14 text-center text-slate-500">Belum ada pengajuan surat pada daftar ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            @forelse($surats as $surat)
                <a href="{{ route('surat.show', $surat) }}" class="block p-5 hover:bg-slate-50 transition active:scale-[0.99] dark:hover:bg-slate-800/40">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900 dark:text-slate-100">{{ $surat->type_label }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $surat->subject }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $surat->status_label }}</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">{{ $surat->user->name }} · {{ $surat->rt?->name ?? 'RT belum ditentukan' }}</p>
                </a>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">Belum ada pengajuan surat.</div>
            @endforelse
        </div>

        @if($surats->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-800">{{ $surats->links() }}</div>
        @endif
    </div>
</div>

@if(auth()->user()->hasPermission('submit-surat'))
    <div
        x-data="{ show: {{ $errors->any() ? 'true' : 'false' }} }"
        x-on:open-modal.window="if ($event.detail === 'form-surat') show = true"
        x-on:keydown.escape.window="show = false"
        x-show="show"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        aria-labelledby="form-surat-title"
    >
        <div class="absolute inset-0" @click="show = false"></div>

        <div x-data="{
            step: {{ $errors->any() ? ($errors->has('attachments') || $errors->has('attachments.*') ? '3' : ($errors->has('purpose') || $errors->has('content') ? '2' : '1')) : '1' }},
            type: '{{ old('type', '') }}',
            subject: '{{ old('subject', '') }}',
            purpose: '{{ old('purpose', '') }}'
        }" x-transition.scale.95 class="relative z-10 max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900 dark:text-white">
            {{-- Header Modal --}}
            <div class="mb-5 flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <i class="fa-solid fa-envelope-open-text text-base"></i>
                    </div>
                    <div>
                        <h2 id="form-surat-title" class="text-lg font-bold text-slate-900 dark:text-slate-100">Form Pengajuan Surat</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Ikuti 3 langkah mudah di bawah ini</p>
                    </div>
                </div>
                <button type="button" @click="show = false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Tutup modal">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            {{-- Stepper Progress Bar (Ramah Lansia & Elegan) --}}
            <div class="mb-6 flex items-center justify-between px-3 sm:px-6">
                {{-- Step 1 --}}
                <div class="flex flex-col items-center gap-1.5 cursor-pointer" @click="step = 1">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-all shadow-xs"
                        :class="step >= 1 ? 'bg-emerald-700 text-white ring-4 ring-emerald-100 dark:ring-emerald-950' : 'bg-slate-100 text-slate-400'">
                        <span x-show="step > 1"><i class="fa-solid fa-check text-[10px]"></i></span>
                        <span x-show="step <= 1">1</span>
                    </span>
                    <span class="text-[11px] font-bold" :class="step === 1 ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400'">Jenis Surat</span>
                </div>

                {{-- Connector 1 --}}
                <div class="flex-1 h-1 mx-2 rounded-full transition-all" :class="step >= 2 ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-700'"></div>

                {{-- Step 2 --}}
                <div class="flex flex-col items-center gap-1.5 cursor-pointer" @click="if(type && subject) step = 2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-all shadow-xs"
                        :class="step >= 2 ? 'bg-emerald-700 text-white ring-4 ring-emerald-100 dark:ring-emerald-950' : 'bg-slate-100 text-slate-400'">
                        <span x-show="step > 2"><i class="fa-solid fa-check text-[10px]"></i></span>
                        <span x-show="step <= 2">2</span>
                    </span>
                    <span class="text-[11px] font-bold" :class="step === 2 ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400'">Keperluan</span>
                </div>

                {{-- Connector 2 --}}
                <div class="flex-1 h-1 mx-2 rounded-full transition-all" :class="step >= 3 ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-700'"></div>

                {{-- Step 3 --}}
                <div class="flex flex-col items-center gap-1.5 cursor-pointer" @click="if(type && subject && purpose) step = 3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-all shadow-xs"
                        :class="step === 3 ? 'bg-emerald-700 text-white ring-4 ring-emerald-100 dark:ring-emerald-950' : 'bg-slate-100 text-slate-400'">
                        3
                    </span>
                    <span class="text-[11px] font-bold" :class="step === 3 ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400'">Berkas &amp; Kirim</span>
                </div>
            </div>

            @if(! auth()->user()->rt_id)
                <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                    <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                    Profil Anda belum terhubung ke RT. Hubungi pengurus RT sebelum mengajukan surat.
                </div>
            @endif

            <form method="POST" action="{{ route('surat.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- LANGKAH 1: JENIS SURAT & PERIHAL --}}
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <div class="rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/60 text-xs text-slate-600 dark:text-slate-300">
                        <span class="font-bold text-emerald-700 dark:text-emerald-400">Langkah 1:</span> Pilih surat yang Anda butuhkan dan tuliskan perihalnya secara singkat.
                    </div>

                    <div>
                        <label for="type" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Pilih Jenis Surat <span class="text-red-500">*</span>
                        </label>
                        <select id="type" name="type" x-model="type" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="">-- Pilih Jenis Surat --</option>
                            @foreach($types as $key => $t)
                                <option value="{{ $key }}" @selected(old('type') === $key)>
                                    {{ $t['label'] }} ({{ $t['requires_rw'] ? 'Persetujuan RT & RW' : 'Persetujuan RT' }})
                                </option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="subject" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Perihal Singkat <span class="text-red-500">*</span>
                        </label>
                        <input id="subject" name="subject" x-model="subject" value="{{ old('subject') }}" required maxlength="255" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: Pengantar pembuatan KTP / Surat Domisili">
                        @error('subject')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="show = false" class="rounded-xl border border-slate-200 px-5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            Batal
                        </button>
                        <button type="button" @click="if(!type) { alert('Silakan pilih jenis surat terlebih dahulu.'); return; } if(!subject) { alert('Silakan isi perihal singkat surat.'); return; } step = 2;" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-800 active:scale-95">
                            <span>Lanjut: Isi Keperluan</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>
                </div>

                {{-- LANGKAH 2: KEPERLUAN & KETERANGAN --}}
                <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <div class="rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/60 text-xs text-slate-600 dark:text-slate-300">
                        <span class="font-bold text-emerald-700 dark:text-emerald-400">Langkah 2:</span> Jelaskan tujuan dan keperluan pembuatan surat ini untuk pengurus RT/RW.
                    </div>

                    <div>
                        <label for="purpose" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Keperluan Surat <span class="text-red-500">*</span>
                        </label>
                        <textarea id="purpose" name="purpose" x-model="purpose" rows="4" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: Diperlukan sebagai syarat pendaftaran kartu keluarga baru di kelurahan.">{{ old('purpose') }}</textarea>
                        @error('purpose')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="content" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Catatan Tambahan <span class="font-normal normal-case text-slate-400">(opsional)</span>
                        </label>
                        <textarea id="content" name="content" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Catatan atau informasi tambahan jika ada.">{{ old('content') }}</textarea>
                        @error('content')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="step = 1" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>Kembali</span>
                        </button>
                        <button type="button" @click="if(!purpose) { alert('Silakan isi keperluan surat terlebih dahulu.'); return; } step = 3;" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-800 active:scale-95">
                            <span>Lanjut: Dokumen &amp; Kirim</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>
                </div>

                {{-- LANGKAH 3: DOKUMEN & KONFIRMASI KIRIM --}}
                <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                    <div class="rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/60 text-xs text-slate-600 dark:text-slate-300">
                        <span class="font-bold text-emerald-700 dark:text-emerald-400">Langkah 3:</span> Unggah dokumen syarat (jika ada), periksa ringkasan, dan kirim.
                    </div>

                    <div>
                        <label for="attachments" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Unggah Dokumen Syarat <span class="font-normal normal-case text-slate-400">(maks. 3 berkas, opsional)</span>
                        </label>
                        <input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 p-4 text-xs dark:border-slate-700 dark:bg-slate-800/50">
                        <p class="mt-1.5 text-[11px] text-slate-400">Format PDF, JPG, atau PNG (maksimal 2 MB per file).</p>
                        @error('attachments')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        @error('attachments.*')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Ringkasan Review Sebelum Kirim (Memberikan Kepastian ke Warga) --}}
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 text-xs dark:border-emerald-800 dark:bg-emerald-950/40">
                        <p class="font-bold text-emerald-900 dark:text-emerald-200 mb-2">
                            <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Ringkasan Pengajuan:
                        </p>
                        <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
                            <p><strong>Perihal:</strong> <span x-text="subject || '-'"></span></p>
                            <p><strong>Keperluan:</strong> <span x-text="purpose || '-'" class="italic"></span></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="step = 2" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>Kembali</span>
                        </button>
                        <button type="submit" @disabled(! auth()->user()->rt_id) class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-800 active:scale-95 disabled:cursor-not-allowed disabled:bg-slate-300">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Kirim Pengajuan Surat</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
