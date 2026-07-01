<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class StoreRequirementRequest extends BaseFormRequest
{
    /**
     * Tokens cross the wire (never ids); the controller resolves the reference
     * file / form / tags and re-scopes them to the owning task's organization.
     * project_id/milestone_id/organization_id are derived from the task, never
     * accepted from the client. New requirements always start Pending, so status
     * is not accepted here (it changes inline like a task's).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'minimum_files' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'maximum_files' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'reference_file' => ['nullable', 'string', 'exists:reference_files,token'],
            'form' => ['nullable', 'string', 'exists:forms,token'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'exists:data_tags,token'],
        ];
    }

    /**
     * Enforce maximum >= minimum only when both bounds are given — a cross-field
     * `gte` rule misbehaves when the compared field is absent.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $min = $this->input('minimum_files');
            $max = $this->input('maximum_files');

            if (is_numeric($min) && is_numeric($max) && (int) $max < (int) $min) {
                $v->errors()->add('maximum_files', 'The maximum files must be greater than or equal to the minimum files.');
            }
        });
    }
}
