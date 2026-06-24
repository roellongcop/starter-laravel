<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();

        Form::factory()
            ->for($organization)
            ->create(['title' => 'Customer Feedback']);
    }
}
