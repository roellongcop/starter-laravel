<?php

namespace App\Http\Requests;

use App\Enums\FieldType;
use App\Models\Form;
use Illuminate\Validation\Rule;

class StoreFormResponseRequest extends BaseFormRequest
{
    /**
     * Build per-field answer rules from the form definition: each field becomes
     * an `answers.{id}` rule shaped by its type, with required/optional driven
     * by the field's `required` flag.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Form $form */
        $form = $this->route('form');

        $rules = ['answers' => ['nullable', 'array']];

        foreach ((array) $form->form_fields as $field) {
            $id = $field['id'] ?? null;
            if (! is_string($id) || $id === '') {
                continue;
            }

            $key = "answers.{$id}";
            $type = $field['type'] ?? null;
            $config = (array) ($field['config'] ?? []);
            $presence = ($field['required'] ?? false) ? 'required' : 'nullable';

            $rules[$key] = match ($type) {
                FieldType::Text->value, FieldType::Paragraph->value => [$presence, 'string', 'max:5000'],
                FieldType::Date->value => [$presence, 'string', 'max:64'],
                FieldType::Duration->value => [$presence, 'string', 'max:32'],
                FieldType::Range->value => $this->rangeRules($presence, $config),
                FieldType::List->value => ($config['multiple'] ?? false) ? [$presence, 'array'] : [$presence, 'string'],
                default => [$presence],
            };

            if ($type === FieldType::List->value) {
                $items = array_values(array_filter((array) ($config['items'] ?? []), 'is_string'));
                if ($config['multiple'] ?? false) {
                    $rules["{$key}.*"] = ['string', Rule::in($items)];
                } else {
                    $rules[$key][] = Rule::in($items);
                }
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, mixed>
     */
    protected function rangeRules(string $presence, array $config): array
    {
        $rules = [$presence, 'numeric'];

        if (isset($config['min'], $config['max']) && is_numeric($config['min']) && is_numeric($config['max'])) {
            $rules[] = 'between:'.((float) $config['min']).','.((float) $config['max']);
        }

        return $rules;
    }
}
