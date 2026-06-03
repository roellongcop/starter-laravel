<?php

namespace App\Http\Middleware;

use App\Models\Theme;
use App\Settings\SystemSettings;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $permissions = $user ? $user->getAllPermissions()->pluck('name')->all() : [];
        $modules = Navigation::modulesFor($permissions);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'roles' => $user->getRoleNames()->all(),
                ] : null,
                // Flat permission names for <Can> checks, and the module_access
                // map for module-level button/group visibility.
                'permissions' => $permissions,
                'modules' => $modules,
            ],
            'navigation' => $user ? Navigation::forUser($user) : [],
            // Bell: unread count + recent notifications. Named `bell` (not
            // `notifications`) to avoid colliding with the Notifications index
            // page's own `notifications` list prop.
            'bell' => fn () => $this->bell($request),
            // App-wide settings + active theme tokens (lazily evaluated; the
            // theme/settings tables may not exist during early migrations).
            'settings' => fn () => $this->appSettings(),
            'theme' => fn () => $this->activeThemeTokens(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'hint' => fn () => $request->session()->get('hint'),
            ],
        ];
    }

    /**
     * Unread count + recent notifications for the navbar bell.
     *
     * @return array{unread_count: int, recent: array<int, array<string, mixed>>}
     */
    protected function bell(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return ['unread_count' => 0, 'recent' => []];
        }

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => $user->notifications()->latest()->take(5)->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'message' => $n->data['message'] ?? '',
                    'link' => $n->data['link'] ?? null,
                    'read' => $n->read_at !== null,
                    'created_at' => $n->created_at?->toIso8601String(),
                ])->all(),
        ];
    }

    /**
     * Whitelisted system settings safe to expose to the browser.
     *
     * @return array<string, mixed>
     */
    protected function appSettings(): array
    {
        try {
            $system = app(SystemSettings::class);

            return [
                'app_name' => $system->app_name,
                'default_theme' => $system->default_theme,
            ];
        } catch (\Throwable) {
            return ['app_name' => config('app.name'), 'default_theme' => 'system'];
        }
    }

    /**
     * The default theme's tokens, or null when none/unavailable.
     *
     * @return array<string, array<string, string>>|null
     */
    protected function activeThemeTokens(): ?array
    {
        try {
            return Theme::where('is_default', true)->value('tokens');
        } catch (\Throwable) {
            return null;
        }
    }
}
