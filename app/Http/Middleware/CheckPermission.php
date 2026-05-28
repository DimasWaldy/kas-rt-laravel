<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! Auth::check()) {
            abort(403, 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        }

        $user = Auth::user();

        $allowed = match ($permission) {
            'admin-only' => $user->isAdmin() || $user->hasPermission('admin-only'),
            'manage-finance' => $user->canManageFinance(),
            'manage-warga' => $user->canManageWarga(),
            'manage-pengaduan' => $user->canManagePengaduan(),
            default => $user->hasPermission($permission),
        };

        if (! $allowed) {
            abort(403, $this->messageFor($permission));
        }

        return $next($request);
    }

    private function messageFor(string $permission): string
    {
        return match ($permission) {
            'admin-only' => 'Halaman ini hanya dapat diakses oleh admin.',
            'manage-finance' => 'Halaman ini hanya dapat diakses oleh admin atau bendahara.',
            'manage-warga' => 'Halaman ini hanya dapat diakses oleh admin atau sekretaris.',
            'manage-pengaduan' => 'Halaman ini hanya dapat diakses oleh admin atau sekretaris.',
            default => 'Anda tidak memiliki hak akses untuk membuka halaman ini.',
        };
    }
}
