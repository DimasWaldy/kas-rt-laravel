<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Retired verification endpoint: never send a verification email.
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
