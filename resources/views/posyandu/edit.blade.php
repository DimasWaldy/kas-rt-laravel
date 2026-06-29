@extends('layouts.app')

@section('title', 'Edit Balita Posyandu')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('posyandu.show', $balita) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-rose-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke detail</a>
    <form method="POST" action="{{ route('posyandu.update', $balita) }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        @method('PUT')
        <div class="mb-7">
            <p class="text-sm font-semibold text-rose-600">Perbarui identitas sasaran</p>
            <h1 class="text-2xl font-black text-slate-900">Edit {{ $balita->nama }}</h1>
        </div>
        @include('posyandu.partials.form', ['submitLabel' => 'Simpan Perubahan'])
    </form>
</div>
@endsection
