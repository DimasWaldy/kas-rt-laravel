@php
    $editing = isset($umkm);
    $kategoriLabels = [
        'makanan_minuman' => 'Makanan & Minuman',
        'jasa' => 'Jasa',
        'kerajinan' => 'Kerajinan',
        'sembako' => 'Sembako',
        'fashion' => 'Fashion',
        'pertanian' => 'Pertanian',
        'lainnya' => 'Lainnya',
    ];
    $initialPreview = $editing && $umkm->foto_usaha ? route('umkm.foto', $umkm) : null;
    $hariLabels = \App\Models\JamOperasionalUmkm::HARI;
    $oldJamOperasional = collect(old('jam_operasional', []))->keyBy(fn ($item) => (int) ($item['hari'] ?? 0));
    $existingJamOperasional = $editing ? $umkm->jamOperasional->keyBy('hari') : collect();
    $initialJamOperasional = collect($hariLabels)->map(function ($label, $hari) use ($oldJamOperasional, $existingJamOperasional) {
        $oldJadwal = $oldJamOperasional->get($hari);
        $existingJadwal = $existingJamOperasional->get($hari);
        $defaultTutup = $hari === 7;

        if ($oldJadwal) {
            $isTutup = filter_var($oldJadwal['is_tutup'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $jamBuka = $oldJadwal['jam_buka'] ?? null;
            $jamTutup = $oldJadwal['jam_tutup'] ?? null;
        } elseif ($existingJadwal) {
            $isTutup = $existingJadwal->is_tutup;
            $jamBuka = $existingJadwal->jam_buka?->format('H:i');
            $jamTutup = $existingJadwal->jam_tutup?->format('H:i');
        } else {
            $isTutup = $defaultTutup;
            $jamBuka = $defaultTutup ? null : '08:00';
            $jamTutup = $defaultTutup ? null : '17:00';
        }

        return [
            'hari' => (int) $hari,
            'label' => $label,
            'is_tutup' => (bool) $isTutup,
            'jam_buka' => $jamBuka,
            'jam_tutup' => $jamTutup,
        ];
    })->values()->all();
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="nama_usaha" class="text-sm font-bold text-slate-700">Nama Usaha</label>
        <input id="nama_usaha" type="text" name="nama_usaha" value="{{ old('nama_usaha', $umkm->nama_usaha ?? '') }}" maxlength="255" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Dapur Bu Sari">
        @error('nama_usaha')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="kategori" class="text-sm font-bold text-slate-700">Kategori</label>
        <select id="kategori" name="kategori" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Pilih kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" @selected(old('kategori', $umkm->kategori ?? '') === $category)>{{ $kategoriLabels[$category] }}</option>
            @endforeach
        </select>
        @error('kategori')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="nomor_whatsapp" class="text-sm font-bold text-slate-700">Nomor WhatsApp</label>
        <input id="nomor_whatsapp" type="text" name="nomor_whatsapp" value="{{ old('nomor_whatsapp', $umkm->nomor_whatsapp ?? '') }}" minlength="10" maxlength="15" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: 081234567890">
        @error('nomor_whatsapp')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="deskripsi" class="text-sm font-bold text-slate-700">Deskripsi Usaha</label>
        <textarea id="deskripsi" name="deskripsi" rows="5" minlength="20" maxlength="1000" required class="mt-2 w-full rounded-xl border-slate-200 text-sm leading-6 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Jelaskan produk atau jasa yang ditawarkan.">{{ old('deskripsi', $umkm->deskripsi ?? '') }}</textarea>
        @error('deskripsi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="alamat_lokasi" class="text-sm font-bold text-slate-700">Alamat / Lokasi Usaha</label>
        <input id="alamat_lokasi" type="text" name="alamat_lokasi" value="{{ old('alamat_lokasi', $umkm->alamat_lokasi ?? '') }}" maxlength="255" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Opsional jika usaha tidak punya lokasi tetap">
        @error('alamat_lokasi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2" x-data="{
        jamOperasional: @js($initialJamOperasional),
        terapkanKeSemua() {
            const senin = this.jamOperasional[0];
            this.jamOperasional.forEach((jadwal, index) => {
                if (index === 0) return;
                jadwal.is_tutup = senin.is_tutup;
                jadwal.jam_buka = senin.jam_buka;
                jadwal.jam_tutup = senin.jam_tutup;
            });
        }
    }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold text-slate-700">Jam Operasional</p>
                <p class="mt-1 text-xs text-slate-500">Atur waktu buka dan tutup untuk setiap hari.</p>
            </div>
            <button type="button" x-on:click="terapkanKeSemua()" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100">
                Terapkan jam Senin ke semua hari
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
            <template x-for="(jadwal, index) in jamOperasional" x-bind:key="jadwal.hari">
                <div class="grid gap-3 border-b border-slate-100 p-4 last:border-b-0 sm:grid-cols-[7rem_6rem_1fr] sm:items-center">
                    <input type="hidden" x-bind:name="`jam_operasional[${index}][hari]`" x-bind:value="jadwal.hari">
                    <input type="hidden" x-bind:name="`jam_operasional[${index}][is_tutup]`" x-bind:value="jadwal.is_tutup ? 1 : 0">

                    <p class="text-sm font-black text-slate-800" x-text="jadwal.label"></p>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600">
                        <input type="checkbox" x-model="jadwal.is_tutup" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-500">
                        Tutup
                    </label>

                    <div x-show="!jadwal.is_tutup" x-transition class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                        <input type="time" x-bind:name="`jam_operasional[${index}][jam_buka]`" x-model="jadwal.jam_buka" x-bind:required="!jadwal.is_tutup" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-slate-400">sampai</span>
                        <input type="time" x-bind:name="`jam_operasional[${index}][jam_tutup]`" x-model="jadwal.jam_tutup" x-bind:required="!jadwal.is_tutup" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <p x-show="jadwal.is_tutup" class="text-sm font-semibold text-slate-400 sm:col-start-3">Tidak beroperasi</p>
                </div>
            </template>
        </div>

        @error('jam_operasional')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @if($errors->has('jam_operasional.*'))
            <p class="mt-2 text-xs font-semibold text-red-600">{{ $errors->first('jam_operasional.*') }}</p>
        @endif
    </div>

    <div class="sm:col-span-2" x-data="{ preview: @js($initialPreview) }">
        <label for="foto_usaha" class="text-sm font-bold text-slate-700">Foto Usaha</label>
        <input id="foto_usaha" type="file" name="foto_usaha" accept="image/jpeg,image/png,image/jpg" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" x-on:change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : @js($initialPreview)">
        <p class="mt-1 text-xs text-slate-500">JPG atau PNG, maksimal 2MB.</p>
        @error('foto_usaha')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        <template x-if="preview">
            <img x-bind:src="preview" alt="Preview foto usaha" class="mt-4 h-56 w-full rounded-2xl object-cover">
        </template>
    </div>
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ $editing ? route('umkm.show', $umkm) : route('umkm.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
    <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">{{ $submitLabel }}</button>
</div>
