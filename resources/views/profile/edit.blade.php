@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[1.9fr_1fr] xl:gap-8">
                <div class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900">Edit Profil Saya</h1>
                            <p class="mt-2 text-sm text-slate-500">Perbarui data keluarga dan kontak Anda agar seluruh informasi profil tetap terkini.</p>
                        </div>
                    </div>

                    <div class="mt-8 space-y-6">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6 min-h-[26rem]">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">Keamanan Akun</h2>
                                <p class="mt-1 text-sm text-slate-500">Ubah kata sandi Anda untuk menjaga akun tetap aman.</p>
                            </div>
                        </div>

                        <div class="mt-8 space-y-6">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6 min-h-[18rem]">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">Hapus Akun</h2>
                                <p class="mt-1 text-sm text-slate-500">Jika Anda ingin menghentikan akses dan menghapus akun.</p>
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
