<?php

namespace App\Http\Requests;

class StoreExportRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', 'in:csv,xls,xlsx,pdf'],
            'resource' => ['required', 'in:users'],
            'filters' => ['array'],
            'filters.search' => ['nullable', 'string', 'max:255'],
            'filters.inactive' => ['nullable', 'boolean'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date'],
        ];
    }
}
