@extends('layouts.app')

@section('content')

<div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded-xl shadow">

    <h1 class="text-2xl font-bold mb-6 text-center">💸 Tambah Kas Masuk</h1>

    <form action="/kas-masuk/store" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block mb-1 font-semibold">Keterangan</label>
            <input type="text" name="keterangan" 
                class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200"
                placeholder="Contoh: Iuran bulanan">
        </div>

        <div>
            <label class="block mb-1 font-semibold">Jumlah</label>
            <input type="number" name="jumlah" 
                class="w-full border rounded-lg p-2 focus:ring focus:ring-green-200"
                placeholder="Masukkan jumlah uang">
        </div>

        <div>
            <label class="block mb-1 font-semibold">Tanggal</label>
            <input type="date" name="tanggal" 
                class="w-full border rounded-lg p-2 focus:ring focus:ring-purple-200">
        </div>

        <button type="submit"
            class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
            Simpan 💾
        </button>

    </form>

</div>

@endsection