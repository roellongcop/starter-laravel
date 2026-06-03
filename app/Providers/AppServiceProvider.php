<?php

namespace App\Providers;

use App\Enums\RecordStatus;
use App\Models\User;
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

        // God mode: the developer role bypasses every permission check. Returning
        // null (not false) for everyone else lets normal gate resolution proceed.
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('developer') ? true : null;
        });
    }

    /**
     * The audit footer every domain table carries. Call after $table->id():
     *
     *     Schema::create('tbl_things', function (Blueprint $table) {
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
