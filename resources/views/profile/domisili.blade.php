<x-guest-layout>
    <div x-data="{ mode: @js(old('mode', $warga->mode_domisili ?? ($rumahs->isEmpty() ? 'new' : 'existing'))) }" class="space-y-5">
        <header>
            <p class="text-sm font-semibold text-emerald-700">Akun telah diverifikasi</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Lengkapi domisili</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Halo {{ $user->name }}. Ajukan rumah tempat Anda tinggal untuk diperiksa pengurus.</p>
        </header>
        <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900">
            Wilayah hasil verifikasi: <strong>{{ $rt->name }} / {{ $rt->rw->name }}</strong>.
            Jika RT ini salah, hubungi pengurus sebelum mengisi domisili.
        </div>
        @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('success') }}</p>@endif
        @if($errors->any())
            <ul role="alert" class="list-inside list-disc text-sm text-red-700">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif
        @if($warga->status_domisili === 'pending')
            <section class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950">
                <h2 class="text-lg font-bold">Menunggu persetujuan domisili</h2>
                <p>Rumah yang Anda ajukan: <strong>{{ $warga->rumahDiajukan?->label ?? $warga->alamat_domisili }}</strong></p>
                <p>Pengurus akan memeriksa bahwa Anda tinggal di alamat tersebut. Layanan dan data rumah dapat diakses setelah domisili disetujui.</p>
                <p>Jika alamat salah, hubungi pengurus agar pengajuan dikembalikan untuk diperbaiki.</p>
                <a href="{{ route('profile.edit') }}" class="inline-block rounded-xl bg-emerald-700 px-4 py-3 font-bold text-white">Periksa status</a>
            </section>
        @else
            @if($warga->status_domisili === 'perlu_perbaikan')
                <div role="alert" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-950">
                    <p class="font-bold">Perbaiki pengajuan domisili</p>
                    <p class="mt-2">{{ $warga->catatan_domisili }}</p>
                    <p class="mt-2">Perbaiki pilihan rumah atau alamat di bawah, lalu kirim kembali.</p>
                </div>
            @endif
        <form method="POST" action="{{ route('domisili.store') }}" class="space-y-5">
            @csrf
            <fieldset class="space-y-2">
                <legend class="mb-2 text-sm font-bold text-slate-800 dark:text-slate-100">Pilih cara pengisian</legend>
                <label class="flex items-center gap-2 text-sm dark:text-slate-100"><input type="radio" name="mode" value="existing" x-model="mode" @disabled($rumahs->isEmpty())> Pilih rumah yang sudah terdaftar</label>
                <label class="flex items-center gap-2 text-sm dark:text-slate-100"><input type="radio" name="mode" value="new" x-model="mode"> Rumah belum terdaftar — isi alamat baru</label>
            </fieldset>
            @if($rumahs->isEmpty())<p class="text-sm text-slate-500">Belum ada rumah di RT ini. Anda tetap bisa melanjutkan dengan mengisi alamat baru.</p>@endif
            <div x-show="mode === 'existing'" x-cloak>
                <label for="domisili-rumah" class="text-sm font-semibold dark:text-slate-100">Rumah Anda</label>
                <select id="domisili-rumah" name="rumah_id" :disabled="mode !== 'existing'" :required="mode === 'existing'" class="mt-2 w-full rounded-xl border-slate-300 dark:bg-slate-800 dark:text-white">
                    <option value="">Pilih rumah</option>
                    @foreach($rumahs as $rumah)<option value="{{ $rumah->id }}" @selected(old('rumah_id', $warga->rumah_diajukan_id) == $rumah->id)>{{ $rumah->kode_rumah }} — {{ $rumah->alamat }}</option>@endforeach
                </select>
            </div>
            <div x-show="mode === 'new'" x-cloak>
                <label for="domisili-alamat" class="text-sm font-semibold dark:text-slate-100">Alamat lengkap rumah</label>
                <textarea id="domisili-alamat" name="alamat" rows="3" minlength="5" maxlength="500" :disabled="mode !== 'new'" :required="mode === 'new'" placeholder="Nama jalan/gang, nomor rumah, blok, dan keterangan lokasi" class="mt-2 w-full rounded-xl border-slate-300 dark:bg-slate-800 dark:text-white">{{ old('alamat', $warga->mode_domisili === 'new' ? $warga->alamat_domisili : '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Kode rumah dibuat otomatis. Jangan membuat alamat baru jika rumah Anda sudah tersedia di daftar.</p>
            </div>
            <p class="text-xs leading-relaxed text-slate-500">Pilihan rumah dan alamat baru perlu persetujuan pengurus. Penetapan penanggung jawab iuran tetap melalui pengurus.</p>
            <button class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-bold text-white hover:bg-emerald-800">Kirim pengajuan domisili</button>
        </form>
        @endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-slate-500 underline">Keluar dulu</button></form>
    </div>
</x-guest-layout>
