@php
    $editing = isset($balita);
    $selectedRt = (int) old('rt_id', $balita->rt_id ?? auth()->user()->rt_id);
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    @if($rts->isNotEmpty())
        <div>
            <label for="rt_id" class="text-sm font-bold text-slate-700">Wilayah RT</label>
            <select id="rt_id" name="rt_id" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
                <option value="">Pilih RT</option>
                @foreach($rts as $rt)
                    <option value="{{ $rt->id }}" @selected($selectedRt === $rt->id)>{{ $rt->name }}</option>
                @endforeach
            </select>
            @error('rt_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    @else
        <div>
            <p class="text-sm font-bold text-slate-700">Wilayah RT</p>
            <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700">{{ auth()->user()->rt?->name ?? 'RT akun' }}</div>
        </div>
    @endif

    <div>
        <label for="nama" class="text-sm font-bold text-slate-700">Nama Lengkap Balita</label>
        <input id="nama" name="nama" value="{{ old('nama', $balita->nama ?? '') }}" required maxlength="255" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
        @error('nama')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="jenis_kelamin" class="text-sm font-bold text-slate-700">Jenis Kelamin</label>
        <select id="jenis_kelamin" name="jenis_kelamin" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
            <option value="">Pilih jenis kelamin</option>
            @foreach(\App\Models\Balita::JENIS_KELAMIN as $value => $label)
                <option value="{{ $value }}" @selected(old('jenis_kelamin', $balita->jenis_kelamin ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('jenis_kelamin')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="tanggal_lahir" class="text-sm font-bold text-slate-700">Tanggal Lahir</label>
        <input id="tanggal_lahir" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', isset($balita) ? $balita->tanggal_lahir->toDateString() : '') }}" max="{{ today()->toDateString() }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
        @error('tanggal_lahir')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="nik" class="text-sm font-bold text-slate-700">NIK Anak</label>
        <input id="nik" name="nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" value="{{ old('nik', $balita->nik ?? '') }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500" placeholder="Opsional, 16 digit">
        @error('nik')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="no_kk" class="text-sm font-bold text-slate-700">Nomor KK</label>
        <input id="no_kk" name="no_kk" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" value="{{ old('no_kk', $balita->no_kk ?? '') }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500" placeholder="Opsional, 16 digit">
        @error('no_kk')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="orang_tua_id" class="text-sm font-bold text-slate-700">Akun Orang Tua / Wali</label>
        <select id="orang_tua_id" name="orang_tua_id" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
            <option value="">Belum dihubungkan</option>
            @foreach($orangTuas as $orangTua)
                <option value="{{ $orangTua->id }}" @selected((int) old('orang_tua_id', $balita->orang_tua_id ?? 0) === $orangTua->id)>{{ $orangTua->name }} - {{ $orangTua->rt?->name }}</option>
            @endforeach
        </select>
        @error('orang_tua_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="rumah_id" class="text-sm font-bold text-slate-700">Rumah</label>
        <select id="rumah_id" name="rumah_id" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
            <option value="">Belum dihubungkan</option>
            @foreach($rumahs as $rumah)
                <option value="{{ $rumah->id }}" @selected((int) old('rumah_id', $balita->rumah_id ?? 0) === $rumah->id)>{{ $rumah->kode_rumah }} - {{ $rumah->alamat ?? 'Tanpa alamat' }}</option>
            @endforeach
        </select>
        @error('rumah_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="nama_ibu" class="text-sm font-bold text-slate-700">Nama Ibu</label>
        <input id="nama_ibu" name="nama_ibu" value="{{ old('nama_ibu', $balita->nama_ibu ?? '') }}" maxlength="255" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
        @error('nama_ibu')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="nama_ayah" class="text-sm font-bold text-slate-700">Nama Ayah</label>
        <input id="nama_ayah" name="nama_ayah" value="{{ old('nama_ayah', $balita->nama_ayah ?? '') }}" maxlength="255" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
        @error('nama_ayah')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="berat_lahir_kg" class="text-sm font-bold text-slate-700">Berat Lahir (kg)</label>
        <input id="berat_lahir_kg" type="number" name="berat_lahir_kg" min="0.5" max="10" step="0.01" value="{{ old('berat_lahir_kg', $balita->berat_lahir_kg ?? '') }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
        @error('berat_lahir_kg')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="panjang_lahir_cm" class="text-sm font-bold text-slate-700">Panjang Lahir (cm)</label>
        <input id="panjang_lahir_cm" type="number" name="panjang_lahir_cm" min="20" max="80" step="0.1" value="{{ old('panjang_lahir_cm', $balita->panjang_lahir_cm ?? '') }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">
        @error('panjang_lahir_cm')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="catatan" class="text-sm font-bold text-slate-700">Catatan Identitas</label>
        <textarea id="catatan" name="catatan" rows="3" maxlength="1000" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500">{{ old('catatan', $balita->catatan ?? '') }}</textarea>
        @error('catatan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    @if($editing)
        <label class="sm:col-span-2 inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $balita->is_active)) class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
            Data balita aktif dan masih mengikuti Posyandu
        </label>
    @endif
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ $editing ? route('posyandu.show', $balita) : route('posyandu.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
    <button class="rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white hover:bg-rose-700">{{ $submitLabel }}</button>
</div>
