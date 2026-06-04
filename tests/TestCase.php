<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Inertia pages render app.blade.php, which calls @vite(). Stub Vite so
        // tests don't require a built public/build/manifest.json — otherwise
        // every page render 500s in CI (where assets aren't compiled).
        $this->withoutVite();
    }
}
