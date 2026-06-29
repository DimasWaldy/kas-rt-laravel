@extends('layouts.app')

@section('title', 'Data Warga')

@section('content')
<div class="space-y-6" x-data="{ showWargaForm: {{ old('_form') === 'admin_warga' ? 'true' : 'false' }} }">
    <div class="rounded-3xl bg-white shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Data Warga</h1>
                <p class="mt-2 text-sm text-slate-500">Kelola profil warga, rumah/unit hunian, dan penanggung jawab iuran.</p>
            </div>
            <button type="button" x-on:click="showWargaForm = true" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">+ Tambah Warga</button>
        </div>

        <form method="GET" action="{{ route('admin.warga.index') }}" class="mb-6 grid gap-4 lg:grid-cols-[1.5fr_auto] xl:grid-cols-[1.5fr_auto_auto] items-end">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600">Cari Warga</label>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Nama, No. KK, kode rumah, alamat, telepon" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-200 focus:ring-2" />
                </div>

                <div class="sm:col-span-1 xl:col-span-2">
                    <label class="block text-sm font-medium text-slate-600">Filter Kepala Keluarga</label>
                    <select name="filter_head" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-200 focus:ring-2">
                        <option value="" {{ request('filter_head') === null ? 'selected' : '' }}>Semua</option>
                        <option value="kepala" {{ request('filter_head') === 'kepala' ? 'selected' : '' }}>Kepala Keluarga</option>
                        <option value="warga" {{ request('filter_head') === 'warga' ? 'selected' : '' }}>Warga Biasa</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">Cari</button>
                <a href="{{ route('admin.warga.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] tracking-[0.2em]">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Rumah</th>
                        <th class="px-4 py-3 text-left">No. KK</th>
                        <th class="px-4 py-3 text-left">Telepon</th>
                        <th class="px-4 py-3 text-left">RT/RW</th>
                        <th class="px-4 py-3 text-left">Kepala KK</th>
                        <th class="px-4 py-3 text-left">PJ Iuran</th>
                        <th class="px-4 py-3 text-left">Status Profil</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-4 text-slate-700">{{ $user->name }}</td>
                            <td class="px-4 py-4 text-slate-500">{{ $user->email }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $user->rumah?->kode_rumah ?? '-' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $user->warga?->kartuKeluarga?->no_kk ?? '-' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $user->phone ?? '-' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $user->rt?->name ?? '-' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $user->warga?->status_dalam_kk === 'kepala_keluarga' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $user->warga?->status_dalam_kk === 'kepala_keluarga' ? 'Ya' : 'Tidak' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $user->is_penanggung_jawab_rumah ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $user->is_penanggung_jawab_rumah ? 'Ya' : 'Tidak' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $user->profile_status === 'Lengkap' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $user->profile_status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.warga.edit', $user) }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700 transition">
                                        Edit
                                    </a>
                                    <button type="button" onclick="confirmDelete('{{ $user->id }}', '{{ addslashes($user->name) }}')" class="inline-flex items-center px-3 py-2 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700 transition">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-slate-500">Tidak ada warga terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $users->links() }}
        </div>
    </div>

    <div x-cloak x-show="showWargaForm" x-transition.opacity class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" x-on:click.self="showWargaForm = false">
        <section x-transition class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between bg-emerald-800 p-6 text-white">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-100">Data Warga</p>
                    <h2 class="mt-2 text-xl font-black">Tambah Warga Baru</h2>
                    <p class="mt-1 text-sm text-emerald-50">Tambahkan warga, rumah, dan penanggung jawab iuran dari halaman ini.</p>
                </div>
                <button type="button" x-on:click="showWargaForm = false" class="rounded-xl p-2 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Tutup form">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.warga.store') }}" method="POST" class="space-y-6 p-6">
                @csrf
                <input type="hidden" name="_form" value="admin_warga">

                @if(old('_form') === 'admin_warga' && $errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        Periksa kembali data warga yang diisi.
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div>
                        <label class="block text-sm font-bold text-slate-700">Nama</label>
                        <input type="text" name="name" value="{{ old('_form') === 'admin_warga' ? old('name') : '' }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'admin_warga') @error('name') border-red-500 @enderror @endif" required>
                        @if(old('_form') === 'admin_warga') @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror @endif
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Email</label>
                        <input type="email" name="email" value="{{ old('_form') === 'admin_warga' ? old('email') : '' }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'admin_warga') @error('email') border-red-500 @enderror @endif" required>
                        @if(old('_form') === 'admin_warga') @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror @endif
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Password</label>
                        <input type="password" name="password" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'admin_warga') @error('password') border-red-500 @enderror @endif" required>
                        @if(old('_form') === 'admin_warga') @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror @endif
                    </div>

                    <div class="lg:col-span-2 rounded-3xl border border-emerald-100 bg-emerald-50/60 p-5">
                        <h3 class="text-sm font-black text-emerald-900">Data Rumah / Unit Hunian</h3>
                        <p class="mt-1 text-xs text-emerald-700">Tagihan iuran dibuat per rumah. Satu rumah bisa punya lebih dari satu KK.</p>

                        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div>
                                <label class="block text-sm font-bold text-slate-700">Pilih Rumah yang Sudah Ada</label>
                                <select name="rumah_id" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                                    <option value="">Buat rumah baru / belum ditentukan</option>
                                    @foreach($rumahs as $rumah)
                                        <option value="{{ $rumah->id }}" {{ old('_form') === 'admin_warga' && old('rumah_id') == $rumah->id ? 'selected' : '' }}>{{ $rumah->label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700">Kode Rumah Baru</label>
                                <input type="text" name="rumah_kode" value="{{ old('_form') === 'admin_warga' ? old('rumah_kode') : '' }}" placeholder="Contoh: A-01" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-bold text-slate-700">Alamat Rumah Baru</label>
                                <input type="text" name="rumah_alamat" value="{{ old('_form') === 'admin_warga' ? old('rumah_alamat') : '' }}" placeholder="Contoh: Jl. Melati No. 1" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">No. KK</label>
                        <input type="text" name="no_kk" value="{{ old('_form') === 'admin_warga' ? old('no_kk') : '' }}" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" autocomplete="off" placeholder="16 digit angka" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'admin_warga') @error('no_kk') border-red-500 @enderror @endif">
                        @if(old('_form') === 'admin_warga') @error('no_kk') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror @endif
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700">Telepon</label>
                        <input type="text" name="phone" value="{{ old('_form') === 'admin_warga' ? old('phone') : '' }}" inputmode="numeric" pattern="[0-9]{10,13}" maxlength="13" autocomplete="tel" placeholder="10-13 digit angka" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:border-emerald-500 focus:ring-emerald-200 @if(old('_form') === 'admin_warga') @error('phone') border-red-500 @enderror @endif">
                        @if(old('_form') === 'admin_warga') @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror @endif
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="is_kepala_keluarga" value="1" {{ old('_form') === 'admin_warga' && old('is_kepala_keluarga') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Kepala Keluarga
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="is_penanggung_jawab_rumah" value="1" {{ old('_form') === 'admin_warga' && old('is_penanggung_jawab_rumah') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Penanggung Jawab Iuran Rumah
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                    <button type="button" x-on:click="showWargaForm = false" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Tambah Warga</button>
                </div>
            </form>
        </section>
    </div>
</div>

<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-lg max-w-sm w-full p-6 space-y-4">
        <h2 class="text-xl font-bold text-slate-800">Hapus Warga?</h2>
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
function confirmDelete(userId, userName) {
    const modal = document.getElementById('deleteModal');
    const message = document.getElementById('deleteMessage');
    const form = document.getElementById('deleteForm');
    
    message.textContent = `Anda akan menghapus data warga "${userName}". Aksi ini tidak dapat dibatalkan.`;
    form.action = `/admin/warga/${userId}`;
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
