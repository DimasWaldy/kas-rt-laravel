<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Smart RW</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-100 p-4 font-sans text-slate-900">
    <main class="flex min-h-[calc(100vh-2rem)] flex-col items-center justify-center">

        {{-- Back to Login --}}
        <div class="mb-4 flex w-[480px] max-w-full justify-start">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold tracking-wide text-slate-600 shadow-xs transition-all hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 active:scale-95">
                <i class="fa-solid fa-arrow-left text-[11px]"></i>
                <span>Kembali ke Login</span>
            </a>
        </div>

        {{-- Card --}}
        <div class="w-[480px] max-w-full overflow-hidden rounded-[32px] bg-white shadow-[0_30px_60px_-15px_rgba(0,0,0,0.15)]">
            <div class="flex flex-col items-center px-8 py-10 sm:px-12" x-data="{ showPassword: false, showConfirm: false }">

                {{-- Icon --}}
                <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-3xl text-emerald-600 shadow-xs" style="animation: float 3s ease-in-out infinite">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>

                <h1 class="mb-1 text-2xl font-black tracking-tight text-slate-800">Buat Password Baru</h1>
                <p class="mb-6 max-w-xs text-center text-xs font-medium leading-5 text-slate-400">
                    Masukkan password baru untuk akun Anda. Pastikan password kuat dan mudah diingat.
                </p>

                <form method="POST" action="{{ route('password.store') }}" class="w-full" id="resetForm">
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    {{-- Email (readonly) --}}
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-4 text-xs text-slate-400"></i>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $request->email) }}"
                            readonly
                            class="w-full rounded-2xl border-none bg-slate-100 py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-500 outline-hidden cursor-not-allowed"
                        >
                    </div>

                    {{-- Password --}}
                    <div class="relative mt-4">
                        <i class="fa-solid fa-lock absolute left-4 top-4 text-xs text-slate-400"></i>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            placeholder="Password Baru"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-2xl border-none bg-slate-50 py-3.5 pl-11 pr-12 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-emerald-500 focus:shadow-[0_0_0_4px_rgba(16,185,129,0.12)]"
                        >
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-3.5 text-slate-400 transition-colors hover:text-emerald-600">
                            <i class="fa-solid text-sm" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>

                    {{-- Confirm Password --}}
                    <div class="relative mt-3">
                        <i class="fa-solid fa-lock absolute left-4 top-4 text-xs text-slate-400"></i>
                        <input
                            :type="showConfirm ? 'text' : 'password'"
                            name="password_confirmation"
                            placeholder="Konfirmasi Password Baru"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-2xl border-none bg-slate-50 py-3.5 pl-11 pr-12 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-emerald-500 focus:shadow-[0_0_0_4px_rgba(16,185,129,0.12)]"
                        >
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4 top-3.5 text-slate-400 transition-colors hover:text-emerald-600">
                            <i class="fa-solid text-sm" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>

                    @if ($errors->any())
                        <div class="mt-3 flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3">
                            <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0 text-xs text-rose-500"></i>
                            <p class="text-xs font-bold text-rose-600">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <button type="submit" id="resetBtn" class="mt-6 flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-4 text-xs font-black uppercase tracking-[0.15em] text-white transition-all hover:bg-emerald-700 hover:shadow-2xl active:scale-95">
                        <span id="resetText">Simpan Password Baru</span>
                        <span id="resetLoading" class="hidden"><i class="fa-solid fa-circle-notch animate-spin"></i></span>
                    </button>
                </form>

                <p class="mt-5 text-center text-[11px] font-semibold text-slate-400">
                    <i class="fa-solid fa-shield-check mr-1 text-emerald-500"></i>
                    Password minimal 8 karakter
                </p>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('resetForm')?.addEventListener('submit', () => {
            document.getElementById('resetText').innerText = 'Menyimpan...';
            document.getElementById('resetLoading').classList.remove('hidden');
            document.getElementById('resetBtn').classList.add('opacity-60', 'pointer-events-none');
        });
    </script>
</body>
</html>
