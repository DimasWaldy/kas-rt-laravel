@extends('layouts.app')

@section('title', 'Detail Aset')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('aset.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke daftar aset</a>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->hasPermission('pinjam-aset') && $aset->jumlah_tersedia > 0 && $aset->is_active && $aset->kondisi !== 'rusak_berat')
                <a href="{{ route('peminjaman-aset.create', ['aset_id' => $aset->id]) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800"><i class="fa-solid fa-hand-holding mr-1"></i> Ajukan Peminjaman</a>
            @endif
            @if(auth()->user()->hasPermission('manage-aset'))
                <a href="{{ route('aset.edit', $aset) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-pen mr-1"></i> Edit</a>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="relative h-80 bg-slate-100">
                    @if($aset->foto)
                        <img src="{{ route('aset.foto', $aset) }}" alt="Foto {{ $aset->nama }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-slate-300"><i class="fa-solid fa-boxes-stacked text-6xl"></i></div>
                    @endif
                    <span class="absolute left-5 top-5 rounded-full border px-3 py-1.5 text-xs font-bold {{ $aset->kondisi_color }}">{{ $aset->kondisi_label }}</span>
                </div>

                <div class="p-6 sm:p-8">
                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">{{ $aset->kategori_label }}</span>
                    <h1 class="mt-4 text-2xl font-black text-slate-900 sm:text-3xl">{{ $aset->nama }}</h1>
                    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $aset->deskripsi ?: 'Belum ada deskripsi aset.' }}</p>

                    <dl class="mt-7 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Jumlah tersedia</dt><dd class="mt-1 text-xl font-black text-slate-900">{{ $aset->jumlah_tersedia }} / {{ $aset->jumlah_total }} unit</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Kondisi</dt><dd class="mt-1 font-bold text-slate-800">{{ $aset->kondisi_label }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nilai perkiraan</dt><dd class="mt-1 font-bold text-slate-800">{{ $aset->nilai_perkiraan ? 'Rp '.number_format($aset->nilai_perkiraan, 0, ',', '.') : '-' }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal pengadaan</dt><dd class="mt-1 font-bold text-slate-800">{{ $aset->tanggal_pengadaan?->translatedFormat('d F Y') ?: '-' }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Lokasi penyimpanan</dt><dd class="mt-1 font-bold text-slate-800">{{ $aset->lokasi_penyimpanan ?: '-' }}</dd></div>
                    </dl>
                </div>
            </article>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold text-slate-900">Peminjaman Aktif</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="text-left text-xs uppercase tracking-wider text-slate-400">
                            <tr><th class="py-3">Pemohon</th><th class="py-3">Tanggal</th><th class="py-3">Jumlah</th><th class="py-3">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($peminjamanAktif as $peminjaman)
                                <tr>
                                    <td class="py-3 font-semibold text-slate-800">{{ $peminjaman->pemohon->name }}</td>
                                    <td class="py-3 text-slate-600">{{ $peminjaman->tanggal_mulai->translatedFormat('d M Y') }} - {{ $peminjaman->tanggal_selesai->translatedFormat('d M Y') }}</td>
                                    <td class="py-3 text-slate-600">{{ $peminjaman->jumlah_dipinjam }}</td>
                                    <td class="py-3"><span class="rounded-full border px-3 py-1 text-xs font-bold {{ $peminjaman->status_color }}">{{ $peminjaman->status_label }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-slate-500">Tidak ada peminjaman aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
                <h2 class="font-bold text-emerald-950">Status Aset</h2>
                <p class="mt-3 text-3xl font-black text-emerald-800">{{ $aset->jumlah_tersedia }}</p>
                <p class="text-sm font-semibold text-emerald-700">unit tersedia dari {{ $aset->jumlah_total }}</p>
                <p class="mt-4 text-xs leading-relaxed text-emerald-700">Aset hanya dapat dipinjam warga RT yang sama dan tidak boleh bentrok jadwal.</p>
            </section>

            @if(auth()->user()->hasPermission('manage-aset'))
                <section class="rounded-3xl border border-red-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-red-900">Hapus Aset</h2>
                    <p class="mt-1 text-xs text-red-600">Aset tidak bisa dihapus jika masih ada peminjaman aktif.</p>
                    <form method="POST" action="{{ route('aset.destroy', $aset) }}" class="mt-4" onsubmit="return confirm('Hapus aset ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700">Hapus Aset</button>
                    </form>
                </section>
            @endif

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold text-slate-900">Riwayat Peminjaman</h2>
                <div class="mt-4 space-y-3">
                    @forelse($riwayat->whereIn('status', ['dikembalikan', 'ditolak']) as $peminjaman)
                        <a href="{{ route('peminjaman-aset.show', $peminjaman) }}" class="block rounded-2xl border border-slate-100 bg-slate-50 p-4 hover:bg-slate-100">
                            <div class="flex items-center justify-between gap-3">
                                <p class="truncate text-sm font-bold text-slate-800">{{ $peminjaman->pemohon->name }}</p>
                                <span class="shrink-0 rounded-full border px-2 py-1 text-[10px] font-bold {{ $peminjaman->status_color }}">{{ $peminjaman->status_label }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $peminjaman->tanggal_mulai->translatedFormat('d M Y') }} - {{ $peminjaman->tanggal_selesai->translatedFormat('d M Y') }}</p>
                        </a>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500">Belum ada riwayat.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
