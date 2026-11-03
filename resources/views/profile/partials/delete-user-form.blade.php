<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Akun hanya dapat dihapus jika tidak memiliki riwayat keuangan. Catatan tagihan, simpanan, pinjaman, dan bank sampah wajib tetap tersimpan untuk laporan pengurus.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Penghapusan akun bersifat permanen dan akan ditolak jika ada riwayat keuangan. Masukkan kata sandi dan ketik "konfirmasi" untuk melanjutkan.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="password" value="Kata Sandi Akun" />

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-full sm:w-3/4"
                        placeholder="Masukkan kata sandi akun"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="confirmation" value="Ketik kata 'konfirmasi'" />

                    <x-text-input
                        id="confirmation"
                        name="confirmation"
                        type="text"
                        class="mt-1 block w-full sm:w-3/4"
                        placeholder="Ketik konfirmasi"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('confirmation')" class="mt-2" />
                </div>

                <x-input-error :messages="$errors->userDeletion->get('account')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
