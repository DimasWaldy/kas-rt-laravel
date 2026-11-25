@extends('layouts.app')

@section('title', 'Buat Pengumuman')

@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('pengumuman.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-400">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Papan Pengumuman
    </a>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:p-8">
        <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Informasi Lingkungan</p>
        <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100">Buat Pengumuman Baru</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Terbitkan pengumuman penting untuk seluruh RW atau khusus warga RT Anda.</p>

        <form method="POST" action="{{ route('pengumuman.store') }}" class="mt-7 space-y-5">
            @csrf
            @include('pengumuman.partials.form', ['pengumuman' => null])

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
                <a href="{{ route('pengumuman.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</a>
                <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Simpan Pengumuman</button>
            </div>
        </form>
    </div>
</div>
@endsection
