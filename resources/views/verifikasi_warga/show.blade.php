@extends('layouts.app')

@section('title', 'Proses Verifikasi Warga')

@section('content')
<div
    class="space-y-6"
    x-data='{
        statusDalamKk: @js(old("status_dalam_kk", $warga->status_dalam_kk)),
        rumahId: @js((string) old("rumah_id", $warga->rumah_diajukan_id ?? $warga->user?->rumah_id ?? "")),
        kartuKeluargaId: @js((string) old("kartu_keluarga_id", "")),
        kkOptions: [],
        kkLoading: false,
        showTolak: @js($errors->has("catatan_verifikasi")),
        async loadKk() {
            this.kkOptions = [];
            if (!this.rumahId) return;
            this.kkLoading = true;
            try {
                const response = await fetch(`${@js(url("/api/rumah"))}/${this.rumahId}/kartu-keluarga`, {
                    headers: { "Accept": "application/json" }
                });
                if (!response.ok) throw new Error("Gagal mengambil daftar KK");
                this.kkOptions = await response.json();
            } finally {
                this.kkLoading = false;
            }
        }
    }'
    x-init="if (rumahId) loadKk()"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('verifikasi-warga.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali</a>
            <h1 class="mt-3 text-2xl font-black text-slate-800">{{ $warga->nama_lengkap }}</h1>
            <p class="mt-1 text-sm text-slate-500">Terdaftar {{ $warga->created_at->translatedFormat('d F Y, H:i') }}</p>
        </div>
        <span class="inline-flex w-fit rounded-full bg-amber-50 px-4 py-2 text-sm font-bold text-amber-700">{{ $warga->status_verifikasi_label }}</span>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <div class="space-y-6">
            <section class="rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="font-black text-slate-800">Data Calon Warga</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nama akun</dt><dd class="mt-1 font-semibold text-slate-800">{{ $warga->user?->name }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</dt><dd class="mt-1 font-semibold text-slate-800">{{ $warga->user?->email }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nomor HP</dt><dd class="mt-1 font-semibold text-slate-800">{{ $warga->user?->phone }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Status keluarga</dt><dd class="mt-1 font-semibold text-slate-800">{{ $warga->status_dalam_kk === 'kepala_keluarga' ? 'Kepala Keluarga' : 'Anggota Keluarga' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">NIK awal</dt><dd class="mt-1 font-semibold text-slate-800">{{ $warga->nik ?: 'Belum diisi' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Domisili diajukan</dt><dd class="mt-1 font-semibold leading-6 text-slate-800">{{ $warga->rumahDiajukan?->label ?? $warga->rumah_diajukan ?? 'Belum ada' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="font-black text-slate-800">Dokumen Pendukung</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @if ($warga->dokumen_kk)
                        <a href="{{ route('verifikasi-warga.dokumen', [$warga, 'kk']) }}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-2xl border border-blue-200 bg-blue-50 p-4 font-bold text-blue-700 hover:bg-blue-100">
                            <span><i class="fa-solid fa-file-lines mr-2"></i>Lihat Dokumen KK</span><i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                        </a>
                    @endif
                    @if ($warga->dokumen_ktp)
                        <a href="{{ route('verifikasi-warga.dokumen', [$warga, 'ktp']) }}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-2xl border border-blue-200 bg-blue-50 p-4 font-bold text-blue-700 hover:bg-blue-100">
                            <span><i class="fa-solid fa-id-card mr-2"></i>Lihat Dokumen KTP</span><i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                        </a>
                    @endif
                    @if (! $warga->dokumen_kk && ! $warga->dokumen_ktp)
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Tidak ada dokumen yang diunggah. Lakukan verifikasi tatap muka.</p>
                    @endif
                </div>
            </section>
        </div>

        <section class="rounded-3xl bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-lg font-black text-slate-800">Konfirmasi Data Final</h2>
            <p class="mt-1 text-sm text-slate-500">Pastikan Rumah, KK, dan NIK sesuai dokumen atau hasil pertemuan.</p>

            <form method="POST" action="{{ route('verifikasi-warga.verifikasi', $warga) }}" class="mt-6 space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <label for="rumah_id" class="text-sm font-bold text-slate-700">Rumah final</label>
                    <select id="rumah_id" name="rumah_id" x-model="rumahId" x-on:change="kartuKeluargaId = ''; loadKk()" required class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Pilih rumah</option>
                        @foreach ($rumahs as $rumah)
                            <option value="{{ $rumah->id }}">{{ $rumah->label }}</option>
                        @endforeach
                    </select>
                </div>

                <fieldset>
                    <legend class="text-sm font-bold text-slate-700">Status dalam KK</legend>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4">
                            <input type="radio" name="status_dalam_kk" value="kepala_keluarga" x-model="statusDalamKk" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-bold">Kepala Keluarga</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4">
                            <input type="radio" name="status_dalam_kk" value="anggota" x-model="statusDalamKk" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-bold">Anggota Keluarga</span>
                        </label>
                    </div>
                </fieldset>

                <div x-show="statusDalamKk === 'kepala_keluarga'" x-cloak>
                    <label for="no_kk" class="text-sm font-bold text-slate-700">Nomor KK baru</label>
                    <input id="no_kk" name="no_kk" type="text" value="{{ old('no_kk') }}" :required="statusDalamKk === 'kepala_keluarga'" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="16 digit Nomor KK">
                </div>

                <div x-show="statusDalamKk === 'anggota'" x-cloak>
                    <label for="kartu_keluarga_id" class="text-sm font-bold text-slate-700">Kartu Keluarga di rumah ini</label>
                    <select id="kartu_keluarga_id" name="kartu_keluarga_id" x-model="kartuKeluargaId" :required="statusDalamKk === 'anggota'" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="" x-text="kkLoading ? 'Memuat daftar KK...' : 'Pilih Kartu Keluarga'"></option>
                        <template x-for="kk in kkOptions" :key="kk.id">
                            <option :value="String(kk.id)" x-text="`${kk.no_kk} — ${kk.nama_kepala_keluarga}`"></option>
                        </template>
                    </select>
                    <p x-show="!kkLoading && rumahId && kkOptions.length === 0" class="mt-2 text-xs text-amber-700">Belum ada KK di rumah ini. Pilih Kepala Keluarga untuk membuat KK baru.</p>
                </div>

                <div>
                    <label for="nik" class="text-sm font-bold text-slate-700">NIK final</label>
                    <input id="nik" name="nik" type="text" value="{{ old('nik', $warga->nik) }}" required inputmode="numeric" pattern="[0-9]{16}" maxlength="16" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="16 digit NIK">
                </div>

                <div>
                    <label for="metode_verifikasi" class="text-sm font-bold text-slate-700">Metode verifikasi</label>
                    <select id="metode_verifikasi" name="metode_verifikasi" required class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="tatap_muka" @selected(old('metode_verifikasi', $warga->metode_verifikasi ?? 'tatap_muka') === 'tatap_muka')>Tatap muka</option>
                        <option value="dokumen" @selected(old('metode_verifikasi', $warga->metode_verifikasi) === 'dokumen')>Review dokumen</option>
                    </select>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" x-on:click="showTolak = true" class="rounded-2xl border border-rose-200 px-5 py-3 text-sm font-bold text-rose-700 hover:bg-rose-50">Tolak</button>
                    <button type="submit" class="rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">Verifikasi & Aktifkan</button>
                </div>
            </form>
        </section>
    </div>

    <div x-show="showTolak" x-cloak x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4" x-on:keydown.escape.window="showTolak = false">
        <div x-on:click.outside="showTolak = false" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
            <h2 class="text-xl font-black text-slate-800">Tolak Pendaftaran</h2>
            <p class="mt-2 text-sm text-slate-500">Tuliskan alasan yang jelas agar warga dapat melakukan klarifikasi.</p>
            <form method="POST" action="{{ route('verifikasi-warga.tolak', $warga) }}" class="mt-5">
                @csrf
                @method('PATCH')
                <label for="catatan_verifikasi" class="text-sm font-bold text-slate-700">Alasan penolakan</label>
                <textarea id="catatan_verifikasi" name="catatan_verifikasi" rows="5" minlength="10" required class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-rose-500 focus:ring-rose-500" placeholder="Minimal 10 karakter">{{ old('catatan_verifikasi') }}</textarea>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" x-on:click="showTolak = false" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700">Batal</button>
                    <button type="submit" class="rounded-2xl bg-rose-600 px-5 py-3 text-sm font-black text-white hover:bg-rose-700">Tolak Pendaftaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
