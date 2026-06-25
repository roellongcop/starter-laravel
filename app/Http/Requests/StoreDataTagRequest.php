<?php

namespace App\Http\Requests;

use App\Models\DataTag;
use App\Models\Organization;
use Illuminate\Validation\Rule;

class StoreDataTagRequest extends BaseFormRequest
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
                Rule::unique('data_tags', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'organization' => ['required', 'string', 'exists:organizations,token'],
            'color' => ['required', 'string', Rule::in(DataTag::COLORS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This organization already has a tag with that name.',
            'color.in' => 'Choose a colour from the palette.',
        ];
    }
}
