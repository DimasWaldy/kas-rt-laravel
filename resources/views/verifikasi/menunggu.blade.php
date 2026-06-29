<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Verifikasi | Smart RW</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-4 font-sans text-slate-900">
    <main class="w-full max-w-xl overflow-hidden rounded-3xl bg-white shadow-xl shadow-slate-200/70">
        @if ($user->status_akun === 'ditolak')
            <div class="bg-gradient-to-r from-rose-700 to-rose-500 px-8 py-10 text-center text-white">
                <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/15 text-4xl">
                    <i class="fa-solid fa-circle-xmark"></i>
                </span>
                <h1 class="mt-5 text-2xl font-black">Verifikasi Ditolak</h1>
                <p class="mt-2 text-sm text-rose-50">Pengurus RT belum dapat menyetujui pendaftaran Anda.</p>
            </div>
            <div class="space-y-5 p-8">
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-rose-500">Catatan pengurus</p>
                    <p class="mt-2 text-sm leading-6 text-rose-900">{{ $warga?->catatan_verifikasi ?: 'Tidak ada catatan tambahan.' }}</p>
                </div>
                <p class="text-center text-sm text-slate-600">Hubungi pengurus RT untuk klarifikasi atau perbaikan data.</p>
            </div>
        @else
            <div class="bg-gradient-to-r from-amber-500 to-orange-400 px-8 py-10 text-center text-white">
                <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/15 text-4xl">
                    <i class="fa-regular fa-clock"></i>
                </span>
                <h1 class="mt-5 text-2xl font-black">Menunggu Verifikasi RT</h1>
                <p class="mt-2 text-sm text-amber-50">Akun Anda sudah dibuat dan sedang masuk antrean pemeriksaan.</p>
            </div>
            <div class="space-y-5 p-8">
                <div class="rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">
                    <div class="flex justify-between gap-4"><span>Nama</span><strong class="text-right text-slate-800">{{ $warga?->nama_lengkap ?? $user->name }}</strong></div>
                    <div class="mt-3 flex justify-between gap-4"><span>Metode</span><strong class="text-right text-slate-800">{{ $warga?->metode_verifikasi === 'dokumen' ? 'Review dokumen' : 'Tatap muka' }}</strong></div>
                    <div class="mt-3 flex justify-between gap-4"><span>Status</span><strong class="text-right text-amber-700">Menunggu Verifikasi RT</strong></div>
                </div>
                <p class="text-center text-sm leading-6 text-slate-600">Pengurus RT akan meninjau dokumen atau menghubungi Anda untuk verifikasi langsung.</p>
            </div>
        @endif

        <div class="border-t border-slate-100 px-8 py-5">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    Keluar dari Akun
                </button>
            </form>
        </div>
    </main>
</body>
</html>
