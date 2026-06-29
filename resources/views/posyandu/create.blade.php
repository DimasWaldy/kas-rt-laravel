@extends('layouts.app')

@section('title', 'Tambah Balita Posyandu')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('posyandu.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-rose-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Posyandu</a>
    <form method="POST" action="{{ route('posyandu.store') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <div class="mb-7">
            <p class="text-sm font-semibold text-rose-600">Registrasi sasaran Posyandu</p>
            <h1 class="text-2xl font-black text-slate-900">Tambah Data Balita</h1>
            <p class="mt-1 text-sm text-slate-500">Hubungkan balita dengan RT dan akun orang tua agar KMS dapat dilihat keluarga.</p>
        </div>
        @include('posyandu.partials.form', ['submitLabel' => 'Simpan Balita'])
    </form>
</div>
@endsection
