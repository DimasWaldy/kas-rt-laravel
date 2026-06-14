@extends('layouts.app')

@section('title', 'Edit Kegiatan RW')

@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('kegiatan.show', $kegiatan) }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke detail</a>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-semibold text-emerald-700">Perbarui agenda</p>
        <h1 class="text-2xl font-black text-slate-900">Edit Kegiatan RW</h1>

        @if($kegiatan->foto)
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                <img src="{{ route('kegiatan.foto', $kegiatan) }}" alt="Foto {{ $kegiatan->nama }}" class="h-56 w-full object-cover">
                <p class="p-3 text-xs text-slate-500">Unggah foto baru pada form untuk mengganti foto ini.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('kegiatan.update', $kegiatan) }}" enctype="multipart/form-data" class="mt-7 space-y-5">
            @csrf
            @method('PUT')
            @include('kegiatan.partials.form', ['kegiatan' => $kegiatan])

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('kegiatan.show', $kegiatan) }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
                <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
