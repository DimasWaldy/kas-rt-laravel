@extends('layouts.app')

@section('title', 'Detail RT')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('direktori-rw.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Direktori RW
            </a>
            <p class="mt-4 text-sm font-semibold text-emerald-700">{{ $rt->rw?->name ?? 'RW' }}</p>
            <h1 class="text-2xl font-black text-slate-900">{{ $rt->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $rt->description ?: 'Belum ada deskripsi RT.' }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rumah</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ $statistik['total_rumah'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Warga</p>
            <p class="mt-2 text-3xl font-black text-blue-700">{{ $statistik['total_warga'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Kepala Keluarga</p>
            <p class="mt-2 text-3xl font-black text-indigo-700">{{ $statistik['kepala_keluarga'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Profil Lengkap</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ $statistik['profil_lengkap'] }}</p>
        </div>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-black text-slate-900">Pengurus RT</h2>
        <div class="grid gap-3 md:grid-cols-3">
            @forelse($pengurus as $pengurusRt)
                <article class="rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ str($pengurusRt->role?->name)->replace('_', ' ')->headline() }}</p>
                    <h3 class="mt-1 font-black text-slate-900">{{ $pengurusRt->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $pengurusRt->email }}</p>
                    @if($pengurusRt->phone)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $pengurusRt->phone) }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100">
                            <i class="fa-brands fa-whatsapp"></i> {{ $pengurusRt->phone }}
                        </a>
                    @else
                        <p class="mt-3 text-xs text-slate-400">Nomor HP belum diisi.</p>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl bg-slate-50 p-8 text-center text-sm text-slate-500 md:col-span-3">Belum ada pengurus RT terdaftar.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-black text-slate-900">Daftar Rumah</h2>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($rumahs as $rumah)
                <article class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-900">{{ $rumah->kode_rumah }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $rumah->alamat ?: 'Alamat belum diisi' }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $rumah->warga_count }} warga</span>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl bg-slate-50 p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">Belum ada data rumah.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-black text-slate-900">Daftar Warga {{ $rt->name }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Warga</th>
                        <th class="px-4 py-3">Rumah</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3">KK</th>
                        <th class="px-4 py-3">Profil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($wargas as $warga)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-black text-slate-900">{{ $warga->name }}</p>
                                <p class="text-xs text-slate-500">{{ $warga->email }}</p>
                                <p class="mt-1 text-xs text-slate-400">NIK: {{ $warga->warga?->nik ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $warga->rumah?->kode_rumah ?? '-' }}</p>
                                <p class="max-w-xs text-xs text-slate-500">{{ $warga->rumah?->alamat ?? 'Alamat belum diisi' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $warga->phone ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $warga->warga?->kartuKeluarga?->no_kk ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $warga->warga?->status_dalam_kk ? str($warga->warga->status_dalam_kk)->replace('_', ' ')->headline() : '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $warga->profile_status === 'Lengkap' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $warga->profile_status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada warga di RT ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $wargas->links() }}
        </div>
    </section>
</div>
@endsection
