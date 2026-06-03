<?php

namespace Tests\Fixtures;

use App\Models\BaseModel;

/**
 * Test-only concrete model for exercising BaseModel plumbing (table prefix,
 * record-status scopes, blameable, auditing). Its `widgets` table is created
 * in the foundation tests' setup, not by a real migration.
 */
class Widget extends BaseModel
{
    protected $fillable = ['name', 'record_status', 'created_by', 'updated_by'];
}
