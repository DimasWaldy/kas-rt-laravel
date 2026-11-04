<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureInitialDomicile
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user?->onboarding_domisili) {
            return $next($request);
        }

        if ($request->routeIs('dashboard', 'logout', 'pendaftaran.show', 'pendaftaran.resubmit', 'domisili.store', 'profile.edit', 'profile.update')) {
            if ($request->routeIs('domisili.store')) {
                abort_unless($user->isAktif() && $user->warga?->status_verifikasi === 'terverifikasi', 403, 'Akun masih menunggu verifikasi pengurus.');
            }

            return $next($request);
        }

        if ($request->expectsJson() || ! $request->isMethodSafe()) {
            abort(403, 'Lengkapi dan tunggu persetujuan domisili terlebih dahulu.');
        }

        return redirect()->route('dashboard');
    }
}
