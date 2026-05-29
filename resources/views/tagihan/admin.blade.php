@extends('layouts.app')

@section('title', 'Verifikasi Tagihan')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-3xl shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Manajemen Tagihan</h2>
                <p class="text-slate-600 mt-2">Lihat, buat, verifikasi pembayaran, dan kelola tagihan iuran per rumah.</p>
            </div>
            <a href="{{ route('tagihan.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                + Buat Tagihan
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm p-6 border border-slate-200 overflow-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Rumah / Warga</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tagihan</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Bulan</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Total</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Due Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Metode</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Pembayaran</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($tagihans as $tagihan)
                    <tr>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">
                            <div class="font-bold text-slate-900">{{ $tagihan->rumah?->kode_rumah ?? 'Rumah belum diatur' }}</div>
                            <div class="text-xs text-slate-500">{{ $tagihan->user->name }}</div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">
                            <div class="font-bold text-slate-900">{{ $tagihan->display_title }}</div>
                            <div class="text-xs text-slate-500">{{ $tagihan->billing_group }}</div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">{{ $tagihan->bulan }}/{{ $tagihan->tahun }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700">Rp {{ number_format($tagihan->total, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tagihan->status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : ($tagihan->status === 'pending_transfer' ? 'bg-amber-100 text-amber-700' : ($tagihan->status === 'pending_offline' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700')) }}">
                                {{ $tagihan->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tagihan->due_status_class }}">
                                {{ $tagihan->due_status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700 capitalize">{{ str_replace('_', ' ', $tagihan->payment_method) }}</td>
                        <td class="px-4 py-4 min-w-[14rem] text-slate-700">
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">{{ str_replace('_', ' ', $tagihan->payment_method) }}</span>
                                    @if($tagihan->bukti)
                                        <a href="{{ Storage::url($tagihan->bukti) }}" target="_blank" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">
                                            Buka Bukti
                                        </a>
                                    @else
                                        <span class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-slate-500">Tanpa bukti</span>
                                    @endif
                                </div>
                                @if($tagihan->note)
                                    <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-600">{{ $tagihan->note }}</p>
                                @endif
                                @if($tagihan->paid_at)
                                    <p class="mt-2 text-xs font-semibold text-emerald-700">Dibayar: {{ $tagihan->paid_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-2">
                                <form action="{{ route('tagihan.confirm') }}" method="POST" class="flex gap-2">
                                    @csrf
                                    <input type="hidden" name="tagihan_id" value="{{ $tagihan->id }}">
                                    <select name="status" class="rounded-lg border border-slate-300 px-2 py-1 text-xs flex-1">
                                        <option value="belum_bayar" {{ $tagihan->status !== 'lunas' ? 'selected' : '' }}>Belum Bayar</option>
                                        <option value="lunas" {{ $tagihan->status === 'lunas' ? 'selected' : '' }}>Lunas</option>
                                    </select>
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700 transition whitespace-nowrap">Simpan</button>
                                </form>
                                <div class="flex gap-2">
                                    <a href="{{ route('tagihan.edit', $tagihan) }}" class="flex-1 text-center rounded-lg bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700 transition">Edit</a>
                                    <button type="button" onclick="confirmDelete('{{ $tagihan->id }}', '{{ addslashes($tagihan->user->name) }} {{ $tagihan->bulan }}/{{ $tagihan->tahun }}')" class="flex-1 rounded-lg bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700 transition">Hapus</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada tagihan yang tersedia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-lg max-w-sm w-full p-6 space-y-4">
        <h2 class="text-xl font-bold text-slate-800">Hapus Tagihan?</h2>
        <p id="deleteMessage" class="text-slate-600"></p>
        <div class="flex gap-3 justify-end">
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
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
@endsection
