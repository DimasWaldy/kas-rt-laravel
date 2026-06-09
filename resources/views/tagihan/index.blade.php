@extends('layouts.app')

@section('title', 'Tagihan Saya')

@section('content')
<div class="flex flex-col gap-6">
    <div class="bg-white rounded-3xl shadow-sm p-6 border-l-8 border-yellow-500">
        <h2 class="text-xl font-bold text-slate-800">Tagihan Bulanan</h2>
        <p class="text-slate-500 mt-2">Berikut tagihan iuran rumah Anda untuk bulan ini dan riwayat tagihan sebelumnya.</p>
        @if($rumah)
            <p class="mt-2 text-sm font-semibold text-emerald-700">Rumah: {{ $rumah->label }}</p>
        @endif
        <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3">
            <p class="text-sm font-semibold text-emerald-900">Tagihan dihitung per rumah/unit hunian, bukan per KK.</p>
            <p class="mt-1 text-xs leading-5 text-emerald-700">Jika satu rumah berisi lebih dari satu KK, tagihan iuran tetap dibuat satu kali untuk rumah tersebut dan dibayarkan oleh penanggung jawab rumah.</p>
        </div>
    </div>

    @if($showHeadNotice)
        <div class="bg-amber-50 rounded-3xl shadow-sm p-4 border border-amber-200 text-amber-900">
            <p class="text-sm font-semibold">Tagihan rumah ini dikelola oleh penanggung jawab <strong>{{ $headUser?->name ?? 'yang belum ditentukan' }}</strong>.</p>
            <p class="text-sm text-slate-600 mt-1">Hanya penanggung jawab rumah yang dapat mengajukan pembayaran iuran.</p>
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
                            <h3 class="text-lg font-bold text-slate-900">{{ $item->display_title }}</h3>
                            <p class="text-slate-500">
                                @php
                                    $namaBulan = \Carbon\Carbon::create(null, $item->bulan)->translatedFormat('F');
                                @endphp
                                Periode: <span class="font-bold text-slate-800">Bulan ke-{{ $item->bulan }} ({{ $namaBulan }}) {{ $item->tahun }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-2 rounded-full text-sm font-semibold {{ $item->status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : ($item->status === 'failed' ? 'bg-rose-100 text-rose-700' : ($item->status === 'pending_transfer' ? 'bg-amber-100 text-amber-700' : ($item->status === 'pending_offline' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700'))) }}">
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
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400 mb-3">Rincian Nota Pembayaran</p>
                            <div class="space-y-2 mb-4 border-b border-slate-200 pb-4">
                                @forelse($item->details as $detail)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600">
                                            {{ $detail->nama }}
                                            <span class="ml-1 text-[10px] px-2 py-0.5 rounded-full {{ $detail->is_wajib ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600' }}">
                                                {{ $detail->is_wajib ? 'Wajib' : 'Opsional' }}
                                            </span>
                                        </span>
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
                            <p class="text-sm text-slate-600">No. Transaksi: <span class="font-semibold">{{ $item->payment_reference }}</span></p>
                            <p class="text-sm text-slate-600 mt-2">Status Bukti: <span class="font-semibold">{{ $item->verification_status_label }}</span></p>
                            <p class="text-sm text-slate-600">Metode: <span class="font-semibold capitalize">{{ $item->payment_method === 'none' ? 'Belum Bayar' : str_replace('_', ' ', $item->payment_method) }}</span></p>
                            @if($item->note)
                                <p class="text-sm text-slate-600 mt-2">Catatan: {{ $item->note }}</p>
                            @endif
                            @if($item->verification_note)
                                <p class="text-sm text-slate-600 mt-2">Catatan Verifikasi: {{ $item->verification_note }}</p>
                            @endif
                            @if($item->status === 'failed' && $item->rejection_reason)
                                <div class="mt-2 rounded-2xl bg-rose-50 p-3 text-sm text-rose-700">
                                    <p class="font-bold">Alasan Ditolak: {{ $item->rejection_reason }}</p>
                                    @if($item->rejected_at)
                                        <p class="mt-1 text-xs font-semibold text-rose-600">Ditolak pada {{ $item->rejected_at->format('d/m/Y H:i') }}.</p>
                                    @endif
                                </div>
                            @endif
                            @if($item->bukti)
                                <p class="text-sm text-slate-600 mt-2">Bukti: <a href="{{ route('tagihan.bukti', $item) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a></p>
                            @endif
                        </div>

                        <div class="p-4 rounded-3xl border border-slate-200 bg-white">
                            @if(in_array($item->status, ['belum_bayar', 'failed'], true))
                                @if($canPayTagihan)
                                <form action="{{ route('tagihan.pay') }}" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="{ paymentMethod: '{{ old('tagihan_id') == $item->id ? old('payment_method', 'transfer') : 'transfer' }}' }">
                                    @csrf
                                    <input type="hidden" name="tagihan_id" value="{{ $item->id }}">

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700">Metode Pembayaran</label>
                                        <select name="payment_method" x-model="paymentMethod" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            <option value="transfer">Transfer Bank</option>
                                            <option value="offline">Bayar Tunai / Offline</option>
                                        </select>
                                        @if(old('tagihan_id') == $item->id)
                                            @error('payment_method')
                                                <p class="text-xs font-semibold text-rose-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>

                                    <div x-show="paymentMethod === 'transfer'" x-transition class="space-y-1">
                                        <label class="block text-sm font-semibold text-slate-700">
                                            Bukti Transfer <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="file" name="bukti" accept="image/jpeg,image/png,application/pdf" class="mt-2 w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        <p class="text-xs text-slate-400 mt-1">Unggah bukti transfer (format: JPG, PNG, PDF. Maksimal 2MB).</p>
                                        @if(old('tagihan_id') == $item->id)
                                            @error('bukti')
                                                <p class="text-xs font-semibold text-rose-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>

                                    <div class="space-y-1">
                                        <label class="block text-sm font-semibold text-slate-700">
                                            Catatan <span class="text-rose-500" x-show="paymentMethod === 'offline'">*</span>
                                        </label>
                                        <textarea name="note" rows="3" class="mt-2 w-full rounded-2xl border p-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 @if(old('tagihan_id') == $item->id) @error('note') border-rose-300 @else border-slate-300 @enderror @else border-slate-300 @endif" :placeholder="paymentMethod === 'offline' ? 'Wajib diisi. Tuliskan detail penyerahan (misal: Diserahkan langsung ke Pak RT di rumah)' : 'Tuliskan catatan tambahan jika ada (opsional)'">{{ old('tagihan_id') == $item->id ? old('note') : '' }}</textarea>
                                        @if(old('tagihan_id') == $item->id)
                                            @error('note')
                                                <p class="text-xs font-semibold text-rose-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>

                                    <button type="submit" class="w-full rounded-2xl bg-green-600 px-5 py-3 text-sm font-bold text-white hover:bg-green-700 transition shadow-sm hover:shadow-md">Bayar / Ajukan Pembayaran</button>
                                </form>
                                @else
                                    <div class="rounded-3xl bg-amber-50 p-4">
                                        <p class="text-sm font-semibold text-amber-800">Pembayaran hanya dapat diajukan oleh penanggung jawab rumah.</p>
                                        <p class="text-sm text-slate-600 mt-2">Silakan koordinasikan pembayaran dengan penanggung jawab iuran rumah.</p>
                                    </div>
                                @endif
                            @elseif($item->status === 'pending_transfer')
                                <div class="rounded-3xl bg-amber-50 p-4">
                                    <p class="text-sm font-semibold text-amber-800">Transaksi transfer sedang menunggu konfirmasi RT.</p>
                                    <p class="text-sm text-slate-600 mt-2">Nomor transaksi: <span class="font-bold">{{ $item->payment_reference }}</span>.</p>
                                </div>
                            @elseif($item->status === 'pending_offline')
                                <div class="rounded-3xl bg-sky-50 p-4">
                                    <p class="text-sm font-semibold text-sky-800">Pembayaran offline menunggu verifikasi.</p>
                                    <p class="text-sm text-slate-600 mt-2">Nomor transaksi: <span class="font-bold">{{ $item->payment_reference }}</span>.</p>
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
