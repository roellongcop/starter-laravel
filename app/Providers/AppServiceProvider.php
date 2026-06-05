<?php

namespace App\Providers;

use App\Enums\RecordStatus;
use App\Enums\SystemRole;
use App\Models\User;
use App\Settings\EmailSettings;
use App\Settings\SystemSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->registerAuditColumnsMacro();

        $this->applySystemSettings();
        $this->applyEmailSettings();

        // God mode: the developer role bypasses every permission check. Returning
        // null (not false) for everyone else lets normal gate resolution proceed.
        Gate::before(function (User $user): ?bool {
            return $user->hasRole(SystemRole::Developer->value) ? true : null;
        });
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
     * The audit footer every domain table carries. Call after $table->id():
     *
     *     Schema::create('things', function (Blueprint $table) {
     *         $table->id();
     *         $table->string('name');
     *         $table->auditColumns();
     *     });
     *
     * created_by/updated_by are logical FKs to users (null = system); no hard
     * constraint is added to keep migration ordering and self-references simple.
     */
    protected function registerAuditColumnsMacro(): void
    {
        Blueprint::macro('auditColumns', function (): void {
            /** @var Blueprint $this */
            $this->unsignedTinyInteger('record_status')->default(RecordStatus::Active->value);
            $this->unsignedBigInteger('created_by')->nullable();
            $this->unsignedBigInteger('updated_by')->nullable();
            $this->timestamps();

            $this->index(['created_at', 'id']); // keyset (cursor) pagination
            $this->index('record_status');
            $this->index('created_by');
            $this->index('updated_by');
        });
    }
}
