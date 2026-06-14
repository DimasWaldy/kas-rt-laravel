@extends('layouts.app')

@section('title', 'Ajukan Surat')

@section('content')
<div class="mx-auto max-w-xl rounded-2xl border border-blue-200 bg-blue-50 p-6 text-center">
    <p class="font-semibold text-blue-900">Form pengajuan sekarang tersedia melalui modal di halaman Surat Menyurat.</p>
    <a href="{{ route('surat.index') }}" class="mt-4 inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white">Kembali ke Surat Menyurat</a>
</div>
@endsection
