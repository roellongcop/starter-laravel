<?php

namespace App\Http\Requests;

use App\Models\Organization;
use App\Models\OrganizationRole;
use Illuminate\Validation\Rule;

class UpdateOrganizationRoleRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = Organization::where('token', $this->input('organization'))->value('id');

        /** @var OrganizationRole|null $role */
        $role = $this->route('organization_role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organization_roles', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                )->ignore($role?->id),
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
            'name.unique' => 'This organization already has a role with that name.',
        ];
    }
}
