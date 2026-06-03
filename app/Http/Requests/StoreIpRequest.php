<?php

namespace App\Http\Requests;

use App\Enums\IpListType;
use Illuminate\Validation\Rule;

class StoreIpRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'ip', 'max:45'],
            'list_type' => ['required', Rule::enum(IpListType::class)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
