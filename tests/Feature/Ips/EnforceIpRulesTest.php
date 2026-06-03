<?php

use App\Models\Ip;
use App\Settings\SystemSettings;

it('allows all requests by default', function (): void {
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->get('/login')
        ->assertOk();
});

it('blocks a blacklisted IP', function (): void {
    Ip::factory()->blacklist()->create(['ip_address' => '198.51.100.9']);

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
        ->get('/login')
        ->assertForbidden();
});

it('restricts to whitelisted IPs when whitelist_ip_only is on', function (): void {
    $settings = app(SystemSettings::class);
    $settings->whitelist_ip_only = true;
    $settings->save();

    Ip::factory()->whitelist()->create(['ip_address' => '198.51.100.20']);

    // whitelisted passes
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
        ->get('/login')
        ->assertOk();

    // non-whitelisted is blocked
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
        ->get('/login')
        ->assertForbidden();
});
