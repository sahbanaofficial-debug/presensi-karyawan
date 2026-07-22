<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Mengisi data awal Kantor Cabang 02.
     */
    public function run(): void
    {
        Branch::query()->updateOrCreate(
            [
                'code' => 'CB02',
            ],
            [
                'name' => 'Kantor Cabang 02',
                'address' => 'Jalan Brigjen Zein Hamid, Kompleks Katamso Indah No. A9',
                'latitude' => null,
                'longitude' => null,
                'geofence_radius' => 30.00,
                'maximum_accuracy' => 25.00,
                'status' => 'active',
            ]
        );
    }
}
