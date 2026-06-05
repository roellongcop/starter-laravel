<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Mail\TestMail;
use App\Settings\EmailSettings;
use App\Settings\ImageSettings;
use App\Settings\NotificationSettings;
use App\Settings\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelSettings\Settings;

class SettingsController extends Controller
{
    /** group => settings class */
    protected const GROUPS = [
        'system' => SystemSettings::class,
        'email' => EmailSettings::class,
        'image' => ImageSettings::class,
        'notification' => NotificationSettings::class,
    ];

    public function index(): Response
    {
        $this->authorize('settings.index');

        return Inertia::render('Settings/Index', [
            'settings' => [
                'system' => app(SystemSettings::class)->toArray(),
                'email' => $this->emailForDisplay(),
                'image' => app(ImageSettings::class)->toArray(),
                'notification' => app(NotificationSettings::class)->toArray(),
            ],
            'can' => ['update' => request()->user()->can('settings.update')],
        ]);
    }

    public function update(SettingsRequest $request, string $group): RedirectResponse
    {
        $this->authorize('settings.update');

        $class = self::GROUPS[$group] ?? throw ValidationException::withMessages([
            'group' => 'Unknown settings group.',
        ]);

        /** @var Settings $settings */
        $settings = app($class);
        $data = $request->validated();

        // Keep the existing SMTP password when the field is left blank.
        if ($group === 'email' && blank($data['smtp_password'] ?? null)) {
            unset($data['smtp_password']);
        }

        $settings->fill($data)->save();

        return back()->with('success', ucfirst($group).' settings saved.');
    }

    /**
     * Send a test email to the current user using the configured mailer, so an
     * admin can confirm the saved SMTP settings actually deliver. Tests the
     * *saved* settings (applied to config at boot) — save before testing.
     */
    public function testEmail(): RedirectResponse
    {
        $this->authorize('settings.update');

        $user = request()->user();

        try {
            Mail::to($user->email)->send(new TestMail);

            return back()->with('success', 'Test email sent to '.$user->email.'.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email failed: '.$e->getMessage());
        }
    }

    /**
     * Email settings minus the secret value (never sent to the browser).
     *
     * @return array<string, mixed>
     */
    protected function emailForDisplay(): array
    {
        $data = app(EmailSettings::class)->toArray();
        $data['smtp_password'] = '';

        return $data;
    }
}
