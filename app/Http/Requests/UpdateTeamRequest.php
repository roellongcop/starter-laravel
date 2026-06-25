<?php

namespace App\Http\Requests;

use App\Models\Organization;
use App\Models\Team;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = Organization::where('token', $this->input('organization'))->value('id');

        /** @var Team|null $team */
        $team = $this->route('team');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                )->ignore($team?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'organization' => ['required', 'string', 'exists:organizations,token'],
            // The category must exist AND belong to the chosen organization.
            'team_category' => [
                'required',
                'string',
                Rule::exists('team_categories', 'token')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            // The role must exist AND belong to the chosen organization.
            'organization_role' => [
                'required',
                'string',
                Rule::exists('organization_roles', 'token')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'members' => ['nullable', 'array'],
            'members.*' => ['string', 'exists:users,token'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This organization already has a team with that name.',
            'team_category.exists' => 'Select a category that belongs to the chosen organization.',
            'organization_role.exists' => 'Select a role that belongs to the chosen organization.',
        ];
    }
}
