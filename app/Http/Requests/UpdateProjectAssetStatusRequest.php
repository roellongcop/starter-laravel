<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Validation\Rule;

class UpdateProjectAssetStatusRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ];
    }
}
