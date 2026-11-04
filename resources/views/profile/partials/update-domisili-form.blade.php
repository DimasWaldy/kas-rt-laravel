<div x-data="{ mode: @js(old('mode', $wargaDomisili?->mode_domisili ?? ($rumahsDomisili->isEmpty() ? 'new' : 'existing'))) }" class="space-y-5">
    @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900 dark:bg-slate-800/60 dark:text-emerald-300">{{ session('success') }}</p>@endif
    @if($errors->any())
        <ul role="alert" class="list-inside list-disc text-sm text-red-700">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif

    @if($wargaDomisili?->status_domisili === 'pending')
        <section class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
            <h2 class="text-lg font-bold">Menunggu persetujuan domisili</h2>
            <p>Rumah yang Anda ajukan: <strong>{{ $wargaDomisili->rumahDiajukan?->label ?? $wargaDomisili->alamat_domisili }}</strong></p>
            <p>Pengurus akan memeriksa bahwa Anda tinggal di alamat tersebut. Layanan dan data rumah dapat diakses setelah domisili disetujui.</p>
            <p>Jika alamat salah, hubungi pengurus agar pengajuan dikembalikan untuk diperbaiki.</p>
        </section>
    @else
        @if($wargaDomisili?->status_domisili === 'perlu_perbaikan')
            <div role="alert" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950/30 dark:text-amber-200">
                <p class="font-bold">Perbaiki pengajuan domisili</p>
                <p class="mt-2">{{ $wargaDomisili->catatan_domisili }}</p>
                <p class="mt-2">Perbaiki pilihan rumah atau alamat di bawah, lalu kirim kembali.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('domisili.store') }}" class="space-y-5">
            @csrf
            <fieldset class="space-y-2">
                <legend class="mb-2 text-sm font-bold text-slate-800 dark:text-slate-200">Pilih cara pengisian</legend>
                <label class="flex items-center gap-2 text-sm dark:text-slate-300"><input type="radio" name="mode" value="existing" x-model="mode" @disabled($rumahsDomisili->isEmpty())> Pilih rumah yang sudah terdaftar</label>
                <label class="flex items-center gap-2 text-sm dark:text-slate-300"><input type="radio" name="mode" value="new" x-model="mode"> Rumah belum terdaftar — isi alamat baru</label>
            </fieldset>
            @if($rumahsDomisili->isEmpty())<p class="text-sm text-slate-500 dark:text-slate-400">Belum ada rumah di RT ini. Anda tetap bisa melanjutkan dengan mengisi alamat baru.</p>@endif
            <div x-show="mode === 'existing'" x-cloak>
                <label for="domisili-rumah" class="text-sm font-semibold dark:text-slate-300">Rumah Anda</label>
                <select id="domisili-rumah" name="rumah_id" :disabled="mode !== 'existing'" :required="mode === 'existing'" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700">
                    <option value="">Pilih rumah</option>
                    @foreach($rumahsDomisili as $rumah)<option value="{{ $rumah->id }}" @selected(old('rumah_id', $wargaDomisili?->rumah_diajukan_id) == $rumah->id)>{{ $rumah->kode_rumah }} — {{ $rumah->alamat }}</option>@endforeach
                </select>
            </div>
            <div x-show="mode === 'new'" x-cloak>
                <label for="domisili-alamat" class="text-sm font-semibold dark:text-slate-300">Alamat lengkap rumah</label>
                <textarea id="domisili-alamat" name="alamat" rows="3" minlength="5" maxlength="500" :disabled="mode !== 'new'" :required="mode === 'new'" placeholder="Nama jalan/gang, nomor rumah, blok, dan keterangan lokasi" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700">{{ old('alamat', $wargaDomisili?->mode_domisili === 'new' ? $wargaDomisili?->alamat_domisili : '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Kode rumah dibuat otomatis. Jangan membuat alamat baru jika rumah Anda sudah tersedia di daftar.</p>
            </div>
            <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">Pilihan rumah dan alamat baru perlu persetujuan pengurus. Penetapan penanggung jawab iuran tetap melalui pengurus.</p>
            <button class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-bold text-white hover:bg-emerald-800">Kirim pengajuan domisili</button>
        </form>
    @endif
</div>