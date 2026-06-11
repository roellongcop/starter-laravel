<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasRecordStatus;
use App\Models\Concerns\HasToken;
use App\Models\Concerns\IsResource;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property string $token
 * @property string|null $username
 * @property string|null $password_hint
 * @property string|null $avatar
 * @property int|null $avatar_file_id
 * @property-read string|null $avatar_url
 */
class User extends Authenticatable implements Auditable, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use AuditableTrait, Blameable, HasApiTokens, HasFactory, HasRecordStatus, HasRoles, HasToken, IsResource, Notifiable;

    /**
     * Explicit table name (the model lives outside the BaseModel hierarchy).
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
        'avatar_file_id',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
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

    /**
     * The File whose media backs this user's avatar.
     *
     * @return BelongsTo<File, $this>
     */
    public function avatarFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'avatar_file_id');
    }

    /**
     * Resolved avatar URL for the frontend: the gated stream route when an
     * avatar file is set, else the legacy `avatar` string if it's a URL, else
     * null (the UI falls back to initials).
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->avatar_file_id !== null) {
                // The `v` token busts the browser + Glide cache whenever the
                // photo changes: the avatar route binds the user (stable token),
                // so we tag it with the avatar File's own token, which changes on
                // every re-upload (a new File row = a new token).
                $file = $this->avatarFile;
                $version = $file !== null ? $file->token : $this->avatar_file_id;

                return route('profile.avatar', ['user' => $this, 'v' => $version]);
            }

            if (is_string($this->avatar) && str_starts_with($this->avatar, 'http')) {
                return $this->avatar;
            }

            return null;
        });
    }
}
