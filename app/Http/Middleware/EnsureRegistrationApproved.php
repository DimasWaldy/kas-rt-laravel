<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRegistrationApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->status_akun, ['pending_verifikasi', 'ditolak'], true)
            || $request->routeIs('pendaftaran.show', 'pendaftaran.resubmit', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson() || ! $request->isMethodSafe()) {
            abort(403, 'Pendaftaran belum disetujui. Buka halaman status pendaftaran.');
        }

        return redirect()->route('pendaftaran.show');
    }
}
