<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $target = route('dashboard', absolute: false).'?verified=1';

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($target)->with('hint', 'Your email address is already verified.');
        }

        if (! $request->user()->markEmailAsVerified()) {
            return redirect()->intended($target)->with('error', 'We could not verify your email. Please request a new link.');
        }

        event(new Verified($request->user()));

        return redirect()->intended($target)->with('success', 'Your email address has been verified.');
    }
}
