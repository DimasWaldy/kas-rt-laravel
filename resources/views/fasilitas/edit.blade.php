@extends('layouts.app')

@section('title', 'Edit Fasilitas')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('fasilitas.show', $fasilitas) }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali</a>
        <h1 class="mt-3 text-2xl font-black text-slate-900">Edit Fasilitas</h1>
        <p class="text-sm text-slate-500">Perbarui data fasilitas dan kondisi terbarunya.</p>
    </div>

    <form method="POST" action="{{ route('fasilitas.update', $fasilitas) }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('fasilitas.partials.form', ['submitLabel' => 'Update Fasilitas'])
    </form>
</div>
@endsection
