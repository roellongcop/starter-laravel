<?php

namespace App\Http\Requests;

use App\Models\Organization;
use App\Models\ReferenceFile;
use Illuminate\Validation\Rule;

class UpdateReferenceFileRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = Organization::where('token', $this->input('organization'))->value('id');

        /** @var ReferenceFile|null $referenceFile */
        $referenceFile = $this->route('reference_file');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reference_files', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                )->ignore($referenceFile?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'organization' => ['required', 'string', 'exists:organizations,token'],
            'file_token' => ['nullable', 'string', 'exists:files,token'],
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
            'name.unique' => 'This organization already has a reference with that name.',
        ];
    }
}
