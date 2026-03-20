@extends('layouts.app')

@section('content')

<div class="flex justify-center mt-10">
    <div class="w-full max-w-md bg-white p-6 rounded-xl shadow">

        <h1 class="text-xl font-bold mb-6 text-center text-red-500">
            💸 Tambah Data Kas Keluar
        </h1>

        <form action="/kas-keluar/store" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium">Keterangan</label>
                <input type="text" name="keterangan"
                    class="w-full border rounded-lg p-2 focus:ring focus:ring-red-200">
            </div>

            <div>
                <label class="block text-sm font-medium">Jumlah</label>
                <input type="number" name="jumlah"
                    class="w-full border rounded-lg p-2 focus:ring focus:ring-red-200">
            </div>

            <div>
                <label class="block text-sm font-medium">Tanggal</label>
                <input type="date" name="tanggal"
                    class="w-full border rounded-lg p-2 focus:ring focus:ring-red-200">
            </div>

            <div>
                <label class="block text-sm font-medium">Bukti (Foto)</label>
                <input type="file" name="bukti"
                    class="w-full text-sm">
            </div>

            <button type="submit"
                class="w-full bg-red-500 text-white py-2 rounded-lg hover:bg-red-600">
                Simpan 💾
            </button>

        </form>

    </div>
</div>

@endsection