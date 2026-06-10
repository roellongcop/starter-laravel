<?php

use Spatie\Backup\Tasks\Backup\DbDumperFactory;

/**
 * Guards the Postgres backup-restore fix: pg_dump must run with --clean --if-exists
 * so a restore drops+recreates the dumped tables (and the dump's setval() calls land
 * on fresh sequences) instead of layering onto live data and causing duplicate-key
 * (23505) crashes on the next insert. Pure command-string assertion — no DB needed,
 * so it runs in the normal SQLite suite. See tests/Integration for the real round-trip.
 */
it('builds a self-cleaning postgres dump so restores reset sequences', function (): void {
    // Build the command for the pgsql connection by name — no need to switch the
    // default connection (which would break the RefreshDatabase transaction teardown).
    $command = DbDumperFactory::createFromConnection('pgsql')->getDumpCommand('/tmp/probe.sql');

    expect($command)
        ->toContain('--clean')
        ->toContain('--if-exists')
        // operational tables must still be excluded from the dump
        ->toContain('-T backups')
        ->toContain('-T jobs')
        ->toContain('-T sessions');
});
