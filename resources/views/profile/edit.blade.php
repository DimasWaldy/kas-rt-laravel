@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[1.9fr_1fr] xl:gap-8">
                <div class="rounded-3xl bg-white border border-slate-200 shadow-xs p-6 dark:border-slate-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Edit Profil Saya</h1>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Perbarui data pribadi dan informasi domisili Anda. Penetapan data KK dilakukan melalui pengurus.</p>
                        </div>
                    </div>

                    <div class="mt-8 space-y-6">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl bg-white border border-slate-200 shadow-xs p-6 min-h-[26rem] dark:border-slate-800">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Keamanan Akun</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ubah kata sandi Anda untuk menjaga akun tetap aman.</p>
                            </div>
                        </div>

                        <div class="mt-8 space-y-6">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white border border-slate-200 shadow-xs p-6 min-h-[18rem] dark:border-slate-800">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Hapus Akun</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Jika Anda ingin menghentikan akses dan menghapus akun.</p>
                            </div>
                        </div>

                        <div class="mt-8">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
