<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Terima kasih sudah mendaftar. Sebelum masuk ke Smart RW, mohon verifikasi alamat email dengan membuka link
        yang sudah kami kirimkan. Link verifikasi bisa kedaluwarsa, jadi kirim ulang email verifikasi jika link lama
        tidak bisa digunakan atau belum masuk ke inbox.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            Link verifikasi baru sudah dikirim ke alamat email Anda.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Kirim ulang email verifikasi
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
