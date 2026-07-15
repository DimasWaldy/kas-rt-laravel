<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Warga | Smart RW</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <main class="mx-auto max-w-2xl px-4 py-8 sm:py-12">
        <div class="mb-6 flex items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-emerald-700">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke Login
            </a>
            <span class="text-sm font-black tracking-wide text-emerald-700">SMART RW</span>
        </div>

        <form
            action="{{ route('register') }}"
            method="POST"
            class="overflow-hidden rounded-3xl bg-white shadow-xl shadow-slate-200/60"
        >
            @csrf

            <div class="bg-gradient-to-r from-emerald-700 to-emerald-500 px-6 py-8 text-white sm:px-10">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl">
                        <i class="fa-solid fa-user-plus"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-black sm:text-3xl">Pendaftaran Warga</h1>
                        <p class="mt-2 max-w-xl text-sm text-emerald-50">Buat akun untuk masuk ke sistem Smart RW.</p>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="mx-6 mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 sm:mx-10">
                    <p class="font-bold">Periksa kembali data berikut:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-6 p-6 sm:p-10">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="text-sm font-bold text-slate-700">Nama Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Nama sesuai identitas">
                    </div>
                    <div>
                        <label for="email" class="text-sm font-bold text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="nama@email.com">
                    </div>
                    <div>
                        <label for="phone" class="text-sm font-bold text-slate-700">Nomor HP</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required inputmode="numeric" pattern="[0-9]{10,13}" maxlength="13" autocomplete="tel" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="08xxxxxxxxxx">
                    </div>
                    <div>
                        <label for="password" class="text-sm font-bold text-slate-700">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Minimal 8 karakter">
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-sm font-bold text-slate-700">Konfirmasi Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-2xl border-slate-200 px-4 py-3 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Ulangi password">
                    </div>
                </div>

                <div class="rounded-2xl bg-blue-50 p-4 text-sm text-blue-800">
                    <p class="font-semibold"><i class="fa-solid fa-circle-info mr-2"></i>Informasi Penting</p>
                    <p class="mt-1 ml-6">Setelah mendaftar, akun Anda perlu diaktifkan oleh pengurus RT sebelum dapat login ke sistem.</p>
                </div>

                <div class="flex flex-col-reverse gap-3 pt-4 sm:flex-row sm:justify-end">
                    <a href="{{ route('login') }}" class="rounded-2xl border border-slate-200 px-6 py-3 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</a>
                    <button type="submit" class="rounded-2xl bg-emerald-600 px-7 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">
                        Kirim Pendaftaran
                    </button>
                </div>
            </div>
        </form>
    </main>
    <script>
        document.querySelectorAll('input[inputmode="numeric"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, input.maxLength || undefined);
            });
        });
    </script>
</body>
</html>
