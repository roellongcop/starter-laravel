<?php

namespace Database\Seeders;

use App\Enums\IpListType;
use App\Models\Ip;
use Illuminate\Database\Seeder;

class IpSeeder extends Seeder
{
    public function run(): void
    {
        Ip::firstOrCreate(
            ['ip_address' => '127.0.0.1'],
            ['list_type' => IpListType::Whitelist, 'description' => 'Localhost'],
        );

        Ip::firstOrCreate(
            ['ip_address' => '192.0.2.1'],
            ['list_type' => IpListType::Blacklist, 'description' => 'Example blocked host'],
        );
    }
}
