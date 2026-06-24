<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\Form;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    protected $model = Form::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(),
            'form_fields' => $this->sampleFields(),
            'organization_id' => Organization::factory(),
        ];
    }

    /** A form with no fields yet (a draft). */
    public function empty(): static
    {
        return $this->state(fn () => ['form_fields' => []]);
    }

    /**
     * One field of each kind, so seeded/factory forms exercise every type.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function sampleFields(): array
    {
        return [
            [
                'id' => (string) Str::uuid(),
                'type' => FieldType::Text->value,
                'label' => 'Your name',
                'description' => null,
                'required' => true,
                'config' => ['placeholder' => 'Jane Doe'],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => FieldType::Paragraph->value,
                'label' => 'Tell us more',
                'description' => 'Optional details.',
                'required' => false,
                'config' => ['placeholder' => ''],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => FieldType::Date->value,
                'label' => 'Preferred date',
                'description' => null,
                'required' => false,
                'config' => ['include_time' => true],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => FieldType::Range->value,
                'label' => 'How likely are you to recommend us?',
                'description' => null,
                'required' => false,
                'config' => ['min' => 0, 'max' => 10, 'step' => 1, 'min_label' => 'Unlikely', 'max_label' => 'Very likely'],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => FieldType::List->value,
                'label' => 'Which options apply?',
                'description' => null,
                'required' => false,
                'config' => ['multiple' => true, 'items' => ['Email', 'Phone', 'SMS']],
            ],
        ];
    }
}
