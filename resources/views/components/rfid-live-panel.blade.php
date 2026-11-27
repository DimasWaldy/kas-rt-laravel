@props(['accent' => 'emerald'])

<div class="space-y-3">
    {{-- Status Live ESP32 Reader --}}
    <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-xs transition"
        :class="state?.device_online
            ? 'border-emerald-200 bg-emerald-50/90 text-emerald-950 dark:border-emerald-800/70 dark:bg-emerald-950/40 dark:text-emerald-100'
            : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'"
    >
        <div class="flex items-center gap-3">
            <span
                class="h-2.5 w-2.5 shrink-0 rounded-full"
                :class="state?.device_online ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"
            ></span>
            <div>
                <p
                    class="font-bold"
                    x-text="state?.device_online ? 'Alat Reader ESP32 Online & Siap Scan' : (state ? 'Alat Reader ESP32 Standby / Belum Terhubung' : 'Mengecek status alat reader ESP32…')"
                ></p>
                <p class="mt-0.5 text-[11px] opacity-80" x-show="state?.device_online && state?.last_device?.device_id" x-cloak>
                    <span x-text="state?.last_device?.device_id"></span>
                    <template x-if="state?.last_device?.ip">
                        <span x-text="' · IP ' + state.last_device.ip"></span>
                    </template>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold"
                :class="state?.device_online
                    ? 'bg-emerald-600/15 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
            >
                <span class="h-1.5 w-1.5 rounded-full" :class="state?.device_online ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                <span x-text="state?.device_online ? 'Live: Terhubung' : 'Live: Standby'"></span>
            </span>
        </div>
    </div>

    {{-- Notif Cepat Auto-Tampil Hasil Tap ESP32 --}}
    <div
        x-show="liveScan"
        x-cloak
        x-transition.duration.250ms
        role="status"
        aria-live="polite"
        class="rounded-2xl border-2 p-4 shadow-sm transition-all"
        :class="liveScan?.status === 'error'
            ? 'border-rose-400 bg-rose-50 text-rose-950 dark:border-rose-700 dark:bg-rose-950/70 dark:text-rose-100'
            : (liveScan?.feedback === 'duplicate'
                ? 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-700 dark:bg-amber-950/60 dark:text-amber-100'
                : 'border-emerald-400 bg-emerald-50 text-emerald-950 dark:border-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-100')"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white shadow-xs"
                    :class="liveScan?.status === 'error'
                        ? 'bg-rose-600'
                        : (liveScan?.feedback === 'duplicate' ? 'bg-amber-600' : 'bg-emerald-600')"
                >
                    <i
                        class="fa-solid text-sm"
                        :class="liveScan?.status === 'error'
                            ? 'fa-triangle-exclamation'
                            : (liveScan?.feedback === 'duplicate' ? 'fa-circle-info' : 'fa-circle-check')"
                    ></i>
                </div>
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-sm font-extrabold"
                            x-text="liveScan?.status === 'error'
                                ? 'Tap Kartu Ditolak'
                                : (liveScan?.direction === 'masuk'
                                    ? 'Gerbang MASUK Dibuka'
                                    : (liveScan?.direction === 'keluar'
                                        ? 'Gerbang KELUAR Dibuka'
                                        : (liveScan?.feedback === 'duplicate' ? 'Kartu Sudah Tercatat' : 'Tap Kartu Berhasil')))"
                        ></span>
                        <template x-if="liveScan?.direction">
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-black uppercase"
                                :class="liveScan.direction === 'masuk'
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-sky-600 text-white'"
                                x-text="liveScan.direction === 'masuk' ? 'MASUK' : 'KELUAR'"
                            ></span>
                        </template>
                        <template x-if="liveScan?.open_gate">
                            <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[11px] font-bold text-amber-900 dark:text-amber-200">
                                Servo 90° Aktif
                            </span>
                        </template>
                        <template x-if="liveScan?.user_name">
                            <span class="rounded-full bg-black/10 px-2 py-0.5 text-xs font-bold dark:bg-white/10" x-text="liveScan.user_name"></span>
                        </template>
                        <template x-if="liveScan?.uid_masked">
                            <span class="rounded bg-black/10 px-1.5 py-0.5 font-mono text-[11px] font-bold dark:bg-white/10" x-text="'UID: ' + liveScan.uid_masked"></span>
                        </template>
                    </div>
                    <p class="text-xs font-medium leading-relaxed" x-text="liveScan?.message"></p>
                    <div class="flex flex-wrap items-center gap-3 pt-0.5 text-[11px] opacity-75">
                        <span x-show="liveScan?.at" x-text="liveScan?.at ? 'Waktu: ' + new Date(liveScan.at).toLocaleTimeString('id-ID') + ' WIB' : ''"></span>
                        <button
                            type="button"
                            x-show="needsReload"
                            @click="window.location.reload()"
                            class="font-bold underline hover:opacity-100"
                        >
                            Muat ulang daftar warga
                        </button>
                    </div>
                </div>
            </div>
            <button
                type="button"
                @click="dismissScan()"
                class="rounded-lg p-1 opacity-60 transition hover:bg-black/5 hover:opacity-100 dark:hover:bg-white/10"
                title="Tutup notifikasi"
                aria-label="Tutup notifikasi hasil tap"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
</div>
