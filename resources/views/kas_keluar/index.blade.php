@extends('layouts.app')

@section('title', 'Data Kas Keluar')

@section('content')

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-800 flex items-center">
            <span class="bg-red-100 text-red-600 p-2 rounded-lg mr-3 shadow-sm">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </span>
            Pengeluaran Kas RT
        </h1>
        <p class="text-sm text-slate-500 mt-1 italic">
            <i class="fa-solid fa-circle-info text-xs mr-1"></i> Klik baris untuk melihat detail bukti pengeluaran.
        </p>
    </div>

    <a href="/kas-keluar/create" 
       class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 transition-all shadow-lg shadow-red-100 active:scale-95">
        <i class="fa-solid fa-plus text-xs"></i>
        Input Pengeluaran
    </a>
</div>
<div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 mb-6">
    <form action="{{ route('kas-keluar.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Cari Keperluan</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: Lampu, Perbaikan, Konsumsi..." 
                    class="w-full bg-slate-50 border-none pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-red-500 text-sm font-bold text-slate-700 transition-all">
            </div>
        </div>

        <div class="w-full md:w-48">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Bulan</label>
            <select name="bulan" class="w-full bg-slate-50 border-none px-4 py-3 rounded-xl focus:ring-2 focus:ring-red-500 text-sm font-bold text-slate-700 appearance-none cursor-pointer">
                <option value="">Semua Bulan</option>
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full md:w-32">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Tahun</label>
            <select name="tahun" class="w-full bg-slate-50 border-none px-4 py-3 rounded-xl focus:ring-2 focus:ring-red-500 text-sm font-bold text-slate-700 appearance-none cursor-pointer">
                @php $currentYear = date('Y'); @endphp
                @for($y = $currentYear; $y >= $currentYear - 3; $y--)
                    <option value="{{ $y }}" {{ request('tahun', $currentYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>

        <div class="flex gap-2 w-full md:w-auto">
            <button type="submit" class="flex-1 md:flex-none bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg active:scale-95">
                <i class="fa-solid fa-filter mr-2"></i> Filter
            </button>
            @if(request()->anyFilled(['search', 'bulan']))
                <a href="{{ route('kas-keluar.index') }}" class="bg-slate-100 text-slate-500 px-4 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            @endif
        </div>
    </form>
</div>
<div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-6 py-5 text-[10px] uppercase tracking-[0.2em] font-extrabold text-slate-400">Keperluan / Keterangan</th>
                    <th class="px-6 py-5 text-[10px] uppercase tracking-[0.2em] font-extrabold text-slate-400 text-center">Nominal</th>
                    <th class="px-6 py-5 text-[10px] uppercase tracking-[0.2em] font-extrabold text-slate-400 text-center">Tanggal</th>
                    <th class="px-6 py-5 text-[10px] uppercase tracking-[0.2em] font-extrabold text-slate-400 text-center">Bukti Nota</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($data as $item)
                <tr class="hover:bg-red-50/30 transition-all cursor-pointer group"
                    onclick="openModal('{{ $item->keterangan }}', '{{ number_format($item->jumlah,0,',','.') }}', '{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}', '{{ $item->bukti }}')">
                    
                    <td class="px-6 py-5">
                        <span class="font-bold text-slate-700 text-sm group-hover:text-red-600 transition-colors">{{ $item->keterangan }}</span>
                    </td>
                    
                    <td class="px-6 py-5 text-center">
                        <span class="text-sm font-black text-red-500">
                            - Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                        </span>
                    </td>

                    <td class="px-6 py-5 text-center">
                        <div class="inline-flex items-center gap-2 text-slate-500 text-xs font-bold bg-slate-100 px-3 py-1.5 rounded-lg">
                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                        </div>
                    </td>

                    <td class="px-6 py-5 text-center">
                        @if($item->bukti)
                            <div class="relative inline-block group/img">
                                <img src="{{ asset('storage/'.$item->bukti) }}" class="w-12 h-12 object-cover rounded-xl shadow-sm border-2 border-white group-hover/img:scale-110 transition-transform">
                                <div class="absolute inset-0 bg-black/20 rounded-xl opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition-opacity text-white text-[10px]">
                                    <i class="fa-solid fa-eye"></i>
                                </div>
                            </div>
                        @else
                            <span class="text-[10px] font-bold text-slate-300 italic">No Image</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center">
                            <div class="bg-red-50 w-20 h-20 rounded-full flex items-center justify-center mb-4">
                                <i class="fa-solid fa-receipt text-red-200 text-3xl"></i>
                            </div>
                            <p class="text-slate-400 font-bold">Belum ada catatan pengeluaran.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50/80 border-t-2 border-slate-200">
                <tr>
                    <td class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        Total Pengeluaran Periode Ini
                    </td>
                    <td class="px-6 py-5 text-center">
                        <div class="inline-block">
                            <span class="text-[9px] font-bold text-slate-400 block text-left uppercase tracking-tighter mb-1">
                                <i class="fa-solid fa-calculator mr-1"></i> Akumulasi Nominal
                            </span>
                            <span class="text-xl font-black text-red-600 flex items-center justify-center gap-1">
                                <span class="text-sm">-</span> Rp {{ number_format($data->sum('jumlah'), 0, ',', '.') }}
                            </span>
                        </div>
                    </td>
                    <td colspan="2" class="px-6 py-5 text-right">
                        <div class="flex flex-col items-end">
                            <span class="text-[10px] font-bold text-slate-400 italic">
                                *Data berdasarkan filter aktif
                            </span>
                            <span class="bg-red-100 text-red-600 text-[10px] font-black px-3 py-1 rounded-full mt-1">
                                {{ $data->count() }} Transaksi
                            </span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div id="modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex justify-center items-center z-[999] p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md overflow-hidden shadow-2xl transform transition-all">
        <div class="bg-red-600 p-6 text-white relative">
            <button onclick="closeModal()" class="absolute top-4 right-4 text-white/70 hover:text-white transition-colors">
                <i class="fa-solid fa-circle-xmark text-2xl"></i>
            </button>
            <h2 class="text-xl font-bold flex items-center gap-2">
                <i class="fa-solid fa-receipt"></i> Detail Pengeluaran
            </h2>
        </div>

        <div class="p-8">
            <div class="space-y-4 border-b border-dashed border-slate-200 pb-6 mb-6 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-400 font-medium">Keperluan:</span>
                    <span id="modal-keterangan" class="text-slate-800 font-extrabold text-right"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400 font-medium">Tanggal:</span>
                    <span id="modal-tanggal" class="text-slate-800 font-bold"></span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-slate-400 font-medium">Total Nominal:</span>
                    <span class="text-2xl font-black text-red-600">Rp <span id="modal-jumlah"></span></span>
                </div>
            </div>

            <div class="text-center">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 text-left px-1">Lampiran Bukti / Nota:</p>
                <div id="modal-gambar" class="rounded-2xl overflow-hidden border-4 border-slate-50 shadow-inner"></div>
            </div>
            
            <button onclick="closeModal()" class="w-full mt-8 py-4 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition-all uppercase tracking-widest text-xs">
                Tutup Detail
            </button>
        </div>
    </div>
</div>

<script>
function openModal(keterangan, jumlah, tanggal, bukti) {
    const modal = document.getElementById('modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.getElementById('modal-keterangan').innerText = keterangan;
    document.getElementById('modal-jumlah').innerText = jumlah;
    document.getElementById('modal-tanggal').innerText = tanggal;

    let gambar = '';
    if (bukti) {
        gambar = `<img src="/storage/${bukti}" class="w-full h-auto max-h-[300px] object-contain cursor-zoom-in" onclick="zoomImage(this.src)">`;
    } else {
        gambar = `<div class="py-10 bg-slate-50 text-slate-300 text-xs italic font-bold uppercase tracking-widest text-center">Tidak ada lampiran gambar</div>`;
    }

    document.getElementById('modal-gambar').innerHTML = gambar;
}

function closeModal() {
    const modal = document.getElementById('modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function zoomImage(src) {
    window.open(src, '_blank');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('modal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

@endsection