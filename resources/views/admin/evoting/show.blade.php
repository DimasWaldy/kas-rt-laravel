@extends('layouts.app')

@section('title', 'Kelola ' . $pemilihan->title)

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.evoting.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-purple-700 dark:text-slate-400 dark:hover:text-purple-300">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Pemilihan
            </a>
            <div class="mt-2 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-purple-100 px-2.5 py-1 text-xs font-black text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                    <i class="fa-solid fa-gear"></i> Pengaturan Pemilu
                </span>
                <span class="text-xs font-bold text-slate-400">•</span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    {{ $pemilihan->scope_type === 'rw' ? 'Wilayah RW' : ($pemilihan->rt ? 'RT '.$pemilihan->rt->name : 'Wilayah RT') }}
                </span>
            </div>
            <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-slate-100 sm:text-3xl">{{ $pemilihan->title }}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('evoting.show', $pemilihan->slug) }}" target="_blank" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Buka Bilik Suara Warga
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-bold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
            <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-xs font-bold text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Detail & Status Bar --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
            <h2 class="text-base font-black text-slate-900 dark:text-slate-100">Informasi Agenda Pemilihan</h2>
            
            <div class="mt-4 grid grid-cols-1 gap-4 text-xs sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/60">
                    <span class="text-slate-400">Waktu Mulai:</span>
                    <p class="mt-0.5 font-black text-slate-800 dark:text-slate-200">{{ $pemilihan->start_date->translatedFormat('d M Y H:i') }} WIB</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/60">
                    <span class="text-slate-400">Waktu Selesai:</span>
                    <p class="mt-0.5 font-black text-slate-800 dark:text-slate-200">{{ $pemilihan->end_date->translatedFormat('d M Y H:i') }} WIB</p>
                </div>
            </div>

            @if($pemilihan->description)
                <div class="mt-4 rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                    <span class="font-bold text-slate-400">Tata Tertib / Catatan:</span>
                    <p class="mt-1 leading-relaxed">{{ $pemilihan->description }}</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                        <i class="fa-solid fa-users text-base"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400">Total Pemilih Mencoblos</span>
                        <p class="text-base font-black text-slate-900 dark:text-slate-100">{{ $pemilihan->voters->count() }} Warga</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <i class="fa-solid fa-box-archive text-base"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400">Total Surat Suara Sah</span>
                        <p class="text-base font-black text-emerald-600 dark:text-emerald-400">{{ $totalSuara }} Suara</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ubah Status Pemilihan --}}
        <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-base font-black text-slate-900 dark:text-slate-100">Status Pemilihan</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ubah tahapan pemilihan sesuai jadwal resmi.</p>

            <form method="POST" action="{{ route('admin.evoting.status', $pemilihan->slug) }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')

                <div class="space-y-1.5">
                    <label for="status" class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih Status Tahapan</label>
                    <select name="status" id="status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-purple-600 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="draft" @selected($pemilihan->status === 'draft')>Draft (Persiapan)</option>
                        <option value="aktif" @selected($pemilihan->status === 'aktif')>Aktif (Bilik Dibuka)</option>
                        <option value="selesai" @selected($pemilihan->status === 'selesai')>Selesai (Ditutup & Hasil Final)</option>
                        <option value="dibatalkan" @selected($pemilihan->status === 'dibatalkan')>Dibatalkan</option>
                    </select>
                </div>

                <button type="submit" class="w-full rounded-xl bg-purple-700 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-purple-800">
                    Perbarui Status
                </button>
            </form>
        </div>
    </div>

    {{-- KANDIDAT PASLON --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-slate-100">Daftar Pasangan Calon (Kandidat)</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Kelola nomor urut, nama calon ketua & wakil, serta visi misi.</p>
            </div>
            <span class="rounded-lg bg-purple-100 px-3 py-1 text-xs font-black text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                {{ $pemilihan->kandidats->count() }} Paslon Terdaftar
            </span>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($pemilihan->kandidats as $kandidat)
                <div class="flex flex-col justify-between rounded-3xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-700 text-lg font-black text-white">
                                {{ sprintf('%02d', $kandidat->nomor_urut) }}
                            </span>

                            <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-800">
                                @if($kandidat->foto_path)
                                    <img src="{{ asset('storage/'.$kandidat->foto_path) }}" alt="{{ $kandidat->nama_calon }}" class="h-full w-full object-cover">
                                @else
                                    <i class="fa-solid fa-user-tie text-2xl text-purple-400"></i>
                                @endif
                            </div>
                        </div>

                        <div>
                            <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400">Paslon No. {{ $kandidat->nomor_urut }}</span>
                            <h3 class="text-base font-black text-slate-900 dark:text-slate-100">{{ $kandidat->nama_calon }}</h3>
                            @if($kandidat->nama_wakil)
                                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">& {{ $kandidat->nama_wakil }}</p>
                            @endif
                            @if($kandidat->slogan)
                                <p class="mt-1 text-xs italic text-slate-600 dark:text-slate-400">"{{ $kandidat->slogan }}"</p>
                            @endif
                        </div>

                        <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                            <p><strong>Suara Diraih:</strong> {{ $kandidat->suaras->count() }} suara</p>
                        </div>
                    </div>

                    @if($totalSuara === 0)
                        <div class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800">
                            <form method="POST" action="{{ route('admin.evoting.kandidat.destroy', $kandidat->id) }}" onsubmit="return confirm('Yakin ingin menghapus kandidat paslon ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full rounded-xl bg-rose-50 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300">
                                    <i class="fa-solid fa-trash mr-1.5"></i> Hapus Paslon
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-800 md:col-span-2 lg:col-span-3">
                    <p class="text-xs font-bold text-slate-500">Belum ada kandidat terdaftar. Silakan tambahkan kandidat menggunakan formulir di bawah.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- FORM TAMBAH KANDIDAT --}}
    <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:p-8">
        <h2 class="text-lg font-black text-slate-900 dark:text-slate-100">Tambah Pasangan Calon Baru</h2>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Daftarkan pasangan calon ketua dan wakil untuk agenda pemilu ini.</p>

        <form method="POST" action="{{ route('admin.evoting.kandidat.store', $pemilihan->slug) }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="space-y-1.5">
                    <label for="nomor_urut" class="text-xs font-bold text-slate-700 dark:text-slate-300">Nomor Urut <span class="text-rose-500">*</span></label>
                    <input
                        type="number"
                        name="nomor_urut"
                        id="nomor_urut"
                        value="{{ old('nomor_urut', $pemilihan->kandidats->count() + 1) }}"
                        min="1"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-900 focus:border-purple-600 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >
                </div>

                <div class="space-y-1.5">
                    <label for="nama_calon" class="text-xs font-bold text-slate-700 dark:text-slate-300">Nama Calon Ketua <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="nama_calon"
                        id="nama_calon"
                        value="{{ old('nama_calon') }}"
                        required
                        placeholder="Nama lengkap calon ketua"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-900 focus:border-purple-600 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >
                </div>

                <div class="space-y-1.5">
                    <label for="nama_wakil" class="text-xs font-bold text-slate-700 dark:text-slate-300">Nama Calon Wakil (Opsional)</label>
                    <input
                        type="text"
                        name="nama_wakil"
                        id="nama_wakil"
                        value="{{ old('nama_wakil') }}"
                        placeholder="Nama lengkap calon wakil"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-900 focus:border-purple-600 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="slogan" class="text-xs font-bold text-slate-700 dark:text-slate-300">Slogan / Tagline Paslon</label>
                    <input
                        type="text"
                        name="slogan"
                        id="slogan"
                        value="{{ old('slogan') }}"
                        placeholder="Contoh: Bersama Warga Mewujudkan Lingkungan Asri & Digital"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-900 focus:border-purple-600 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >
                </div>

                <div class="space-y-1.5">
                    <label for="foto" class="text-xs font-bold text-slate-700 dark:text-slate-300">Foto Paslon (JPG/PNG, Max 2MB)</label>
                    <input
                        type="file"
                        name="foto"
                        id="foto"
                        accept="image/*"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="visi" class="text-xs font-bold text-slate-700 dark:text-slate-300">Visi Kandidat</label>
                    <textarea
                        name="visi"
                        id="visi"
                        rows="2"
                        placeholder="Visi utama pasangan calon..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-900 focus:border-purple-600 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >{{ old('visi') }}</textarea>
                </div>

                <div class="space-y-1.5">
                    <label for="misi" class="text-xs font-bold text-slate-700 dark:text-slate-300">Misi Kandidat</label>
                    <textarea
                        name="misi"
                        id="misi"
                        rows="2"
                        placeholder="Misi-misi program kerja kandidat..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-900 focus:border-purple-600 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >{{ old('misi') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-purple-800">
                    <i class="fa-solid fa-plus"></i>
                    <span>Simpan Kandidat</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
