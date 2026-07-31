<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    /**
     * Mengisi tiga pola jadwal kerja PT Gadai Ogan Baru.
     */
    public function run(): void
    {
        $schedules = [
            [
                'name' => 'Jadwal Penuh',
                'check_in_time' => '08:45:00',
                'check_out_time' => '21:30:00',
                'check_in_open_minutes' => 30,
                'late_tolerance_minutes' => 5,
                'check_out_limit_minutes' => 60,
                'status' => 'active',
            ],
            [
                'name' => 'Pulang Sore',
                'check_in_time' => '08:45:00',
                'check_out_time' => '17:00:00',
                'check_in_open_minutes' => 30,
                'late_tolerance_minutes' => 5,
                'check_out_limit_minutes' => 60,
                'status' => 'active',
            ],
            [
                'name' => 'Masuk Siang',
                'check_in_time' => '13:00:00',
                'check_out_time' => '21:30:00',
                'check_in_open_minutes' => 30,
                'late_tolerance_minutes' => 5,
                'check_out_limit_minutes' => 60,
                'status' => 'active',
            ],
        ];

        foreach ($schedules as $schedule) {
            WorkSchedule::query()->updateOrCreate(
                [
                    'name' => $schedule['name'],
                ],
                $schedule
            );
        }
    }
}
