@extends('layouts.app')

@section('title', 'Verifikasi Warga')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-gradient-to-r from-emerald-700 to-emerald-500 p-6 text-white shadow-lg shadow-emerald-200/60">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-100">Administrasi RT</p>
                <h1 class="mt-2 text-2xl font-black">Verifikasi Warga Baru</h1>
                <p class="mt-2 text-sm text-emerald-50">Periksa identitas dan tetapkan Rumah serta Kartu Keluarga calon warga.</p>
            </div>
            <div class="rounded-2xl bg-white/15 px-5 py-4 text-center">
                <p class="text-3xl font-black">{{ $wargas->count() }}</p>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-50">Menunggu</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="font-black text-slate-800">Daftar Calon Warga</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Domisili diajukan</th>
                        <th class="px-6 py-4">Metode</th>
                        <th class="px-6 py-4">Tanggal daftar</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($wargas as $warga)
                        @php
                            $rumahLabel = $warga->rumahDiajukan?->label
                                ?? $warga->rumah_diajukan
                                ?? 'Belum menentukan rumah';
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-800">{{ $warga->nama_lengkap }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $warga->user?->email }}</p>
                            </td>
                            <td class="max-w-xs px-6 py-5 text-slate-600">{{ $rumahLabel }}</td>
                            <td class="px-6 py-5">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $warga->metode_verifikasi === 'dokumen' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $warga->metode_verifikasi === 'dokumen' ? 'Dokumen' : 'Tatap muka' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $warga->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="whitespace-nowrap px-6 py-5 text-right">
                                <a href="{{ route('verifikasi-warga.show', $warga) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 font-bold text-white hover:bg-emerald-700">
                                    Proses Verifikasi
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <i class="fa-solid fa-circle-check text-4xl text-emerald-400"></i>
                                <p class="mt-4 font-bold text-slate-700">Tidak ada pengajuan yang menunggu.</p>
                                <p class="mt-1 text-sm text-slate-500">Semua calon warga sudah diproses.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
