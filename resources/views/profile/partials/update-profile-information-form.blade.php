<section>
    <header>
        <h2 class="text-lg font-bold text-slate-900">
            Informasi Profil
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Perbarui data pribadi Anda. Perubahan domisili dan penanggung jawab iuran harus melalui pengurus.
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
            <p class="mt-2 text-sm text-emerald-900">
                RT: {{ $user->rt?->name ?? 'Belum ditetapkan' }}<br>
                Rumah: {{ $user->rumah?->kode_rumah ?? 'Belum ditetapkan' }}<br>
                Penanggung jawab iuran: {{ $user->is_penanggung_jawab_rumah ? 'Ya' : 'Tidak' }}
            </p>
            <p class="mt-2 text-xs leading-5 text-emerald-700">
                Hubungi pengurus untuk menetapkan atau mengubah RT, rumah, dan penanggung jawab iuran.
                Data ini tidak dapat diubah melalui profil pribadi.
            </p>
            @foreach (['rt_id', 'rumah_id', 'rumah_kode', 'rumah_alamat', 'is_penanggung_jawab_rumah'] as $field)
                <x-input-error class="mt-2" :messages="$errors->get($field)" />
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="phone" value="Nomor HP" />
                <x-text-input id="phone" name="phone" type="text" inputmode="numeric" pattern="[0-9]{10,13}" maxlength="13" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('phone', $user->phone)" required autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-input-label for="nik" value="NIK (16 Digit)" />
                <x-text-input id="nik" name="nik" type="text" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('nik', $user->warga?->nik)" placeholder="Isi 16 digit NIK" />
                <x-input-error class="mt-2" :messages="$errors->get('nik')" />
            </div>

            <div>
                <x-input-label for="no_kk" value="Nomor KK (16 Digit)" />
                <x-text-input id="no_kk" name="no_kk" type="text" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('no_kk', $user->warga?->kartuKeluarga?->no_kk)" placeholder="Isi nomor KK" />
                <x-input-error class="mt-2" :messages="$errors->get('no_kk')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="status_dalam_kk" value="Status dalam Kartu Keluarga" />
                <select id="status_dalam_kk" name="status_dalam_kk" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Pilih Status dalam KK</option>
                    <option value="kepala_keluarga" {{ old('status_dalam_kk', $user->warga?->status_dalam_kk) === 'kepala_keluarga' ? 'selected' : '' }}>Kepala Keluarga</option>
                    <option value="anggota" {{ old('status_dalam_kk', $user->warga?->status_dalam_kk) === 'anggota' ? 'selected' : '' }}>Anggota Keluarga</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('status_dalam_kk')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div>
                <p class="mt-2 text-sm text-slate-700">
                    Email Anda belum diverifikasi.

                    <button form="send-verification" class="rounded-md text-sm text-emerald-700 underline hover:text-emerald-900 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
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
