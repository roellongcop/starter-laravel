<?php

namespace App\Providers;

use App\Enums\AuthEvent;
use App\Enums\RecordStatus;
use App\Enums\SystemRole;
use App\Models\LoginHistory;
use App\Models\Person;
use App\Models\Team;
use App\Models\User;
use App\Settings\EmailSettings;
use App\Settings\SystemSettings;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope is a dev-only dependency (composer --dev + dont-discover), so
        // register it by hand and only in local — the classes don't exist in a
        // production install built with --no-dev.
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Surface N+1s as exceptions outside production so a missing with()/load()
        // fails a test or dev request instead of silently shipping. No-op in prod.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Stable, readable polymorphic type aliases (e.g. tasks.assignee_type),
        // so the DB stores 'team'/'person' rather than fully-qualified class names.
        Relation::morphMap([
            'team' => Team::class,
            'person' => Person::class,
        ]);

        // Behind an HTTPS proxy/tunnel nginx is reached over plain HTTP,
        // so generated asset/route URLs would be http:// and the browser blocks them as mixed content.
        // Force https when APP_URL is https; harmless for local http dev.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        $this->registerAuditColumnsMacro();

        $this->applySystemSettings();
        $this->applyEmailSettings();

        // God mode: the developer role bypasses every permission check. Returning
        // null (not false) for everyone else lets normal gate resolution proceed.
        Gate::before(function (User $user): ?bool {
            return $user->hasRole(SystemRole::Developer->value) ? true : null;
        });

        // Pulse dashboard (/pulse) is developer-only, matching the Telescope gate.
        Gate::define('viewPulse', fn (User $user): bool => $user->hasRole(SystemRole::Developer->value));

        // Record session (web) sign-ins/outs to login_history. The stateless API
        // never fires these events, so it records its own (see Api\V1\AuthController).
        Event::listen(Login::class, fn (Login $e) => LoginHistory::record($e->user, AuthEvent::Login, request()->ip(), request()->userAgent()));
        Event::listen(Logout::class, fn (Logout $e) => LoginHistory::record($e->user, AuthEvent::Logout, request()->ip(), request()->userAgent()));
    }

    /**
     * Apply stored SystemSettings that override static config at runtime.
     *
     * Runs once per request after config is loaded but before any controller or
     * middleware. Wrapped defensively because the settings table may not exist
     * yet during early migrations (same pattern as
     * HandleInertiaRequests::appSettings()).
     */
    protected function applySystemSettings(): void
    {
        try {
            $settings = app(SystemSettings::class);

            if ($settings->timezone !== '') {
                config(['app.timezone' => $settings->timezone]);
                date_default_timezone_set($settings->timezone); // config() alone won't update PHP's tz
            }

            if ($settings->pagination_size > 0) {
                config(['keen.pagination_size' => $settings->pagination_size]);
            }
        } catch (\Throwable) {
            // Settings table absent (early migrations) — config-file defaults stand.
        }
    }

    /**
     * Apply stored EmailSettings onto the SMTP mailer config — but only when SMTP
     * is the active transport. In dev (MAIL_MAILER=log/array) the env mail config
     * is left untouched; set MAIL_MAILER=smtp in prod to let these settings drive
     * mail. Same defensive try/catch as applySystemSettings().
     */
    protected function applyEmailSettings(): void
    {
        if (config('mail.default') !== 'smtp') {
            return;
        }

        try {
            $email = app(EmailSettings::class);

            if ($email->from_address !== '') {
                config([
                    'mail.from.address' => $email->from_address,
                    'mail.from.name' => $email->from_name,
                ]);
            }

            if ($email->smtp_host !== '') {
                config([
                    'mail.mailers.smtp.host' => $email->smtp_host,
                    'mail.mailers.smtp.port' => $email->smtp_port,
                ]);
            }

            // Only override credentials when set, so empty DB defaults don't clobber env.
            if (filled($email->smtp_username)) {
                config(['mail.mailers.smtp.username' => $email->smtp_username]);
            }

            if (filled($email->smtp_password)) {
                config(['mail.mailers.smtp.password' => $email->smtp_password]);
            }

            // smtp_encryption (tls|ssl) → Symfony scheme: ssl = implicit TLS (smtps),
            // tls = STARTTLS (smtp). config/mail.php's smtp mailer uses a `scheme` key.
            if (filled($email->smtp_encryption)) {
                config([
                    'mail.mailers.smtp.scheme' => $email->smtp_encryption === 'ssl' ? 'smtps' : 'smtp',
                ]);
            }
        } catch (\Throwable) {
            // Settings table absent (early migrations) — env mail config stands.
        }
    }

    /**
     * The audit footer every domain table carries — call after $table->id().
     * Adds record_status/created_by/updated_by/timestamps + the composite keyset
     * index. See docs/conventions/backend.md ("The audit footer").
     */
    protected function registerAuditColumnsMacro(): void
    {
        Blueprint::macro('auditColumns', function (): void {
            /** @var Blueprint $this */
            $this->unsignedTinyInteger('record_status')->default(RecordStatus::Active->value);
            $this->unsignedBigInteger('created_by')->nullable();
            $this->unsignedBigInteger('updated_by')->nullable();
            $this->timestamps();

            // Composite keyset index for the active-scope list pattern (filter
            // record_status, order created_at/id). See docs/conventions/backend.md.
            $this->index(['record_status', 'created_at', 'id']);
            $this->index('created_by');
            $this->index('updated_by');
        });
    }
}
