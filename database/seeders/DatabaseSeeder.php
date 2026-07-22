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
            BranchSeeder::class,
            WorkScheduleSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
