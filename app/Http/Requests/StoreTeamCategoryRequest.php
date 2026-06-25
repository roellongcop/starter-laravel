<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Validation\Rule;

class StoreTeamCategoryRequest extends BaseFormRequest
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
                Rule::unique('team_categories', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'organization' => ['required', 'string', 'exists:organizations,token'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This organization already has a category with that name.',
        ];
    }
}
