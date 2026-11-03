@extends('layouts.app')

@section('title', 'Verifikasi Warga')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-linear-to-r/srgb from-emerald-700 to-emerald-500 p-6 text-white shadow-lg shadow-emerald-200/60">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-100">Administrasi RT</p>
                <h1 class="mt-2 text-2xl font-black">Verifikasi Warga</h1>
                <p class="mt-2 text-sm text-emerald-50">Periksa pendaftaran akun dan pastikan pengajuan domisili sesuai tempat tinggal warga.</p>
            </div>
            <div class="rounded-2xl bg-white/15 px-5 py-4 text-center">
                <p class="text-3xl font-black">{{ $antrian->count() }}</p>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-50">Menunggu</p>
            </div>
        </div>
    </div>

    @if($pjHouse)
        <section role="status" class="rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10 p-5 text-sm text-amber-950 dark:text-amber-100">
            <h2 class="font-bold">Langkah berikutnya: tetapkan PJ iuran</h2>
            <p class="mt-2">Domisili {{ $pjHouse->label }} sudah disetujui, tetapi rumah belum siap menerima tagihan. Tetapkan PJ terlebih dahulu, lalu minta bendahara generate ulang periode yang diperlukan.</p>
            <a href="{{ route('admin.rumah.edit', $pjHouse) }}" class="mt-3 inline-block rounded-xl bg-amber-800 px-4 py-2 font-bold text-white">Tetapkan PJ iuran</a>
        </section>
    @endif
    <section class="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-xs">
        <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-5">
            <h2 class="font-black text-slate-800 dark:text-slate-200">Antrean Warga</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Satu antrean untuk pemeriksaan akun dan pengajuan domisili. Proses sesuai jenis pengajuan.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-6 py-4">Warga / RT</th>
                        <th class="px-6 py-4">Jenis</th>
                        <th class="px-6 py-4">Domisili diajukan</th>
                        <th class="px-6 py-4">Diajukan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($antrian as $item)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/70">
                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-800 dark:text-slate-200">{{ $item->warga->nama_lengkap }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $item->rt ?? $item->warga->user?->rt?->name }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-5">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $item->jenis === 'akun' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300' : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' }}">
                                    {{ $item->jenis === 'akun' ? 'Akun baru' : 'Pengajuan domisili' }}
                                </span>
                            </td>
                            <td class="max-w-xs px-6 py-5 text-slate-600 dark:text-slate-300">
                                <p class="truncate">{{ $item->label }}</p>
                                @if($item->jenis === 'akun')
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $item->warga->metode_verifikasi === 'dokumen' ? 'Metode: Dokumen' : 'Metode: Tatap muka' }}
                                    </p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-5 text-slate-600 dark:text-slate-300">{{ $item->tanggal?->translatedFormat('d M Y, H:i') }}</td>
                            <td class="whitespace-nowrap px-6 py-5 text-right">
                                @if($item->jenis === 'akun')
                                    <a href="{{ route('verifikasi-warga.show', $item->warga) }}" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2 font-bold text-white hover:bg-emerald-700">
                                        Proses Verifikasi
                                        <i class="fa-solid fa-arrow-right text-xs"></i>
                                    </a>
                                @else
                                    <a href="{{ route('verifikasi-warga.domisili.show', $item->warga) }}" class="inline-block whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2 font-bold text-white hover:bg-emerald-700">Periksa domisili</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <i class="fa-solid fa-circle-check text-4xl text-emerald-400"></i>
                                <p class="mt-4 font-bold text-slate-700 dark:text-slate-300">Tidak ada pengajuan yang menunggu.</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Semua calon warga dan pengajuan domisili sudah diproses.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
