<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    /**
     * Keep old bookmarks working without requiring email verification.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->onboarding_domisili ? 'domisili.create' : 'dashboard');
    }
}
