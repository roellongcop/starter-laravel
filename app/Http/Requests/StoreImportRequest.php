<?php

namespace App\Http\Requests;

class StoreImportRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx', 'max:10240'],
            'resource' => ['required', 'in:users'],
        ];
    }
}
