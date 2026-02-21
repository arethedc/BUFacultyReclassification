<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentsSeeder::class,
            RankLevelsSeeder::class,
        ]);
    }
}

