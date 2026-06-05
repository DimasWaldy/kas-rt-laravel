@extends('layouts.app')

@section('title', 'Data Rumah')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Data Rumah / Unit Hunian</h1>
                <p class="mt-2 text-sm text-slate-500">Pantau rumah, penghuni, penanggung jawab iuran, dan status tagihan per rumah.</p>
            </div>

            <form method="GET" action="{{ route('admin.rumah.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari kode, alamat, PJ" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                <button class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Cari</button>
                <a href="{{ route('admin.rumah.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Total Rumah</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Rumah Aktif</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ $stats['aktif'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Tanpa PJ</p>
            <p class="mt-2 text-3xl font-black text-amber-800">{{ $stats['tanpa_pj'] }}</p>
        </div>
        <div class="rounded-2xl border border-lime-100 bg-lime-50 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-lime-700">Belum Lunas</p>
            <p class="mt-2 text-3xl font-black text-lime-800">{{ $stats['belum_lunas'] }}</p>
        </div>
    </div>

    <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-emerald-100 text-sm">
                <thead class="bg-emerald-50 text-left text-[11px] uppercase tracking-[0.2em] text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Alamat</th>
                        <th class="px-4 py-3">RT/RW</th>
                        <th class="px-4 py-3">PJ Iuran</th>
                        <th class="px-4 py-3">Warga</th>
                        <th class="px-4 py-3">KK</th>
                        <th class="px-4 py-3">Status Rumah</th>
                        <th class="px-4 py-3">Status Tagihan</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-100">
                    @forelse($rumahs as $rumah)
                        @php $tagihan = $tagihanByRumah->get($rumah->id); @endphp
                        <tr class="hover:bg-emerald-50/60">
                            <td class="px-4 py-4 font-black text-emerald-700">{{ $rumah->kode_rumah }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $rumah->alamat ?? '-' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ ($rumah->rt ?? '-') . '/' . ($rumah->rw ?? '-') }}</td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">{{ $rumah->penanggungJawab?->name ?? '-' }}</div>
                                @if($rumah->penanggungJawab)
                                    <div class="text-xs text-slate-500">{{ $rumah->penanggungJawab->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 font-bold text-slate-800">{{ $rumah->warga_count }}</td>
                            <td class="px-4 py-4 font-bold text-slate-800">{{ $rumah->kepala_keluarga_count }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $rumah->status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : ($rumah->status === 'kosong' ? 'bg-slate-100 text-slate-600' : 'bg-rose-100 text-rose-700') }}">
                                    {{ ucfirst($rumah->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                @if($tagihan)
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $tagihan->status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : ($tagihan->status === 'belum_bayar' ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $tagihan->status_label }}
                                    </span>
                                    <p class="mt-1 text-xs text-slate-500">{{ $tagihan->count }} nota | Rp {{ number_format($tagihan->total, 0, ',', '.') }}</p>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.rumah.show', $rumah) }}" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">Detail</a>
                                    <a href="{{ route('admin.rumah.edit', $rumah) }}" class="rounded-xl border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-50">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">Belum ada data rumah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $rumahs->links() }}
        </div>
    </div>
</div>
@endsection
