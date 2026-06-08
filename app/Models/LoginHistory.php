<?php

namespace App\Models;

use App\Enums\AuthEvent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Append-only record of authentication events (login / logout) with the IP and
 * user agent of the request. Written by the web auth event listeners (see
 * AppServiceProvider::boot) and the stateless mobile API (Api\V1\AuthController);
 * read-only in the UI. Not a BaseModel resource — no token/record-status/auditing
 * (it *is* an audit trail).
 *
 * @property int $id
 * @property int|null $user_id
 * @property AuthEvent $event
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 */
class LoginHistory extends Model
{
    use HasFactory;

    protected $table = 'login_history';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'event',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => AuthEvent::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an auth event. Fails open — a logging error never breaks the
     * authentication request it is observing (same spirit as the old
     * TrackVisitor middleware).
     */
    public static function record(?Authenticatable $user, AuthEvent $event, ?string $ip, ?string $userAgent): void
    {
        try {
            static::create([
                'user_id' => $user?->getAuthIdentifier(),
                'event' => $event,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record login history.', ['exception' => $e]);
        }
    }
}
