<?php

namespace App\Http\Requests;

class UpdateProjectRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'private' => ['required', 'boolean'],
            'organization' => ['required', 'string', 'exists:organizations,token'],
        ];
    }
}
