<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk & Daftar | KAS RT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css'])
    <style>
        /* Animasi Inti Sliding */
        .container.active .sign-in { transform: translateX(100%); opacity: 0; }
        .container.active .sign-up { transform: translateX(100%); opacity: 1; z-index: 5; animation: move 0.6s; }
        
        @keyframes move {
            0%, 49.9% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }

        .container.active .toggle-container { transform: translateX(-100%); border-radius: 0 150px 100px 0; }
        .container.active .toggle { transform: translateX(50%); }

        /* Toast & Visual */
        .toast-animate { animation: slideIn 0.5s ease-out forwards; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        /* Custom Input Glow */
        input:focus { box-shadow: 0 0 15px rgba(79, 70, 229, 0.1); }
        /* Tambahan biar teks ga bocor ke tengah */
    .toggle-panel {
        position: absolute;
        width: 50%;
        h-full;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0 40px;
        text-align: center;
        top: 0;
        transform: translateX(0);
        transition: all 0.6s ease-in-out;
    }

    /* Form kiri (Login) pas posisi default */
    .toggle-left {
        transform: translateX(-200%); /* Lempar jauh ke kiri biar ga keliatan */
    }

    /* Pas form aktif (Geser) */
    .container.active .toggle-left {
        transform: translateX(0);
    }

    .container.active .toggle-right {
        transform: translateX(200%); /* Lempar jauh ke kanan pas ga dipake */
    }

    /* Biar form yang di belakang ga bisa diklik */
    .form-container {
        transition: all 0.6s ease-in-out;
    }

    .container.active .sign-in {
        transform: translateX(100%);
        opacity: 0;
        pointer-events: none; /* Biar ga ngeblock form signup */
    }

    .container.active .sign-up {
        transform: translateX(100%);
        opacity: 1;
        z-index: 5;
    }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen font-sans p-4 overflow-hidden">

    @if(session('success') || session('error'))
    <div id="toast" class="fixed top-5 right-5 z-[9999] flex items-center p-4 w-full max-w-xs text-white {{ session('success') ? 'bg-emerald-500' : 'bg-rose-500' }} rounded-2xl shadow-2xl toast-animate">
        <div class="ml-3 text-sm font-bold">{{ session('success') ?? session('error') }}</div>
        <button type="button" onclick="closeToast()" class="ml-auto p-1.5 hover:bg-white/20 rounded-lg transition-colors">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    <div class="container bg-white rounded-[40px] shadow-[0_30px_60px_-15px_rgba(0,0,0,0.2)] relative overflow-hidden w-[900px] max-w-full min-h-[620px] transition-all duration-700 ease-in-out" id="container">
        
        <div class="form-container sign-up absolute top-0 h-full transition-all duration-700 ease-in-out left-0 w-1/2 opacity-0 z-1">
            <form action="{{ route('register') }}" method="POST" class="bg-white flex flex-col items-center justify-center px-12 h-full text-center">
                @csrf
                <div class="bg-indigo-100 text-indigo-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-4 shadow-sm">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h1 class="text-3xl font-black text-slate-800 mb-1 tracking-tight">Buat Akun</h1>
                <p class="text-slate-400 text-xs mb-6 font-medium">Lengkapi data diri untuk akses iuran warga</p>
                
                <div class="w-full space-y-3">
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-4 top-4 text-slate-400 text-xs"></i>
                        <input type="text" name="name" placeholder="Nama Lengkap" class="w-full bg-slate-50 border-none pl-11 pr-4 py-3.5 text-sm rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-700" value="{{ old('name') }}" required>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-4 text-slate-400 text-xs"></i>
                        <input type="email" name="email" placeholder="Email Aktif" class="w-full bg-slate-50 border-none pl-11 pr-4 py-3.5 text-sm rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-700" value="{{ old('email') }}" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="password" name="password" placeholder="Password" class="bg-slate-50 border-none px-4 py-3.5 text-sm rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-700" required>
                        <input type="password" name="password_confirmation" placeholder="Konfirmasi" class="bg-slate-50 border-none px-4 py-3.5 text-sm rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-700" required>
                    </div>
                </div>

                @if ($errors->any() && (old('name') || request()->is('register')))
                    <p class="text-rose-500 text-[10px] mt-3 font-bold uppercase tracking-wider">{{ $errors->first() }}</p>
                @endif

                <button type="submit" class="bg-indigo-600 text-white text-xs py-4 px-14 rounded-2xl font-black tracking-[0.15em] uppercase mt-8 hover:bg-indigo-700 hover:shadow-xl hover:shadow-indigo-200 transition-all active:scale-95">
                    Daftar Sekarang
                </button>
            </form>
        </div>

        <div class="form-container sign-in absolute top-0 h-full transition-all duration-700 ease-in-out left-0 w-1/2 z-2">
            <form action="{{ route('login') }}" method="POST" class="bg-white flex flex-col items-center justify-center px-12 h-full text-center">
                @csrf
                <div class="bg-blue-100 text-blue-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-4 shadow-sm">
                    <i class="fa-solid fa-fingerprint"></i>
                </div>
                <h1 class="text-3xl font-black text-slate-800 mb-1 tracking-tight">Selamat Datang</h1>
                <p class="text-slate-400 text-xs mb-8 font-medium">Masuk untuk mengelola keuangan lingkungan</p>
                
                <div class="w-full space-y-4">
                    <div class="relative">
                        <i class="fa-solid fa-at absolute left-4 top-4 text-slate-400 text-xs"></i>
                        <input type="email" name="email" placeholder="Email" class="w-full bg-slate-50 border-none pl-11 pr-4 py-3.5 text-sm rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 transition-all font-semibold text-slate-700" required>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-4 text-slate-400 text-xs"></i>
                        <input type="password" name="password" placeholder="Password" class="w-full bg-slate-50 border-none pl-11 pr-4 py-3.5 text-sm rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 transition-all font-semibold text-slate-700" required>
                    </div>
                </div>

                @if ($errors->any() && !old('name') && !request()->is('register'))
                    <p class="text-rose-500 text-[10px] mt-3 font-bold uppercase tracking-wider">{{ $errors->first() }}</p>
                @endif

                <div class="w-full text-right mt-3">
                    <a href="#" class="text-[10px] font-bold text-slate-400 hover:text-blue-600 uppercase tracking-widest transition-colors">Lupa Password?</a>
                </div>

                <button type="submit" id="loginBtn" class="w-full bg-slate-900 text-white text-xs py-4 rounded-2xl font-black tracking-[0.15em] uppercase mt-8 hover:bg-slate-800 hover:shadow-2xl transition-all active:scale-95 flex items-center justify-center gap-3">
                    <span id="loginText">Masuk Ke Sistem</span>
                    <span id="loginLoading" class="hidden"><i class="fa-solid fa-circle-notch animate-spin"></i></span>
                </button>
            </form>
        </div>

        <div class="toggle-container absolute top-0 left-1/2 w-1/2 h-full overflow-hidden transition-all duration-700 ease-in-out z-[100] rounded-l-[150px]">
    <div class="toggle bg-gradient-to-br from-[#4f46e5] via-[#3b82f6] to-[#2563eb] text-white relative -left-full h-full w-[200%] transform transition-all duration-700 ease-in-out">
        
        <div class="toggle-panel toggle-left absolute w-1/2 h-full flex flex-col items-center justify-center px-12 text-center top-0 transition-all duration-700 ease-in-out">
            <h1 class="text-3xl font-black mb-4 tracking-tighter uppercase">Sudah Terdaftar?</h1>
            <p class="text-sm leading-relaxed mb-8 opacity-80">Kembali masuk untuk memantau saldo kas.</p>
            <button class="bg-transparent border-2 border-white/50 text-white text-xs py-3 px-12 rounded-2xl font-black uppercase hover:bg-white hover:text-blue-600 transition-all" id="login">Login Disini</button>
        </div>

        <div class="toggle-panel toggle-right absolute w-1/2 h-full flex flex-col items-center justify-center px-12 text-center top-0 right-0 transition-all duration-700 ease-in-out">
            <h1 class="text-3xl font-black mb-4 tracking-tighter uppercase">Belum Gabung?</h1>
            <p class="text-sm leading-relaxed mb-8 opacity-80">Daftar sekarang untuk akses penuh laporan keuangan.</p>
            <button class="bg-transparent border-2 border-white/50 text-white text-xs py-3 px-12 rounded-2xl font-black uppercase hover:bg-white hover:text-blue-600 transition-all" id="register">Daftar Warga</button>
        </div>

    </div>
</div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        // Initial State Check
        if (window.location.pathname.includes('register')) {
            container.classList.add("active");
        }

        registerBtn.addEventListener('click', () => {
            container.classList.add("active");
            window.history.pushState({}, '', '/register'); 
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
            window.history.pushState({}, '', '/login');
        });

        function closeToast() {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }
        }

        // Loading Effect
        document.querySelector('.sign-in form').addEventListener('submit', () => {
            document.getElementById('loginText').innerText = "Memproses...";
            document.getElementById('loginLoading').classList.remove('hidden');
            document.getElementById('loginBtn').classList.add('opacity-50', 'pointer-events-none');
        });
    </script>
</body>
</html>