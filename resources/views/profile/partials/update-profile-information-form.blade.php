@php
    $isOnboardingDomisili = $user->role_name === 'warga' && $user->onboarding_domisili && ! $user->rumah_id;
    $initialRtId = $user->rt_id ?? '';
    $initialMode = old('mode', $wargaDomisili?->mode_domisili ?? ($rumahsDomisili->isEmpty() ? 'new' : 'existing'));
    $initialRumahs = $rumahsDomisili->map(fn($r) => ['id' => $r->id, 'kode_rumah' => $r->kode_rumah, 'alamat' => $r->alamat])->values();
    $selectedRumahId = old('rumah_id', $wargaDomisili?->rumah_diajukan_id ?? '');
@endphp

<section x-data="{
    rtId: @js((string) $initialRtId),
    mode: @js($initialMode),
    rumahs: @js($initialRumahs),
    selectedRumahId: @js((string) $selectedRumahId),
    loadingRumah: false,
    async onRtChange(newRtId) {
        if (!newRtId) {
            this.rumahs = [];
            this.mode = 'new';
            this.selectedRumahId = '';
            return;
        }
        this.loadingRumah = true;
        try {
            const res = await fetch('{{ route('profile.edit') }}?rt_id=' + newRtId, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (res.ok) {
                const data = await res.json();
                this.rumahs = data.rumahs || [];
                if (this.rumahs.length === 0) {
                    this.mode = 'new';
                    this.selectedRumahId = '';
                } else if (!this.rumahs.some(r => String(r.id) === String(this.selectedRumahId))) {
                    this.selectedRumahId = '';
                }
            }
        } catch (e) {
            console.error('Gagal mengambil daftar rumah:', e);
        } finally {
            this.loadingRumah = false;
        }
    }
}">
    <header>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
            Informasi Profil
        </h2>

        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Perbarui data pribadi Anda. Penetapan data KK dilakukan melalui pengurus.
        </p>
    </header>

    @if(session('success'))
        <p role="status" class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900 dark:bg-slate-800/60 dark:text-emerald-300">
            {{ session('success') }}
        </p>
    @endif

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <x-billing-contact-warning :rumah="$user->rumah" />
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
        </div>

        @if($isOnboardingDomisili)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-xs space-y-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Data Kartu Keluarga (KK)</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Isi data KK Anda. Jika Anda kepala keluarga, Anda juga dapat mencantumkan jumlah anggota keluarga.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="no_kk" value="Nomor Kartu Keluarga (16 Digit)" />
                        <x-text-input id="no_kk" name="no_kk" type="text" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" class="mt-1 block w-full focus:border-emerald-500 focus:ring-emerald-500" :value="old('no_kk', $user->warga?->kartuKeluarga?->no_kk)" placeholder="16 digit nomor KK" />
                        <x-input-error class="mt-2" :messages="$errors->get('no_kk')" />
                    </div>

                    <div>
                        <x-input-label for="status_dalam_kk" value="Status dalam KK" />
                        <select id="status_dalam_kk" name="status_dalam_kk" class="mt-1 block w-full rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white px-4 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="anggota" @selected(old('status_dalam_kk', $user->warga?->status_dalam_kk) === 'anggota')>Anggota Keluarga</option>
                            <option value="kepala_keluarga" @selected(old('status_dalam_kk', $user->warga?->status_dalam_kk) === 'kepala_keluarga')>Kepala Keluarga</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status_dalam_kk')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="jumlah_anggota" value="Jumlah Anggota dalam 1 KK (Opsional)" />
                    <x-text-input id="jumlah_anggota" name="jumlah_anggota" type="number" min="1" max="50" class="mt-1 block w-full sm:w-1/2 focus:border-emerald-500 focus:ring-emerald-500" :value="old('jumlah_anggota', $user->warga?->kartuKeluarga?->jumlah_anggota)" placeholder="Contoh: 4 (orang)" />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Total jiwa yang tertera di Kartu Keluarga Anda (termasuk kepala keluarga).</p>
                    <x-input-error class="mt-2" :messages="$errors->get('jumlah_anggota')" />
                </div>
            </div>
        @endif

        @if($isOnboardingDomisili)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-xs space-y-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Data Domisili</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Tentukan wilayah RT dan rumah tempat tinggal Anda di lingkungan ini.</p>
                </div>

                @if($wargaDomisili?->status_domisili === 'pending')
                    <section class="space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
                        <h4 class="text-base font-bold">Menunggu persetujuan domisili</h4>
                        <p>RT: <strong>{{ $user->rt?->name ?? 'Belum ditetapkan' }}</strong></p>
                        <p>Rumah yang Anda ajukan: <strong>{{ $wargaDomisili->rumahDiajukan?->label ?? $wargaDomisili->alamat_domisili }}</strong></p>
                        <p class="text-xs">Pengurus akan memeriksa domisili Anda. Data rumah dan fitur warga akan aktif setelah disetujui. Hubungi pengurus jika perlu perubahan.</p>
                    </section>
                @else
                    @if($wargaDomisili?->status_domisili === 'perlu_perbaikan')
                        <div role="alert" class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                            <p class="font-bold">Perbaiki pengajuan domisili</p>
                            <p class="mt-1">{{ $wargaDomisili->catatan_domisili }}</p>
                            <p class="mt-2 text-xs">Perbaiki rumah atau alamat di bawah, lalu klik Simpan Profil. Hubungi pengurus jika RT perlu diubah.</p>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="rt_id" value="RT Hasil Verifikasi" />
                        <select id="rt_id" name="rt_id" x-model="rtId" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            @foreach($rts as $rtItem)
                                <option value="{{ $rtItem->id }}">
                                    {{ $rtItem->name }} ({{ $rtItem->rw?->name ?? 'RW' }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('rt_id')" />
                    </div>

                    <fieldset class="space-y-2 pt-2">
                        <legend class="mb-2 text-sm font-bold text-slate-800 dark:text-slate-200">Cara Pengisian Rumah</legend>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="radio" name="mode" value="existing" x-model="mode" :disabled="rumahs.length === 0" class="text-emerald-600 focus:ring-emerald-500">
                            Pilih rumah yang sudah terdaftar di RT ini
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="radio" name="mode" value="new" x-model="mode" class="text-emerald-600 focus:ring-emerald-500">
                            Rumah belum terdaftar — isi alamat baru
                        </label>
                        <x-input-error class="mt-1" :messages="$errors->get('mode')" />
                    </fieldset>

                    <div x-show="loadingRumah" class="rounded-xl bg-slate-100 p-3 text-xs text-slate-600 dark:bg-slate-900/60 dark:text-slate-400 flex items-center gap-2">
                        <span>Memuat daftar rumah...</span>
                    </div>

                    <div x-show="!loadingRumah && rumahs.length === 0" class="rounded-xl bg-slate-100 p-3 text-xs text-slate-600 dark:bg-slate-900/60 dark:text-slate-400">
                        Belum ada rumah di RT ini. Gunakan opsi isi alamat baru.
                    </div>

                    <div x-show="!loadingRumah && rumahs.length > 0 && mode === 'existing'" x-cloak>
                        <x-input-label for="domisili-rumah" value="Pilih Rumah" />
                        <select id="domisili-rumah" name="rumah_id" x-model="selectedRumahId" :disabled="mode !== 'existing'" :required="mode === 'existing'" class="mt-1 block w-full rounded-xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            <option value="">Pilih rumah</option>
                            <template x-for="rumahOption in rumahs" :key="rumahOption.id">
                                <option :value="rumahOption.id" x-text="rumahOption.kode_rumah + ' — ' + rumahOption.alamat"></option>
                            </template>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('rumah_id')" />
                    </div>

                    <div x-show="mode === 'new'" x-cloak>
                        <x-input-label for="domisili-alamat" value="Alamat Lengkap Rumah" />
                        <textarea id="domisili-alamat" name="alamat" rows="3" minlength="5" maxlength="500" :disabled="mode !== 'new'" :required="mode === 'new'" placeholder="Nama jalan/gang, nomor rumah, blok, dan keterangan lokasi" class="mt-1 block w-full rounded-xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">{{ old('alamat', $wargaDomisili?->mode_domisili === 'new' ? $wargaDomisili?->alamat_domisili : '') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Kode rumah dibuat otomatis. Jangan membuat alamat baru jika rumah sudah ada dalam daftar.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('alamat')" />
                    </div>

                    <div>
                        <x-input-label for="jumlah_penghuni_rumah" value="Estimasi Jumlah Penghuni di Rumah Ini (Opsional)" />
                        <x-text-input id="jumlah_penghuni_rumah" name="jumlah_penghuni_rumah" type="number" min="1" max="50" class="mt-1 block w-full sm:w-1/2 focus:border-emerald-500 focus:ring-emerald-500" :value="old('jumlah_penghuni_rumah', $user->warga?->jumlah_penghuni_rumah)" placeholder="Contoh: 5 (jiwa total di rumah)" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Informasi tambahan bila dalam satu rumah ditempati lebih dari 1 keluarga atau beberapa jiwa.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('jumlah_penghuni_rumah')" />
                    </div>

                    <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/40 space-y-2">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="ajukan_pj_iuran" value="1"
                                @checked(old('ajukan_pj_iuran', $user->warga?->ajukan_pj_iuran))
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            <span class="text-sm text-blue-950 dark:text-slate-200">
                                <strong>Saya ingin menjadi penanggung jawab (PJ) iuran rumah ini</strong>
                                <span class="block text-xs text-blue-700 dark:text-slate-400 mt-0.5">Satu rumah ditagihkan kepada satu PJ. Pengurus akan mengonfirmasi saat verifikasi.</span>
                            </span>
                        </label>
                        <x-input-error class="mt-1" :messages="$errors->get('ajukan_pj_iuran')" />
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Data Domisili</h3>
                <dl class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <dt class="font-medium text-slate-500 dark:text-slate-400">Wilayah RT</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $user->rt?->name ?? 'Belum ditetapkan' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <dt class="font-medium text-slate-500 dark:text-slate-400">Kode & Alamat Rumah</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $user->rumah?->kode_rumah ?? 'Belum ditetapkan' }} {{ $user->rumah?->alamat ? '— ' . $user->rumah->alamat : '' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <dt class="font-medium text-slate-500 dark:text-slate-400">Penanggung Jawab Iuran</dt>
                        <dd>
                            @if($user->is_penanggung_jawab_rumah)
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">Ya — Anda adalah PJ iuran</span>
                            @elseif($user->rumah?->penanggungJawab)
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $user->rumah->penanggungJawab->name }} (PJ aktif)</span>
                            @else
                                <span class="font-medium text-amber-600 dark:text-amber-400">Belum ada — hubungi pengurus</span>
                            @endif
                        </dd>
                    </div>
                    @if($user->warga?->jumlah_penghuni_rumah)
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <dt class="font-medium text-slate-500 dark:text-slate-400">Estimasi Jumlah Penghuni</dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ $user->warga->jumlah_penghuni_rumah }} jiwa</dd>
                        </div>
                    @endif
                </dl>
                <p class="mt-3 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    Hubungi pengurus untuk menetapkan atau mengubah RT, rumah, dan penanggung jawab iuran.
                </p>
                @foreach (['rt_id', 'rumah_id', 'rumah_kode', 'rumah_alamat', 'is_penanggung_jawab_rumah'] as $field)
                    <x-input-error class="mt-2" :messages="$errors->get($field)" />
                @endforeach
            </div>
        @endif

        @if($isOnboardingDomisili)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-xs space-y-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Dokumen Pendukung (Opsional)</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Unggah foto atau scan KTP/KK agar pengurus dapat memverifikasi secara langsung.</p>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="dokumen_ktp" value="Foto / Scan KTP (JPG, PNG, PDF maks 2MB)" />
                        <input id="dokumen_ktp" name="dokumen_ktp" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700" />
                        @if($user->warga?->dokumen_ktp)
                            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400"><i class="fa-solid fa-circle-check mr-1"></i>Dokumen KTP sudah terunggah.</p>
                        @endif
                        <x-input-error class="mt-2" :messages="$errors->get('dokumen_ktp')" />
                    </div>

                    <div>
                        <x-input-label for="dokumen_kk" value="Foto / Scan KK (JPG, PNG, PDF maks 2MB)" />
                        <input id="dokumen_kk" name="dokumen_kk" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700" />
                        @if($user->warga?->dokumen_kk)
                            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400"><i class="fa-solid fa-circle-check mr-1"></i>Dokumen KK sudah terunggah.</p>
                        @endif
                        <x-input-error class="mt-2" :messages="$errors->get('dokumen_kk')" />
                    </div>
                </div>
            </div>
        @endif

        @unless($isOnboardingDomisili)
            <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Data Kartu Keluarga</h3>
                <dl class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <dt class="font-medium text-slate-500 dark:text-slate-400">Nomor KK</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $user->warga?->kartuKeluarga?->no_kk ?? 'Belum ditetapkan' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <dt class="font-medium text-slate-500 dark:text-slate-400">Kepala Keluarga</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $user->warga?->kartuKeluarga?->nama_kepala_keluarga ?? 'Belum ditetapkan' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <dt class="font-medium text-slate-500 dark:text-slate-400">Status dalam KK</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ match($user->warga?->status_dalam_kk) { 'kepala_keluarga' => 'Kepala Keluarga', 'anggota' => 'Anggota Keluarga', default => 'Belum ditetapkan' } }}</dd>
                    </div>
                    @if($user->warga?->kartuKeluarga?->jumlah_anggota)
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <dt class="font-medium text-slate-500 dark:text-slate-400">Jumlah Anggota KK</dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ $user->warga->kartuKeluarga->jumlah_anggota }} orang</dd>
                        </div>
                    @endif
                </dl>
                <p class="mt-3 text-xs leading-5 text-slate-500 dark:text-slate-400">Hubungi pengurus untuk menetapkan atau mengubah KK dan kepala keluarga. Pengurus memproses perubahan melalui menu Data Warga.</p>
                @foreach(['no_kk', 'status_dalam_kk', 'kartu_keluarga_id', 'nama_kepala_keluarga', 'is_kepala_keluarga', 'ganti_kepala_keluarga'] as $field)
                    <x-input-error class="mt-2" :messages="$errors->get($field)" />
                @endforeach
            </section>
        @endunless

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" class="inline-flex items-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                Simpan Profil
            </button>

            @if (session('status') === 'profile-updated' && ! session('success'))
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm text-slate-500 dark:text-slate-400"
                >Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
