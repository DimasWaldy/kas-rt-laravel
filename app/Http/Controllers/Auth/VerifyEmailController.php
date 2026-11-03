<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Old signed links no longer change the account or email verification state.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
