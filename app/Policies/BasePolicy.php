<?php

namespace App\Policies;

use App\Models\User;

/**
 * Permission-based policy skeleton. Concrete policies declare the resource's
 * permission key (e.g. "users"); each ability maps to a declared permission like
 * "users.index" / "users.update" and is checked via spatie/laravel-permission's
 * $user->can(). The permission registry + roles are seeded in Phase 2.
 *
 * Authorization lives here and in route `can:` middleware — never in model events.
 */
abstract class BasePolicy
{
    /** Global ability to see inactive (record_status = 0) rows. */
    public const VIEW_INACTIVE = 'view-inactive';

    /** Resource permission key, e.g. "users". */
    abstract protected function key(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->ability('index'));
    }

    public function view(User $user): bool
    {
        return $user->can($this->ability('show'));
    }

    public function create(User $user): bool
    {
        return $user->can($this->ability('create'));
    }

    public function update(User $user): bool
    {
        return $user->can($this->ability('update'));
    }

    public function delete(User $user): bool
    {
        return $user->can($this->ability('delete'));
    }

    public function viewInactive(User $user): bool
    {
        return $user->can(self::VIEW_INACTIVE);
    }

    protected function ability(string $action): string
    {
        return "{$this->key()}.{$action}";
    }
}
