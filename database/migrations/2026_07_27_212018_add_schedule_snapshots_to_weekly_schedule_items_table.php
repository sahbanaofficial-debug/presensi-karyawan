<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan snapshot pola kerja pada item roster mingguan.
     *
     * Snapshot menjaga histori roster yang telah diterbitkan agar
     * tidak berubah ketika master pola kerja diperbarui.
     */
    public function up(): void
    {
        Schema::table(
            'weekly_schedule_items',
            function (Blueprint $table): void {
                $table->string(
                    'work_schedule_name_snapshot',
                    100
                )->nullable()->after('schedule_status');

                $table->time(
                    'check_in_time_snapshot'
                )->nullable()->after(
                    'work_schedule_name_snapshot'
                );

                $table->time(
                    'check_out_time_snapshot'
                )->nullable()->after(
                    'check_in_time_snapshot'
                );

                $table->unsignedSmallInteger(
                    'check_in_open_minutes_snapshot'
                )->nullable()->after(
                    'check_out_time_snapshot'
                );

                $table->unsignedSmallInteger(
                    'check_in_limit_minutes_snapshot'
                )->nullable()->after(
                    'check_in_open_minutes_snapshot'
                );

                $table->unsignedSmallInteger(
                    'late_tolerance_minutes_snapshot'
                )->nullable()->after(
                    'check_in_limit_minutes_snapshot'
                );

                $table->unsignedSmallInteger(
                    'check_out_limit_minutes_snapshot'
                )->nullable()->after(
                    'late_tolerance_minutes_snapshot'
                );
            }
        );

        $this->backfillExistingSnapshots();
    }

    /**
     * Menghapus snapshot roster mingguan.
     */
    public function down(): void
    {
        Schema::table(
            'weekly_schedule_items',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'work_schedule_name_snapshot',
                    'check_in_time_snapshot',
                    'check_out_time_snapshot',
                    'check_in_open_minutes_snapshot',
                    'check_in_limit_minutes_snapshot',
                    'late_tolerance_minutes_snapshot',
                    'check_out_limit_minutes_snapshot',
                ]);
            }
        );
    }

    /**
     * Mengisi snapshot untuk item roster yang sudah ada.
     */
    private function backfillExistingSnapshots(): void
    {
        DB::table('weekly_schedule_items')
            ->whereNotNull('work_schedule_id')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($items): void {
                    $workScheduleIds = $items
                        ->pluck('work_schedule_id')
                        ->filter()
                        ->unique()
                        ->values();

                    $workSchedules = DB::table(
                        'work_schedules'
                    )
                        ->whereIn(
                            'id',
                            $workScheduleIds
                        )
                        ->get()
                        ->keyBy('id');

                    foreach ($items as $item) {
                        $workSchedule = $workSchedules->get(
                            $item->work_schedule_id
                        );

                        if ($workSchedule === null) {
                            continue;
                        }

                        DB::table('weekly_schedule_items')
                            ->where('id', $item->id)
                            ->update([
                                'work_schedule_name_snapshot' =>
                                    $workSchedule->name,

                                'check_in_time_snapshot' =>
                                    $workSchedule->check_in_time,

                                'check_out_time_snapshot' =>
                                    $workSchedule->check_out_time,

                                'check_in_open_minutes_snapshot' =>
                                    $workSchedule
                                        ->check_in_open_minutes,

                                'check_in_limit_minutes_snapshot' =>
                                    $workSchedule
                                        ->check_in_limit_minutes,

                                'late_tolerance_minutes_snapshot' =>
                                    $workSchedule
                                        ->late_tolerance_minutes,

                                'check_out_limit_minutes_snapshot' =>
                                    $workSchedule
                                        ->check_out_limit_minutes,
                            ]);
                    }
                }
            );
    }
};