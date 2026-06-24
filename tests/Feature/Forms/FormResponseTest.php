<?php

use App\Enums\SystemRole;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

function formWithFields(): Form
{
    return Form::factory()->create([
        'form_fields' => [
            ['id' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'config' => []],
            ['id' => 'color', 'type' => 'list', 'label' => 'Color', 'required' => false, 'config' => ['multiple' => false, 'items' => ['Red', 'Blue']]],
        ],
    ]);
}

it('stores a valid response with the respondent as creator', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $form = formWithFields();

    $this->post(route('forms.responses.store', $form), [
        'answers' => ['name' => 'Bob', 'color' => 'Red'],
    ])->assertRedirect(route('forms.show', $form->token));

    $response = FormResponse::where('form_id', $form->id)->first();
    expect($response)->not->toBeNull()
        ->and($response->answers['name'])->toBe('Bob')
        ->and($response->answers['color'])->toBe('Red')
        ->and($response->created_by)->toBe($user->id);
});

it('rejects a response missing a required answer', function (): void {
    actingAsRole(SystemRole::Developer);
    $form = formWithFields();

    $this->post(route('forms.responses.store', $form), [
        'answers' => ['color' => 'Red'],
    ])->assertSessionHasErrors('answers.name');

    expect(FormResponse::where('form_id', $form->id)->exists())->toBeFalse();
});

it('rejects a list answer outside the allowed items', function (): void {
    actingAsRole(SystemRole::Developer);
    $form = formWithFields();

    $this->post(route('forms.responses.store', $form), [
        'answers' => ['name' => 'Bob', 'color' => 'Green'],
    ])->assertSessionHasErrors('answers.color');
});

it('lists responses for a form', function (): void {
    actingAsRole(SystemRole::Developer);
    $form = formWithFields();
    FormResponse::factory()->count(2)->create(['form_id' => $form->id]);

    $this->get(route('forms.responses.index', $form))
        ->assertInertia(fn ($page) => $page
            ->component('Forms/Responses')
            ->has('responses.data', 2)
            ->where('responsesTotal', 2));
});

it('shows a single response', function (): void {
    actingAsRole(SystemRole::Developer);
    $form = formWithFields();
    $response = FormResponse::factory()->create([
        'form_id' => $form->id,
        'answers' => ['name' => 'Bob', 'color' => 'Blue'],
    ]);

    $this->get(route('responses.show', $response))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Forms/ResponseShow')
            ->where('response.answers.name', 'Bob'));
});

it('deletes a response', function (): void {
    actingAsRole(SystemRole::Developer);
    $form = formWithFields();
    $response = FormResponse::factory()->create(['form_id' => $form->id]);

    $this->delete(route('responses.destroy', $response))
        ->assertRedirect(route('forms.responses.index', $form->token));

    expect(FormResponse::withInactive()->find($response->id))->toBeNull();
});

it('forbids managing responses without permission', function (): void {
    $form = formWithFields();

    $noRole = User::factory()->create();
    $this->actingAs($noRole)
        ->get(route('forms.responses.index', $form))
        ->assertForbidden();
});
