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
            || $request->routeIs('dashboard', 'pendaftaran.show', 'pendaftaran.resubmit', 'logout')
            || ($user->status_akun === 'pending_verifikasi' && $request->routeIs('profile.edit', 'profile.update'))) {
            return $next($request);
        }

        if ($request->expectsJson() || ! $request->isMethodSafe()) {
            abort(403, 'Pendaftaran belum disetujui.');
        }

        return redirect()->route('dashboard');
    }
}
