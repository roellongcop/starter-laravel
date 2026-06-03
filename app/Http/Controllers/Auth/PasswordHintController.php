<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordHintController extends Controller
{
    /**
     * Return the password hint for an email as a memory aid on the
     * forgot-password screen. A generic message is flashed when no hint exists,
     * to avoid confirming whether an account is registered.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->string('email'))->first();
        $hint = $user?->password_hint;

        return back()->with(
            'hint',
            $hint ?: 'No password hint is available for that email address.',
        );
    }
}
