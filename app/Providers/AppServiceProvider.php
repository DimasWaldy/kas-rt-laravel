<?php

namespace App\Providers;

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
        Gate::define('admin-only', fn($user) => $user->isAdmin() || $user->hasPermission('admin-only'));
        Gate::define('manage-finance', fn($user) => $user->canManageFinance());
        Gate::define('manage-warga', fn($user) => $user->canManageWarga());
        Gate::define('manage-pengaduan', fn($user) => $user->canManagePengaduan());
    }
}
