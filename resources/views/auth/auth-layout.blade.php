<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk & Daftar | Smart RW</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
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
                min-height: 480px !important;
                border-radius: 28px !important;
                display: grid;
                overflow: hidden;
            }

            .form-container {
                grid-area: 1 / 1;
                position: relative !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: auto !important;
                min-height: 100% !important;
                transition: transform 0.55s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.55s cubic-bezier(0.22, 1, 0.36, 1), visibility 0.55s !important;
                backface-visibility: hidden;
                will-change: transform, opacity;
            }

            .sign-in {
                transform: translateX(0) !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                visibility: visible !important;
            }

            .sign-up {
                transform: translateX(100%) !important;
                opacity: 0 !important;
                pointer-events: none !important;
                visibility: hidden !important;
                animation: none !important;
            }

            .auth-card.active .sign-in {
                transform: translateX(-100%) !important;
                opacity: 0 !important;
                pointer-events: none !important;
                visibility: hidden !important;
            }

            .auth-card.active .sign-up {
                transform: translateX(0) !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                visibility: visible !important;
                animation: none !important;
            }

            .toggle-container { display: none; }
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-100 p-4 font-sans text-slate-900">
    <main class="flex min-h-[calc(100vh-2rem)] flex-col items-center justify-center">
        @if(session('success') || session('error') || session('status'))
            <div id="toast" class="toast-animate fixed right-5 top-5 z-[9999] flex w-full max-w-xs items-center rounded-2xl p-4 text-white shadow-2xl {{ session('error') ? 'bg-rose-500' : 'bg-emerald-500' }}">
                <div class="ml-3 text-sm font-bold">{{ session('success') ?? session('status') ?? session('error') }}</div>
                <button type="button" onclick="closeToast()" class="ml-auto rounded-lg p-1.5 transition-colors hover:bg-white/20">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        <div class="mb-4 flex w-[920px] max-w-full justify-start">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold tracking-wide text-slate-600 shadow-xs transition-all hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 active:scale-95">
                <i class="fa-solid fa-arrow-left text-[11px]"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>

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

            <div class="form-container sign-in absolute left-0 top-0 z-[2] h-full w-1/2 overflow-y-auto transition-all duration-700 ease-in-out">
                <div class="flex min-h-full flex-col items-center justify-center bg-white px-8 py-10 text-center sm:px-12">
                    <form action="{{ route('login') }}" method="POST" class="flex w-full flex-col items-center">
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
                    </form>

                    @if(\App\Http\Controllers\Auth\DemoLoginController::isEnabled())
                        <div class="mt-4 w-full">
                            <div class="relative mb-3 flex items-center">
                                <div class="grow border-t border-slate-200"></div>
                                <span class="mx-3 shrink-0 text-[10px] font-bold uppercase tracking-widest text-slate-400"><i class="fa-solid fa-bolt mr-1 text-amber-400"></i>Akses Cepat Demo</span>
                                <div class="grow border-t border-slate-200"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach(\App\Http\Controllers\Auth\DemoLoginController::accounts() as $key => $account)
                                    @php
                                        $colors = match($account['color']) {
                                            'rose' => [
                                                'border' => 'hover:border-rose-200',
                                                'bg' => 'hover:bg-rose-50',
                                                'icon_bg' => 'bg-rose-100 group-hover:bg-rose-200',
                                                'icon_text' => 'text-rose-600',
                                            ],
                                            'violet' => [
                                                'border' => 'hover:border-violet-200',
                                                'bg' => 'hover:bg-violet-50',
                                                'icon_bg' => 'bg-violet-100 group-hover:bg-violet-200',
                                                'icon_text' => 'text-violet-600',
                                            ],
                                            'blue' => [
                                                'border' => 'hover:border-blue-200',
                                                'bg' => 'hover:bg-blue-50',
                                                'icon_bg' => 'bg-blue-100 group-hover:bg-blue-200',
                                                'icon_text' => 'text-blue-600',
                                            ],
                                            'cyan' => [
                                                'border' => 'hover:border-cyan-200',
                                                'bg' => 'hover:bg-cyan-50',
                                                'icon_bg' => 'bg-cyan-100 group-hover:bg-cyan-200',
                                                'icon_text' => 'text-cyan-600',
                                            ],
                                            'amber' => [
                                                'border' => 'hover:border-amber-200',
                                                'bg' => 'hover:bg-amber-50',
                                                'icon_bg' => 'bg-amber-100 group-hover:bg-amber-200',
                                                'icon_text' => 'text-amber-600',
                                            ],
                                            default => [
                                                'border' => 'hover:border-emerald-200',
                                                'bg' => 'hover:bg-emerald-50',
                                                'icon_bg' => 'bg-emerald-100 group-hover:bg-emerald-200',
                                                'icon_text' => 'text-emerald-600',
                                            ],
                                        };
                                    @endphp
                                    @if($key === 'warga')
                                        <button type="button" onclick="openWargaDemoModal()" class="group flex w-full items-center gap-2.5 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2.5 text-left transition-all {{ $colors['border'] }} {{ $colors['bg'] }} hover:shadow-sm active:scale-[0.97]">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $colors['icon_bg'] }} {{ $colors['icon_text'] }} transition-colors">
                                                <i class="fa-solid {{ $account['icon'] }} text-xs"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="truncate text-[11px] font-bold text-slate-700">{{ $account['label'] }}</span>
                                                    <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[8px] font-black text-emerald-700">30 Akun</span>
                                                </div>
                                                <div class="truncate text-[9px] text-slate-400">{{ $account['description'] }}</div>
                                            </div>
                                        </button>
                                    @else
                                        <form action="{{ route('demo-login') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="account" value="{{ $key }}">
                                            <button type="submit" class="group flex w-full items-center gap-2.5 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2.5 text-left transition-all {{ $colors['border'] }} {{ $colors['bg'] }} hover:shadow-sm active:scale-[0.97]">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $colors['icon_bg'] }} {{ $colors['icon_text'] }} transition-colors">
                                                    <i class="fa-solid {{ $account['icon'] }} text-xs"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="truncate text-[11px] font-bold text-slate-700">{{ $account['label'] }}</div>
                                                    <div class="truncate text-[9px] text-slate-400">{{ $account['description'] }}</div>
                                                </div>
                                            </button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                            <p class="mt-2 text-center text-[9px] font-medium text-slate-400">
                                <i class="fa-solid fa-circle-info mr-0.5"></i> Password semua akun demo: <code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-slate-500">password</code>
                            </p>
                        </div>
                    @endif

                    <button type="button" class="mt-4 text-[11px] font-black uppercase tracking-widest text-indigo-600 md:hidden" data-auth-switch="register">
                        Belum punya akun? Daftar
                    </button>
                </div>
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

    @if(\App\Http\Controllers\Auth\DemoLoginController::isEnabled())
        <div id="wargaDemoModal" class="fixed inset-0 z-[200] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target === this) closeWargaDemoModal()">
            <div class="relative flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-2xl">
                <!-- Header Modal -->
                <div class="flex items-center justify-between border-b border-slate-100 bg-emerald-50/60 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-200">
                            <i class="fa-solid fa-users text-sm"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-black text-slate-900">Pilih Akun Demo Warga</h3>
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-800">30 Warga</span>
                            </div>
                            <p class="text-[11px] text-slate-500">14 teman Anda + 16 warga random untuk simulasi</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeWargaDemoModal()" class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Search & Quick Action -->
                <div class="border-b border-slate-100 bg-white px-5 py-3">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                        <input type="text" id="wargaSearchInput" placeholder="Ketik nama teman atau warga..." class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-2 pl-9 pr-3 text-xs text-slate-800 transition placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:outline-hidden" oninput="filterWargaDemo(this.value)">
                    </div>
                </div>

                <!-- Scrollable Warga List -->
                <div class="overflow-y-auto px-5 py-3 space-y-1.5 max-h-[52vh]" id="wargaDemoList">
                    @foreach(\App\Http\Controllers\Auth\DemoLoginController::wargaAccounts() as $index => $w)
                        <form action="{{ route('demo-login') }}" method="POST" class="warga-item" data-warga-name="{{ strtolower($w['name']) }}" data-warga-email="{{ strtolower($w['email']) }}" data-warga-rt="{{ strtolower($w['rt']) }}">
                            @csrf
                            <input type="hidden" name="account" value="warga">
                            <input type="hidden" name="email" value="{{ $w['email'] }}">
                            <button type="submit" class="group flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-2.5 text-left transition hover:border-emerald-300 hover:bg-emerald-50/50 hover:shadow-xs active:scale-[0.99]">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $w['is_friend'] ? 'bg-emerald-600 text-white font-black' : 'bg-slate-200 text-slate-600 font-bold' }} text-xs shadow-2xs group-hover:scale-105 transition">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate text-xs font-bold text-slate-900 group-hover:text-emerald-900">{{ $w['name'] }}</span>
                                            @if($w['is_friend'])
                                                <span class="shrink-0 rounded-md bg-amber-100 px-1.5 py-0.2 text-[9px] font-black text-amber-800">
                                                    <i class="fa-solid fa-user-check text-[8px] mr-0.5"></i>Teman
                                                </span>
                                            @endif
                                        </div>
                                        <div class="truncate text-[10px] text-slate-400">
                                            <span>{{ $w['email'] }}</span>
                                            <span class="mx-1">&bull;</span>
                                            <span class="font-medium text-slate-500">{{ $w['rt'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-emerald-200 bg-white px-3 py-1.5 text-[11px] font-bold text-emerald-700 shadow-2xs transition group-hover:border-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">
                                    <span>Masuk</span>
                                    <i class="fa-solid fa-arrow-right text-[9px]"></i>
                                </span>
                            </button>
                        </form>
                    @endforeach
                    <div id="wargaNotFound" class="hidden py-8 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-user-slash text-2xl text-slate-300 mb-2 block"></i>
                        Tidak ada warga dengan nama tersebut.
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/80 px-5 py-3 text-[11px] text-slate-500">
                    <span>
                        <i class="fa-solid fa-key mr-1 text-amber-500"></i>Password: <code class="rounded bg-white px-1.5 py-0.5 font-mono font-bold text-slate-700 border border-slate-200">password</code>
                    </span>
                    <button type="button" onclick="closeWargaDemoModal()" class="rounded-xl px-3 py-1 font-bold text-slate-600 transition hover:bg-slate-200/70 hover:text-slate-900">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

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

        function openWargaDemoModal() {
            const modal = document.getElementById('wargaDemoModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                const search = document.getElementById('wargaSearchInput');
                if (search) {
                    search.value = '';
                    filterWargaDemo('');
                    setTimeout(() => search.focus(), 100);
                }
            }
        }

        function closeWargaDemoModal() {
            const modal = document.getElementById('wargaDemoModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }

        function filterWargaDemo(query) {
            const q = (query || '').toLowerCase().trim();
            const items = document.querySelectorAll('#wargaDemoList .warga-item');
            let visibleCount = 0;

            items.forEach((item) => {
                const name = item.dataset.wargaName || '';
                const email = item.dataset.wargaEmail || '';
                const rt = item.dataset.wargaRt || '';
                const match = !q || name.includes(q) || email.includes(q) || rt.includes(q);
                item.style.display = match ? '' : 'none';
                if (match) {
                    visibleCount++;
                }
            });

            const notFound = document.getElementById('wargaNotFound');
            if (notFound) {
                notFound.classList.toggle('hidden', visibleCount > 0);
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeWargaDemoModal();
            }
        });
    </script>
</body>
</html>
