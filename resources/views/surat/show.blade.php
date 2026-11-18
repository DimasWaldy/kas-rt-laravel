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
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
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
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
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

        <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 lg:self-start">
            <div class="flex items-center gap-2.5 mb-5 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <i class="fa-solid fa-route text-sm"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Lacak Alur Surat</h2>
                    <p class="text-xs text-slate-400">Status persetujuan pengurus RT &amp; RW</p>
                </div>
            </div>

            @php
                $steps = [
                    ['Pengajuan masuk dari Warga', $surat->submitted_at, $surat->user->name],
                    ['Verifikasi Sekretaris RT', $surat->verified_rt_at, $surat->verifierRt?->name],
                    ['Persetujuan Ketua RT', $surat->approved_rt_at, $surat->approverRt?->name],
                ];
                if ($surat->requires_rw) {
                    $steps[] = ['Verifikasi Sekretaris RW', $surat->verified_rw_at, $surat->verifierRw?->name];
                    $steps[] = ['Persetujuan Ketua RW', $surat->approved_rw_at, $surat->approverRw?->name];
                }

                // Tentukan step yang sedang aktif menunggu tindakan
                $currentActiveFound = false;
            @endphp

            <ol class="relative pl-3 space-y-6 before:absolute before:left-[23px] before:top-4 before:bottom-4 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700">
                @foreach($steps as [$label, $time, $actor])
                    @php
                        $isDone = (bool) $time;
                        $isCurrent = ! $isDone && ! $currentActiveFound && $surat->status !== 'rejected';
                        if ($isCurrent) {
                            $currentActiveFound = true;
                        }
                    @endphp
                    <li class="relative flex items-start gap-3.5 z-10">
                        @if($isDone)
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-xs shadow-emerald-500/30">
                                <i class="fa-solid fa-check text-[11px]"></i>
                            </span>
                        @elseif($isCurrent)
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white shadow-xs shadow-amber-500/30 ring-4 ring-amber-100 dark:ring-amber-950/40 animate-pulse">
                                <i class="fa-solid fa-hourglass-half text-[10px]"></i>
                            </span>
                        @else
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400 border border-slate-200 dark:border-slate-700 dark:bg-slate-800">
                                <i class="fa-solid fa-circle text-[6px]"></i>
                            </span>
                        @endif

                        <div class="min-w-0 pt-0.5">
                            <p class="text-xs font-bold leading-tight {{ $isDone ? 'text-slate-900 dark:text-slate-100' : ($isCurrent ? 'text-amber-700 dark:text-amber-300' : 'text-slate-400') }}">
                                {{ $label }}
                            </p>
                            @if($isDone)
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $actor }}</span><br>
                                    <span class="text-slate-400">{{ $time->translatedFormat('d M Y, H:i') }} WIB</span>
                                </p>
                            @elseif($isCurrent)
                                <span class="mt-1 inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                    Sedang diproses
                                </span>
                            @else
                                <p class="mt-0.5 text-[10px] text-slate-400">Menunggu tahap sebelumnya</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
</div>
@endsection
