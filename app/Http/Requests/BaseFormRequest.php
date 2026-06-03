<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for the app's form requests. Concrete requests in later phases define
 * rules()/authorize(); authorization defers to Policies (never to model events).
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Typed accessor for the authenticated user (nicer than the mixed return of
     * the inherited user() helper).
     */
    public function authUser(): ?User
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user;
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validation rules for an admin-managed navigation tree + priority (used by
     * the role menu builder). Two levels deep; hrefs must be http(s) or an
     * app-relative path (blocks javascript: and other schemes).
     *
     * @return array<string, mixed>
     */
    protected function navigationRules(): array
    {
        $href = ['nullable', 'string', 'max:2048', 'regex:#^(https?://|/)#'];

        return [
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'main_navigation' => ['nullable', 'array'],
            'main_navigation.*.label' => ['required', 'string', 'max:255'],
            'main_navigation.*.icon' => ['nullable', 'string', 'max:64'],
            'main_navigation.*.key' => ['nullable', 'string', 'max:64'],
            'main_navigation.*.href' => $href,
            'main_navigation.*.external' => ['boolean'],
            'main_navigation.*.children' => ['array'],
            'main_navigation.*.children.*.label' => ['required', 'string', 'max:255'],
            'main_navigation.*.children.*.icon' => ['nullable', 'string', 'max:64'],
            'main_navigation.*.children.*.key' => ['nullable', 'string', 'max:64'],
            'main_navigation.*.children.*.href' => $href,
            'main_navigation.*.children.*.external' => ['boolean'],
        ];
    }
}
