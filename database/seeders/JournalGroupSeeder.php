<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JournalGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['code' => 0,  'name' => 'Почетна состојба'],
            ['code' => 10, 'name' => 'Денарски изводи'],
            ['code' => 20, 'name' => 'Влезни фактури'],
            ['code' => 30, 'name' => 'Излезни фактури'],
            ['code' => 40, 'name' => 'Останати налози'],
            ['code' => 50, 'name' => 'Плати, исплати, даноци'],
            ['code' => 60, 'name' => 'Останати расходи'],
            ['code' => 70, 'name' => 'Останати приходи'],
            ['code' => 80, 'name' => 'Вонредни ставки'],
            ['code' => 90, 'name' => 'Затворање година'],
        ];

        foreach ($groups as $group) {
            DB::table('journal_groups')->insertOrIgnore([
                'code'       => $group['code'],
                'name'       => $group['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}