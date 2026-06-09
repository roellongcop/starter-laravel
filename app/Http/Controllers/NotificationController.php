<?php

namespace App\Http\Controllers;

use App\Filters\NotificationFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationFilters $filters): Response
    {
        $this->authorize('notifications.index');

        $notifications = $filters->apply($request->user()->notifications()->getQuery())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Notifications/Index', [
            'notifications' => cursorResponse($notifications, fn (DatabaseNotification $n) => $this->row($n)),
            'filters' => $filters->echoBack(),
        ]);
    }

    public function update(Request $request, string $notification): RedirectResponse
    {
        $this->authorize('notifications.update');

        $validated = $request->validate(['read' => ['required', 'boolean']]);

        $model = $request->user()->notifications()->findOrFail($notification);
        $validated['read'] ? $model->markAsRead() : $model->markAsUnread();

        return back();
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'process' => ['required', 'in:read,unread,delete'],
            'ids' => ['required', 'array'],
        ]);

        $this->authorize($validated['process'] === 'delete' ? 'notifications.delete' : 'notifications.update');

        $query = $request->user()->notifications()->whereIn('id', $validated['ids']);

        match ($validated['process']) {
            'read' => $query->update(['read_at' => now()]),
            'unread' => $query->update(['read_at' => null]),
            'delete' => $query->delete(),
            default => null,
        };

        return back()->with('success', 'Notifications updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(DatabaseNotification $n): array
    {
        /** @var array<string, mixed> $data */
        $data = $n->data;

        return [
            'id' => $n->id,
            'type' => $data['type'] ?? 'Info',
            'message' => $data['message'] ?? '',
            'link' => $data['link'] ?? null,
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }
}
