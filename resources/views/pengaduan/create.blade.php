@extends('layouts.app')

@section('title', 'Tulis Pengaduan Baru')

@section('content')
<div class="font-sans max-w-4xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('pengaduan.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 transition">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Kembali ke Daftar Pengaduan
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Formulir --}}
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-800 mb-2">Formulir Pengaduan & Aspirasi</h2>
            <p class="text-xs text-slate-400 mb-6">Aduan Anda akan langsung dikirim kepada pengurus RT dan terdistribusi secara transparan.</p>

            <form action="{{ route('pengaduan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Judul --}}
                <div class="mb-5">
                    <label for="judul" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Judul Pengaduan <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" id="judul" value="{{ old('judul') }}" required
                           class="w-full px-4 py-3 rounded-xl border @error('judul') border-red-400 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition-all"
                           placeholder="Tulis judul yang singkat & jelas (contoh: Lampu Jalan Blok C Padam)">
                    @error('judul')
                        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kategori --}}
                <div class="mb-5">
                    <label for="kategori" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Kategori <span class="text-red-500">*</span></label>
                    <select name="kategori" id="kategori" required
                            class="w-full px-4 py-3 rounded-xl border @error('kategori') border-red-400 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition-all bg-white">
                        <option value="" disabled selected>-- Pilih Kategori --</option>
                        <option value="Keamanan" {{ old('kategori') === 'Keamanan' ? 'selected' : '' }}>Keamanan & Ketertiban</option>
                        <option value="Kebersihan" {{ old('kategori') === 'Kebersihan' ? 'selected' : '' }}>Kebersihan Lingkungan / Sampah</option>
                        <option value="Infrastruktur" {{ old('kategori') === 'Infrastruktur' ? 'selected' : '' }}>Infrastruktur / Jalan / Saluran Air</option>
                        <option value="Sosial" {{ old('kategori') === 'Sosial' ? 'selected' : '' }}>Masalah Sosial & Warga</option>
                        <option value="Lainnya" {{ old('kategori') === 'Lainnya' ? 'selected' : '' }}>Lainnya / Aspirasi Umum</option>
                    </select>
                    @error('kategori')
                        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Deskripsi --}}
                <div class="mb-5">
                    <label for="deskripsi" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Isi Pengaduan / Kronologi <span class="text-red-500">*</span></label>
                    <textarea name="deskripsi" id="deskripsi" rows="5" required
                              class="w-full px-4 py-3 rounded-xl border @error('deskripsi') border-red-400 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition-all leading-relaxed"
                              placeholder="Tuliskan secara lengkap deskripsi masalah, lokasi kejadian, dan detail penunjang lainnya agar pengurus RT dapat segera merespon..."></textarea>
                    @error('deskripsi')
                        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Foto Bukti --}}
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Unggah Foto Bukti (Opsional)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-2xl hover:border-blue-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <i class="fa-regular fa-image text-slate-400 text-3xl mb-3 block"></i>
                            <div class="flex text-sm text-slate-600 justify-center">
                                <label for="foto" class="relative cursor-pointer bg-white rounded-md font-bold text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Pilih berkas foto</span>
                                    <input id="foto" name="foto" type="file" class="sr-only" accept="image/*" onchange="previewFile(this)">
                                </label>
                            </div>
                            <p class="text-xs text-slate-400">PNG, JPG, JPEG maks. 2MB</p>
                            <p id="file-chosen" class="text-xs font-bold text-emerald-600 mt-2 hidden"></p>
                        </div>
                    </div>
                    @error('foto')
                        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('pengaduan.index') }}" 
                       class="px-5 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-50 transition">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm px-6 py-3 rounded-xl shadow-md transition transform hover:-translate-y-0.5">
                        Kirim Laporan
                    </button>
                </div>
            </form>
        </div>

        {{-- Kolom Kanan: Panduan --}}
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-slate-800 to-slate-950 text-white rounded-3xl p-6 shadow-md">
                <h3 class="text-sm font-bold tracking-wider uppercase text-blue-400 mb-4">Panduan Melapor</h3>
                <ul class="space-y-4 text-xs leading-relaxed text-slate-300">
                    <li class="flex gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold flex-shrink-0">1</span>
                        <span>Tulis <strong>Judul</strong> yang spesifik agar cepat dipahami pengurus.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold flex-shrink-0">2</span>
                        <span>Pilih <strong>Kategori</strong> yang paling sesuai agar penanganan lebih terarah.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold flex-shrink-0">3</span>
                        <span>Jelaskan <strong>Deskripsi</strong> secara terperinci (Lokasi spesifik, kronologi kejadian).</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold flex-shrink-0">4</span>
                        <span>Unggah <strong>Foto Bukti</strong> yang jelas untuk memperkuat validitas aduan Anda.</span>
                    </li>
                </ul>
            </div>

            <div class="bg-blue-50/50 border border-blue-100 rounded-3xl p-6">
                <h4 class="text-sm font-bold text-slate-800 mb-2">Kebijakan Pengaduan</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Setiap laporan yang masuk akan diverifikasi terlebih dahulu oleh admin/Ketua RT. Mohon gunakan bahasa yang sopan, santun, konstruktif, serta dilarang menyebarkan berita bohong (hoax) atau SARA.
                </p>
            </div>
        </div>
    </div>

</div>

<script>
    function previewFile(input) {
        const fileChosen = document.getElementById('file-chosen');
        if (input.files.length > 0) {
            fileChosen.textContent = "Berkas terpilih: " + input.files[0].name;
            fileChosen.classList.remove('hidden');
        } else {
            fileChosen.classList.add('hidden');
        }
    }
</script>
@endsection
