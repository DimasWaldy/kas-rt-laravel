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
                <p class="text-3xl font-black">{{ $wargas->count() + $domisilis->count() }}</p>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-50">Menunggu</p>
            </div>
        </div>
    </div>

    @if($pjHouse)
        <section role="status" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950">
            <h2 class="font-bold">Langkah berikutnya: tetapkan PJ iuran</h2>
            <p class="mt-2">Domisili {{ $pjHouse->label }} sudah disetujui, tetapi rumah belum siap menerima tagihan. Tetapkan PJ terlebih dahulu, lalu minta bendahara generate ulang periode yang diperlukan.</p>
            <a href="{{ route('admin.rumah.edit', $pjHouse) }}" class="mt-3 inline-block rounded-xl bg-amber-800 px-4 py-2 font-bold text-white">Tetapkan PJ iuran</a>
        </section>
    @endif
    <section class="overflow-hidden rounded-3xl bg-white shadow-xs">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="font-black text-slate-800">Pengajuan Domisili <span class="text-emerald-700">({{ $domisilis->count() }})</span></h2>
            <p class="mt-1 text-sm text-slate-500">Persetujuan akun belum menetapkan rumah. Periksa alamat sebelum menghubungkan warga ke data rumah.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500"><tr><th class="px-6 py-4">Warga / RT</th><th class="px-6 py-4">Rumah diajukan</th><th class="px-6 py-4">Diajukan</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($domisilis as $domisili)
                        <tr>
                            <td class="px-6 py-5"><p class="font-bold text-slate-800">{{ $domisili->nama_lengkap }}</p><p class="mt-1 text-xs text-slate-500">{{ $domisili->user?->rt?->name }}</p></td>
                            <td class="max-w-xs px-6 py-5 text-slate-600">{{ $domisili->rumahDiajukan?->label ?? $domisili->alamat_domisili }}</td>
                            <td class="whitespace-nowrap px-6 py-5 text-slate-600">{{ $domisili->domisili_diajukan_at?->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-6 py-5 text-right"><a href="{{ route('verifikasi-warga.domisili.show', $domisili) }}" class="inline-block whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2 font-bold text-white hover:bg-emerald-700">Periksa domisili</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Tidak ada pengajuan domisili yang menunggu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="overflow-hidden rounded-3xl bg-white shadow-xs">
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
                                ?? ($warga->user?->onboarding_domisili ? 'Diisi warga setelah verifikasi akun' : 'Belum menentukan rumah');
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
