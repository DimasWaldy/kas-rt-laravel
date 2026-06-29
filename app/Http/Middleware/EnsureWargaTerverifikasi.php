<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWargaTerverifikasi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role_name !== 'warga') {
            return $next($request);
        }

        if ($user->isAktif()) {
            return $next($request);
        }

        $message = match ($user->status_akun) {
            'pending_verifikasi' => 'Akun Anda sedang menunggu verifikasi RT.',
            'ditolak' => 'Verifikasi akun Anda ditolak. Silakan hubungi RT untuk klarifikasi.',
            'nonaktif' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi RT untuk informasi lebih lanjut.',
            default => 'Akun Anda belum aktif. Silakan hubungi RT untuk informasi lebih lanjut.',
        };

        return redirect()
            ->route('verifikasi.menunggu')
            ->with('error', $message);
    }
}
