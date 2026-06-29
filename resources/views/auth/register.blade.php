<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Warga | Smart RW</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <main class="mx-auto max-w-5xl px-4 py-8 sm:py-12">
        <div class="mb-6 flex items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-emerald-700">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke Login
            </a>
            <span class="text-sm font-black tracking-wide text-emerald-700">SMART RW</span>
        </div>

        <form
            action="{{ route('register') }}"
            method="POST"
            enctype="multipart/form-data"
            class="overflow-hidden rounded-3xl bg-white shadow-xl shadow-slate-200/60"
            x-data='{
                rumahBelumAda: @js(filled(old("rumah_baru_alamat"))),
                rumahId: @js((string) old("rumah_id", "")),
                pencarianRumah: "",
                rumahs: @js($rumahs->map(fn ($rumah) => ["id" => (string) $rumah->id, "label" => $rumah->label])->values()),
                get rumahTersaring() {
                    const kata = this.pencarianRumah.toLowerCase().trim();
                    return kata ? this.rumahs.filter(rumah => rumah.label.toLowerCase().includes(kata)) : this.rumahs;
                }
            }'
        >
            @csrf

            <div class="bg-gradient-to-r from-emerald-700 to-emerald-500 px-6 py-8 text-white sm:px-10">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl">
                        <i class="fa-solid fa-user-plus"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-black sm:text-3xl">Pendaftaran Warga</h1>
                        <p class="mt-2 max-w-2xl text-sm text-emerald-50">Buat akun dan pilih domisili. Data Anda akan diperiksa oleh pengurus RT sebelum akses fitur diaktifkan.</p>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="mx-6 mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 sm:mx-10">
                    <p class="font-bold">Periksa kembali data berikut:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-8 p-6 sm:p-10">
                <section>
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-700">1</span>
                        <div>
                            <h2 class="font-black text-slate-800">Data Akun</h2>
                            <p class="text-xs text-slate-500">Informasi untuk masuk dan menerima kabar dari RT.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="text-sm font-bold text-slate-700">Nama akun</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Nama yang tampil di aplikasi">
                        </div>
                        <div>
                            <label for="email" class="text-sm font-bold text-slate-700">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="nama@email.com">
                        </div>
                        <div>
                            <label for="phone" class="text-sm font-bold text-slate-700">Nomor HP</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required inputmode="numeric" pattern="[0-9]{10,13}" maxlength="13" autocomplete="tel" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="08xxxxxxxxxx">
                        </div>
                        <div></div>
                        <div>
                            <label for="password" class="text-sm font-bold text-slate-700">Password</label>
                            <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Minimal 8 karakter">
                        </div>
                        <div>
                            <label for="password_confirmation" class="text-sm font-bold text-slate-700">Konfirmasi password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Ulangi password">
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-8">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-700">2</span>
                        <div>
                            <h2 class="font-black text-slate-800">Pilih Domisili</h2>
                            <p class="text-xs text-slate-500">Pilih rumah yang telah didata pengurus RT.</p>
                        </div>
                    </div>

                    <div x-show="!rumahBelumAda" x-cloak class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="pencarian_rumah" class="text-sm font-bold text-slate-700">Cari rumah</label>
                            <div class="relative mt-2">
                                <i class="fa-solid fa-magnifying-glass absolute left-4 top-4 text-slate-400"></i>
                                <input id="pencarian_rumah" type="search" x-model="pencarianRumah" class="w-full rounded-2xl border-slate-200 py-3 pl-11 pr-4 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Cari kode rumah atau alamat">
                            </div>
                        </div>
                        <div>
                            <label for="rumah_id" class="text-sm font-bold text-slate-700">Rumah</label>
                            <select id="rumah_id" name="rumah_id" x-model="rumahId" :required="!rumahBelumAda" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Pilih rumah</option>
                                <template x-for="rumah in rumahTersaring" :key="rumah.id">
                                    <option :value="rumah.id" x-text="rumah.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <label class="mt-4 inline-flex cursor-pointer items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">
                        <input type="checkbox" x-model="rumahBelumAda" x-on:change="if (rumahBelumAda) rumahId = ''" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Rumah saya belum ada di daftar
                    </label>

                    <div x-show="rumahBelumAda" x-cloak class="mt-4">
                        <label for="rumah_baru_alamat" class="text-sm font-bold text-slate-700">Alamat rumah yang diajukan</label>
                        <textarea id="rumah_baru_alamat" name="rumah_baru_alamat" :required="rumahBelumAda" rows="3" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Tuliskan alamat lengkap dan patokan rumah">{{ old('rumah_baru_alamat') }}</textarea>
                        <p class="mt-2 text-xs text-amber-700">Pengurus RT akan mengonfirmasi alamat dan membuat master rumah jika valid.</p>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-8">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-700">3</span>
                        <div>
                            <h2 class="font-black text-slate-800">Data Keluarga</h2>
                            <p class="text-xs text-slate-500">Data kependudukan akan dikonfirmasi pengurus RT.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="nama_lengkap" class="text-sm font-bold text-slate-700">Nama lengkap sesuai identitas</label>
                            <input id="nama_lengkap" name="nama_lengkap" type="text" value="{{ old('nama_lengkap', old('name')) }}" required class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Nama lengkap sesuai KTP">
                        </div>
                        <div>
                            <label for="nik" class="text-sm font-bold text-slate-700">NIK <span class="font-normal text-slate-400">(opsional)</span></label>
                            <input id="nik" name="nik" type="text" value="{{ old('nik') }}" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="16 digit NIK">
                        </div>
                    </div>

                    <fieldset class="mt-4">
                        <legend class="text-sm font-bold text-slate-700">Status dalam Kartu Keluarga</legend>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:border-emerald-400">
                                <input type="radio" name="status_dalam_kk" value="kepala_keluarga" required class="text-emerald-600 focus:ring-emerald-500" @checked(old('status_dalam_kk') === 'kepala_keluarga')>
                                <span><strong class="block text-sm">Kepala Keluarga</strong><small class="text-slate-500">Akan dibuatkan data KK baru setelah diverifikasi.</small></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:border-emerald-400">
                                <input type="radio" name="status_dalam_kk" value="anggota" required class="text-emerald-600 focus:ring-emerald-500" @checked(old('status_dalam_kk', 'anggota') === 'anggota')>
                                <span><strong class="block text-sm">Anggota Keluarga</strong><small class="text-slate-500">Akan dihubungkan ke KK yang sudah ada.</small></span>
                            </label>
                        </div>
                    </fieldset>
                </section>

                <section class="border-t border-slate-100 pt-8">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-700">4</span>
                        <div>
                            <h2 class="font-black text-slate-800">Dokumen Pendukung <span class="text-sm font-normal text-slate-400">(opsional)</span></h2>
                            <p class="text-xs text-slate-500">JPEG, PNG, JPG, atau PDF. Maksimal 2 MB per dokumen.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-dashed border-slate-300 p-4">
                            <label for="dokumen_kk" class="text-sm font-bold text-slate-700">Dokumen KK</label>
                            <input id="dokumen_kk" name="dokumen_kk" type="file" accept="image/jpeg,image/png,application/pdf" class="mt-3 block w-full text-sm text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-bold file:text-emerald-700">
                        </div>
                        <div class="rounded-2xl border border-dashed border-slate-300 p-4">
                            <label for="dokumen_ktp" class="text-sm font-bold text-slate-700">Dokumen KTP</label>
                            <input id="dokumen_ktp" name="dokumen_ktp" type="file" accept="image/jpeg,image/png,application/pdf" class="mt-3 block w-full text-sm text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-bold file:text-emerald-700">
                        </div>
                    </div>
                    <p class="mt-4 rounded-2xl bg-blue-50 p-4 text-sm text-blue-800"><i class="fa-solid fa-circle-info mr-2"></i>Jika tidak mengunggah dokumen, RT akan menghubungi Anda untuk verifikasi langsung.</p>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('login') }}" class="rounded-2xl border border-slate-200 px-6 py-3 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</a>
                    <button type="submit" class="rounded-2xl bg-emerald-600 px-7 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">
                        Kirim Pendaftaran
                    </button>
                </div>
            </div>
        </form>
    </main>
</body>
</html>
