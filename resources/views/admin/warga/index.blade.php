@extends('layouts.app')

@section('title', 'Data Warga')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-white shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Data Warga</h1>
                <p class="mt-2 text-sm text-slate-500">Kelola profil warga dan perbarui informasi keluarga.</p>
            </div>
            <a href="{{ route('admin.warga.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">+ Tambah Warga</a>
        </div>

        <form method="GET" action="{{ route('admin.warga.index') }}" class="mb-6 grid gap-4 lg:grid-cols-[1.5fr_auto] xl:grid-cols-[1.5fr_auto_auto] items-end">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600">Cari Warga</label>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Nama, No. KK, RT, RW, Telepon" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-200 focus:ring-2" />
                </div>

                <div class="sm:col-span-1 xl:col-span-2">
                    <label class="block text-sm font-medium text-slate-600">Filter Kepala Keluarga</label>
                    <select name="filter_head" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-200 focus:ring-2">
                        <option value="" {{ request('filter_head') === null ? 'selected' : '' }}>Semua</option>
                        <option value="kepala" {{ request('filter_head') === 'kepala' ? 'selected' : '' }}>Kepala Keluarga</option>
                        <option value="warga" {{ request('filter_head') === 'warga' ? 'selected' : '' }}>Warga Biasa</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition">Cari</button>
                <a href="{{ route('admin.warga.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] tracking-[0.2em]">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">No. KK</th>
                        <th class="px-4 py-3 text-left">Telepon</th>
                        <th class="px-4 py-3 text-left">RT/RW</th>
                        <th class="px-4 py-3 text-left">Jumlah Keluarga</th>
                        <th class="px-4 py-3 text-left">Kepala KK</th>
                        <th class="px-4 py-3 text-left">Status Profil</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-4 text-slate-700">{{ $user->name }}</td>
                            <td class="px-4 py-4 text-slate-500">{{ $user->email }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $user->no_kk ?? '-' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $user->phone ?? '-' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ trim(($user->rt ?? '-') . '/' . ($user->rw ?? '-'), '/') }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $user->jumlah_anggota_keluarga ?? '-' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $user->is_kepala_keluarga ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $user->is_kepala_keluarga ? 'Ya' : 'Tidak' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $user->profile_status === 'Lengkap' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $user->profile_status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.warga.edit', $user) }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700 transition">
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
                            <td colspan="9" class="px-4 py-6 text-center text-slate-500">Tidak ada warga terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $users->links() }}
        </div>
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
