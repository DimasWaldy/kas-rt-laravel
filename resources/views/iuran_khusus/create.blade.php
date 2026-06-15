@extends('layouts.app')

@section('title', 'Buat Iuran Khusus')

@section('content')
<div class="mx-auto max-w-5xl" x-data="{ nominal: {{ (int) old('nominal_per_warga', 0) }}, jumlahWarga: {{ $jumlahWarga }} }">
    <a href="{{ route('iuran-khusus.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali</a>

    <div class="grid gap-6 lg:grid-cols-[0.8fr_1.4fr]">
        <aside class="space-y-5">
            <section class="rounded-3xl border border-emerald-100 bg-emerald-800 p-6 text-white shadow-lg">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-xl"><i class="fa-solid fa-users"></i></span>
                <h2 class="mt-5 text-xl font-black">Target Tagihan</h2>
                <p class="mt-2 text-sm leading-6 text-emerald-50">Iuran ini akan di-generate kepada kepala keluarga atau penanggung jawab rumah di RT Anda.</p>
                <p class="mt-5 text-4xl font-black">{{ $jumlahWarga }}</p>
                <p class="text-sm text-emerald-100">warga target</p>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total estimasi</p>
                <p class="mt-2 text-2xl font-black text-slate-900" x-text="new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(nominal * jumlahWarga)"></p>
                <p class="mt-2 text-xs leading-5 text-slate-500">Nominal per warga dikalikan jumlah target tagihan.</p>
            </section>
        </aside>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold text-emerald-700">Batch insidental baru</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900">Buat Iuran Khusus</h1>

            @if($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">Periksa kembali data iuran yang diisi.</div>
            @endif

            <form method="POST" action="{{ route('iuran-khusus.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="jenis" class="block text-sm font-bold text-slate-700">Jenis Iuran</label>
                    <select id="jenis" name="jenis" required class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Pilih jenis iuran</option>
                        @foreach(['kematian' => 'Iuran Kematian', 'pembangunan' => 'Iuran Pembangunan', 'sosial' => 'Iuran Sosial', 'kegiatan' => 'Iuran Kegiatan', 'lainnya' => 'Iuran Lainnya'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('jenis') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('jenis')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="judul" class="block text-sm font-bold text-slate-700">Judul Iuran</label>
                    <input id="judul" name="judul" value="{{ old('judul') }}" required maxlength="255" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Iuran Kematian Bapak Ahmad">
                    @error('judul')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="keterangan" class="block text-sm font-bold text-slate-700">Keterangan <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea id="keterangan" name="keterangan" rows="4" maxlength="1000" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Jelaskan tujuan dan penggunaan dana.">{{ old('keterangan') }}</textarea>
                    @error('keterangan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="nominal_per_warga" class="block text-sm font-bold text-slate-700">Nominal per Warga</label>
                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-200 focus-within:border-emerald-500"><span class="flex items-center bg-emerald-50 px-4 text-sm font-black text-emerald-800">Rp</span><input id="nominal_per_warga" name="nominal_per_warga" type="number" min="1000" step="1000" x-model.number="nominal" required class="w-full border-0 px-4 py-3 text-sm focus:ring-0"></div>
                        @error('nominal_per_warga')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="tanggal_kejadian" class="block text-sm font-bold text-slate-700">Tanggal Kejadian <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input id="tanggal_kejadian" name="tanggal_kejadian" type="date" value="{{ old('tanggal_kejadian') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @error('tanggal_kejadian')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"><i class="fa-solid fa-circle-info mr-2"></i>Iuran khusus bersifat opsional. Bendahara dapat mengecualikan warga yang tidak mampu setelah batch dibuat.</div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('iuran-khusus.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
                    <button class="rounded-2xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-900" {{ $jumlahWarga === 0 ? 'disabled' : '' }}>Generate Tagihan</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
