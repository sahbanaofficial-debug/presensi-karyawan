<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use RuntimeException;

class BranchSeeder extends Seeder
{
    /**
     * Mengisi data awal Kantor Cabang 02.
     */
    public function run(): void
    {
        $defaultSchedule = WorkSchedule::query()
            ->where('name', 'Jadwal Penuh')
            ->first();

        if ($defaultSchedule === null) {
            throw new RuntimeException(
                'Jadwal Penuh belum tersedia. Jalankan WorkScheduleSeeder terlebih dahulu.'
            );
        }

        Branch::query()->updateOrCreate(
            [
                'code' => 'CB02',
            ],
            [
                'default_work_schedule_id' => $defaultSchedule->id,
                'name' => 'Kantor Cabang 02',
                'address' => 'Jalan Brigjen Zein Hamid, Kompleks Katamso Indah No. A9',
                'latitude' => 3.53744900,
                'longitude' => 98.68446000,
                'geofence_radius' => 30.00,
                'maximum_accuracy' => 25.00,
                'status' => 'active',
            ]
        );
    }
}
