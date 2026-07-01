<?php

namespace App\Http\Requests;

/**
 * Same shape as StoreRequirementRequest — a requirement stays attached to its
 * task (task_id is never reassigned), and status changes inline, so the editable
 * fields are identical on create and update.
 */
class UpdateRequirementRequest extends StoreRequirementRequest {}
