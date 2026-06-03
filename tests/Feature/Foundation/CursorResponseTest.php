<?php

use Tests\Fixtures\Widget;

beforeEach(function (): void {
    createWidgetsTable();
});

it('shapes a cursor paginator into {data, next_cursor, prev_cursor, has_more}', function (): void {
    foreach (range(1, 5) as $i) {
        Widget::create(['name' => "w{$i}"]);
    }

    $paginator = Widget::query()->keyset()->cursorPaginate(2);
    $response = cursorResponse($paginator);

    expect($response)->toHaveKeys(['data', 'next_cursor', 'prev_cursor', 'has_more'])
        ->and($response['data'])->toHaveCount(2)
        ->and($response['has_more'])->toBeTrue()
        ->and($response['next_cursor'])->toBeString()
        ->and($response['prev_cursor'])->toBeNull();
});

it('applies the optional per-item map', function (): void {
    Widget::create(['name' => 'alpha']);

    $paginator = Widget::query()->keyset()->cursorPaginate(2);
    $response = cursorResponse($paginator, fn (Widget $w) => ['label' => strtoupper($w->name)]);

    expect($response['data'][0])->toBe(['label' => 'ALPHA'])
        ->and($response['has_more'])->toBeFalse()
        ->and($response['next_cursor'])->toBeNull();
});
