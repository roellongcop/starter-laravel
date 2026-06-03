<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasRecordStatus;
use App\Models\Concerns\IsResource;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property UserStatus $user_status
 * @property RecordStatus $record_status
 * @property string|null $username
 * @property string|null $password_hint
 * @property string|null $avatar
 */
class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use AuditableTrait, Blameable, HasApiTokens, HasFactory, HasRecordStatus, HasRoles, IsResource, Notifiable;

    /**
     * Framework/sanctum/permission tables reference `users`, so this model opts
     * out of the tbl_ prefix.
     */
    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_hint',
        'username',
        'user_status',
        'avatar',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_status' => UserStatus::class,
            'record_status' => RecordStatus::class,
        ];
    }

    /**
     * Arbitrary per-user custom fields.
     *
     * @return HasMany<UserMeta, $this>
     */
    public function meta(): HasMany
    {
        return $this->hasMany(UserMeta::class);
    }
}
