<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset Password — Smart RW')
                ->greeting('Halo!')
                ->line('Kami menerima permintaan untuk mereset password akun Anda.')
                ->action('Reset Password Sekarang', $url)
                ->line('Link ini akan kedaluwarsa dalam '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' menit.')
                ->line('Jika Anda tidak meminta reset password, abaikan email ini.')
                ->salutation('Salam, Tim Smart RW');
        });

        Gate::define('admin-only', fn ($user) => $user->hasPermission('admin-only'));
        Gate::define('manage-finance', fn ($user) => $user->canManageFinance());
        Gate::define('manage-warga', fn ($user) => $user->canManageWarga());
        Gate::define('manage-pengaduan', fn ($user) => $user->canManagePengaduan());
    }
}
