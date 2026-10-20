<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('TestDataSeeder cannot run in production.');
        }

        $this->call([UserSeeder::class, MerchantSeeder::class, WalletSeeder::class]);
    }
}
