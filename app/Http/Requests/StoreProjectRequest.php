<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = Organization::where('token', $this->input('organization'))->value('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'private' => ['required', 'boolean'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'organization' => ['required', 'string', 'exists:organizations,token'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'exists:data_tags,token'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This organization already has a project with that name.',
        ];
    }
}
