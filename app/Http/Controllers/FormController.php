<?php

namespace App\Http\Controllers;

use App\Enums\FieldType;
use App\Filters\FormFilters;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Form;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormController extends Controller
{
    public function index(Request $request, FormFilters $filters): Response
    {
        $this->authorize('viewAny', Form::class);

        $forms = $filters->apply(Form::query()->with('organization')->withCount('responses'))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (Form $form) => $this->row($form));

        return Inertia::render('Forms/Index', [
            'forms' => Inertia::scroll($forms),
            'filters' => $filters->echoBack(),
            'organizations' => $this->organizationOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Form::class);

        return Inertia::render('Forms/Create', [
            'organizations' => $this->organizationOptions(),
            'fieldTypes' => FieldType::options(),
        ]);
    }

    public function store(StoreFormRequest $request): RedirectResponse
    {
        $this->authorize('create', Form::class);

        $form = Form::create($this->resolveOrganization($request->validated()));

        return redirect()->route('forms.show', $form->token)->with('success', 'Form created.');
    }

    public function show(Form $form): Response
    {
        $this->authorize('view', $form);

        return Inertia::render('Forms/Show', [
            'form' => $this->row($form->load('organization')->loadCount('responses')),
        ]);
    }

    public function edit(Form $form): Response
    {
        $this->authorize('update', $form);

        return Inertia::render('Forms/Edit', [
            'form' => $this->row($form->load('organization')),
            'organizations' => $this->organizationOptions(),
            'fieldTypes' => FieldType::options(),
        ]);
    }

    public function update(UpdateFormRequest $request, Form $form): RedirectResponse
    {
        $this->authorize('update', $form);

        $form->update($this->resolveOrganization($request->validated()));

        return redirect()->route('forms.show', $form->token)->with('success', 'Form updated.');
    }

    public function destroy(Form $form): RedirectResponse
    {
        $this->authorize('delete', $form);

        $form->delete();

        return redirect()->route('forms.index')->with('success', 'Form deleted.');
    }

    /**
     * Translate the organization token into its id (never trust ids from the
     * frontend — only tokens cross the wire).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveOrganization(array $data): array
    {
        $data['organization_id'] = Organization::where('token', $data['organization'])->value('id');
        unset($data['organization']);

        return $data;
    }

    /**
     * Selectable organizations for the picker, keyed by token.
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function organizationOptions(): array
    {
        return Organization::query()
            ->orderBy('name')
            ->get(['token', 'name'])
            ->map(fn (Organization $organization) => ['value' => $organization->token, 'label' => $organization->name])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Form $form): array
    {
        return [
            'token' => $form->token,
            'title' => $form->title,
            'description' => $form->description,
            'form_fields' => $form->form_fields ?? [],
            'organization' => $form->organization->token,
            'organization_name' => $form->organization->name,
            'responses_count' => $form->responses_count,
            'record_status' => $form->record_status->value,
            'created_at' => $form->created_at?->toIso8601String(),
        ];
    }
}
