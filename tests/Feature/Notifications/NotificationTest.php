<?php

use App\Enums\SystemRole;
use App\Notifications\AdminNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('lists the user notifications and shares the bell count', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $user->notify(new AdminNotification('Hello there'));

    $this->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 1)
            ->where('bell.recent.0.message', 'Hello there')
            ->where('bell.unread_count', 1));
});

it('marks a notification read and unread', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $user->notify(new AdminNotification('Ping'));
    $id = $user->notifications()->first()->id;

    $this->patch(route('notifications.update', $id), ['read' => true]);
    expect($user->unreadNotifications()->count())->toBe(0);

    $this->patch(route('notifications.update', $id), ['read' => false]);
    expect($user->unreadNotifications()->count())->toBe(1);
});

it('bulk deletes notifications', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $user->notify(new AdminNotification('a'));
    $user->notify(new AdminNotification('b'));
    $ids = $user->notifications()->pluck('id')->all();

    $this->post(route('notifications.bulk'), ['process' => 'delete', 'ids' => $ids])
        ->assertRedirect();

    expect($user->notifications()->count())->toBe(0);
});
