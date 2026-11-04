<div class="mt-4">
    <label for="warga-rt" class="block text-sm font-medium text-slate-700 dark:text-slate-300">RT warga dan rumah <span class="text-red-500">*</span></label>
    <select id="warga-rt" name="rt_id" x-model="rtId" x-on:change="$refs.residentHouse.value = ''" required class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        <option value="">Pilih RT aktif</option>
        @foreach($rts ?? collect() as $rt)
            <option value="{{ $rt->id }}" @selected((string) old('rt_id', $user->rt_id ?? (auth()->user()->canAccessAllRts() ? '' : auth()->user()->rt_id)) === (string) $rt->id)>{{ $rt->rw->name }} — {{ $rt->name }}</option>
        @endforeach
    </select>
    <p class="mt-2 text-xs text-emerald-800 dark:text-emerald-300">Warga aktif wajib memiliki RT. Rumah baru mengikuti RT ini; rumah yang sudah ada harus berada di RT yang sama.</p>
    @if(($rts ?? collect())->isEmpty())<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">Belum ada RT aktif yang dapat dipilih. Minta superadmin menyiapkan wilayah RT/RW sebelum menambahkan warga.</p>@endif
    @error('rt_id')<p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
</div>
