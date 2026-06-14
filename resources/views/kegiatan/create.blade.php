@extends('layouts.app')

@section('title', 'Buat Kegiatan RW')

@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('kegiatan.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali</a>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-semibold text-emerald-700">Agenda baru</p>
        <h1 class="text-2xl font-black text-slate-900">Buat Kegiatan RW</h1>
        <p class="mt-2 text-sm text-slate-500">Informasi kegiatan akan terlihat oleh seluruh warga lintas RT dalam RW ini.</p>

        <form method="POST" action="{{ route('kegiatan.store') }}" enctype="multipart/form-data" class="mt-7 space-y-5">
            @csrf
            @include('kegiatan.partials.form', ['kegiatan' => null])

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('kegiatan.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
                <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Simpan Kegiatan</button>
            </div>
        </form>
    </div>
</div>
@endsection
