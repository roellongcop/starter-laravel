<?php

namespace App\Policies;

use App\Models\User;

/**
 * Permission-based policy skeleton: concrete policies declare a resource key()
 * and each ability checks "{key}.{action}" via $user->can().
 * See docs/features/users-roles-permissions.md.
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
