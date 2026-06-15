@extends('layouts.app')

@section('title', 'Detail Iuran Khusus')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('iuran-khusus.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke daftar</a>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $iuranKhusus->jenis_color }}">{{ $iuranKhusus->jenis_label }}</span>
                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $iuranKhusus->billing_group }}</span>
            </div>
            <h1 class="mt-3 text-2xl font-black text-slate-900 sm:text-3xl">{{ $iuranKhusus->judul }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">{{ $iuranKhusus->keterangan ?: 'Tidak ada keterangan tambahan.' }}</p>
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Tagihan</p><p class="mt-2 text-3xl font-black text-slate-900">{{ $iuranKhusus->total_tagihan }}</p></div>
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Sudah Lunas</p><p class="mt-2 text-3xl font-black text-emerald-800">{{ $lunas }}</p><p class="mt-1 text-xs text-emerald-700">Terkumpul Rp {{ number_format($terkumpul, 0, ',', '.') }}</p></div>
        <div class="rounded-3xl border border-purple-200 bg-purple-50 p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-purple-600">Dikecualikan</p><p class="mt-2 text-3xl font-black text-purple-800">{{ $dikecualikan }}</p></div>
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-amber-600">Belum Bayar</p><p class="mt-2 text-3xl font-black text-amber-800">{{ $belumBayar }}</p></div>
    </section>

    <section class="grid gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <div><p class="text-xs text-slate-400">Wilayah</p><p class="mt-1 font-bold text-slate-800">{{ $iuranKhusus->rt->name }}</p></div>
        <div><p class="text-xs text-slate-400">Dibuat oleh</p><p class="mt-1 font-bold text-slate-800">{{ $iuranKhusus->creator->name }}</p></div>
        <div><p class="text-xs text-slate-400">Tanggal kejadian</p><p class="mt-1 font-bold text-slate-800">{{ $iuranKhusus->tanggal_kejadian?->translatedFormat('d F Y') ?? '-' }}</p></div>
        <div><p class="text-xs text-slate-400">Nominal per warga</p><p class="mt-1 font-bold text-slate-800">Rp {{ number_format($iuranKhusus->nominal_per_warga, 0, ',', '.') }}</p></div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5"><h2 class="text-lg font-black text-slate-900">Daftar Tagihan Warga</h2><p class="mt-1 text-sm text-slate-500">Kelola pengecualian tanpa mengubah alur pembayaran tagihan.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left font-bold text-slate-600">Warga</th><th class="px-5 py-3 text-left font-bold text-slate-600">Status</th><th class="px-5 py-3 text-left font-bold text-slate-600">Pengecualian</th><th class="px-5 py-3 text-right font-bold text-slate-600">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($iuranKhusus->tagihans->sortBy('user.name') as $tagihan)
                        <tr x-data="{ showExclude: {{ $errors->any() && old('tagihan_id') == $tagihan->id ? 'true' : 'false' }} }">
                            <td class="px-5 py-4"><p class="font-bold text-slate-800">{{ $tagihan->user->name }}</p><p class="text-xs text-slate-400">Rp {{ number_format($tagihan->total, 0, ',', '.') }}</p></td>
                            <td class="px-5 py-4">
                                @if($tagihan->status === 'lunas')
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Lunas</span><p class="mt-1 text-xs text-slate-400">{{ $tagihan->paid_at?->translatedFormat('d M Y, H:i') }}</p>
                                @elseif(in_array($tagihan->status, ['pending_transfer', 'pending_offline'], true))
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">Menunggu Verifikasi</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Belum Bayar</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($tagihan->isDikecualikan())
                                    <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-700">Dikecualikan</span><p class="mt-2 max-w-xs text-xs text-slate-500">{{ $tagihan->alasan_dikecualikan }}</p>
                                @else
                                    <span class="text-xs text-slate-400">Tidak</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($tagihan->status !== 'lunas' && ! $tagihan->isDikecualikan())
                                    <button type="button" @click="showExclude = true" class="rounded-xl bg-purple-50 px-3 py-2 text-xs font-bold text-purple-700 hover:bg-purple-100">Kecualikan</button>
                                @elseif($tagihan->isDikecualikan())
                                    <form method="POST" action="{{ route('iuran-khusus.batal-kecualikan', $tagihan) }}">@csrf @method('PATCH')<button class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Batalkan Pengecualian</button></form>
                                @else
                                    <span class="text-xs text-slate-400">Selesai</span>
                                @endif

                                <div x-cloak x-show="showExclude" x-transition.opacity class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 text-left" @click.self="showExclude = false">
                                    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                                        <div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-purple-600">Pengecualian Iuran</p><h3 class="mt-1 text-xl font-black text-slate-900">{{ $tagihan->user->name }}</h3></div><button type="button" @click="showExclude = false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100"><i class="fa-solid fa-xmark"></i></button></div>
                                        <form method="POST" action="{{ route('iuran-khusus.kecualikan', $tagihan) }}" class="mt-5 space-y-4">@csrf @method('PATCH')<input type="hidden" name="tagihan_id" value="{{ $tagihan->id }}"><div><label class="block text-sm font-bold text-slate-700">Alasan Pengecualian</label><textarea name="alasan_dikecualikan" rows="4" required minlength="5" maxlength="255" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="Contoh: Warga sedang mengalami kesulitan ekonomi">{{ old('tagihan_id') == $tagihan->id ? old('alasan_dikecualikan') : '' }}</textarea>@if(old('tagihan_id') == $tagihan->id) @error('alasan_dikecualikan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror @endif</div><div class="flex justify-end gap-3"><button type="button" @click="showExclude = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Batal</button><button class="rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-purple-800">Simpan Pengecualian</button></div></form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Tidak ada tagihan dalam batch ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
