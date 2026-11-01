<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk & Daftar | Smart RW</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .auth-card.active .sign-in {
            transform: translateX(100%);
            opacity: 0;
            pointer-events: none;
        }

        .auth-card.active .sign-up {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            pointer-events: auto;
            animation: authMove 0.6s;
        }

        @keyframes authMove {
            0%, 49.9% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }

        .auth-card.active .toggle-container {
            transform: translateX(-100%);
            border-radius: 0 150px 100px 0;
        }

        .auth-card.active .toggle {
            transform: translateX(50%);
        }

        .toggle-panel {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0 42px;
            text-align: center;
            transition: all 0.6s ease-in-out;
        }

        .toggle-left { transform: translateX(-200%); }
        .auth-card.active .toggle-left { transform: translateX(0); }
        .auth-card.active .toggle-right { transform: translateX(200%); }

        .auth-input:focus {
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
        }

        .toast-animate {
            animation: slideIn 0.45s ease-out forwards;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .auth-card {
                min-height: auto !important;
                border-radius: 28px !important;
            }

            .form-container {
                position: relative !important;
                width: 100% !important;
                min-height: auto !important;
                transform: none !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }

            .sign-up { display: none; }
            .auth-card.active .sign-in { display: none; }
            .auth-card.active .sign-up { display: block; }
            .toggle-container { display: none; }
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-100 p-4 font-sans text-slate-900">
    <main class="flex min-h-[calc(100vh-2rem)] items-center justify-center">
        @if(session('success') || session('error') || session('status'))
            <div id="toast" class="toast-animate fixed right-5 top-5 z-[9999] flex w-full max-w-xs items-center rounded-2xl p-4 text-white shadow-2xl {{ session('error') ? 'bg-rose-500' : 'bg-emerald-500' }}">
                <div class="ml-3 text-sm font-bold">{{ session('success') ?? session('status') ?? session('error') }}</div>
                <button type="button" onclick="closeToast()" class="ml-auto rounded-lg p-1.5 transition-colors hover:bg-white/20">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        <div id="authCard" class="auth-card relative min-h-[640px] w-[920px] max-w-full overflow-hidden rounded-[40px] bg-white shadow-[0_30px_60px_-15px_rgba(0,0,0,0.22)] transition-all duration-700 ease-in-out">
            <div class="form-container sign-up pointer-events-none absolute left-0 top-0 h-full w-1/2 opacity-0 transition-all duration-700 ease-in-out">
                <form action="{{ route('register') }}" method="POST" class="flex h-full flex-col items-center justify-center bg-white px-8 py-10 text-center sm:px-12">
                    @csrf

                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl text-indigo-600 shadow-xs">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h1 class="mb-1 text-3xl font-black tracking-tight text-slate-800">Buat Akun</h1>
                    <p class="mb-6 text-xs font-medium text-slate-400">Daftar sebagai warga Smart RW</p>

                    <div class="w-full space-y-3">
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-4 top-4 text-xs text-slate-400"></i>
                            <input type="text" name="name" placeholder="Nama Lengkap" class="auth-input w-full rounded-2xl border-none bg-slate-50 py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-indigo-500" value="{{ old('name') }}" required autocomplete="name">
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-envelope absolute left-4 top-4 text-xs text-slate-400"></i>
                            <input type="email" name="email" placeholder="Email Aktif" class="auth-input w-full rounded-2xl border-none bg-slate-50 py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-indigo-500" value="{{ old('email') }}" required autocomplete="email">
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-phone absolute left-4 top-4 text-xs text-slate-400"></i>
                            <input type="text" name="phone" placeholder="Nomor HP" inputmode="numeric" pattern="[0-9]{10,13}" maxlength="13" autocomplete="tel" class="numeric-only auth-input w-full rounded-2xl border-none bg-slate-50 py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-indigo-500" value="{{ old('phone') }}" required>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <input type="password" name="password" placeholder="Password" class="auth-input rounded-2xl border-none bg-slate-50 px-4 py-3.5 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-indigo-500" required autocomplete="new-password">
                            <input type="password" name="password_confirmation" placeholder="Konfirmasi" class="auth-input rounded-2xl border-none bg-slate-50 px-4 py-3.5 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-indigo-500" required autocomplete="new-password">
                        </div>
                    </div>

                    @if (isset($errors) && $errors->any() && (old('name') || request()->is('register')))
                        <p class="mt-3 text-[10px] font-bold uppercase tracking-wider text-rose-500">{{ $errors->first() }}</p>
                    @endif

                    <p class="mt-4 rounded-2xl bg-blue-50 px-4 py-3 text-left text-xs font-semibold leading-5 text-blue-800">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Daftar tanpa memilih rumah. Masuk untuk memantau verifikasi pengurus, lalu ajukan domisili setelah disetujui. Tidak perlu verifikasi email.
                    </p>

                    <button type="submit" class="mt-6 rounded-2xl bg-indigo-600 px-12 py-4 text-xs font-black uppercase tracking-[0.15em] text-white transition-all hover:bg-indigo-700 hover:shadow-xl hover:shadow-indigo-200 active:scale-95">
                        Daftar Sekarang
                    </button>

                    <button type="button" class="mt-4 text-[11px] font-black uppercase tracking-widest text-emerald-600 md:hidden" data-auth-switch="login">
                        Sudah punya akun? Login
                    </button>
                </form>
            </div>

            <div class="form-container sign-in absolute left-0 top-0 z-[2] h-full w-1/2 transition-all duration-700 ease-in-out">
                <form action="{{ route('login') }}" method="POST" class="flex h-full flex-col items-center justify-center bg-white px-8 py-10 text-center sm:px-12">
                    @csrf

                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-3xl text-emerald-600 shadow-xs">
                        <i class="fa-solid fa-fingerprint"></i>
                    </div>
                    <h1 class="mb-1 text-3xl font-black tracking-tight text-slate-800">Selamat Datang</h1>
                    <p class="mb-4 text-xs font-medium text-slate-500">Masuk untuk layanan Smart RW atau cek status pendaftaran. Jika ditolak, perbaiki lewat akun yang sama.</p>

                    <div class="w-full space-y-4">
                        <div class="relative">
                            <i class="fa-solid fa-at absolute left-4 top-4 text-xs text-slate-400"></i>
                            <input type="email" name="email" placeholder="Email" class="auth-input w-full rounded-2xl border-none bg-slate-50 py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-emerald-500" value="{{ old('email') }}" required autocomplete="username">
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-4 top-4 text-xs text-slate-400"></i>
                            <input type="password" name="password" placeholder="Password" class="auth-input w-full rounded-2xl border-none bg-slate-50 py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-700 outline-hidden transition-all focus:ring-2 focus:ring-emerald-500" required autocomplete="current-password">
                        </div>
                    </div>

                    @if (isset($errors) && $errors->any() && !old('name') && !request()->is('register'))
                        <p class="mt-3 text-[10px] font-bold uppercase tracking-wider text-rose-500">{{ $errors->first() }}</p>
                    @endif

                    <div class="mt-3 w-full text-right">
                        <a href="{{ route('password.request') }}" class="text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors hover:text-emerald-600">Lupa Password?</a>
                    </div>

                    <button type="submit" id="loginBtn" class="mt-8 flex w-full items-center justify-center gap-3 rounded-2xl bg-emerald-600 py-4 text-xs font-black uppercase tracking-[0.15em] text-white transition-all hover:bg-emerald-700 hover:shadow-2xl active:scale-95">
                        <span id="loginText">Masuk Ke Sistem</span>
                        <span id="loginLoading" class="hidden"><i class="fa-solid fa-circle-notch animate-spin"></i></span>
                    </button>

                    <button type="button" class="mt-4 text-[11px] font-black uppercase tracking-widest text-indigo-600 md:hidden" data-auth-switch="register">
                        Belum punya akun? Daftar
                    </button>
                </form>
            </div>

            <div class="toggle-container absolute left-1/2 top-0 z-[100] h-full w-1/2 overflow-hidden rounded-l-[150px] transition-all duration-700 ease-in-out">
                <div class="toggle relative -left-full h-full w-[200%] bg-linear-to-br/srgb from-emerald-700 via-emerald-500 to-indigo-500 text-white transition-all duration-700 ease-in-out">
                    <div class="toggle-panel toggle-left">
                        <h1 class="mb-4 text-3xl font-black uppercase tracking-tighter">Sudah Terdaftar?</h1>
                        <p class="mb-8 text-sm leading-relaxed opacity-85">Kembali masuk untuk mengelola layanan warga dan memantau aktivitas RW.</p>
                        <button type="button" class="rounded-2xl border-2 border-white/50 bg-transparent px-12 py-3 text-xs font-black uppercase text-white transition-all hover:bg-white hover:text-emerald-600" data-auth-switch="login">Login Disini</button>
                    </div>

                    <div class="toggle-panel toggle-right right-0">
                        <h1 class="mb-4 text-3xl font-black uppercase tracking-tighter">Belum Gabung?</h1>
                        <p class="mb-8 text-sm leading-relaxed opacity-85">Daftar sebagai warga untuk akses tagihan, surat, koperasi, dan layanan RW.</p>
                        <button type="button" class="rounded-2xl border-2 border-white/50 bg-transparent px-12 py-3 text-xs font-black uppercase text-white transition-all hover:bg-white hover:text-indigo-600" data-auth-switch="register">Daftar Warga</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const authCard = document.getElementById('authCard');
        const registerUrl = @js(route('register', absolute: false));
        const loginUrl = @js(route('login', absolute: false));

        if (window.location.pathname.includes('register')) {
            authCard.classList.add('active');
        }

        document.querySelectorAll('[data-auth-switch]').forEach((button) => {
            button.addEventListener('click', () => {
                if (button.dataset.authSwitch === 'register') {
                    authCard.classList.add('active');
                    window.history.pushState({}, '', registerUrl);
                    return;
                }

                authCard.classList.remove('active');
                window.history.pushState({}, '', loginUrl);
            });
        });

        function closeToast() {
            const toast = document.getElementById('toast');

            if (! toast) {
                return;
            }

            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }

        const loginForm = document.querySelector('.sign-in form');
        loginForm?.addEventListener('submit', () => {
            document.getElementById('loginText').innerText = 'Memproses...';
            document.getElementById('loginLoading').classList.remove('hidden');
            document.getElementById('loginBtn').classList.add('opacity-60', 'pointer-events-none');
        });

        document.querySelectorAll('.numeric-only').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, input.maxLength || undefined);
            });
        });
    </script>
</body>
</html>
