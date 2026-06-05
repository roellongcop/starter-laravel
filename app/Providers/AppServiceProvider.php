<?php

namespace App\Providers;

use App\Enums\RecordStatus;
use App\Models\User;
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

        // God mode: the developer role bypasses every permission check. Returning
        // null (not false) for everyone else lets normal gate resolution proceed.
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('developer') ? true : null;
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
