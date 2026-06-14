@extends('layouts.app')

@section('title', 'Surat Menyurat')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Layanan administrasi Smart RW</p>
            <h1 class="text-2xl font-black text-slate-900">Surat Menyurat</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau pengajuan dan proses persetujuan surat warga.</p>
        </div>

        @if(auth()->user()->hasPermission('submit-surat'))
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'form-surat')"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800"
            >
                <i class="fa-solid fa-plus"></i>
                Ajukan Surat
            </button>
        @endif
    </div>

    @if(session('info'))
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-800">
            {{ session('info') }}
        </div>
    @endif

    <form method="GET" action="{{ route('surat.index') }}" class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @foreach(['' => 'Semua', 'submitted' => 'Masuk', 'verified_rt' => 'Verifikasi RT', 'approved_rt' => 'Proses RW', 'done' => 'Selesai', 'rejected' => 'Ditolak'] as $value => $label)
            <button
                type="submit"
                name="status"
                value="{{ $value }}"
                class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === $value ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Pengajuan</th>
                        <th class="px-5 py-4">Pemohon / Wilayah</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($surats as $surat)
                        @php
                            $statusClass = match($surat->status) {
                                'done' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                'rejected' => 'border-red-200 bg-red-50 text-red-700',
                                'submitted' => 'border-amber-200 bg-amber-50 text-amber-700',
                                default => 'border-blue-200 bg-blue-50 text-blue-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-800">{{ $surat->type_label }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $surat->subject }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $surat->submitted_at?->translatedFormat('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-700">{{ $surat->user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $surat->rt?->name ?? 'RT belum ditentukan' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full border px-3 py-1.5 text-xs font-bold {{ $statusClass }}">{{ $surat->status_label }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('surat.show', $surat) }}" class="font-bold text-emerald-700 hover:text-emerald-900">Lihat detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-14 text-center text-slate-500">Belum ada pengajuan surat pada daftar ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($surats as $surat)
                <a href="{{ route('surat.show', $surat) }}" class="block p-5 hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-800">{{ $surat->type_label }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $surat->subject }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">{{ $surat->status_label }}</span>
                    </div>
                    <p class="mt-4 text-xs text-slate-400">{{ $surat->user->name }} · {{ $surat->rt?->name ?? 'RT belum ditentukan' }}</p>
                </a>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">Belum ada pengajuan surat.</div>
            @endforelse
        </div>

        @if($surats->hasPages())
            <div class="border-t border-slate-100 p-4">{{ $surats->links() }}</div>
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        aria-labelledby="form-surat-title"
    >
        <div class="absolute inset-0" @click="show = false"></div>

        <div x-transition.scale class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Layanan warga</p>
                    <h2 id="form-surat-title" class="text-xl font-bold text-slate-900">Form Pengajuan Surat</h2>
                </div>
                <button type="button" @click="show = false" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            @if(! auth()->user()->rt_id)
                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Profil Anda belum memiliki RT. Hubungi operator sebelum mengajukan surat.
                </div>
            @endif

            <form method="POST" action="{{ route('surat.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label for="type" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Jenis Surat</label>
                    <select id="type" name="type" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Pilih jenis surat</option>
                        @foreach($types as $key => $type)
                            <option value="{{ $key }}" @selected(old('type') === $key)>
                                {{ $type['label'] }} ({{ $type['requires_rw'] ? 'Persetujuan RT & RW' : 'Persetujuan RT' }})
                            </option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="subject" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Perihal Singkat</label>
                    <input id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Pengantar administrasi perkawinan">
                    @error('subject')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="purpose" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Keperluan</label>
                    <textarea id="purpose" name="purpose" rows="4" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Jelaskan surat ini akan digunakan untuk apa.">{{ old('purpose') }}</textarea>
                    @error('purpose')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="content" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Keterangan Tambahan <span class="font-normal normal-case text-slate-400">(opsional)</span></label>
                    <textarea id="content" name="content" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Data atau catatan tambahan yang perlu diketahui pengurus.">{{ old('content') }}</textarea>
                    @error('content')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="attachments" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Dokumen Pendukung <span class="font-normal normal-case text-slate-400">(maks. 3 berkas)</span></label>
                    <input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm">
                    <p class="mt-2 text-xs text-slate-400">PDF/JPG/PNG, maksimal 2 MB per berkas.</p>
                    @error('attachments')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" @click="show = false" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" @disabled(! auth()->user()->rt_id) class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
