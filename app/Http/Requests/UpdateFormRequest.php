<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class UpdateFormRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'organization' => ['required', 'string', 'exists:organizations,token'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'exists:data_tags,token'],
            ...$this->formFieldsRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateFormFieldConfigs($validator);
    }
}
