<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureInitialDomicile
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user?->onboarding_domisili || $request->routeIs('logout', 'pendaftaran.show', 'pendaftaran.resubmit')) {
            return $next($request);
        }

        abort_unless($user->isAktif() && $user->warga?->status_verifikasi === 'terverifikasi', 403, 'Akun masih menunggu verifikasi pengurus.');

        if (! $request->routeIs('domisili.*')) {
            return redirect()->route('domisili.create');
        }

        return $next($request);
    }
}
