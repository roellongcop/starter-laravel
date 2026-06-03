<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Show the public contact form.
     */
    public function create(): Response
    {
        return Inertia::render('Contact');
    }

    /**
     * Handle a contact submission. For the starter this records the message to
     * the log channel; wiring it to EmailSettings/Mail is a Phase 4 concern.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Log::channel('stack')->info('Contact form submission', $data);

        return back()->with('success', 'Thanks for reaching out — we will be in touch.');
    }
}
