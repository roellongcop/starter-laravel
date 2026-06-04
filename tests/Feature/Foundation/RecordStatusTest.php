<?php

use App\Enums\RecordStatus;
use Tests\Fixtures\Widget;

beforeEach(function (): void {
    createWidgetsTable();
});

it('defaults queries to active rows via the global scope', function (): void {
    Widget::create(['name' => 'active one']);
    Widget::create(['name' => 'inactive one', 'record_status' => RecordStatus::Inactive]);

    expect(Widget::count())->toBe(1)
        ->and(Widget::first()->name)->toBe('active one');
});

it('reveals inactive rows with withInactive() and onlyInactive()', function (): void {
    Widget::create(['name' => 'active one']);
    Widget::create(['name' => 'inactive one', 'record_status' => RecordStatus::Inactive]);

    expect(Widget::withInactive()->count())->toBe(2)
        ->and(Widget::onlyInactive()->count())->toBe(1)
        ->and(Widget::onlyInactive()->first()->name)->toBe('inactive one');
});

it('casts record_status to the RecordStatus enum', function (): void {
    $widget = Widget::create(['name' => 'a']);

    expect($widget->refresh()->record_status)->toBe(RecordStatus::Active);
});

it('activates and inactivates a single record', function (): void {
    $widget = Widget::create(['name' => 'a']);

    $widget->inactivate();
    expect(Widget::count())->toBe(0)
        ->and(Widget::withInactive()->first()->record_status)->toBe(RecordStatus::Inactive);

    $widget->activate();
    expect(Widget::count())->toBe(1);
});

it('runs bulk active / in_active / delete processes', function (): void {
    $a = Widget::create(['name' => 'a']);
    $b = Widget::create(['name' => 'b']);

    Widget::bulkAction('in_active', [$a->token, $b->token]);
    expect(Widget::count())->toBe(0);

    Widget::bulkAction('active', [$a->token]);
    expect(Widget::count())->toBe(1);

    $affected = Widget::bulkAction('delete', [$a->token, $b->token]);
    expect($affected)->toBe(2)
        ->and(Widget::withInactive()->count())->toBe(0);
});

it('throws on an unknown bulk process', function (): void {
    Widget::bulkAction('nope', [1]);
})->throws(InvalidArgumentException::class);
