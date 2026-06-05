<?php

namespace App\Http\Requests;

class SettingsRequest extends BaseFormRequest
{
    /**
     * Group-specific rules keyed by the {group} route segment.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match ($this->route('group')) {
            'system' => [
                'app_name' => ['required', 'string', 'max:255'],
                'timezone' => ['required', 'timezone'],
                'pagination_size' => ['required', 'integer', 'min:1', 'max:200'],
                'auto_logout_seconds' => ['required', 'integer', 'min:0'],
                'enable_visitor' => ['required', 'boolean'],
                'whitelist_ip_only' => ['required', 'boolean'],
                'default_theme' => ['required', 'in:light,dark,system'],
            ],
            'email' => [
                'from_address' => ['required', 'email', 'max:255'],
                'from_name' => ['required', 'string', 'max:255'],
                'smtp_host' => ['required', 'string', 'max:255'],
                'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
                'smtp_username' => ['nullable', 'string', 'max:255'],
                'smtp_password' => ['nullable', 'string', 'max:255'],
                'smtp_encryption' => ['nullable', 'in:tls,ssl'],
            ],
            'image' => [
                'favicon_token' => ['nullable', 'string', 'exists:files,token'],
                'square_logo_token' => ['nullable', 'string', 'exists:files,token'],
                'landscape_logo_token' => ['nullable', 'string', 'exists:files,token'],
            ],
            'notification' => [
                'templates' => ['array'],
                'templates.*' => ['nullable', 'string', 'max:1000'],
            ],
            default => [],
        };
    }
}
