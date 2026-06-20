@extends('layouts.app')

@section('title', 'Daftarkan UMKM')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('umkm.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"><i class="fa-solid fa-arrow-left"></i> Kembali ke direktori</a>

    <form method="POST" action="{{ route('umkm.store') }}" enctype="multipart/form-data" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <div class="mb-7">
            <p class="text-sm font-semibold text-emerald-700">Direktori usaha warga</p>
            <h1 class="text-2xl font-black text-slate-900">Daftarkan Usaha Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Data akan diperiksa pengurus sebelum tampil untuk warga satu RW.</p>
        </div>

        @include('umkm.partials.form', ['submitLabel' => 'Kirim Pendaftaran'])
    </form>
</div>
@endsection
