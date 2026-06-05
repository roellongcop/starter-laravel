<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    // TelescopeServiceProvider is registered conditionally (local only) in
    // AppServiceProvider::register() — it's a dev dependency, absent in prod.
];
