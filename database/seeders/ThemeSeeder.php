<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        // The canonical "Keen" palette mirrors resources/css/app.css so the seeded
        // default changes nothing visually until edited.
        Theme::firstOrCreate(
            ['name' => 'Keen'],
            [
                'description' => 'Default slate palette.',
                'is_default' => true,
                'tokens' => [
                    'light' => [
                        '--background' => '0 0% 100%',
                        '--foreground' => '222.2 84% 4.9%',
                        '--primary' => '222.2 47.4% 11.2%',
                        '--primary-foreground' => '210 40% 98%',
                        '--secondary' => '210 40% 96.1%',
                        '--muted' => '210 40% 96.1%',
                        '--accent' => '210 40% 96.1%',
                        '--destructive' => '0 84.2% 60.2%',
                        '--border' => '214.3 31.8% 91.4%',
                        '--ring' => '222.2 84% 4.9%',
                    ],
                    'dark' => [
                        '--background' => '222.2 84% 4.9%',
                        '--foreground' => '210 40% 98%',
                        '--primary' => '210 40% 98%',
                        '--primary-foreground' => '222.2 47.4% 11.2%',
                        '--secondary' => '217.2 32.6% 17.5%',
                        '--muted' => '217.2 32.6% 17.5%',
                        '--accent' => '217.2 32.6% 17.5%',
                        '--destructive' => '0 62.8% 30.6%',
                        '--border' => '217.2 32.6% 17.5%',
                        '--ring' => '212.7 26.8% 83.9%',
                    ],
                ],
            ],
        );
    }
}
