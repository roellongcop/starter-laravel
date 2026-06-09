<?php

namespace App\Http\Controllers;

use App\Filters\ThemeFilters;
use App\Http\Requests\StoreThemeRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ThemeController extends Controller
{
    public function index(Request $request, ThemeFilters $filters): Response
    {
        $this->authorize('viewAny', Theme::class);

        $themes = $filters->apply(Theme::query())
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Themes/Index', [
            'themes' => cursorResponse($themes, fn (Theme $t) => $this->row($t)),
            'filters' => $filters->echoBack(),
            'can' => [
                'create' => $request->user()->can('themes.create'),
                'update' => $request->user()->can('themes.update'),
                'delete' => $request->user()->can('themes.delete'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Theme::class);

        return Inertia::render('Themes/Create');
    }

    public function store(StoreThemeRequest $request): RedirectResponse
    {
        $this->authorize('create', Theme::class);

        $theme = DB::transaction(function () use ($request) {
            $theme = Theme::create($request->validated());
            $this->ensureSingleDefault($theme);

            return $theme;
        });

        return redirect()->route('themes.show', $theme)->with('success', 'Theme created.');
    }

    public function show(Theme $theme): Response
    {
        $this->authorize('view', $theme);

        return Inertia::render('Themes/Show', ['theme' => $this->row($theme, detailed: true)]);
    }

    public function edit(Theme $theme): Response
    {
        $this->authorize('update', $theme);

        return Inertia::render('Themes/Edit', ['theme' => $this->row($theme, detailed: true)]);
    }

    public function update(UpdateThemeRequest $request, Theme $theme): RedirectResponse
    {
        $this->authorize('update', $theme);

        DB::transaction(function () use ($request, $theme) {
            $theme->update($request->validated());
            $this->ensureSingleDefault($theme);
        });

        return redirect()->route('themes.show', $theme)->with('success', 'Theme updated.');
    }

    public function destroy(Theme $theme): RedirectResponse
    {
        $this->authorize('delete', $theme);

        $theme->delete();

        return redirect()->route('themes.index')->with('success', 'Theme deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'process' => ['required', 'in:active,in_active,delete'],
            'tokens' => ['required', 'array'],
            'tokens.*' => ['string'],
        ]);

        $this->authorize($validated['process'] === 'delete' ? 'delete' : 'update', Theme::class);

        $count = Theme::bulkAction($validated['process'], $validated['tokens']);

        return back()->with('success', "{$count} theme(s) updated.");
    }

    /**
     * Enforce the single-default invariant: if this theme is the default, clear
     * the flag on every other theme.
     */
    protected function ensureSingleDefault(Theme $theme): void
    {
        if ($theme->is_default) {
            Theme::withInactive()
                ->whereKeyNot($theme->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Theme $theme, bool $detailed = false): array
    {
        $data = [
            'token' => $theme->token,
            'name' => $theme->name,
            'description' => $theme->description,
            'is_default' => $theme->is_default,
            'created_at' => $theme->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['preview_image'] = $theme->preview_image;
            $data['tokens'] = $theme->tokens ?? ['light' => [], 'dark' => []];
        }

        return $data;
    }
}
