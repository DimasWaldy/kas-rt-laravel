@php
    $tanggalMulai = old('tanggal_mulai', $pengumuman?->tanggal_mulai?->format('Y-m-d\TH:i'));
    $tanggalBerakhir = old('tanggal_berakhir', $pengumuman?->tanggal_berakhir?->format('Y-m-d\TH:i'));
    $isPinned = (bool) old('is_pinned', $pengumuman?->is_pinned ?? false);
    $isPublic = (bool) old('is_public', $pengumuman?->is_public ?? true);
    $isPublished = (bool) old('is_published', $pengumuman?->is_published ?? true);
@endphp

@if($isRwOfficial)
    <div>
        <label for="rt_id" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Lingkup Pengumuman</label>
        <select id="rt_id" name="rt_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            <option value="">Lingkup RW (Semua RT)</option>
            @foreach($rts as $rt)
                <option value="{{ $rt->id }}" @selected((string) old('rt_id', $pengumuman?->rt_id) === (string) $rt->id)>
                    Khusus {{ $rt->name }} saja
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-400">Pilih RT tertentu atau biarkan Lingkup RW agar terlihat oleh seluruh warga.</p>
        @error('rt_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
@else
    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm font-semibold text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300">
        <i class="fa-solid fa-circle-info mr-1"></i>
        Pengumuman ini akan diterbitkan khusus untuk warga {{ auth()->user()->rt?->name ?? 'RT Anda' }}.
    </div>
@endif

<div>
    <label for="judul" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Judul Pengumuman</label>
    <input id="judul" name="judul" value="{{ old('judul', $pengumuman?->judul) }}" required minlength="3" maxlength="150" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Contoh: Jadwal Fogging DBD Lingkungan RW 01">
    @error('judul')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="kategori" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Kategori</label>
        <select id="kategori" name="kategori" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            @foreach($categories as $key => $label)
                <option value="{{ $key }}" @selected(old('kategori', $pengumuman?->kategori ?? 'umum') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('kategori')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="prioritas" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Tingkat Prioritas</label>
        <select id="prioritas" name="prioritas" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            @foreach($priorities as $key => $label)
                <option value="{{ $key }}" @selected(old('prioritas', $pengumuman?->prioritas ?? 'normal') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-400">Prioritas <strong>Darurat</strong> &amp; <strong>Penting</strong> tampil menonjol di atas halaman.</p>
        @error('prioritas')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="isi" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Isi Pengumuman</label>
    <textarea id="isi" name="isi" rows="6" required minlength="10" maxlength="3000" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm leading-relaxed focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Tulis rincian informasi, waktu, lokasi, dan himbauan lengkap untuk warga...">{{ old('isi', $pengumuman?->isi) }}</textarea>
    @error('isi')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="tanggal_mulai" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Mulai Tayang <span class="font-normal normal-case text-slate-400">(opsional)</span></label>
        <input id="tanggal_mulai" name="tanggal_mulai" type="datetime-local" value="{{ $tanggalMulai }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        @error('tanggal_mulai')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="tanggal_berakhir" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Berakhir Otomatis <span class="font-normal normal-case text-slate-400">(opsional)</span></label>
        <input id="tanggal_berakhir" name="tanggal_berakhir" type="datetime-local" value="{{ $tanggalBerakhir }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        <p class="mt-1.5 text-xs text-slate-400">Kosongkan bila tidak dibatasi tanggal berakhir.</p>
        @error('tanggal_berakhir')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-950/60">
    <p class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Pengaturan Penayangan</p>

    <label class="flex items-start gap-3 cursor-pointer">
        <input type="hidden" name="is_pinned" value="0">
        <input type="checkbox" name="is_pinned" value="1" @checked($isPinned) class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
        <span>
            <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">Sematkan di Bagian Atas (Pin)</span>
            <span class="block text-xs text-slate-500 dark:text-slate-400">Pengumuman akan selalu berada di urutan teratas papan pengumuman.</span>
        </span>
    </label>

    <label class="flex items-start gap-3 cursor-pointer">
        <input type="hidden" name="is_public" value="0">
        <input type="checkbox" name="is_public" value="1" @checked($isPublic) class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
        <span>
            <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">Tampilkan di Landing Page Publik</span>
            <span class="block text-xs text-slate-500 dark:text-slate-400">Jika dimatikan, pengumuman hanya terlihat oleh warga yang login di Dashboard (internal lingkungan).</span>
        </span>
    </label>

    <label class="flex items-start gap-3 cursor-pointer">
        <input type="hidden" name="is_published" value="0">
        <input type="checkbox" name="is_published" value="1" @checked($isPublished) class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
        <span>
            <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">Terbitkan Sekarang</span>
            <span class="block text-xs text-slate-500 dark:text-slate-400">Hilangkan centang jika ingin menyimpan sebagai draf terlebih dahulu.</span>
        </span>
    </label>
</div>
