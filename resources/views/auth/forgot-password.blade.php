<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password | Smart RW</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .toast-animate {
            animation: slideIn 0.45s ease-out forwards;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-100 p-4 font-sans text-slate-900">
    <main class="flex min-h-[calc(100vh-2rem)] flex-col items-center justify-center">

        {{-- Toast --}}
        @if(session('status'))
            <div id="toast" class="toast-animate fixed right-5 top-5 z-[9999] flex w-full max-w-sm items-center rounded-2xl bg-emerald-500 p-4 text-white shadow-2xl">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/20">
                    <i class="fa-solid fa-check text-sm"></i>
                </div>
                <div class="ml-3 text-sm font-bold">{{ session('status') }}</div>
                <button type="button" onclick="this.closest('#toast').style.opacity='0'; setTimeout(()=>this.closest('#toast').remove(),500)" class="ml-auto rounded-lg p-1.5 transition-colors hover:bg-white/20">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        {{-- Back to Login --}}
        <div class="mb-4 flex w-[480px] max-w-full justify-start">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold tracking-wide text-slate-600 shadow-xs transition-all hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 active:scale-95">
                <i class="fa-solid fa-arrow-left text-[11px]"></i>
                <span>Kembali ke Login</span>
            </a>
        </div>

        {{-- Card --}}
        <div class="w-[480px] max-w-full overflow-hidden rounded-[32px] bg-white shadow-[0_30px_60px_-15px_rgba(0,0,0,0.15)]">
            <div class="flex flex-col items-center px-8 py-10 sm:px-12">

                {{-- Icon --}}
                <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-3xl text-amber-600 shadow-xs" style="animation: float 3s ease-in-out infinite">
                    <i class="fa-solid fa-key"></i>
                </div>

                <h1 class="mb-1 text-2xl font-black tracking-tight text-slate-800">Lupa Password?</h1>
                <p class="mb-6 max-w-xs text-center text-xs font-medium leading-5 text-slate-400">
                    Tenang, masukkan email yang terdaftar dan kami akan kirimkan link untuk reset password Anda.
                </p>

                <form method="POST" action="{{ route('password.email') }}" class="w-full" id="forgotForm">
                    @csrf

                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-4 text-xs text-slate-400"></i>
                        <input
                            type="email"
                            name="email"
                            placeholder="Masukkan email terdaftar"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            class="w-full rounded-2xl border-none bg-slate-50 py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-emerald-500 focus:shadow-[0_0_0_4px_rgba(16,185,129,0.12)]"
                        >
                    </div>

                    @if ($errors->has('email'))
                        <div class="mt-3 flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3">
                            <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0 text-xs text-rose-500"></i>
                            <p class="text-xs font-bold text-rose-600">{{ $errors->first('email') }}</p>
                        </div>
                    @endif

                    <button type="submit" id="submitBtn" class="mt-6 flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-4 text-xs font-black uppercase tracking-[0.15em] text-white transition-all hover:bg-emerald-700 hover:shadow-2xl active:scale-95">
                        <span id="submitText">Kirim Link Reset Password</span>
                        <span id="submitLoading" class="hidden"><i class="fa-solid fa-circle-notch animate-spin"></i></span>
                    </button>
                </form>

                <div class="mt-6 flex items-center gap-2 rounded-2xl bg-blue-50 px-4 py-3">
                    <i class="fa-solid fa-circle-info shrink-0 text-xs text-blue-500"></i>
                    <p class="text-[11px] font-semibold leading-4 text-blue-700">
                        Pastikan cek folder <strong>Spam</strong> jika email tidak masuk dalam beberapa menit.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('forgotForm')?.addEventListener('submit', () => {
            document.getElementById('submitText').innerText = 'Mengirim...';
            document.getElementById('submitLoading').classList.remove('hidden');
            document.getElementById('submitBtn').classList.add('opacity-60', 'pointer-events-none');
        });
    </script>
</body>
</html>
