<?php

use App\Enums\IpListType;
use App\Models\Ip;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    actingAsRole('developer');
});

it('creates an IP entry', function (): void {
    $this->post(route('ips.store'), [
        'ip_address' => '203.0.113.5',
        'list_type' => 'Blacklist',
        'description' => 'bad actor',
    ])->assertRedirect();

    expect(Ip::where('ip_address', '203.0.113.5')->first()->list_type)
        ->toBe(IpListType::Blacklist);
});

it('validates the ip address', function (): void {
    $this->post(route('ips.store'), [
        'ip_address' => 'not-an-ip',
        'list_type' => 'Blacklist',
    ])->assertSessionHasErrors('ip_address');
});

it('runs the custom white_list bulk action', function (): void {
    $a = Ip::factory()->blacklist()->create();
    $b = Ip::factory()->blacklist()->create();

    $this->post(route('ips.bulk'), ['process' => 'white_list', 'tokens' => [$a->token, $b->token]])
        ->assertRedirect();

    expect($a->fresh()->list_type)->toBe(IpListType::Whitelist)
        ->and($b->fresh()->list_type)->toBe(IpListType::Whitelist);
});

it('still runs the default bulk actions', function (): void {
    $a = Ip::factory()->create();

    $this->post(route('ips.bulk'), ['process' => 'in_active', 'tokens' => [$a->token]])
        ->assertRedirect();

    expect(Ip::find($a->id))->toBeNull()
        ->and(Ip::onlyInactive()->find($a->id))->not->toBeNull();
});
