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
}
