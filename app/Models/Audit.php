<?php

namespace App\Models;

use App\Models\Concerns\HasToken;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Jenssegers\Agent\Agent;
use OwenIt\Auditing\Models\Audit as BaseAudit;

/**
 * The owen-it audit row, enriched with browser/os/device parsed from the stored
 * user_agent (jenssegers/agent) for the read-only Logs views. Configured as the
 * audit implementation (config/audit.php) so HasToken fills `token` on every write.
 *
 * @property int $id
 * @property string $token
 * @property string $event
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $url
 * @property string|null $referrer
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $tags
 * @property Carbon|null $created_at
 * @property-read User|null $user
 * @property-read string $browser
 * @property-read string $os
 * @property-read string $device
 */
class Audit extends BaseAudit
{
    use HasToken;

    /**
     * @return MorphTo<Model, $this>
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    protected function makeAgent(): Agent
    {
        $agent = new Agent;
        $agent->setUserAgent((string) $this->user_agent);

        return $agent;
    }

    protected function browser(): Attribute
    {
        return Attribute::get(fn (): string => $this->makeAgent()->browser() ?: 'Unknown');
    }

    protected function os(): Attribute
    {
        return Attribute::get(fn (): string => $this->makeAgent()->platform() ?: 'Unknown');
    }

    protected function device(): Attribute
    {
        return Attribute::get(function (): string {
            $agent = $this->makeAgent();

            return $agent->isTablet() ? 'Tablet' : ($agent->isPhone() ? 'Mobile' : 'Desktop');
        });
    }
}
