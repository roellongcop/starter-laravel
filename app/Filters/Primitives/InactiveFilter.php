<?php

namespace App\Filters\Primitives;

use App\Policies\BasePolicy;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Show only inactive (record_status = 0) rows, gated by the view-inactive
 * permission. The echo-back reports the *effective* (requested AND permitted)
 * boolean, so a non-permitted user passing ?inactive=1 sees inactive: false.
 */
class InactiveFilter extends AbstractFilter
{
    public function __construct(
        protected string $key = 'inactive',
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        // @phpstan-ignore-next-line onlyInactive() is provided by HasRecordStatus
        $query->onlyInactive();

        return $next($query);
    }

    public function isActive(): bool
    {
        return $this->effective();
    }

    /**
     * @return array<string, bool>
     */
    public function applied(): array
    {
        return [$this->key => $this->effective()];
    }

    /** Requested via the param AND allowed by the view-inactive permission. */
    protected function effective(): bool
    {
        return $this->request->boolean($this->key)
            && (bool) $this->request->user()?->can(BasePolicy::VIEW_INACTIVE);
    }
}
