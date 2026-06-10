@extends('layouts.app')

@section('title', 'Detail Pengaduan Warga')

@section('content')
<div class="font-sans max-w-5xl mx-auto">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <a href="{{ route('pengaduan.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 transition">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Kembali ke Daftar Pengaduan
        </a>

        @if(auth()->id() === $pengaduan->user_id && $pengaduan->status === 'pending')
            <form action="{{ route('pengaduan.destroy', $pengaduan) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengaduan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs px-4 py-2.5 rounded-xl border border-red-100 transition shadow-sm">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                    Hapus Pengaduan
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Kolom Kiri: Detail Pengaduan --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                {{-- Kategori & Status --}}
                <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
                    @php
                        $catColor = match($pengaduan->kategori) {
                            'Kebersihan' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                            'Keamanan' => 'bg-rose-50 text-rose-700 border-rose-100',
                            'Infrastruktur' => 'bg-blue-50 text-blue-700 border-blue-100',
                            'Sosial' => 'bg-purple-50 text-purple-700 border-purple-100',
                            default => 'bg-slate-50 text-slate-700 border-slate-100'
                        };
                    @endphp
                    <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full border {{ $catColor }}">
                        {{ $pengaduan->kategori }}
                    </span>

                    @php
                        $statusBadge = match($pengaduan->status) {
                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'proses' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'ditolak' => 'bg-red-50 text-red-700 border-red-200',
                        };
                        $statusText = match($pengaduan->status) {
                            'pending' => 'Menunggu Verifikasi',
                            'proses' => 'Sedang Diproses',
                            'selesai' => 'Selesai / Teratasi',
                            'ditolak' => 'Ditolak / Diarsipkan',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-1.5 rounded-full border {{ $statusBadge }}">
                        {{ $statusText }}
                    </span>
                </div>

                {{-- Judul --}}
                <h1 class="text-xl font-bold text-slate-800 mb-4 leading-snug">{{ $pengaduan->judul }}</h1>

                {{-- Waktu & Pelapor --}}
                <div class="flex items-center gap-3 bg-slate-50 rounded-2xl p-4 mb-6 border border-slate-100">
                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                        {{ substr($pengaduan->user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-700 truncate">{{ $pengaduan->user->name }}</p>
                        <p class="text-xs text-slate-400">Dilaporkan pada {{ $pengaduan->created_at->translatedFormat('d F Y, H:i') }} WIB ({{ $pengaduan->created_at->diffForHumans() }})</p>
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div class="text-sm text-slate-600 leading-relaxed space-y-4 mb-6">
                    <p class="whitespace-pre-line">{{ $pengaduan->deskripsi }}</p>
                </div>

                {{-- Foto Bukti --}}
                @if($pengaduan->foto)
                    <div>
                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Foto Bukti Terlampir</p>
                        <div class="relative w-full rounded-2xl overflow-hidden border border-slate-200 bg-slate-50">
                            <img src="{{ route('pengaduan.foto', $pengaduan) }}" alt="Bukti Aduan" class="w-full object-contain max-h-[450px] mx-auto">
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tanggapan Admin --}}
            @if($pengaduan->tanggapan)
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-user-tie"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Tanggapan Pengurus RT</h3>
                                <p class="text-[10px] text-slate-400">Oleh: {{ $pengaduan->responder->name ?? 'Admin RT' }}</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-600 bg-white border border-emerald-200 px-2 py-1 rounded-full">
                            {{ $pengaduan->tanggapan_at ? $pengaduan->tanggapan_at->diffForHumans() : '' }}
                        </span>
                    </div>
                    <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line bg-white rounded-2xl p-4 border border-emerald-100/50 shadow-sm">
                        {{ $pengaduan->tanggapan }}
                    </div>
                </div>
            @endif

            {{-- Formulir Tanggapan Khusus Admin --}}
            @if(auth()->user()->canManagePengaduan())
                <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-800 mb-2">Tanggapan & Solusi Pengurus RT</h2>
                    <p class="text-xs text-slate-400 mb-6">Berikan tanggapan, update penanganan, atau tindak lanjut atas pengaduan warga.</p>

                    <form action="{{ route('pengaduan.status', $pengaduan) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Update Status Laporan</label>
                                <select name="status" id="status" required
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition-all bg-white">
                                    <option value="pending" {{ $pengaduan->status === 'pending' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                                    <option value="proses" {{ $pengaduan->status === 'proses' ? 'selected' : '' }}>Diproses / Investigasi</option>
                                    <option value="selesai" {{ $pengaduan->status === 'selesai' ? 'selected' : '' }}>Selesai / Teratasi</option>
                                    <option value="ditolak" {{ $pengaduan->status === 'ditolak' ? 'selected' : '' }}>Ditolak / Diarsipkan</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="tanggapan" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Tanggapan / Catatan Penanganan</label>
                            <textarea name="tanggapan" id="tanggapan" rows="4" required
                                      class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition-all leading-relaxed"
                                      placeholder="Tuliskan respon resmi pengurus RT (contoh: Laporan diterima. Tim kebersihan akan dikerahkan sore ini untuk membersihkan jalan...)">{{ old('tanggapan', $pengaduan->tanggapan) }}</textarea>
                            @error('tanggapan')
                                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm px-6 py-3 rounded-xl shadow-md transition transform hover:-translate-y-0.5">
                                Simpan Tanggapan & Perbarui Status
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        {{-- Kolom Kanan: Linimasa Perkembangan --}}
        <div>
            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm sticky top-6">
                <h3 class="text-sm font-bold text-slate-800 mb-6 border-b border-slate-100 pb-3">Linimasa Penanganan</h3>

                <div class="flow-root">
                    <ul class="-mb-8">
                        {{-- Tahap 1: Laporan Masuk --}}
                        <li>
                            <div class="relative pb-8">
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-blue-500" aria-hidden="true"></span>
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white text-white">
                                            <i class="fa-solid fa-paper-plane text-[10px]"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-xs font-bold text-slate-800">Laporan Diterima</p>
                                            <p class="text-[10px] text-slate-400">Masuk ke sistem pengaduan</p>
                                        </div>
                                        <div class="text-right text-[10px] whitespace-nowrap text-slate-500">
                                            {{ $pengaduan->created_at->format('d/m/y') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        {{-- Tahap 2: Diproses --}}
                        @php
                            $isProses = in_array($pengaduan->status, ['proses', 'selesai', 'ditolak']);
                            $prosesBg = $isProses ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-400';
                            $lineBg = in_array($pengaduan->status, ['selesai', 'ditolak']) ? 'bg-indigo-500' : 'bg-slate-200';
                        @endphp
                        <li>
                            <div class="relative pb-8">
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 {{ $lineBg }}" aria-hidden="true"></span>
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full {{ $prosesBg }} flex items-center justify-center ring-8 ring-white">
                                            <i class="fa-solid fa-spinner fa-spin-pulse text-[10px]"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-xs font-bold {{ $isProses ? 'text-slate-800' : 'text-slate-400' }}">Sedang Ditinjau</p>
                                            <p class="text-[10px] text-slate-400">Verifikasi lapangan & koordinasi</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        {{-- Tahap 3: Selesai atau Ditolak --}}
                        @php
                            $isEnd = in_array($pengaduan->status, ['selesai', 'ditolak']);
                            if ($isEnd) {
                                $endBg = $pengaduan->status === 'selesai' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white';
                                $endIcon = $pengaduan->status === 'selesai' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark';
                            } else {
                                $endBg = 'bg-slate-100 text-slate-400';
                                $endIcon = 'fa-solid fa-flag-checkered';
                            }
                        @endphp
                        <li>
                            <div class="relative">
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full {{ $endBg }} flex items-center justify-center ring-8 ring-white">
                                            <i class="{{ $endIcon }} text-[10px]"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-xs font-bold {{ $isEnd ? 'text-slate-800' : 'text-slate-400' }}">Tindak Lanjut Selesai</p>
                                            <p class="text-[10px] text-slate-400">Solusi terdokumentasi lengkap</p>
                                        </div>
                                        @if($isEnd && $pengaduan->tanggapan_at)
                                            <div class="text-right text-[10px] whitespace-nowrap text-slate-500">
                                                {{ $pengaduan->tanggapan_at->format('d/m/y') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
