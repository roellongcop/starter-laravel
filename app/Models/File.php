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
     * Content-unique cache-bust token for image URLs. Keyed on the random
     * storage-path basename (never reused, even after migrate:fresh) so an
     * `immutable`-cached derivative is never served for a different file that
     * happened to reuse an id. Falls back to the id when the path is unset.
     */
    public function cacheVersion(): string
    {
        return $this->path !== null
            ? pathinfo($this->path, PATHINFO_FILENAME)
            : (string) $this->id;
    }

    /** Gated, on-demand resized URL for this image at the given width. */
    public function imageUrl(int $width): string
    {
        return route('media.img', [
            'file' => $this->id,
            'w' => $width,
            'v' => $this->cacheVersion(),
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
