<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Settings\EmailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
     * Handle a contact submission: log it (audit), then mail it to the site inbox
     * (EmailSettings::from_address) with Reply-To set to the visitor. Sending uses
     * the configured mailer; a failure is logged but never shown to the visitor.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Log::channel('stack')->info('Contact form submission', $data);

        try {
            $recipient = app(EmailSettings::class)->from_address;

            Mail::to($recipient)->send(
                new ContactMessage($data['name'], $data['email'], $data['message']),
            );
        } catch (\Throwable $e) {
            // Don't surface transport errors to the visitor — the message is logged above.
            Log::channel('stack')->error('Contact mail send failed', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Thanks for reaching out — we will be in touch.');
    }
}
