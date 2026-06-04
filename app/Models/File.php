<?php

namespace App\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * An uploaded file. The binary lives in medialibrary on the private `uploads`
 * disk; the columns below denormalize the stored Media's metadata so lists and
 * search need no media join. Downloads go only through gated controller actions.
 *
 * @property string $token
 * @property int|null $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class File extends BaseModel implements HasMedia
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public const COLLECTION = 'file';

    protected $fillable = [
        'original_name',
        'extension',
        'mime',
        'size',
        'disk',
        'path',
        'owner_id',
        'tag',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Gated, on-demand resized URL for this image at the given width. The route
     * binds File by its token (HasToken), so the unguessable, never-reused token
     * sits in the path and doubles as the `immutable`-cache buster — a re-upload
     * is a new row with a new token, hence a new URL.
     */
    public function imageUrl(int $width): string
    {
        return route('media.img', [
            'file' => $this->getRouteKey(),
            'w' => $width,
        ]);
    }

    /**
     * @param  Builder<File>  $query
     */
    public function scopeImages(Builder $query): void
    {
        $query->where('mime', 'like', 'image/%');
    }

    /**
     * @param  Builder<File>  $query
     */
    public function scopeOwnedBy(Builder $query, int $ownerId): void
    {
        $query->where('owner_id', $ownerId);
    }

    /**
     * @param  Builder<File>  $query
     */
    public function scopeDocuments(Builder $query): void
    {
        $query->whereIn('extension', config('keen.document_extensions', ['pdf', 'doc', 'docx']));
    }
}
