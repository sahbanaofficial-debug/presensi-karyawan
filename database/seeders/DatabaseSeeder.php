<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Menjalankan seluruh seeder data awal aplikasi.
     */
    public function run(): void
    {
        $this->call([
            WorkScheduleSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
