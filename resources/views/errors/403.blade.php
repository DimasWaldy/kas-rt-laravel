<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - KAS RT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="min-h-screen flex items-center justify-center px-6 py-12">
        <section class="w-full max-w-lg bg-white border border-slate-200 rounded-2xl shadow-sm p-8 text-center">
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                <span class="text-2xl font-bold">!</span>
            </div>

            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">403</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Akses Ditolak</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman ini.' }}
            </p>

            <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full sm:w-auto rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        Kembali ke Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full sm:w-auto rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        Masuk
                    </a>
                @endauth

                <a href="{{ url()->previous() }}" class="w-full sm:w-auto rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Kembali
                </a>
            </div>
        </section>
    </main>
</body>
</html>
