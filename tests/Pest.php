<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
| Integration tests run against a REAL Postgres database (group "pg"), not the
| in-memory SQLite default — they exercise external pg_dump/psql processes that
| can't see an uncommitted RefreshDatabase transaction. They manage their own
| committed migrate:fresh per test (see tests/Integration), so no DB trait here.
| Excluded from `make test`; run via `make test-pg`.
*/
pest()->extend(TestCase::class)
    ->group('pg')
    ->in('Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create a user with the given role and authenticate as them. Requires the
 * permission + role seeders to have run first.
 */
function actingAsRole(SystemRole|string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role instanceof SystemRole ? $role->value : $role);
    test()->actingAs($user);

    return $user;
}

/**
 * Create the test-only widgets table using the auditColumns() macro, so the
 * foundation tests can exercise BaseModel without a real domain migration.
 */
function createWidgetsTable(): void
{
    Schema::dropIfExists('widgets');

    Schema::create('widgets', function (Blueprint $table): void {
        $table->id();
        $table->uuid('token')->unique(); // HasToken (via BaseModel) fills it on create
        $table->string('name');
        $table->auditColumns();
    });
}
