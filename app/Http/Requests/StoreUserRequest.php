<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'password_hint' => ['nullable', 'string', 'max:255'],
            'user_status' => ['required', Rule::enum(UserStatus::class)],
            'avatar_file_token' => ['nullable', 'string', 'exists:files,token'],

            'roles' => ['array'],
            'roles.*' => [Rule::exists('roles', 'name')],

            'meta' => ['array'],
            'meta.*.key' => ['required_with:meta', 'string', 'max:255'],
            'meta.*.value' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
