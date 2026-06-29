<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One durably-stored chunk of an UploadSession. The unique (upload_session_id,
 * part_number) makes a retried chunk idempotent; `etag` is the S3 part ETag that
 * completeMultipartUpload needs (null on the local driver). A plain detail table,
 * not a routed resource, so it skips the BaseModel audit/token plumbing.
 *
 * @property int $id
 * @property int $upload_session_id
 * @property int $part_number
 * @property string|null $etag
 * @property int $size
 */
class UploadSessionPart extends Model
{
    protected $fillable = [
        'upload_session_id',
        'part_number',
        'etag',
        'size',
    ];

    /**
     * @return BelongsTo<UploadSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(UploadSession::class, 'upload_session_id');
    }
}
