<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormResponse>
 */
class FormResponseFactory extends Factory
{
    protected $model = FormResponse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'answers' => [],
        ];
    }

    /**
     * Build answers keyed by the given form's field ids (one plausible value
     * per field type).
     */
    public function forForm(Form $form): static
    {
        return $this->state(function () use ($form) {
            $answers = [];

            foreach ((array) $form->form_fields as $field) {
                $id = $field['id'] ?? null;
                if (! is_string($id)) {
                    continue;
                }
                $config = (array) ($field['config'] ?? []);
                $answers[$id] = match ($field['type'] ?? null) {
                    'text' => 'Sample answer',
                    'paragraph' => 'A longer sample answer.',
                    'date' => '2026-06-24',
                    'duration' => '01:30',
                    'range' => $config['min'] ?? 0,
                    'list' => ($config['multiple'] ?? false)
                        ? array_slice((array) ($config['items'] ?? []), 0, 1)
                        : (($config['items'] ?? [])[0] ?? null),
                    default => null,
                };
            }

            return ['form_id' => $form->id, 'answers' => $answers];
        });
    }
}
