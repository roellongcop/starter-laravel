<?php

namespace App\Http\Requests;

class UpdateTaskRequest extends BaseFormRequest
{
    /**
     * Tokens cross the wire (never ids); the controller resolves them and re-scopes
     * the milestone/users/reference-file to this project + asset's organization. A
     * task may move milestones on update, so `milestone` stays required.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'milestone' => ['required', 'string', 'exists:milestones,token'],
            'assigned_to' => ['nullable', 'string', 'exists:users,token'],
            'approver' => ['nullable', 'string', 'exists:users,token'],
            'observer' => ['nullable', 'string', 'exists:users,token'],
            'private' => ['boolean'],
            'due_date' => ['nullable', 'date'],
            'reference_file' => ['nullable', 'string', 'exists:reference_files,token'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'exists:data_tags,token'],
        ];
    }
}
