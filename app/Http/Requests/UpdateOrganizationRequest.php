<?php

namespace App\Http\Requests;

class UpdateOrganizationRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'point_of_contact' => ['nullable', 'string', 'exists:users,token'],
        ];
    }
}
