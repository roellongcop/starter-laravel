<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormResponseRequest;
use App\Models\Form;
use App\Models\FormResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FormResponseController extends Controller
{
    /**
     * Render the fill page. Anyone who can view the form may respond.
     */
    public function create(Form $form): Response
    {
        $this->authorize('view', $form);

        return Inertia::render('Forms/Respond', [
            'form' => $this->formRow($form),
        ]);
    }

    public function store(StoreFormResponseRequest $request, Form $form): RedirectResponse
    {
        $this->authorize('view', $form);

        $form->responses()->create([
            'answers' => $request->validated()['answers'] ?? [],
        ]);

        return redirect()->route('forms.show', $form->token)->with('success', 'Response submitted.');
    }

    public function index(Form $form): Response
    {
        $this->authorize('viewAny', FormResponse::class);

        $responses = $form->responses()->with('creator')
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (FormResponse $response) => $this->responseRow($response));

        return Inertia::render('Forms/Responses', [
            'form' => $this->formRow($form),
            'responses' => Inertia::scroll($responses),
            'responsesTotal' => $form->responses()->count(),
        ]);
    }

    public function show(FormResponse $response): Response
    {
        $this->authorize('view', $response);

        $response->load(['form.organization', 'creator']);

        return Inertia::render('Forms/ResponseShow', [
            'form' => $this->formRow($response->form),
            'response' => $this->responseRow($response),
        ]);
    }

    public function destroy(FormResponse $response): RedirectResponse
    {
        $this->authorize('delete', $response);

        $form = $response->form;
        $response->delete();

        return redirect()->route('forms.responses.index', $form->token)->with('success', 'Response deleted.');
    }

    /**
     * The form definition the fill/response pages render against.
     *
     * @return array<string, mixed>
     */
    protected function formRow(Form $form): array
    {
        $form->loadMissing('organization');

        return [
            'token' => $form->token,
            'title' => $form->title,
            'description' => $form->description,
            'form_fields' => $form->form_fields ?? [],
            'organization_name' => $form->organization->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseRow(FormResponse $response): array
    {
        return [
            'token' => $response->token,
            'answers' => $response->answers ?? [],
            'respondent' => $response->creator?->name,
            'created_at' => $response->created_at?->toIso8601String(),
        ];
    }
}
