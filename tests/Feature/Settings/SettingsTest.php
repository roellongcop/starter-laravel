<?php

use App\Mail\TestMail;
use App\Providers\AppServiceProvider;
use App\Settings\EmailSettings;
use App\Settings\SystemSettings;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('renders all settings groups', function (): void {
    actingAsRole('developer');

    $this->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('settings.system')
            ->has('settings.email')
            ->has('settings.image'));
});

it('persists a system settings update', function (): void {
    actingAsRole('developer');

    $this->put(route('settings.update', 'system'), [
        'app_name' => 'Renamed App',
        'timezone' => 'UTC',
        'pagination_size' => 30,
        'auto_logout_seconds' => 0,
        'enable_visitor' => true,
        'whitelist_ip_only' => false,
        'default_theme' => 'dark',
    ])->assertRedirect();

    $system = app(SystemSettings::class);
    expect($system->app_name)->toBe('Renamed App')
        ->and($system->pagination_size)->toBe(30)
        ->and($system->default_theme)->toBe('dark');
});

it('applies stored timezone and pagination_size to config at boot', function (): void {
    $system = app(SystemSettings::class);
    $system->timezone = 'Asia/Manila';
    $system->pagination_size = 7;
    $system->save();

    // The provider captures settings at boot, before this test changed them, so
    // re-run boot() to pick up the freshly saved values.
    (new AppServiceProvider(app()))->boot();

    expect(config('app.timezone'))->toBe('Asia/Manila')
        ->and(config('keen.pagination_size'))->toBe(7);
});

it('applies stored email settings to the smtp mailer when smtp is active', function (): void {
    $email = app(EmailSettings::class);
    $email->from_address = 'sender@keen.test';
    $email->from_name = 'Keen Sender';
    $email->smtp_host = 'smtp.keen.test';
    $email->smtp_port = 2525;
    $email->smtp_username = 'mailer';
    $email->smtp_password = 's3cret';
    $email->smtp_encryption = 'ssl';
    $email->save();

    // Boot only applies email settings under the smtp transport; the test env
    // default is `array`, so flip it then re-run boot to pick up the saved values.
    config(['mail.default' => 'smtp']);
    (new AppServiceProvider(app()))->boot();

    expect(config('mail.from.address'))->toBe('sender@keen.test')
        ->and(config('mail.from.name'))->toBe('Keen Sender')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.keen.test')
        ->and(config('mail.mailers.smtp.port'))->toBe(2525)
        ->and(config('mail.mailers.smtp.username'))->toBe('mailer')
        ->and(config('mail.mailers.smtp.password'))->toBe('s3cret')
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtps'); // ssl → smtps
});

it('keeps the env smtp host when the email tab host is unset (override dormant)', function (): void {
    $envHost = config('mail.mailers.smtp.host');

    $email = app(EmailSettings::class);
    $email->smtp_host = '';                 // not configured — env is the base layer
    $email->from_address = 'sender@keen.test';
    $email->save();

    config(['mail.default' => 'smtp']);
    (new AppServiceProvider(app()))->boot();

    // Host/port stay on env; only the explicitly-set from identity is applied.
    expect(config('mail.mailers.smtp.host'))->toBe($envHost)
        ->and(config('mail.from.address'))->toBe('sender@keen.test');
});

it('leaves the mail config untouched when the active transport is not smtp', function (): void {
    $envHost = config('mail.mailers.smtp.host');

    $email = app(EmailSettings::class);
    $email->smtp_host = 'should-not-apply.test';
    $email->from_address = 'nope@keen.test';
    $email->save();

    // Test env mailer is `array` (not smtp), so the override must be skipped.
    (new AppServiceProvider(app()))->boot();

    expect(config('mail.mailers.smtp.host'))->toBe($envHost)
        ->and(config('mail.mailers.smtp.host'))->not->toBe('should-not-apply.test');
});

it('exposes auto_logout_seconds in the shared inertia props', function (): void {
    actingAsRole('developer');

    $system = app(SystemSettings::class);
    $system->auto_logout_seconds = 120;
    $system->save();

    $this->get(route('settings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('settings.system.auto_logout_seconds', 120));
});

it('sends a test email to the current user from the email tab', function (): void {
    Mail::fake();
    $user = actingAsRole('developer');

    $this->post(route('settings.email.test'))
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(TestMail::class, fn (TestMail $mail) => $mail->hasTo($user->email));
});

it('forbids sending a test email without the settings.update permission', function (): void {
    Mail::fake();
    actingAsRole('admin'); // has settings.index, not settings.update

    $this->post(route('settings.email.test'))->assertForbidden();

    Mail::assertNothingSent();
});

it('validates settings input', function (): void {
    actingAsRole('developer');

    $this->put(route('settings.update', 'system'), [
        'app_name' => '',
        'timezone' => 'Not/AZone',
        'pagination_size' => 0,
        'auto_logout_seconds' => -5,
        'enable_visitor' => true,
        'whitelist_ip_only' => false,
        'default_theme' => 'neon',
    ])->assertSessionHasErrors(['app_name', 'timezone', 'pagination_size', 'auto_logout_seconds', 'default_theme']);
});

it('forbids updating settings without the permission', function (): void {
    // admin has settings.index but not settings.update.
    actingAsRole('admin');

    $this->get(route('settings.index'))->assertOk();
    $this->put(route('settings.update', 'system'), [
        'app_name' => 'Nope',
        'timezone' => 'UTC',
        'pagination_size' => 20,
        'auto_logout_seconds' => 0,
        'enable_visitor' => true,
        'whitelist_ip_only' => false,
        'default_theme' => 'system',
    ])->assertForbidden();
});
