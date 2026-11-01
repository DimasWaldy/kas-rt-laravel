<x-guest-layout>
    <div class="space-y-5 text-slate-800 dark:text-slate-100">
        <header>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Status pendaftaran</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $user->status_akun === 'ditolak' ? 'Pendaftaran ditolak' : 'Menunggu verifikasi pengurus' }}</h1>
            <p class="mt-2 text-sm">{{ $user->name }} — {{ $user->email }}</p>
        </header>
        @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('success') }}</p>@endif
        @if($errors->any())
            <ul role="alert" class="list-inside list-disc text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif
        @if($user->status_akun === 'ditolak')
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                <h2 class="font-bold">Alasan dari pengurus</h2>
                <p class="mt-2 whitespace-pre-line">{{ $warga?->catatan_verifikasi ?: 'Alasan belum tercatat. Hubungi pengurus untuk klarifikasi.' }}</p>
            </section>
        @endif
        @if($canResubmit)
            <p class="text-sm">Perbaiki data atau jelaskan klarifikasi di bawah. Gunakan akun dan email yang sama; tidak perlu daftar baru. Untuk pemeriksaan identitas atau dokumen, hubungi pengurus dan bawa dokumen saat tatap muka.</p>
            <form method="POST" action="{{ route('pendaftaran.resubmit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="registration-name" class="text-sm font-semibold">Nama lengkap</label>
                    <input id="registration-name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name" class="mt-2 w-full rounded-xl border-slate-300 dark:bg-slate-800">
                </div>
                <div>
                    <label for="registration-phone" class="text-sm font-semibold">Nomor HP</label>
                    <input id="registration-phone" name="phone" value="{{ old('phone', $user->phone) }}" required pattern="[0-9]{10,13}" inputmode="tel" autocomplete="tel" class="mt-2 w-full rounded-xl border-slate-300 dark:bg-slate-800">
                </div>
                <div>
                    <label for="registration-note" class="text-sm font-semibold">Perbaikan / klarifikasi untuk pengurus</label>
                    <textarea id="registration-note" name="klarifikasi" required minlength="10" maxlength="1000" rows="4" class="mt-2 w-full rounded-xl border-slate-300 dark:bg-slate-800">{{ old('klarifikasi') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-300">Jelaskan tanggapan terhadap alasan penolakan, minimal 10 karakter.</p>
                </div>
                <p class="text-sm">Pengajuan ulang tetap memerlukan persetujuan pengurus sebelum layanan warga dapat digunakan.</p>
                <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-bold text-white hover:bg-emerald-800">Kirim perbaikan & ajukan ulang</button>
            </form>
        @elseif($user->status_akun === 'pending_verifikasi')
            <p class="text-sm">Pendaftaran Anda sedang diperiksa. Tidak perlu daftar ulang. Pengurus akan memeriksa identitas Anda sebelum mengaktifkan akses layanan.</p>
            <p class="text-sm">Jika ada data yang perlu diperbaiki, hubungi pengurus agar pengajuan dikembalikan beserta alasan perbaikannya.</p>
        @else
            <p class="text-sm">Hubungi pengurus untuk memeriksa data akun Anda sebelum mengajukan ulang.</p>
        @endif
        <a href="{{ route('pendaftaran.show') }}" class="inline-block text-sm font-bold text-emerald-700 dark:text-emerald-400 underline">Periksa status terbaru</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-sm underline">Keluar</button></form>
    </div>
</x-guest-layout>
