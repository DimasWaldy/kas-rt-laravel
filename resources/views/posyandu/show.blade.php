@extends('layouts.app')

@section('title', 'KMS ' . $balita->nama)

@section('content')
@php
    $canManage = auth()->user()->hasPermission('manage-posyandu');
    $canRecord = auth()->user()->hasPermission('record-posyandu') && $balita->is_active;
    $last = $balita->pemeriksaans->last();
    $statusMeta = fn ($status) => match($status) {
        'berat_sangat_kurang' => ['Berat sangat kurang', 'border-red-200 bg-red-50 text-red-700'],
        'berat_kurang' => ['Berat kurang', 'border-amber-200 bg-amber-50 text-amber-700'],
        'normal' => ['Normal', 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        'di_atas_rentang_normal' => ['Di atas rentang normal', 'border-blue-200 bg-blue-50 text-blue-700'],
        default => ['Belum dinilai', 'border-slate-200 bg-slate-50 text-slate-600'],
    };
    [$lastStatus, $lastStatusClass] = $statusMeta($last?->status_bb_u);
@endphp

<div class="mx-auto max-w-7xl space-y-6" x-data="{ recordOpen: {{ $errors->any() ? 'true' : 'false' }}, editId: null }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('posyandu.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-rose-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke daftar</a>
        <div class="flex flex-wrap gap-2">
            @if($canManage)
                <a href="{{ route('posyandu.edit', $balita) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-pen mr-1"></i> Edit Identitas</a>
                <form method="POST" action="{{ route('posyandu.toggle-active', $balita) }}">@csrf @method('PATCH')<button class="rounded-xl border px-4 py-2.5 text-sm font-bold {{ $balita->is_active ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">{{ $balita->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
            @endif
            @if($canRecord)
                <button type="button" x-on:click="recordOpen = true; editId = null" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-rose-700"><i class="fa-solid fa-weight-scale mr-1"></i> Catat Pemeriksaan</button>
            @endif
        </div>
    </div>

    <section class="grid gap-5 lg:grid-cols-[1.1fr_1.9fr]">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl {{ $balita->jenis_kelamin === 'laki_laki' ? 'bg-blue-50 text-blue-600' : 'bg-rose-50 text-rose-600' }}"><i class="fa-solid fa-child-reaching text-2xl"></i></span>
                <span class="rounded-full border px-3 py-1.5 text-xs font-black {{ $lastStatusClass }}">{{ $lastStatus }}</span>
            </div>
            <h1 class="mt-5 text-2xl font-black text-slate-900">{{ $balita->nama }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $balita->jenis_kelamin_label }} &middot; {{ $balita->rt->name }} &middot; {{ $balita->is_active ? 'Aktif' : 'Nonaktif' }}</p>
            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-xs text-slate-400">Tanggal lahir</dt><dd class="mt-1 font-bold text-slate-800">{{ $balita->tanggal_lahir->translatedFormat('d F Y') }}</dd></div>
                <div><dt class="text-xs text-slate-400">Usia sekarang</dt><dd class="mt-1 font-bold text-slate-800">{{ $balita->usia_sekarang_bulan }} bulan</dd></div>
                <div><dt class="text-xs text-slate-400">NIK</dt><dd class="mt-1 font-bold text-slate-800">{{ $balita->nik ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-400">Nomor KK</dt><dd class="mt-1 font-bold text-slate-800">{{ $balita->no_kk ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-400">Berat lahir</dt><dd class="mt-1 font-bold text-slate-800">{{ $balita->berat_lahir_kg ? number_format($balita->berat_lahir_kg, 2, ',', '.').' kg' : '-' }}</dd></div>
                <div><dt class="text-xs text-slate-400">Panjang lahir</dt><dd class="mt-1 font-bold text-slate-800">{{ $balita->panjang_lahir_cm ? number_format($balita->panjang_lahir_cm, 1, ',', '.').' cm' : '-' }}</dd></div>
            </dl>
            <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                <p><i class="fa-solid fa-user-group mr-2 text-rose-500"></i><strong>Wali:</strong> {{ $balita->orangTua?->name ?? 'Belum dihubungkan' }}</p>
                <p class="mt-2"><strong>Ibu:</strong> {{ $balita->nama_ibu ?: '-' }} &middot; <strong>Ayah:</strong> {{ $balita->nama_ayah ?: '-' }}</p>
            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-3">
                <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Berat Terakhir</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $last ? number_format($last->berat_kg, 2, ',', '.').' kg' : '-' }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Z-score BB/U</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $last ? number_format($last->z_score_bb_u, 2, ',', '.') : '-' }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pemeriksaan</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $balita->pemeriksaans->count() }}x</p></div>
            </div>
            <div class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-xs leading-5 text-blue-800"><i class="fa-solid fa-circle-info mr-1"></i> Status ini hanya berdasarkan indikator <strong>berat badan menurut umur (BB/U)</strong>. Penilaian stunting memerlukan panjang/tinggi badan menurut umur, sedangkan wasting memerlukan berat terhadap panjang/tinggi badan.</div>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-5">
            <p class="text-sm font-semibold text-rose-600">Kartu Menuju Sehat digital</p>
            <h2 class="text-xl font-black text-slate-900">Kurva Berat Badan Menurut Umur</h2>
            <p class="mt-1 text-xs text-slate-500">Referensi {{ $kmsStandards->first()?->versi_standar ?? 'WHO Child Growth Standards' }} &middot; {{ $balita->jenis_kelamin_label }} &middot; 0-60 bulan.</p>
        </div>
        @if($kmsStandards->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-800">Data referensi WHO belum tersedia. Jalankan seeder standar pertumbuhan sebelum menampilkan grafik.</div>
        @else
            @include('posyandu.partials.kms-chart')
        @endif
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5 sm:p-6"><h2 class="text-xl font-black text-slate-900">Riwayat Pemeriksaan</h2><p class="mt-1 text-sm text-slate-500">Hasil Z-score dihitung server dari jenis kelamin, umur, dan berat badan.</p></div>
        @if($balita->pemeriksaans->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-slate-500">Belum ada pemeriksaan yang tercatat.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Tanggal / Usia</th><th class="px-5 py-3">Berat</th><th class="px-5 py-3">Tinggi</th><th class="px-5 py-3">Z-score</th><th class="px-5 py-3">Status BB/U</th><th class="px-5 py-3">Petugas</th>@if(auth()->user()->hasPermission('record-posyandu'))<th class="px-5 py-3 text-right">Aksi</th>@endif</tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($balita->pemeriksaans->sortByDesc('tanggal_pemeriksaan') as $item)
                            @php
                                [$label, $class] = $statusMeta($item->status_bb_u);
                            @endphp
                            <tr><td class="px-5 py-4"><p class="font-bold text-slate-800">{{ $item->tanggal_pemeriksaan->format('d-m-Y') }}</p><p class="text-xs text-slate-400">{{ $item->usia_bulan }} bulan</p></td><td class="px-5 py-4 font-bold">{{ number_format($item->berat_kg, 2, ',', '.') }} kg</td><td class="px-5 py-4">{{ $item->panjang_tinggi_cm ? number_format($item->panjang_tinggi_cm, 1, ',', '.').' cm' : '-' }}</td><td class="px-5 py-4 font-black">{{ number_format($item->z_score_bb_u, 2, ',', '.') }}</td><td class="px-5 py-4"><span class="rounded-full border px-3 py-1 text-xs font-bold {{ $class }}">{{ $label }}</span></td><td class="px-5 py-4 text-slate-500">{{ $item->petugas?->name ?? '-' }}</td>
                                @if(auth()->user()->hasPermission('record-posyandu'))
                                    <td class="px-5 py-4"><div class="flex justify-end gap-2"><button type="button" x-on:click="editId = {{ $item->id }}" class="text-xs font-bold text-blue-600">Edit</button><form method="POST" action="{{ route('posyandu.pemeriksaan.destroy', $item) }}" onsubmit="return confirm('Hapus pemeriksaan ini?')">@csrf @method('DELETE')<button class="text-xs font-bold text-red-600">Hapus</button></form></div></td>
                                @endif
                            </tr>
                            @if(auth()->user()->hasPermission('record-posyandu'))
                                <tr x-cloak x-show="editId === {{ $item->id }}"><td colspan="7" class="bg-blue-50 px-5 py-5">
                                    <form method="POST" action="{{ route('posyandu.pemeriksaan.update', $item) }}" class="grid gap-3 md:grid-cols-4">@csrf @method('PUT')
                                        <input type="date" name="tanggal_pemeriksaan" value="{{ $item->tanggal_pemeriksaan->toDateString() }}" max="{{ today()->toDateString() }}" required class="rounded-xl border-slate-200 text-sm"><input type="number" name="berat_kg" value="{{ $item->berat_kg }}" min="0.5" max="40" step="0.01" required class="rounded-xl border-slate-200 text-sm"><input type="number" name="panjang_tinggi_cm" value="{{ $item->panjang_tinggi_cm }}" min="30" max="130" step="0.1" class="rounded-xl border-slate-200 text-sm"><select name="metode_ukur_tinggi" class="rounded-xl border-slate-200 text-sm"><option value="">Metode tinggi</option><option value="terlentang" @selected($item->metode_ukur_tinggi === 'terlentang')>Terlentang</option><option value="berdiri" @selected($item->metode_ukur_tinggi === 'berdiri')>Berdiri</option></select>
                                        <div class="md:col-span-4 flex justify-end gap-2"><button type="button" x-on:click="editId = null" class="rounded-xl bg-white px-4 py-2 text-xs font-bold text-slate-600">Batal</button><button class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white">Hitung Ulang & Simpan</button></div>
                                    </form>
                                </td></tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if($canRecord)
        <div x-cloak x-show="recordOpen" x-transition.opacity x-on:click.self="recordOpen = false" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-4">
            <form method="POST" action="{{ route('posyandu.pemeriksaan.store', $balita) }}" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
                @csrf
                <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-rose-600">Penimbangan {{ $balita->nama }}</p><h2 class="text-xl font-black text-slate-900">Catat Pemeriksaan</h2></div><button type="button" x-on:click="recordOpen = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-xl"></i></button></div>
                @if($errors->any())<div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700">Periksa kembali data pemeriksaan yang diisi.</div>@endif
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div><label class="text-sm font-bold text-slate-700">Tanggal Pemeriksaan</label><input type="date" name="tanggal_pemeriksaan" value="{{ old('tanggal_pemeriksaan', today()->toDateString()) }}" min="{{ $balita->tanggal_lahir->toDateString() }}" max="{{ today()->toDateString() }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm">@error('tanggal_pemeriksaan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-bold text-slate-700">Berat Badan (kg)</label><input type="number" name="berat_kg" value="{{ old('berat_kg') }}" min="0.5" max="40" step="0.01" required class="mt-2 w-full rounded-xl border-slate-200 text-sm">@error('berat_kg')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-bold text-slate-700">Panjang / Tinggi (cm)</label><input type="number" name="panjang_tinggi_cm" value="{{ old('panjang_tinggi_cm') }}" min="30" max="130" step="0.1" class="mt-2 w-full rounded-xl border-slate-200 text-sm">@error('panjang_tinggi_cm')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-bold text-slate-700">Metode Ukur</label><select name="metode_ukur_tinggi" class="mt-2 w-full rounded-xl border-slate-200 text-sm"><option value="">Pilih jika tinggi diisi</option><option value="terlentang" @selected(old('metode_ukur_tinggi') === 'terlentang')>Terlentang</option><option value="berdiri" @selected(old('metode_ukur_tinggi') === 'berdiri')>Berdiri</option></select>@error('metode_ukur_tinggi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-bold text-slate-700">Lingkar Kepala (cm)</label><input type="number" name="lingkar_kepala_cm" value="{{ old('lingkar_kepala_cm') }}" min="20" max="70" step="0.1" class="mt-2 w-full rounded-xl border-slate-200 text-sm"></div>
                    <div><label class="text-sm font-bold text-slate-700">Lingkar Lengan (cm)</label><input type="number" name="lingkar_lengan_cm" value="{{ old('lingkar_lengan_cm') }}" min="5" max="40" step="0.1" class="mt-2 w-full rounded-xl border-slate-200 text-sm"></div>
                    <label class="sm:col-span-2 inline-flex items-center gap-3 rounded-xl bg-amber-50 p-4 text-sm font-bold text-amber-800"><input type="checkbox" name="vitamin_a" value="1" @checked(old('vitamin_a')) class="rounded border-amber-300 text-amber-600 focus:ring-amber-500"> Mendapat vitamin A pada pemeriksaan ini</label>
                    <div class="sm:col-span-2"><label class="text-sm font-bold text-slate-700">Catatan Petugas</label><textarea name="catatan" rows="3" maxlength="1000" class="mt-2 w-full rounded-xl border-slate-200 text-sm">{{ old('catatan') }}</textarea></div>
                </div>
                <div class="mt-6 flex justify-end gap-2"><button type="button" x-on:click="recordOpen = false" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600">Batal</button><button class="rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white hover:bg-rose-700">Simpan & Hitung Z-score</button></div>
            </form>
        </div>
    @endif
</div>
@endsection
