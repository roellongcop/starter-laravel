<?php

namespace App\Http\Requests;

class StoreTaskRequest extends BaseFormRequest
{
    /**
     * Tokens cross the wire (never ids); the controller resolves them and re-scopes
     * the milestone/reference-file and the assignee/approver/observer (each a Team
     * or Person token) to this project + asset's organization.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'milestone' => ['required', 'string', 'exists:milestones,token'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'approver' => ['nullable', 'string', 'max:255'],
            'observer' => ['nullable', 'string', 'max:255'],
            'private' => ['boolean'],
            'due_date' => ['nullable', 'date'],
            'reference_file' => ['nullable', 'string', 'exists:reference_files,token'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'exists:data_tags,token'],
        ];
    }
}
