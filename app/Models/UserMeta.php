<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Arbitrary per-user custom fields (key/value). Managed inline through the user
 * form. Table: tbl_user_meta.
 */
class UserMeta extends BaseModel
{
    // "meta" reads as a mass noun; pin the table rather than auto-pluralizing to
    // tbl_user_metas.
    protected $table = 'tbl_user_meta';

    protected $fillable = ['user_id', 'key', 'value'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
