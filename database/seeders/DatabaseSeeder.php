<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\JournalGroupSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            ChartOfAccountsSeeder::class,
            JournalGroupSeeder::class,
        ]);
    }
}
