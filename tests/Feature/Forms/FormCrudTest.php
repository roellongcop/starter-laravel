<?php

use App\Enums\SystemRole;
use App\Models\Form;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

function validFormPayload(Organization $organization): array
{
    return [
        'title' => 'Customer Feedback',
        'description' => 'How did we do?',
        'organization' => $organization->token,
        'form_fields' => [
            ['id' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true, 'config' => ['placeholder' => 'Jane']],
            ['id' => 'rating', 'type' => 'range', 'label' => 'Rating', 'required' => false, 'config' => ['min' => 0, 'max' => 10, 'step' => 1]],
            ['id' => 'channel', 'type' => 'list', 'label' => 'Channel', 'required' => false, 'config' => ['multiple' => false, 'items' => ['Email', 'Phone']]],
        ],
    ];
}

it('creates a form, resolves the organization token, and persists the fields', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('forms.store'), validFormPayload($organization))
        ->assertRedirect();

    $form = Form::where('title', 'Customer Feedback')->first();
    expect($form)->not->toBeNull()
        ->and($form->organization_id)->toBe($organization->id)
        ->and($form->form_fields)->toHaveCount(3)
        ->and($form->form_fields[0]['type'])->toBe('text');
});

it('requires a title', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $payload = validFormPayload($organization);
    unset($payload['title']);

    $this->post(route('forms.store'), $payload)->assertSessionHasErrors('title');
});

it('rejects an unknown field type', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $payload = validFormPayload($organization);
    $payload['form_fields'][0]['type'] = 'bogus';

    $this->post(route('forms.store'), $payload)
        ->assertSessionHasErrors('form_fields.0.type');
});

it('rejects a list field with no items', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $payload = validFormPayload($organization);
    $payload['form_fields'][2]['config']['items'] = [];

    $this->post(route('forms.store'), $payload)
        ->assertSessionHasErrors('form_fields.2.config.items');
});

it('rejects a list field with duplicate items', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $payload = validFormPayload($organization);
    $payload['form_fields'][2]['config']['items'] = ['Email', 'Email'];

    $this->post(route('forms.store'), $payload)
        ->assertSessionHasErrors('form_fields.2.config.items');
});

it('rejects a range field whose max is not greater than its min', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $payload = validFormPayload($organization);
    $payload['form_fields'][1]['config'] = ['min' => 5, 'max' => 5, 'step' => 1];

    $this->post(route('forms.store'), $payload)
        ->assertSessionHasErrors('form_fields.1.config.max');
});

it('updates a form', function (): void {
    actingAsRole(SystemRole::Developer);
    $form = Form::factory()->create(['title' => 'Old']);

    $this->patch(route('forms.update', $form), [
        'title' => 'Updated',
        'organization' => $form->organization->token,
        'form_fields' => [
            ['id' => 'q1', 'type' => 'paragraph', 'label' => 'Comments', 'required' => false, 'config' => []],
        ],
    ])->assertRedirect();

    expect($form->fresh())
        ->title->toBe('Updated')
        ->and($form->fresh()->form_fields)->toHaveCount(1);
});

it('deletes a form', function (): void {
    actingAsRole(SystemRole::Developer);
    $form = Form::factory()->create();

    $this->delete(route('forms.destroy', $form))->assertRedirect();
    expect(Form::withInactive()->find($form->id))->toBeNull();
});

it('renders forms on the index page', function (): void {
    actingAsRole(SystemRole::Developer);
    Form::factory()->count(3)->create();

    $this->get(route('forms.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Forms/Index')
            ->has('forms.data', 3));
});

it('filters the index by organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    Form::factory()->count(2)->create(['organization_id' => $orgA->id]);
    Form::factory()->create(['organization_id' => $orgB->id]);

    $this->get(route('forms.index', ['organization' => $orgA->token]))
        ->assertInertia(fn ($page) => $page
            ->has('forms.data', 2)
            ->where('filters.organization', $orgA->token));
});

it('never leaks the organization id to the frontend', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $form = Form::factory()->create(['organization_id' => $organization->id]);

    $this->get(route('forms.show', $form))
        ->assertInertia(fn ($page) => $page
            ->component('Forms/Show')
            ->where('form.organization', $organization->token)
            ->where('form.organization_name', $organization->name)
            ->missing('form.organization_id'));
});

it('forbids form access without permission', function (): void {
    $this->get(route('forms.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('forms.index'))->assertForbidden();
});
