@extends('layouts.app')

@section('title', 'Detail Surat')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('surat.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke daftar
        </a>
        @if($surat->isFinal())
            <a href="{{ route('surat.print', $surat) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">
                <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
            </a>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $surat->type_label }}</p>
                        <h1 class="mt-1 text-xl font-black text-slate-900">{{ $surat->subject }}</h1>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">{{ $surat->status_label }}</span>
                </div>

                <dl class="mt-6 grid gap-4 rounded-2xl bg-slate-50 p-5 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-400">Pemohon</dt><dd class="font-bold text-slate-800">{{ $surat->user->name }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Wilayah</dt><dd class="font-bold text-slate-800">{{ $surat->rt?->name ?? '-' }} / {{ $surat->rt?->rw?->name ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Diajukan</dt><dd class="font-semibold text-slate-700">{{ $surat->submitted_at?->translatedFormat('d F Y, H:i') }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Nomor Surat</dt><dd class="font-semibold text-slate-700">{{ $surat->surat_number ?? 'Terbit setelah disetujui' }}</dd></div>
                </dl>

                <div class="mt-6 space-y-4 text-sm leading-relaxed text-slate-700">
                    <div><h2 class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Keperluan</h2><p class="whitespace-pre-line">{{ $surat->purpose }}</p></div>
                    @if($surat->content)<div><h2 class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Keterangan Tambahan</h2><p class="whitespace-pre-line">{{ $surat->content }}</p></div>@endif
                </div>
            </section>

            @if($surat->attachments->isNotEmpty())
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-slate-900">Dokumen Pendukung</h2>
                    <div class="mt-4 space-y-2">
                        @foreach($surat->attachments as $attachment)
                            <a href="{{ route('surat.attachment', [$surat, $attachment]) }}" class="flex items-center justify-between rounded-xl border border-slate-200 p-3 text-sm hover:border-emerald-300 hover:bg-emerald-50">
                                <span class="min-w-0 truncate font-semibold text-slate-700"><i class="fa-solid fa-paperclip mr-2 text-emerald-600"></i>{{ $attachment->file_name }}</span>
                                <span class="ml-3 text-xs text-slate-400">{{ number_format($attachment->file_size / 1024) }} KB</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if(collect($actions)->contains(true))
                <section class="rounded-3xl border border-emerald-200 bg-emerald-50/50 p-6">
                    <h2 class="font-bold text-slate-900">Tindakan Pengurus</h2>
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach(['verify_rt' => ['surat.verify-rt', 'Verifikasi sebagai Sekretaris RT'], 'approve_rt' => ['surat.approve-rt', 'Setujui sebagai Ketua RT'], 'verify_rw' => ['surat.verify-rw', 'Verifikasi sebagai Sekretaris RW'], 'approve_rw' => ['surat.approve-rw', 'Setujui sebagai Ketua RW']] as $action => [$routeName, $label])
                            @if($actions[$action])
                                <form method="POST" action="{{ route($routeName, $surat) }}">@csrf @method('PATCH')<button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-800">{{ $label }}</button></form>
                            @endif
                        @endforeach
                    </div>

                    @if($actions['reject'])
                        <form method="POST" action="{{ route('surat.reject', $surat) }}" class="mt-5 border-t border-emerald-200 pt-5">
                            @csrf @method('PATCH')
                            <label for="rejected_reason" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Alasan Penolakan</label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <input id="rejected_reason" name="rejected_reason" required minlength="5" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm" placeholder="Jelaskan data yang perlu diperbaiki">
                                <button class="rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700">Tolak Pengajuan</button>
                            </div>
                            @error('rejected_reason')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </form>
                    @endif
                </section>
            @endif

            @if($surat->status === 'rejected')
                <section class="rounded-3xl border border-red-200 bg-red-50 p-6">
                    <h2 class="font-bold text-red-900">Pengajuan Ditolak</h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-red-800">{{ $surat->rejected_reason }}</p>
                    <p class="mt-2 text-xs text-red-600">Oleh {{ $surat->rejector?->name }} pada {{ $surat->rejected_at?->translatedFormat('d F Y, H:i') }}</p>
                </section>
            @endif
        </div>

        <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:self-start">
            <h2 class="font-bold text-slate-900">Alur Persetujuan</h2>
            @php
                $steps = [
                    ['Pengajuan masuk', $surat->submitted_at, $surat->user->name],
                    ['Verifikasi Sekretaris RT', $surat->verified_rt_at, $surat->verifierRt?->name],
                    ['Persetujuan Ketua RT', $surat->approved_rt_at, $surat->approverRt?->name],
                ];
                if ($surat->requires_rw) {
                    $steps[] = ['Verifikasi Sekretaris RW', $surat->verified_rw_at, $surat->verifierRw?->name];
                    $steps[] = ['Persetujuan Ketua RW', $surat->approved_rw_at, $surat->approverRw?->name];
                }
            @endphp
            <ol class="mt-5 space-y-5">
                @foreach($steps as [$label, $time, $actor])
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $time ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400' }}"><i class="fa-solid {{ $time ? 'fa-check' : 'fa-clock' }} text-[10px]"></i></span>
                        <div><p class="text-sm font-bold {{ $time ? 'text-slate-800' : 'text-slate-400' }}">{{ $label }}</p>@if($time)<p class="text-xs text-slate-500">{{ $actor }}<br>{{ $time->translatedFormat('d M Y, H:i') }}</p>@endif</div>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
</div>
@endsection
