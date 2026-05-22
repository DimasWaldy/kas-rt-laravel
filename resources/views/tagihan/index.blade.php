@extends('layouts.app')

@section('title', 'Tagihan Saya')

@section('content')
<div class="flex flex-col gap-6">
    <div class="bg-white rounded-3xl shadow-sm p-6 border-l-8 border-yellow-500">
        <h2 class="text-xl font-bold text-slate-800">Tagihan Bulanan</h2>
        <p class="text-slate-500 mt-2">Berikut tagihan iuran Anda untuk bulan ini dan riwayat tagihan sebelumnya.</p>
    </div>

    @if($showHeadNotice)
        <div class="bg-amber-50 rounded-3xl shadow-sm p-4 border border-amber-200 text-amber-900">
            <p class="text-sm font-semibold">Tagihan keluarga ini dikelola oleh Kepala Keluarga <strong>{{ $headUser->name }}</strong>.</p>
            <p class="text-sm text-slate-600 mt-1">Hanya Kepala Keluarga yang dapat melakukan pembayaran tagihan KK.</p>
        </div>
    @endif

    @if($tagihan->isEmpty())
        <div class="bg-white rounded-3xl shadow-sm p-6 text-center text-slate-600">
            <p class="font-semibold">Belum ada tagihan untuk akun Anda.</p>
            <p class="mt-2 text-sm text-slate-500">Tagihan akan muncul setelah admin menetapkan iuran bulanan.</p>
        </div>
    @else
        <div class="grid gap-6">
            @foreach($tagihan as $item)
                <div class="bg-white rounded-3xl shadow-sm p-6 border border-slate-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Tagihan Bulanan</h3>
                            <p class="text-slate-500">Bulan {{ $item->bulan }}/{{ $item->tahun }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-2 rounded-full text-sm font-semibold {{ $item->status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : ($item->status === 'pending_transfer' ? 'bg-amber-100 text-amber-700' : ($item->status === 'pending_offline' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700')) }}">
                                {{ $item->status_label }}
                            </span>
                            <span class="px-3 py-2 rounded-full text-sm font-semibold {{ $item->due_status_class }}">
                                {{ $item->due_status_label }}
                            </span>
                            <span class="text-slate-500 text-sm">Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="bg-slate-50 rounded-3xl p-4">
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400 mb-3">Rincian Komponen Iuran</p>
                            <div class="space-y-2 mb-4 border-b border-slate-200 pb-4">
                                @forelse($item->details as $detail)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600">{{ $detail->nama }}</span>
                                        <span class="font-medium text-slate-900">Rp {{ number_format($detail->jumlah, 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 italic">Data rincian tidak tersedia.</p>
                                @endforelse
                                <div class="flex justify-between text-sm pt-2 font-bold border-t border-dashed border-slate-300">
                                    <span class="text-slate-800">Total Tagihan</span>
                                    <span class="text-emerald-600">Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400 mb-3">Informasi Pembayaran</p>
                            <p class="text-sm text-slate-600">Metode: <span class="font-semibold capitalize">{{ $item->payment_method === 'none' ? 'Belum Bayar' : str_replace('_', ' ', $item->payment_method) }}</span></p>
                            @if($item->note)
                                <p class="text-sm text-slate-600 mt-2">Catatan: {{ $item->note }}</p>
                            @endif
                            @if($item->bukti)
                                <p class="text-sm text-slate-600 mt-2">Bukti: <a href="{{ Storage::url($item->bukti) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a></p>
                            @endif
                        </div>

                        <div class="p-4 rounded-3xl border border-slate-200 bg-white">
                            @if($item->status === 'belum_bayar')
                                <form action="{{ route('tagihan.pay') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="tagihan_id" value="{{ $item->id }}">

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700">Metode Pembayaran</label>
                                        <select name="payment_method" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm">
                                            <option value="transfer">Transfer</option>
                                            <option value="offline">Offline</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700">Bukti Transfer</label>
                                        <input type="file" name="bukti" accept="image/*" class="mt-2 w-full text-sm" />
                                        <p class="text-xs text-slate-400 mt-1">Unggah bukti jika bayar transfer.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700">Catatan</label>
                                        <textarea name="note" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm" placeholder="Contoh: Bayar via ATM BCA"></textarea>
                                    </div>

                                    <button type="submit" class="w-full rounded-2xl bg-green-600 px-5 py-3 text-sm font-bold text-white hover:bg-green-700 transition">Bayar / Ajukan Pembayaran</button>
                                </form>
                            @elseif($item->status === 'pending_transfer')
                                <div class="rounded-3xl bg-amber-50 p-4">
                                    <p class="text-sm font-semibold text-amber-800">Transaksi transfer sedang menunggu konfirmasi RT.</p>
                                    <p class="text-sm text-slate-600 mt-2">Silakan tunggu admin memverifikasi bukti pembayaran Anda.</p>
                                </div>
                            @elseif($item->status === 'pending_offline')
                                <div class="rounded-3xl bg-sky-50 p-4">
                                    <p class="text-sm font-semibold text-sky-800">Pembayaran offline menunggu verifikasi.</p>
                                    <p class="text-sm text-slate-600 mt-2">Admin akan mengubah status menjadi lunas saat pembayaran diterima.</p>
                                </div>
                            @else
                                <div class="rounded-3xl bg-emerald-50 p-4">
                                    <p class="text-sm font-semibold text-emerald-800">Tagihan sudah lunas.</p>
                                    <p class="text-sm text-slate-600 mt-2">Terima kasih, pembayaran Anda telah dicatat.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
