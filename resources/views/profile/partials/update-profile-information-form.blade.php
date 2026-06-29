<section>
    <header>
        <h2 class="text-lg font-bold text-slate-900">
            Informasi Profil
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Perbarui data pribadi, rumah/unit hunian, dan penanggung jawab iuran rumah.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="name" value="Nama" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        <div class="rounded-3xl border border-emerald-100 bg-emerald-50/70 p-5">
            <h3 class="text-sm font-bold text-emerald-900">Data Rumah / Unit Hunian</h3>
            <p class="mt-1 text-xs leading-5 text-emerald-700">
                Tagihan iuran dibuat per rumah. Jika rumah belum ada di daftar, isi kode dan alamat rumah baru.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rumah_id" value="Pilih Rumah yang Sudah Ada" />
                    <select id="rumah_id" name="rumah_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Buat rumah baru / belum ditentukan</option>
                        @foreach($rumahs as $rumah)
                            <option value="{{ $rumah->id }}" {{ old('rumah_id', $user->rumah_id) == $rumah->id ? 'selected' : '' }}>
                                {{ $rumah->label }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('rumah_id')" />
                </div>

                <div>
                    <x-input-label for="rumah_kode" value="Kode Rumah Baru" />
                    <x-text-input id="rumah_kode" name="rumah_kode" type="text" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('rumah_kode')" placeholder="Contoh: A-01" />
                    <x-input-error class="mt-2" :messages="$errors->get('rumah_kode')" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="rumah_alamat" value="Alamat Rumah Baru" />
                    <x-text-input id="rumah_alamat" name="rumah_alamat" type="text" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('rumah_alamat')" placeholder="Contoh: Jl. Melati No. 1" />
                    <x-input-error class="mt-2" :messages="$errors->get('rumah_alamat')" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="phone" value="Nomor HP" />
                <x-text-input id="phone" name="phone" type="text" inputmode="numeric" pattern="[0-9]{10,13}" maxlength="13" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('phone', $user->phone)" required autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3">
            <label class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900">
                <input id="is_penanggung_jawab_rumah" name="is_penanggung_jawab_rumah" type="checkbox" class="mt-0.5 rounded border-emerald-300 text-emerald-600 shadow-sm focus:ring-emerald-500" value="1" {{ old('is_penanggung_jawab_rumah', $user->is_penanggung_jawab_rumah) ? 'checked' : '' }}>
                <span>
                    Saya Penanggung Jawab Iuran Rumah
                    <span class="block text-xs font-normal text-emerald-700">Akun ini yang bisa membayar tagihan rumah.</span>
                </span>
            </label>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div>
                <p class="mt-2 text-sm text-slate-700">
                    Email Anda belum diverifikasi.

                    <button form="send-verification" class="rounded-md text-sm text-emerald-700 underline hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Kirim ulang email verifikasi.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-sm font-medium text-emerald-600">
                        Link verifikasi baru telah dikirim ke email Anda.
                    </p>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                Simpan Profil
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-500"
                >Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
