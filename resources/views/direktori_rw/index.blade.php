@extends('layouts.app')

@section('title', 'Direktori RW')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Database warga sesuai wilayah</p>
            <h1 class="text-2xl font-black text-slate-900">Direktori RW</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau data RT, rumah, warga, alamat, nomor HP, dan status kelengkapan profil.</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-5">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total RT</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ $statistik['total_rt'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Rumah</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ $statistik['total_rumah'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Warga</p>
            <p class="mt-2 text-3xl font-black text-blue-700">{{ $statistik['total_warga'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Kepala Keluarga</p>
            <p class="mt-2 text-3xl font-black text-indigo-700">{{ $statistik['kepala_keluarga'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Profil Belum Lengkap</p>
            <p class="mt-2 text-3xl font-black text-amber-700">{{ $statistik['profil_belum_lengkap'] }}</p>
        </div>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-900">Daftar RT</h2>
                <p class="mt-1 text-sm text-slate-500">Klik salah satu RT untuk melihat pengurus, rumah, dan daftar warga detail.</p>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($rts as $rt)
                <a href="{{ route('direktori-rw.rt.show', $rt) }}" class="rounded-2xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $rt->rw?->name ?? 'RW' }}</p>
                            <h3 class="mt-1 text-xl font-black text-slate-900">{{ $rt->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $rt->description ?: 'Belum ada deskripsi RT.' }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">Rumah</p>
                            <p class="mt-1 font-black text-slate-900">{{ $rt->rumah_count }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">Warga</p>
                            <p class="mt-1 font-black text-slate-900">{{ $rt->warga_count }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <p class="rounded-2xl bg-slate-50 p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">Belum ada data RT.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-900">Daftar Warga</h2>
                <p class="mt-1 text-sm text-slate-500">Cari berdasarkan nama, HP, NIK, nomor KK, rumah, alamat, atau RT.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('direktori-rw.index') }}" class="mb-5 grid gap-3 lg:grid-cols-[1fr_13rem_13rem_auto]">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari warga, rumah, alamat, NIK, No KK..." class="rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <select name="rt_id" class="rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Semua RT</option>
                @foreach($rts as $rt)
                    <option value="{{ $rt->id }}" @selected($selectedRtId === $rt->id)>{{ $rt->name }}</option>
                @endforeach
            </select>
            <select name="status_profil" class="rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Semua Profil</option>
                <option value="belum_lengkap" @selected($statusProfil === 'belum_lengkap')>Belum Lengkap</option>
            </select>
            <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Filter
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Warga</th>
                        <th class="px-4 py-3">RT</th>
                        <th class="px-4 py-3">Rumah & Alamat</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3">KK</th>
                        <th class="px-4 py-3">Status</th>
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
                                <a href="{{ route('direktori-rw.rt.show', $warga->rt) }}" class="font-bold text-emerald-700 hover:text-emerald-900">{{ $warga->rt?->name ?? '-' }}</a>
                                <p class="text-xs text-slate-500">{{ $warga->rt?->rw?->name ?? '' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $warga->rumah?->kode_rumah ?? '-' }}</p>
                                <p class="max-w-xs text-xs text-slate-500">{{ $warga->rumah?->alamat ?? 'Alamat belum diisi' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($warga->phone)
                                    <p class="font-semibold text-slate-800">{{ $warga->phone }}</p>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $warga->phone) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-emerald-700">
                                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Belum ada nomor HP</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $warga->warga?->kartuKeluarga?->no_kk ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $warga->warga?->status_dalam_kk ? str($warga->warga->status_dalam_kk)->replace('_', ' ')->headline() : 'Status KK belum diisi' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $warga->profile_status === 'Lengkap' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $warga->profile_status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Tidak ada warga sesuai filter.</td></tr>
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
