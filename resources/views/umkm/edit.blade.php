@extends('layouts.app')

@section('title', 'Edit UMKM')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('umkm.show', $umkm) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke detail usaha</a>

    <form method="POST" action="{{ route('umkm.update', $umkm) }}" enctype="multipart/form-data" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        @method('PUT')
        <div class="mb-7">
            <p class="text-sm font-semibold text-emerald-700">Perbarui profil usaha</p>
            <h1 class="text-2xl font-black text-slate-900">Edit {{ $umkm->nama_usaha }}</h1>
            @if($umkm->status === 'rejected' && ! auth()->user()->hasPermission('manage-umkm'))
                <p class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Setelah diperbaiki, usaha akan otomatis diajukan ulang untuk persetujuan.</p>
            @endif
        </div>

        @include('umkm.partials.form', ['submitLabel' => 'Simpan Perubahan'])
    </form>
</div>
@endsection
