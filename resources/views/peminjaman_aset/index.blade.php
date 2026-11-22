@extends('layouts.app')

@section('title', 'Kalender Reservasi & Peminjaman Aset')

@section('content')
@php
    $isRw = ($scope ?? 'rt') === 'rw';
    $indexRoute = $isRw ? 'peminjaman-aset-rw.index' : 'peminjaman-aset.index';
    $createRoute = $isRw ? 'peminjaman-aset-rw.create' : 'peminjaman-aset.create';
    $showRoute = $isRw ? 'peminjaman-aset-rw.show' : 'peminjaman-aset.show';
    $canPinjam = auth()->user()->hasPermission($isRw ? 'pinjam-aset-rw' : 'pinjam-aset');
    $currentTab = $activeTab ?? 'kalender';
    $initialEvents = $kalenderEvents ?? collect();
    $asetList = $asets ?? collect();
    $activeReservationsCount = $initialEvents->whereIn('status', ['disetujui', 'dipinjam'])->count();
    $pendingReservationsCount = $initialEvents->where('status', 'diajukan')->count();
@endphp

<div class="mx-auto max-w-7xl space-y-6" x-data="{
    tab: '{{ $currentTab }}',
    currentYear: {{ now()->year }},
    currentMonth: {{ now()->month - 1 }},
    selectedAsetId: '{{ $selectedAsetId ?? '' }}',
    selectedKategori: '',
    events: @js($initialEvents),
    asets: @js($asetList->map(fn($a) => [
        'id' => $a->id,
        'nama' => $a->nama,
        'kategori' => $a->kategori,
        'kategori_label' => $a->kategori_label,
        'jumlah_tersedia' => $a->jumlah_tersedia,
        'jumlah_total' => $a->jumlah_total,
    ])),
    selectedEvent: null,
    selectedDateDay: null,
    modalBookingDate: null,
    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
    dayNames: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],

    get filteredEvents() {
        return this.events.filter(e => {
            if (this.selectedAsetId && String(e.aset_id) !== String(this.selectedAsetId)) {
                return false;
            }
            if (this.selectedKategori && e.aset_kategori !== this.selectedKategori) {
                return false;
            }
            return true;
        });
    },

    get monthTitle() {
        return this.monthNames[this.currentMonth] + ' ' + this.currentYear;
    },

    get calendarDays() {
        const year = this.currentYear;
        const month = this.currentMonth;
        const firstDayIndex = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        const today = new Date();
        const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

        const days = [];

        // Previous month padding
        for (let i = firstDayIndex - 1; i >= 0; i--) {
            const dayNum = daysInPrevMonth - i;
            const prevMonthDate = new Date(year, month - 1, dayNum);
            const dateStr = prevMonthDate.getFullYear() + '-' + String(prevMonthDate.getMonth() + 1).padStart(2, '0') + '-' + String(dayNum).padStart(2, '0');
            days.push({
                day: dayNum,
                dateString: dateStr,
                isCurrentMonth: false,
                isToday: dateStr === todayStr,
                isPast: dateStr < todayStr,
                events: this.getEventsForDate(dateStr),
            });
        }

        // Current month days
        for (let i = 1; i <= daysInMonth; i++) {
            const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(i).padStart(2, '0');
            days.push({
                day: i,
                dateString: dateStr,
                isCurrentMonth: true,
                isToday: dateStr === todayStr,
                isPast: dateStr < todayStr,
                events: this.getEventsForDate(dateStr),
            });
        }

        // Next month padding to fill complete grid
        const remainingCells = (7 - (days.length % 7)) % 7;
        for (let i = 1; i <= remainingCells; i++) {
            const nextMonthDate = new Date(year, month + 1, i);
            const dateStr = nextMonthDate.getFullYear() + '-' + String(nextMonthDate.getMonth() + 1).padStart(2, '0') + '-' + String(i).padStart(2, '0');
            days.push({
                day: i,
                dateString: dateStr,
                isCurrentMonth: false,
                isToday: dateStr === todayStr,
                isPast: dateStr < todayStr,
                events: this.getEventsForDate(dateStr),
            });
        }

        return days;
    },

    getEventsForDate(dateStr) {
        return this.filteredEvents.filter(e => {
            return dateStr >= e.tanggal_mulai && dateStr <= e.tanggal_selesai;
        });
    },

    prevMonth() {
        if (this.currentMonth === 0) {
            this.currentMonth = 11;
            this.currentYear--;
        } else {
            this.currentMonth--;
        }
    },

    nextMonth() {
        if (this.currentMonth === 11) {
            this.currentMonth = 0;
            this.currentYear++;
        } else {
            this.currentMonth++;
        }
    },

    goToToday() {
        const today = new Date();
        this.currentYear = today.getFullYear();
        this.currentMonth = today.getMonth();
    },

    openEventDetail(evt) {
        this.selectedEvent = evt;
    },

    openQuickBooking(dayObj) {
        if (dayObj.isPast) return;
        this.selectedDateDay = dayObj;
        this.modalBookingDate = dayObj.dateString;
    },

    getBookingUrlFor(asetId, dateStr) {
        let base = '{{ route($createRoute) }}';
        let params = new URLSearchParams();
        if (asetId) params.append('aset_id', asetId);
        if (dateStr) {
            params.append('tanggal_mulai', dateStr);
            params.append('tanggal_selesai', dateStr);
        }
        return base + '?' + params.toString();
    }
}">

    {{-- Hero Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                <i class="fa-solid fa-calendar-check text-emerald-600"></i>
                <span>{{ $isRw ? 'Fasilitas & Aset Lingkungan RW' : 'Fasilitas & Aset Lingkungan RT' }}</span>
            </div>
            <h1 class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl">Kalender Reservasi & Peminjaman Aset</h1>
            <p class="mt-1 text-sm text-slate-500">
                Pantau jadwal ketersediaan Balai Pertemuan, Lapangan, Tenda Acara, dan Sound System secara visual tanpa bentrok jadwal.
            </p>
        </div>

        @if($canPinjam)
            <a href="{{ route($createRoute) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white shadow-md shadow-emerald-700/20 transition hover:bg-emerald-800">
                <i class="fa-solid fa-plus"></i> Ajukan Peminjaman
            </a>
        @endif
    </div>

    {{-- Stat Overview Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <i class="fa-solid fa-boxes-stacked text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Fasilitas</p>
                    <p class="text-xl font-black text-slate-900">{{ $asetList->count() }} <span class="text-xs font-semibold text-slate-400">unit/lokasi</span></p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">
                    <i class="fa-solid fa-calendar-xmark text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Terjadwal/Dipinjam</p>
                    <p class="text-xl font-black text-rose-700">{{ $activeReservationsCount }} <span class="text-xs font-semibold text-slate-400">acara</span></p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                    <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Menunggu Konfirmasi</p>
                    <p class="text-xl font-black text-amber-700">{{ $pendingReservationsCount }} <span class="text-xs font-semibold text-slate-400">pengajuan</span></p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                    <i class="fa-solid fa-shield-halved text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Anti-Bentrok</p>
                    <p class="text-sm font-black text-teal-900">Validasi Otomatis</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Controls --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-100 p-1.5 shadow-xs">
            <button type="button" @click="tab = 'kalender'" :class="tab === 'kalender' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold transition">
                <i class="fa-solid fa-calendar-days text-sm"></i>
                <span>Kalender Ketersediaan</span>
            </button>
            <button type="button" @click="tab = 'daftar'" :class="tab === 'daftar' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold transition">
                <i class="fa-solid fa-list-check text-sm"></i>
                <span>Daftar Pengajuan</span>
                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-700">{{ $peminjamans->total() }}</span>
            </button>
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-600" x-show="tab === 'kalender'">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-full bg-rose-500"></span> Terjadwal / Terisi
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-full bg-amber-400"></span> Menunggu Konfirmasi
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-full bg-emerald-500"></span> Tersedia untuk Dipesan
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-full ring-2 ring-emerald-600 ring-offset-1 bg-white"></span> Hari Ini
            </span>
        </div>
    </div>

    {{-- TAB 1: KALENDER KETERSEDIAAN VISUAL --}}
    <div x-show="tab === 'kalender'" class="space-y-6">
        {{-- Filter & Month Navigation Bar --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xs">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                {{-- Asset & Category Selectors --}}
                <div class="flex flex-wrap items-center gap-3">
                    <div class="w-full sm:w-auto">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Pilih Fasilitas / Aset</label>
                        <select x-model="selectedAsetId" class="mt-1 w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 sm:w-64">
                            <option value="">Semua Fasilitas & Aset</option>
                            @foreach($asetList as $a)
                                <option value="{{ $a->id }}">{{ $a->nama }} ({{ $a->kategori_label }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-auto">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Kategori</label>
                        <select x-model="selectedKategori" class="mt-1 w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 sm:w-48">
                            <option value="">Semua Kategori</option>
                            <option value="gedung">Balai / Gedung</option>
                            <option value="lapangan">Lapangan Olahraga</option>
                            <option value="tenda_dan_terpal">Tenda Acara & Terpal</option>
                            <option value="elektronik">Sound System / Elektronik</option>
                            <option value="furniture">Furniture / Kursi / Meja</option>
                            <option value="panggung">Panggung Acara</option>
                        </select>
                    </div>

                    <template x-if="selectedAsetId || selectedKategori">
                        <div class="self-end">
                            <button type="button" @click="selectedAsetId = ''; selectedKategori = ''" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100">
                                <i class="fa-solid fa-xmark"></i> Reset Filter
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Month Navigator --}}
                <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-3 sm:justify-end lg:border-t-0 lg:pt-0">
                    <button type="button" @click="prevMonth()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="Bulan Sebelumnya">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <div class="min-w-40 text-center">
                        <span class="text-base font-black text-slate-900" x-text="monthTitle"></span>
                    </div>

                    <button type="button" @click="nextMonth()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="Bulan Berikutnya">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>

                    <button type="button" @click="goToToday()" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100">
                        Hari Ini
                    </button>
                </div>
            </div>
        </section>

        {{-- Monthly Calendar Visual Grid --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xs">
            {{-- Day of Week Header --}}
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-black uppercase tracking-wider text-slate-500">
                <template x-for="(name, index) in dayNames" :key="index">
                    <div class="py-3.5" :class="index === 0 ? 'text-rose-600' : (index === 6 ? 'text-amber-600' : 'text-slate-600')" x-text="name"></div>
                </template>
            </div>

            {{-- Day Cells --}}
            <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100 text-xs">
                <template x-for="(cell, cellIdx) in calendarDays" :key="cellIdx">
                    <div class="group relative min-h-28 sm:min-h-32 p-2 transition flex flex-col justify-between"
                         :class="{
                             'bg-slate-50/60 text-slate-400': !cell.isCurrentMonth,
                             'bg-white text-slate-800 hover:bg-slate-50/50': cell.isCurrentMonth && !cell.isToday,
                             'bg-emerald-50/40 ring-2 ring-inset ring-emerald-500 text-slate-900': cell.isToday
                         }">
                        
                        {{-- Date header --}}
                        <div class="flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                                  :class="{
                                      'bg-emerald-700 text-white shadow-xs': cell.isToday,
                                      'font-black text-slate-900': cell.isCurrentMonth && !cell.isToday,
                                      'text-slate-400': !cell.isCurrentMonth
                                  }"
                                  x-text="cell.day">
                            </span>

                            {{-- Quick Booking Button on Empty/Future Day --}}
                            <template x-if="cell.isCurrentMonth && !cell.isPast && canPinjam">
                                <button type="button" @click="openQuickBooking(cell)" class="opacity-0 group-hover:opacity-100 rounded-lg bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 hover:bg-emerald-100 transition" title="Pesan Tanggal Ini">
                                    <i class="fa-solid fa-plus"></i> Pesan
                                </button>
                            </template>
                        </div>

                        {{-- Event badges list --}}
                        <div class="mt-1 flex-1 space-y-1 overflow-y-auto max-h-20 sm:max-h-24">
                            <template x-for="evt in cell.events" :key="evt.id">
                                <div @click="openEventDetail(evt)"
                                     class="cursor-pointer truncate rounded-lg px-2 py-1 text-[11px] font-bold transition shadow-2xs hover:shadow-xs"
                                     :class="{
                                         'bg-rose-100 text-rose-900 border border-rose-200 hover:bg-rose-200': evt.status === 'disetujui' || evt.status === 'dipinjam',
                                         'bg-amber-100 text-amber-900 border border-amber-200 hover:bg-amber-200': evt.status === 'diajukan'
                                     }"
                                     :title="evt.title">
                                    <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full" :class="(evt.status === 'disetujui' || evt.status === 'dipinjam') ? 'bg-rose-600' : 'bg-amber-500'"></span>
                                    <span x-text="evt.aset_nama"></span>:
                                    <span class="font-normal" x-text="evt.keperluan"></span>
                                </div>
                            </template>

                            {{-- Available Indicator when specific asset selected --}}
                            <template x-if="selectedAsetId && cell.isCurrentMonth && !cell.isPast && cell.events.length === 0">
                                <div @click="openQuickBooking(cell)" class="cursor-pointer rounded-lg border border-dashed border-emerald-300 bg-emerald-50/50 p-1.5 text-center text-[10px] font-bold text-emerald-700 hover:bg-emerald-100/60 transition">
                                    <i class="fa-solid fa-circle-check text-emerald-500"></i> Tersedia
                                </div>
                            </template>
                        </div>

                        {{-- Past day watermark --}}
                        <template x-if="cell.isPast && cell.events.length === 0">
                            <div class="text-[10px] text-slate-300 italic">-</div>
                        </template>
                    </div>
                </template>
            </div>
        </section>
    </div>

    {{-- TAB 2: DAFTAR & RIWAYAT PENGAJUAN (TABLE VIEW) --}}
    <div x-show="tab === 'daftar'" class="space-y-6">
        <form method="GET" action="{{ route($indexRoute) }}" class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
            <input type="hidden" name="tab" value="daftar">
            <button type="submit" name="status" value="" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === '' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua
            </button>
            @foreach($statuses as $item)
                <button type="submit" name="status" value="{{ $item }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $status === $item ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ str($item)->headline() }}
                </button>
            @endforeach
        </form>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Aset / Fasilitas</th>
                            <th class="px-5 py-4">Pemohon</th>
                            <th class="px-5 py-4">Tanggal Pinjam</th>
                            <th class="px-5 py-4">Durasi</th>
                            <th class="px-5 py-4">Keperluan</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($peminjamans as $peminjaman)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-800">{{ $peminjaman->aset->nama }}</p>
                                    <p class="text-xs text-slate-400">{{ $peminjaman->aset->kategori_label }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $peminjaman->pemohon->name }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ $peminjaman->tanggal_mulai->translatedFormat('d M Y') }} - {{ $peminjaman->tanggal_selesai->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $peminjaman->durasi_hari }} hari</td>
                                <td class="px-5 py-4 text-slate-600 max-w-xs truncate" title="{{ $peminjaman->keperluan }}">
                                    {{ $peminjaman->keperluan }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $peminjaman->status_color }}">
                                        {{ $peminjaman->status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route($showRoute, $peminjaman) }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center text-slate-500">Belum ada data peminjaman aset.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($peminjamans->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white p-4">{{ $peminjamans->links() }}</div>
        @endif
    </div>

    {{-- MODAL 1: Detail Reservasi Aset --}}
    <div x-show="selectedEvent" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="selectedEvent = null">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl space-y-5" @click.outside="selectedEvent = null">
            <div class="flex items-start justify-between">
                <div>
                    <span class="rounded-full border px-3 py-1 text-xs font-bold"
                          :class="selectedEvent?.status === 'disetujui' || selectedEvent?.status === 'dipinjam' ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-amber-200 bg-amber-50 text-amber-800'"
                          x-text="selectedEvent?.status_label">
                    </span>
                    <h3 class="mt-2 text-lg font-black text-slate-900" x-text="selectedEvent?.aset_nama"></h3>
                    <p class="text-xs text-slate-500" x-text="selectedEvent?.aset_kategori_label"></p>
                </div>
                <button type="button" @click="selectedEvent = null" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="space-y-3 rounded-2xl bg-slate-50 p-4 text-xs font-semibold text-slate-700">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Keperluan Acara</span>
                    <span class="font-bold text-slate-900 text-right max-w-56" x-text="selectedEvent?.keperluan"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Periode Peminjaman</span>
                    <span class="font-bold text-slate-900" x-text="selectedEvent?.tanggal_mulai + ' s/d ' + selectedEvent?.tanggal_selesai"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Durasi</span>
                    <span class="font-bold text-slate-900" x-text="selectedEvent?.durasi_hari + ' hari'"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Pemohon</span>
                    <span class="font-bold text-slate-900" x-text="selectedEvent?.pemohon_nama"></span>
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" @click="selectedEvent = null" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">
                    Tutup
                </button>
                <template x-if="selectedEvent?.detail_url">
                    <a :href="selectedEvent?.detail_url" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-800">
                        Buka Detail Peminjaman
                    </a>
                </template>
            </div>
        </div>
    </div>

    {{-- MODAL 2: Quick Booking Modal pada Tanggal Kosong --}}
    <div x-show="selectedDateDay" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="selectedDateDay = null">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl space-y-5" @click.outside="selectedDateDay = null">
            <div class="flex items-start justify-between">
                <div>
                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                        <i class="fa-solid fa-calendar-plus mr-1"></i> Reservasi Fasilitas
                    </span>
                    <h3 class="mt-2 text-lg font-black text-slate-900">Pilih Fasilitas untuk Tanggal Ini</h3>
                    <p class="text-xs text-slate-500">
                        Tanggal: <strong class="text-slate-800" x-text="selectedDateDay?.dateString"></strong>
                    </p>
                </div>
                <button type="button" @click="selectedDateDay = null" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <p class="text-xs text-slate-500">
                Pilih fasilitas atau aset lingkungan yang ingin Anda gunakan pada tanggal tersebut:
            </p>

            <div class="space-y-2 max-h-64 overflow-y-auto divide-y divide-slate-100">
                <template x-for="ast in asets" :key="ast.id">
                    <div class="flex items-center justify-between py-2.5">
                        <div>
                            <p class="text-sm font-bold text-slate-900" x-text="ast.nama"></p>
                            <p class="text-xs text-slate-400" x-text="ast.kategori_label + ' · Stok: ' + ast.jumlah_tersedia + '/' + ast.jumlah_total"></p>
                        </div>
                        <a :href="getBookingUrlFor(ast.id, selectedDateDay?.dateString)" class="rounded-xl bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-800 transition">
                            Pilih Fasilitas <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </template>
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="button" @click="selectedDateDay = null" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
