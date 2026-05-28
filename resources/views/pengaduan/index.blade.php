@extends('layouts.app')

@section('title', 'Pengaduan & Aspirasi Warga')

@section('content')
<div class="font-sans">

    {{-- =============================================
         SECTION 1: STATISTIK RINGKAS
         ============================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        {{-- Total --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Laporan</p>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-blue-50 text-blue-600 p-3 rounded-xl">
                <i class="fa-solid fa-bullhorn text-lg"></i>
            </div>
        </div>

        {{-- Pending --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Menunggu</p>
                <p class="text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
            </div>
            <div class="bg-amber-50 text-amber-600 p-3 rounded-xl">
                <i class="fa-solid fa-clock text-lg"></i>
            </div>
        </div>

        {{-- Proses --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Diproses</p>
                <p class="text-2xl font-bold text-indigo-600">{{ $stats['proses'] }}</p>
            </div>
            <div class="bg-indigo-50 text-indigo-600 p-3 rounded-xl">
                <i class="fa-solid fa-spinner fa-spin text-lg"></i>
            </div>
        </div>

        {{-- Selesai --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Selesai</p>
                <p class="text-2xl font-bold text-emerald-600">{{ $stats['selesai'] }}</p>
            </div>
            <div class="bg-emerald-50 text-emerald-600 p-3 rounded-xl">
                <i class="fa-solid fa-circle-check text-lg"></i>
            </div>
        </div>
    </div>

    {{-- =============================================
         SECTION 2: HEADER & ACTIONS
         ============================================= --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        {{-- Filter Tab --}}
        <div class="flex flex-wrap items-center bg-slate-100 p-1.5 rounded-2xl border border-slate-200 self-start">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('pengaduan.index', ['filter' => 'semua']) }}" 
                   class="px-4 py-2 text-xs font-semibold rounded-xl transition-all {{ $filter === 'semua' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                    Semua
                </a>
                <a href="{{ route('pengaduan.index', ['filter' => 'pending']) }}" 
                   class="px-4 py-2 text-xs font-semibold rounded-xl transition-all {{ $filter === 'pending' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-500 hover:text-amber-600' }}">
                    Menunggu
                </a>
                <a href="{{ route('pengaduan.index', ['filter' => 'proses']) }}" 
                   class="px-4 py-2 text-xs font-semibold rounded-xl transition-all {{ $filter === 'proses' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-indigo-600' }}">
                    Diproses
                </a>
                <a href="{{ route('pengaduan.index', ['filter' => 'selesai']) }}" 
                   class="px-4 py-2 text-xs font-semibold rounded-xl transition-all {{ $filter === 'selesai' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-emerald-600' }}">
                    Selesai
                </a>
                <a href="{{ route('pengaduan.index', ['filter' => 'ditolak']) }}" 
                   class="px-4 py-2 text-xs font-semibold rounded-xl transition-all {{ $filter === 'ditolak' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-500 hover:text-rose-600' }}">
                    Ditolak
                </a>
            @else
                <a href="{{ route('pengaduan.index', ['filter' => 'semua']) }}" 
                   class="px-4 py-2 text-xs font-semibold rounded-xl transition-all {{ $filter === 'semua' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                    Semua Aspirasi RT
                </a>
                <a href="{{ route('pengaduan.index', ['filter' => 'saya']) }}" 
                   class="px-4 py-2 text-xs font-semibold rounded-xl transition-all {{ $filter === 'saya' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-blue-600' }}">
                    Aduan Saya
                </a>
            @endif
        </div>

        {{-- Action Button --}}
        <div>
            <a href="{{ route('pengaduan.create') }}" 
               class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm px-5 py-3 rounded-2xl shadow-lg shadow-indigo-100 hover:shadow-indigo-200 transition-all transform hover:-translate-y-0.5">
                <i class="fa-solid fa-plus text-xs"></i>
                Tulis Pengaduan Baru
            </a>
        </div>
    </div>

    {{-- =============================================
         SECTION 3: LIST PENGADUAN
         ============================================= --}}
    @if($pengaduans->isEmpty())
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="bg-slate-50 text-slate-400 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                <i class="fa-solid fa-folder-open text-2xl"></i>
            </div>
            <h4 class="text-base font-bold text-slate-800 mb-1">Belum Ada Pengaduan</h4>
            <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">Tidak menemukan laporan dengan kategori atau filter saat ini. Aspirasi Anda sangat berharga untuk kemajuan lingkungan RT kita.</p>
            <a href="{{ route('pengaduan.create') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl transition">
                <i class="fa-solid fa-pen-nib"></i>
                Tulis Aspirasi Sekarang
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            @foreach($pengaduans as $pengaduan)
                <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden flex flex-col justify-between hover:border-blue-400 hover:shadow-xl hover:shadow-slate-100 transition-all duration-300">
                    <div>
                        {{-- Top Header Card --}}
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                @php
                                    $catColor = match($pengaduan->kategori) {
                                        'Kebersihan' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        'Keamanan' => 'bg-rose-50 text-rose-700 border-rose-100',
                                        'Infrastruktur' => 'bg-blue-50 text-blue-700 border-blue-100',
                                        'Sosial' => 'bg-purple-50 text-purple-700 border-purple-100',
                                        default => 'bg-slate-50 text-slate-700 border-slate-100'
                                    };
                                @endphp
                                <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border {{ $catColor }} mb-2">
                                    {{ $pengaduan->kategori }}
                                </span>
                                <h3 class="text-base font-bold text-slate-800 truncate" title="{{ $pengaduan->judul }}">
                                    {{ $pengaduan->judul }}
                                </h3>
                            </div>

                            {{-- Status Badge --}}
                            @php
                                $statusBadge = match($pengaduan->status) {
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'proses' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'ditolak' => 'bg-red-50 text-red-700 border-red-200',
                                };
                                $statusText = match($pengaduan->status) {
                                    'pending' => 'Menunggu',
                                    'proses' => 'Diproses',
                                    'selesai' => 'Selesai',
                                    'ditolak' => 'Ditolak',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full border {{ $statusBadge }}">
                                @if($pengaduan->status === 'proses')
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                                    </span>
                                @endif
                                {{ $statusText }}
                            </span>
                        </div>

                        {{-- Body Content --}}
                        <div class="p-6">
                            <p class="text-sm text-slate-500 line-clamp-3 leading-relaxed mb-4">
                                {{ $pengaduan->deskripsi }}
                            </p>

                            @if($pengaduan->foto)
                                <div class="relative w-full h-40 bg-slate-100 rounded-2xl overflow-hidden mb-4 border border-slate-200">
                                    <img src="{{ asset('storage/' . $pengaduan->foto) }}" alt="Bukti Foto" class="w-full h-full object-cover">
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                {{ substr($pengaduan->user->name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-700 truncate max-w-[120px]">{{ $pengaduan->user->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $pengaduan->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if(auth()->id() === $pengaduan->user_id && $pengaduan->status === 'pending')
                                <form action="{{ route('pengaduan.destroy', $pengaduan) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengaduan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 transition" title="Hapus Pengaduan">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('pengaduan.show', $pengaduan) }}" 
                               class="inline-flex items-center gap-1 bg-white hover:bg-slate-100 text-blue-600 border border-slate-200 font-semibold text-xs px-3.5 py-2 rounded-xl shadow-sm transition">
                                Detail
                                <i class="fa-solid fa-angle-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $pengaduans->links() }}
        </div>
    @endif

</div>
@endsection
