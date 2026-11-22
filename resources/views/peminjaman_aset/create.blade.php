@extends('layouts.app')

@section('title', 'Ajukan Peminjaman Aset')

@section('content')
@php
    $isRw = ($scope ?? 'rt') === 'rw';
    $asetIndexRoute = $isRw ? 'aset-rw.index' : 'aset.index';
    $storeRoute = $isRw ? 'peminjaman-aset-rw.store' : 'peminjaman-aset.store';
    $indexRoute = $isRw ? 'peminjaman-aset-rw.index' : 'peminjaman-aset.index';
    $asetOptions = $asets->map(function ($aset) {
        return [
            'id' => $aset->id,
            'nama' => $aset->nama,
            'kategori_label' => $aset->kategori_label,
            'jumlah_tersedia' => $aset->jumlah_tersedia,
            'jumlah_total' => $aset->jumlah_total,
            'booked_ranges' => $aset->peminjamanAset()
                ->whereIn('status', ['disetujui', 'dipinjam'])
                ->whereDate('tanggal_selesai', '>=', now()->toDateString())
                ->orderBy('tanggal_mulai')
                ->get(['id', 'tanggal_mulai', 'tanggal_selesai', 'keperluan', 'status'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'mulai' => $p->tanggal_mulai->toDateString(),
                    'selesai' => $p->tanggal_selesai->toDateString(),
                    'mulai_label' => $p->tanggal_mulai->translatedFormat('d M Y'),
                    'selesai_label' => $p->tanggal_selesai->translatedFormat('d M Y'),
                    'keperluan' => $p->keperluan,
                    'status' => $p->status,
                ]),
        ];
    })->values();
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route($indexRoute) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke kalender & peminjaman
        </a>
        <a href="{{ route($asetIndexRoute) }}" class="text-xs font-semibold text-slate-400 hover:text-slate-600">
            Katalog Aset <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i>
        </a>
    </div>

    <form method="POST" action="{{ route($storeRoute) }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8"
          x-data="{
              asetId: '{{ old('aset_id', $asetTerpilih?->id) }}',
              mulai: '{{ old('tanggal_mulai', $prefillMulai ?? '') }}',
              selesai: '{{ old('tanggal_selesai', $prefillSelesai ?? '') }}',
              asets: @js($asetOptions),
              get selectedAset() {
                  return this.asets.find(item => String(item.id) === String(this.asetId));
              },
              get durasi() {
                  if (!this.mulai || !this.selesai) return 0;
                  const start = new Date(this.mulai);
                  const end = new Date(this.selesai);
                  const diff = Math.floor((end - start) / 86400000) + 1;
                  return diff > 0 ? diff : 0;
              },
              get conflict() {
                  if (!this.selectedAset || !this.mulai || !this.selesai) return null;
                  const ranges = this.selectedAset.booked_ranges || [];
                  for (const r of ranges) {
                      if (r.mulai <= this.selesai && r.selesai >= this.mulai) {
                          return r;
                      }
                  }
                  return null;
              }
          }">
        @csrf
        <input type="hidden" name="scope" value="{{ $scope ?? 'rt' }}">

        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                <i class="fa-solid fa-calendar-check text-emerald-600"></i>
                <span>Form Reservasi Bebas Bentrok</span>
            </div>
            <h1 class="mt-2 text-2xl font-black text-slate-900">Ajukan Peminjaman Aset {{ $isRw ? 'RW' : 'RT' }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Sistem akan memverifikasi ketersediaan jadwal secara otomatis untuk memastikan tidak ada jadwal bentrok antar warga.
            </p>
        </div>

        <div class="mt-7 grid gap-5 sm:grid-cols-2">
            {{-- Aset Selection --}}
            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pilih Aset / Fasilitas</label>
                <select name="aset_id" x-model="asetId" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">-- Pilih Fasilitas / Aset --</option>
                    @foreach($asets as $aset)
                        <option value="{{ $aset->id }}" @selected((int) old('aset_id', $asetTerpilih?->id) === $aset->id)>
                            {{ $aset->nama }} ({{ $aset->kategori_label }}) - tersedia {{ $aset->jumlah_tersedia }} / {{ $aset->jumlah_total }} unit
                        </option>
                    @endforeach
                </select>
                @error('aset_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror

                <template x-if="selectedAset && selectedAset.jumlah_tersedia === 0">
                    <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800">
                        <i class="fa-solid fa-circle-exclamation mr-1"></i> Aset ini saat ini tidak memiliki sisa stok tersedia.
                    </p>
                </template>

                {{-- Upcoming Booked Dates Preview --}}
                <template x-if="selectedAset && selectedAset.booked_ranges && selectedAset.booked_ranges.length > 0">
                    <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                        <p class="text-xs font-bold text-slate-700">
                            <i class="fa-solid fa-calendar-xmark text-rose-500 mr-1"></i> Jadwal Terisi Mendatang untuk fasilitas ini:
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="b in selectedAset.booked_ranges" :key="b.id">
                                <span class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-800">
                                    <span x-text="b.mulai_label + ' - ' + b.selesai_label"></span>:
                                    <span class="font-normal" x-text="b.keperluan"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Tanggal Mulai --}}
            <div>
                <label class="text-sm font-bold text-slate-700">Tanggal Mulai Peminjaman</label>
                <input type="date" name="tanggal_mulai" x-model="mulai" value="{{ old('tanggal_mulai', $prefillMulai) }}" min="{{ now()->toDateString() }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('tanggal_mulai')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Tanggal Selesai --}}
            <div>
                <label class="text-sm font-bold text-slate-700">Tanggal Selesai Peminjaman</label>
                <input type="date" name="tanggal_selesai" x-model="selesai" value="{{ old('tanggal_selesai', $prefillSelesai) }}" min="{{ old('tanggal_mulai', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('tanggal_selesai')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Live Conflict / Availability Indicators --}}
            <template x-if="conflict">
                <div class="sm:col-span-2 rounded-2xl border border-rose-300 bg-rose-50 p-4 text-rose-900 shadow-xs">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg mt-0.5"></i>
                        <div>
                            <p class="font-bold text-sm">Peringatan: Jadwal Bentrok!</p>
                            <p class="text-xs mt-1 text-rose-700">
                                Fasilitas ini sudah dijadwalkan pada <strong x-text="conflict.mulai_label + ' s/d ' + conflict.selesai_label"></strong> untuk keperluan "<span x-text="conflict.keperluan"></span>". Mohon ganti tanggal peminjaman agar tidak terjadi bentrok agenda.
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="selectedAset && mulai && selesai && !conflict && durasi > 0">
                <div class="sm:col-span-2 rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-900 shadow-xs">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                        <div>
                            <p class="font-bold text-sm">Jadwal Kosong & Tersedia!</p>
                            <p class="text-xs mt-0.5 text-emerald-700">
                                Tidak ada bentrok jadwal pada rentang tanggal tersebut. Pengajuan Anda siap dikirim ke pengurus.
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Jumlah Dipinjam --}}
            <div>
                <label class="text-sm font-bold text-slate-700">Jumlah Dipinjam (Unit)</label>
                <input type="number" name="jumlah_dipinjam" value="{{ old('jumlah_dipinjam', 1) }}" min="1" required class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('jumlah_dipinjam')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Durasi Box --}}
            <div class="rounded-2xl bg-emerald-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Durasi Peminjaman</p>
                <p class="mt-1 text-xl font-black text-emerald-900"><span x-text="durasi"></span> hari</p>
            </div>

            {{-- Keperluan --}}
            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Keperluan / Acara</label>
                <input type="text" name="keperluan" value="{{ old('keperluan') }}" required minlength="5" maxlength="255" placeholder="{{ $isRw ? 'Contoh: Hajatan Pernikahan / Rapat Warga RW' : 'Contoh: Hajatan Syukuran / Turnamen Bulutangkis RT' }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('keperluan')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Catatan Pemohon --}}
            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700">Catatan Pemohon</label>
                <textarea name="catatan_pemohon" rows="4" maxlength="500" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Catatan tambahan opsional (misal: jam pengambilan, kontak person panitia acara)">{{ old('catatan_pemohon') }}</textarea>
                @error('catatan_pemohon')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route($indexRoute) }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
            <button :disabled="!!conflict"
                    :class="conflict ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'"
                    class="rounded-xl px-5 py-3 text-sm font-bold text-white transition">
                <i class="fa-solid fa-paper-plane mr-1.5"></i> Kirim Pengajuan Peminjaman
            </button>
        </div>
    </form>
</div>
@endsection
