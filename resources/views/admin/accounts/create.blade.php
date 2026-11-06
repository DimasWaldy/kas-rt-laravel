@extends('layouts.app')

@section('title', 'Manajemen Akun')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-800 sm:p-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Administrasi Sistem</p>
        <h1 class="mt-2 text-2xl font-black text-slate-900 dark:text-slate-100">Manajemen Akun</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Buat akun pengurus sesuai tanggung jawab dan cakupan wilayah. Akses ini tersedia bagi admin dan super admin.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border-2 border-emerald-400 bg-emerald-50 p-5 dark:border-emerald-600 dark:bg-emerald-950/40">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Pengurus</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Buat akun pengurus</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Untuk admin, ketua, bendahara, sekretaris, atau petugas. Lengkapi formulir di bawah.</p>
        </div>
        <a href="{{ route('admin.warga.create') }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-emerald-400 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-emerald-500 dark:hover:bg-slate-700">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Warga</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Buat akun warga <span aria-hidden="true">→</span></h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Gunakan formulir warga untuk melengkapi RT, rumah, dan data keluarga.</p>
        </a>
    </div>

    @if(session('success'))
        <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
    @endif
    @if(session('warning'))
        <p class="rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-800">{{ session('warning') }}</p>
    @endif
    @if($errors->any())
        <div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.accounts.store') }}" method="POST" class="space-y-6 rounded-3xl border border-emerald-100 bg-white p-6 shadow-xs dark:border-slate-700 dark:bg-slate-800 sm:p-8" x-data="{ role: @js(old('role', '')) }">
        @csrf
        <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Identitas akun</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Isi password awal; sampaikan kepada pemilik akun melalui saluran aman, bukan teks biasa.</p>
        </div>
        <div>
            <label for="account-name" class="block text-sm font-bold text-slate-700">Nama</label>
            <input id="account-name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
        </div>
        <div>
            <label for="account-email" class="block text-sm font-bold text-slate-700">Email</label>
            <input id="account-email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
        </div>
        <div>
            <label for="account-phone" class="block text-sm font-bold text-slate-700">Nomor HP (opsional)</label>
            <input id="account-phone" name="phone" type="tel" inputmode="numeric" pattern="[0-9]{10,13}" value="{{ old('phone') }}" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
        </div>
        <div>
            <label for="account-password" class="block text-sm font-bold text-slate-700 dark:text-slate-200">Password awal</label>
            <input id="account-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
        </div>
        <div>
            <label for="account-password-confirmation" class="block text-sm font-bold text-slate-700 dark:text-slate-200">Konfirmasi password awal</label>
            <input id="account-password-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
        </div>
        <div class="border-t border-slate-200 pt-6 dark:border-slate-700">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Peran dan cakupan wilayah</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pilih kewenangan secukupnya sesuai tugas pemilik akun.</p>
        </div>
        <div>
            <label for="account-role" class="block text-sm font-bold text-slate-700 dark:text-slate-200">Role</label>
            <select id="account-role" name="role" x-model="role" required class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                <option value="">Pilih role</option>
                <optgroup label="Operator global">
                    @foreach($roles->whereIn('name', ['admin']) as $availableRole)
                        <option value="{{ $availableRole->name }}" {{ old('role') === $availableRole->name ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $availableRole->name)) }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Pengurus RW dan petugas RW">
                    @foreach($roles->whereIn('name', ['ketua_rw', 'sekretaris_rw', 'bendahara_rw', 'petugas_bank_sampah', 'petugas_posyandu']) as $availableRole)
                        <option value="{{ $availableRole->name }}" {{ old('role') === $availableRole->name ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $availableRole->name)) }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Pengurus RT">
                    @foreach($roles->whereIn('name', ['ketua_rt', 'bendahara_rt', 'sekretaris_rt']) as $availableRole)
                        <option value="{{ $availableRole->name }}" {{ old('role') === $availableRole->name ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $availableRole->name)) }}</option>
                    @endforeach
                </optgroup>
            </select>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Admin memiliki akses global dan dapat membuat akun selain super admin. Super admin tidak tersedia sebagai pilihan.</p>
        </div>
        <div x-show="['ketua_rt', 'bendahara_rt', 'sekretaris_rt'].includes(role)" x-cloak>
            <label for="account-rt" class="block text-sm font-bold text-slate-700 dark:text-slate-200">RT penempatan *</label>
            <select id="account-rt" name="rt_id" :required="['ketua_rt', 'bendahara_rt', 'sekretaris_rt'].includes(role)" :disabled="!['ketua_rt', 'bendahara_rt', 'sekretaris_rt'].includes(role)" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                <option value="">Pilih RT aktif</option>
                @foreach($rts as $rt)
                    <option value="{{ $rt->id }}" {{ old('rt_id') == $rt->id ? 'selected' : '' }}>{{ $rt->rw->name }} / {{ $rt->name }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="['ketua_rw', 'sekretaris_rw', 'bendahara_rw', 'petugas_bank_sampah', 'petugas_posyandu'].includes(role)" x-cloak>
            <label for="account-rw" class="block text-sm font-bold text-slate-700 dark:text-slate-200">RW penempatan *</label>
            <select id="account-rw" name="rw_id" :required="['ketua_rw', 'sekretaris_rw', 'bendahara_rw', 'petugas_bank_sampah', 'petugas_posyandu'].includes(role)" :disabled="!['ketua_rw', 'sekretaris_rw', 'bendahara_rw', 'petugas_bank_sampah', 'petugas_posyandu'].includes(role)" class="mt-2 w-full rounded-2xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                <option value="">Pilih RW aktif</option>
                @foreach($rws as $rw)
                    <option value="{{ $rw->id }}" {{ old('rw_id') == $rw->id ? 'selected' : '' }}>{{ $rw->name }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="role === 'admin'" x-cloak class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
            Admin mendapat akses lintas wilayah. Tidak perlu memilih RT. Gunakan role pengurus RT/RW jika tugas terbatas pada wilayah tertentu.
        </div>
        <div class="border-t border-slate-200 pt-6 dark:border-slate-700">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Aktivasi akun</h2>
            <ol class="mt-3 list-inside list-decimal space-y-1 text-sm text-slate-600 dark:text-slate-300">
                <li>Akun dibuat sesuai role dan wilayah yang dipilih.</li>
                <li>Password awal ditentukan pembuat akun dan tidak dikirim lewat email.</li>
                <li>Pemilik akun mengganti password sendiri melalui Edit Profil setelah login.</li>
            </ol>
        </div>
        <button type="submit" class="w-full rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800 sm:w-auto">Buat akun pengurus</button>
    </form>
</div>
@endsection
