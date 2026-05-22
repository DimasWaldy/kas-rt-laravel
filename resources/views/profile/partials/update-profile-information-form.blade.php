<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="no_kk" :value="__('Nomor KK')" />
            <x-text-input id="no_kk" name="no_kk" type="text" class="mt-1 block w-full" :value="old('no_kk', $user->no_kk)" required autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('no_kk')" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <x-input-label for="phone" :value="__('Nomor HP Kepala Keluarga')" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" required autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
            <div>
                <x-input-label for="rt" :value="__('RT')" />
                <x-text-input id="rt" name="rt" type="text" class="mt-1 block w-full" :value="old('rt', $user->rt)" required autocomplete="off" />
                <x-input-error class="mt-2" :messages="$errors->get('rt')" />
            </div>
            <div>
                <x-input-label for="rw" :value="__('RW')" />
                <x-text-input id="rw" name="rw" type="text" class="mt-1 block w-full" :value="old('rw', $user->rw)" required autocomplete="off" />
                <x-input-error class="mt-2" :messages="$errors->get('rw')" />
            </div>
        </div>

        <div>
            <x-input-label for="jumlah_anggota_keluarga" :value="__('Jumlah Anggota Keluarga')" />
            <x-text-input id="jumlah_anggota_keluarga" name="jumlah_anggota_keluarga" type="number" min="1" max="20" class="mt-1 block w-full" :value="old('jumlah_anggota_keluarga', $user->jumlah_anggota_keluarga)" required />
            <x-input-error class="mt-2" :messages="$errors->get('jumlah_anggota_keluarga')" />
        </div>

        <div class="flex items-center gap-2">
            <input id="is_kepala_keluarga" name="is_kepala_keluarga" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" value="1" {{ old('is_kepala_keluarga', $user->is_kepala_keluarga) ? 'checked' : '' }}>
            <label for="is_kepala_keluarga" class="text-sm text-gray-700">{{ __('Saya Kepala Keluarga') }}</label>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
