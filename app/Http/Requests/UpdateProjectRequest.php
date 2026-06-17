<?php

namespace App\Http\Requests;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = Organization::where('token', $this->input('organization'))->value('id');

        /** @var Project|null $project */
        $project = $this->route('project');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                )->ignore($project?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'private' => ['required', 'boolean'],
            'organization' => ['required', 'string', 'exists:organizations,token'],
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
