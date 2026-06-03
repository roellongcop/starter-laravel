<?php

namespace App\Http\Requests;

class UpdateThemeRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['boolean'],
            'tokens' => ['array'],
            'tokens.light' => ['array'],
            'tokens.light.*' => ['nullable', 'string', 'max:64'],
            'tokens.dark' => ['array'],
            'tokens.dark.*' => ['nullable', 'string', 'max:64'],
        ];
    }
}
