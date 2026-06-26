<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Создади ги основните улоги за апликацијата.
     */
    public function run(): void
    {
        foreach (['admin', 'accountant', 'company_admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
