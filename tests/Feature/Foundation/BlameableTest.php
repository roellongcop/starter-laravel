<?php

use App\Models\User;
use Tests\Fixtures\Widget;

beforeEach(function (): void {
    createWidgetsTable();
});

it('stamps created_by/updated_by from the authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $widget = Widget::create(['name' => 'a']);

    expect($widget->created_by)->toBe($user->id)
        ->and($widget->updated_by)->toBe($user->id);
});

it('leaves blame null when unauthenticated (console/system)', function (): void {
    $widget = Widget::create(['name' => 'a']);

    expect($widget->created_by)->toBeNull()
        ->and($widget->updated_by)->toBeNull();
});

it('updates updated_by on save', function (): void {
    $creator = User::factory()->create();
    $editor = User::factory()->create();

    $this->actingAs($creator);
    $widget = Widget::create(['name' => 'a']);

    $this->actingAs($editor);
    $widget->update(['name' => 'b']);

    expect($widget->fresh()->created_by)->toBe($creator->id)
        ->and($widget->fresh()->updated_by)->toBe($editor->id);
});

it('writes an audit row through the auditing trait', function (): void {
    // Auditing is skipped on the console by default (audit.console=false); enable
    // it so the test exercises the wiring the way HTTP requests would.
    config(['audit.console' => true]);

    Widget::create(['name' => 'a']);

    expect(DB::table('audits')->where('auditable_type', Widget::class)->count())
        ->toBeGreaterThanOrEqual(1);
});
