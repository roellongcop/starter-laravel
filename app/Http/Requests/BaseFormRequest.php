<?php

namespace App\Http\Requests;

use App\Enums\FieldType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /**
     * Validation rules for the dynamic `form_fields` array on a Form. Common
     * attributes are checked here with dot-notation; type-specific requirements
     * (a list needs items, a range needs a valid min/max) are enforced by
     * validateFormFieldConfigs() in the concrete request's withValidator().
     *
     * @return array<string, mixed>
     */
    protected function formFieldsRules(): array
    {
        return [
            'form_fields' => ['nullable', 'array'],
            'form_fields.*.id' => ['required', 'string', 'max:64'],
            'form_fields.*.type' => ['required', Rule::enum(FieldType::class)],
            'form_fields.*.label' => ['required', 'string', 'max:255'],
            'form_fields.*.description' => ['nullable', 'string', 'max:1000'],
            'form_fields.*.required' => ['boolean'],
            'form_fields.*.config' => ['nullable', 'array'],
            'form_fields.*.config.placeholder' => ['nullable', 'string', 'max:255'],
            'form_fields.*.config.include_time' => ['nullable', 'boolean'],
            'form_fields.*.config.multiple' => ['nullable', 'boolean'],
            'form_fields.*.config.items' => ['nullable', 'array'],
            'form_fields.*.config.items.*' => ['required', 'string', 'max:255'],
            'form_fields.*.config.min' => ['nullable', 'numeric'],
            'form_fields.*.config.max' => ['nullable', 'numeric'],
            'form_fields.*.config.step' => ['nullable', 'numeric', 'gt:0'],
            'form_fields.*.config.min_label' => ['nullable', 'string', 'max:255'],
            'form_fields.*.config.max_label' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Enforce the per-type config requirements that dot-notation rules can't
     * express: a List needs at least one item; a Range needs numeric min/max
     * with max greater than min.
     */
    protected function validateFormFieldConfigs(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var array<int, array<string, mixed>> $fields */
            $fields = (array) $this->input('form_fields', []);

            foreach ($fields as $i => $field) {
                $type = $field['type'] ?? null;
                $config = (array) ($field['config'] ?? []);

                if ($type === FieldType::List->value) {
                    $items = $config['items'] ?? [];
                    if (! is_array($items) || count($items) < 1) {
                        $v->errors()->add("form_fields.{$i}.config.items", 'A list field needs at least one item.');
                    } elseif (count($items) !== count(array_unique($items))) {
                        // Duplicate option values make checkbox/radio selection
                        // ambiguous (picking one toggles every twin), so options
                        // must be distinct.
                        $v->errors()->add("form_fields.{$i}.config.items", 'List items must be unique.');
                    }
                }

                if ($type === FieldType::Range->value) {
                    $min = $config['min'] ?? null;
                    $max = $config['max'] ?? null;
                    if (! is_numeric($min) || ! is_numeric($max)) {
                        $v->errors()->add("form_fields.{$i}.config.min", 'A range field needs numeric min and max values.');
                    } elseif ((float) $max <= (float) $min) {
                        $v->errors()->add("form_fields.{$i}.config.max", 'The max value must be greater than the min value.');
                    }
                }
            }
        });
    }
}
