<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Telescope\Telescope;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Telescope is dev-only (local); prune its entries so the table can't grow
// unbounded. The command only exists when the package is installed.
if (app()->environment('local') && class_exists(Telescope::class)) {
    Schedule::command('telescope:prune --hours=48')->daily();
}

// Unattended backups: nightly DB backup (dispatched to the queue, like the admin
// UI) and a weekly retention prune. Times are in config('app.timezone').
Schedule::command('backups:run')->dailyAt('02:00');
Schedule::command('backups:prune')->weeklyOn(0, '03:00');
// Alert developers a few hours after the nightly run if it didn't produce a backup.
Schedule::command('backups:monitor')->dailyAt('08:00');
