<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends BaseFormRequest
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
                Rule::unique('assets', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'id_code' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
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
            'name.unique' => 'This organization already has an asset with that name.',
        ];
    }
}
