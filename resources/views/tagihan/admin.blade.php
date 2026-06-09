@extends('layouts.app')

@section('title', 'Verifikasi Tagihan')

@section('content')
<div class="space-y-6" x-data="{ showTagihanForm: {{ old('_form') === 'tagihan_manual' ? 'true' : 'false' }} }">
    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Manajemen Tagihan</h2>
                <p class="mt-2 text-slate-600">Verifikasi bukti pembayaran, beri catatan, atau tolak pembayaran dengan alasan yang jelas.</p>
            </div>
            <button type="button" x-on:click="showTagihanForm = true" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                + Buat Tagihan
            </button>
        </div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('tagihan.admin') }}" class="grid gap-4 md:grid-cols-[1fr_1fr_auto_auto] md:items-end">
            <div>
                <label class="block text-sm font-bold text-slate-700">Filter Bulan</label>
                <select name="bulan" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-emerald-500 focus:ring-emerald-200">
                    <option value="">Semua Bulan</option>
                    @foreach($bulanList as $numBulan => $namaBulan)
                        <option value="{{ $numBulan }}" {{ (string) $filterBulan === (string) $numBulan ? 'selected' : '' }}>
                            {{ $namaBulan }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700">Filter Tahun</label>
                <select name="tahun" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-emerald-500 focus:ring-emerald-200">
                    <option value="">Semua Tahun</option>
                    @foreach($tahunList as $tahun)
                        <option value="{{ $tahun }}" {{ (string) $filterTahun === (string) $tahun ? 'selected' : '' }}>
                            {{ $tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">
                Filter
            </button>

            <a href="{{ route('tagihan.admin') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                Reset
            </a>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($tagihans as $tagihan)
            @php
                $buktiUrl = $tagihan->bukti ? route('tagihan.bukti', $tagihan) : null;
                $isPdf = $tagihan->bukti && str_ends_with(strtolower($tagihan->bukti), '.pdf');
            @endphp
            <div class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="grid gap-5 xl:grid-cols-[1fr_0.8fr_1.1fr]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $tagihan->status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : ($tagihan->verification_status === 'ditolak' ? 'bg-rose-100 text-rose-700' : ($tagihan->status === 'pending_transfer' ? 'bg-amber-100 text-amber-700' : ($tagihan->status === 'pending_offline' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700'))) }}">
                                {{ $tagihan->status_label }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $tagihan->verification_status_label }}</span>
                        </div>

                        <h3 class="mt-3 text-lg font-black text-slate-900">{{ $tagihan->display_title }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $tagihan->rumah?->kode_rumah ?? 'Rumah belum diatur' }} - {{ $tagihan->user->name }}</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-emerald-50 p-3">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Nomor Transaksi</p>
                                <p class="mt-1 font-black text-emerald-950">{{ $tagihan->payment_reference }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Periode</p>
                                <p class="mt-1 font-black text-slate-900">{{ $tagihan->bulan }}/{{ $tagihan->tahun }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Metode</p>
                                <p class="mt-1 font-black capitalize text-slate-900">{{ str_replace('_', ' ', $tagihan->payment_method) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Total</p>
                                <p class="mt-1 font-black text-slate-900">Rp {{ number_format($tagihan->total, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        @if($tagihan->note)
                            <p class="mt-4 rounded-2xl bg-slate-50 p-3 text-sm leading-6 text-slate-600">Catatan warga: {{ $tagihan->note }}</p>
                        @endif
                        @if($tagihan->rejection_reason)
                            <p class="mt-3 rounded-2xl bg-rose-50 p-3 text-sm font-semibold leading-6 text-rose-700">Alasan penolakan: {{ $tagihan->rejection_reason }}</p>
                        @endif
                        @if($tagihan->rejected_at)
                            <p class="mt-2 text-xs font-semibold text-rose-600">
                                Ditolak {{ $tagihan->rejected_at->format('d/m/Y H:i') }} oleh {{ $tagihan->rejecter?->name ?? 'pengurus' }}.
                            </p>
                        @endif
                    </div>

                    <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                        <p class="mb-3 text-xs font-black uppercase tracking-[0.18em] text-slate-500">Preview Bukti</p>
                        @if($buktiUrl)
                            @if($isPdf)
                                <div class="flex min-h-56 flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white p-6 text-center">
                                    <i class="fa-solid fa-file-pdf text-4xl text-rose-500"></i>
                                    <p class="mt-3 text-sm font-bold text-slate-700">Bukti berupa PDF</p>
                                    <a href="{{ $buktiUrl }}" target="_blank" class="mt-4 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Buka PDF</a>
                                </div>
                            @else
                                <a href="{{ $buktiUrl }}" target="_blank" class="block overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <img src="{{ $buktiUrl }}" alt="Bukti pembayaran" class="max-h-72 w-full object-contain">
                                </a>
                            @endif
                        @else
                            <div class="flex min-h-56 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white text-sm font-bold text-slate-400">
                                Tidak ada bukti file
                            </div>
                        @endif

                        @if($tagihan->verified_at)
                            <p class="mt-3 text-xs font-semibold text-slate-500">
                                Diverifikasi {{ $tagihan->verified_at->format('d/m/Y H:i') }} oleh {{ $tagihan->verifier?->name ?? 'pengurus' }}.
                            </p>
                        @endif
                    </div>

                    <div class="rounded-3xl border border-emerald-100 bg-emerald-50/50 p-4">
                        <p class="text-sm font-black text-emerald-950">Aksi Verifikasi</p>
                        <form action="{{ route('tagihan.confirm') }}" method="POST" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="tagihan_id" value="{{ $tagihan->id }}">
                            <input type="hidden" name="status" value="lunas">
                            <textarea name="verification_note" rows="2" class="w-full rounded-2xl border border-emerald-100 bg-white p-3 text-sm focus:border-emerald-500 focus:ring-emerald-200" placeholder="Catatan verifikasi opsional, contoh: Bukti sesuai nominal dan rekening tujuan.">{{ old('tagihan_id') == $tagihan->id ? old('verification_note') : $tagihan->verification_note }}</textarea>
                            <button class="w-full rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white hover:bg-emerald-700">
                                Setujui & Jadikan Lunas
                            </button>
                        </form>

                        <form action="{{ route('tagihan.confirm') }}" method="POST" class="mt-4 space-y-3 border-t border-emerald-100 pt-4">
                            @csrf
                            <input type="hidden" name="tagihan_id" value="{{ $tagihan->id }}">
                            <input type="hidden" name="status" value="ditolak">
                            <textarea name="rejection_reason" rows="2" class="w-full rounded-2xl border border-rose-100 bg-white p-3 text-sm focus:border-rose-500 focus:ring-rose-200" placeholder="Alasan penolakan, contoh: Nominal tidak sesuai.">{{ old('tagihan_id') == $tagihan->id ? old('rejection_reason') : '' }}</textarea>
                            <button class="w-full rounded-2xl bg-rose-600 px-4 py-3 text-sm font-black text-white hover:bg-rose-700">
                                Tolak Bukti
                            </button>
                        </form>

                        <form action="{{ route('tagihan.confirm') }}" method="POST" class="mt-4">
                            @csrf
                            <input type="hidden" name="tagihan_id" value="{{ $tagihan->id }}">
                            <input type="hidden" name="status" value="belum_bayar">
                            <button class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-600 hover:bg-slate-50">
                                Reset ke Belum Bayar
                            </button>
                        </form>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('tagihan.edit', $tagihan) }}" class="flex-1 rounded-xl bg-amber-500 px-3 py-2 text-center text-xs font-bold text-white hover:bg-amber-600">Edit</a>
                            <button type="button" onclick="confirmDelete('{{ $tagihan->id }}', '{{ addslashes($tagihan->user->name) }} {{ $tagihan->bulan }}/{{ $tagihan->tahun }}')" class="flex-1 rounded-xl bg-red-600 px-3 py-2 text-xs font-bold text-white hover:bg-red-700">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white p-10 text-center font-bold text-slate-500">Belum ada tagihan yang tersedia.</div>
        @endforelse
    </div>

    @if($tagihans->hasPages())
        <div class="rounded-3xl bg-white p-4 shadow-sm">
            {{ $tagihans->withQueryString()->links() }}
        </div>
    @endif

    <div x-cloak x-show="showTagihanForm" x-transition.opacity class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" x-on:click.self="showTagihanForm = false">
        <section x-transition class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between bg-emerald-800 p-6 text-white">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-100">Tagihan Manual</p>
                    <h2 class="mt-2 text-xl font-black">Buat Tagihan Baru</h2>
                    <p class="mt-1 text-sm text-emerald-50">Gunakan hanya untuk tagihan khusus di luar generate iuran bulanan.</p>
                </div>
                <button type="button" x-on:click="showTagihanForm = false" class="rounded-xl p-2 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Tutup form">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('tagihan.store') }}" method="POST" class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="_form" value="tagihan_manual">

                @if(old('_form') === 'tagihan_manual' && $errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        Periksa kembali data tagihan yang diisi.
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-bold text-slate-700">Kepala Keluarga</label>
                    <select name="user_id" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'tagihan_manual') @error('user_id') border-red-500 @enderror @endif" required>
                        <option value="">Pilih Kepala Keluarga</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('_form') === 'tagihan_manual' && old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} (RT {{ $user->rt }}/RW {{ $user->rw }})
                            </option>
                        @endforeach
                    </select>
                    @if(old('_form') === 'tagihan_manual')
                        @error('user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-bold text-slate-700">Bulan</label>
                        <select name="bulan" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'tagihan_manual') @error('bulan') border-red-500 @enderror @endif" required>
                            <option value="">Pilih Bulan</option>
                            @foreach($bulanList as $numBulan => $namaBulan)
                                <option value="{{ $numBulan }}" {{ old('_form') === 'tagihan_manual' ? (old('bulan', now()->month) == $numBulan ? 'selected' : '') : (now()->month == $numBulan ? 'selected' : '') }}>
                                    {{ $namaBulan }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('_form') === 'tagihan_manual')
                            @error('bulan')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Tahun</label>
                        <select name="tahun" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'tagihan_manual') @error('tahun') border-red-500 @enderror @endif" required>
                            <option value="">Pilih Tahun</option>
                            @foreach($tahunList as $tahun)
                                <option value="{{ $tahun }}" {{ old('_form') === 'tagihan_manual' ? (old('tahun', now()->year) == $tahun ? 'selected' : '') : (now()->year == $tahun ? 'selected' : '') }}>
                                    {{ $tahun }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('_form') === 'tagihan_manual')
                            @error('tahun')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700">Jumlah Tagihan</label>
                    <div class="relative mt-2">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-semibold text-slate-700">Rp</span>
                        <input type="number" name="total" value="{{ old('_form') === 'tagihan_manual' ? old('total') : '' }}"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pl-10 text-slate-900 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'tagihan_manual') @error('total') border-red-500 @enderror @endif"
                            placeholder="0" min="1000" step="1000" required>
                    </div>
                    @if(old('_form') === 'tagihan_manual')
                        @error('total')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700">Catatan</label>
                    <textarea name="note" rows="3" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-emerald-500 focus:ring-emerald-200" placeholder="Opsional, contoh: Tagihan khusus kegiatan RT">{{ old('_form') === 'tagihan_manual' ? old('note') : '' }}</textarea>
                    @if(old('_form') === 'tagihan_manual')
                        @error('note')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    Untuk iuran rutin, lebih disarankan pakai menu Iuran Bulanan agar tagihan tetap berbasis rumah/unit hunian.
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                    <button type="button" x-on:click="showTagihanForm = false" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-800">
                        Buat Tagihan
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 p-4">
    <div class="w-full max-w-sm space-y-4 rounded-3xl bg-white p-6 shadow-lg">
        <h2 class="text-xl font-bold text-slate-800">Hapus Tagihan?</h2>
        <p id="deleteMessage" class="text-slate-600"></p>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="closeDeleteModal()" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
            <form id="deleteForm" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">Hapus</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(tagihanId, tagihanName) {
    const modal = document.getElementById('deleteModal');
    const message = document.getElementById('deleteMessage');
    const form = document.getElementById('deleteForm');

    message.textContent = `Anda akan menghapus tagihan "${tagihanName}". Aksi ini tidak dapat dibatalkan.`;
    form.action = `/tagihan/${tagihanId}`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
@endsection
