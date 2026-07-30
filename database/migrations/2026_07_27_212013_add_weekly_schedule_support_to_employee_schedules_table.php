<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan asal roster mingguan, status cuti,
     * dan snapshot pola waktu pada jadwal harian.
     */
    public function up(): void
    {
        $this->changeScheduleStatusEnum([
            'work',
            'off',
            'leave',
            'permit',
            'sick',
        ]);

        Schema::table(
            'employee_schedules',
            function (Blueprint $table): void {
                $table->foreignId(
                    'weekly_schedule_item_id'
                )
                    ->nullable()
                    ->after('id')
                    ->unique()
                    ->constrained(
                        'weekly_schedule_items'
                    )
                    ->restrictOnDelete();

                $table->string(
                    'schedule_source',
                    20
                )
                    ->default('manual')
                    ->after('schedule_status');

                $table->string(
                    'work_schedule_name_snapshot',
                    100
                )
                    ->nullable()
                    ->after('schedule_source');

                $table->time(
                    'check_in_time_snapshot'
                )
                    ->nullable()
                    ->after(
                        'work_schedule_name_snapshot'
                    );

                $table->time(
                    'check_out_time_snapshot'
                )
                    ->nullable()
                    ->after(
                        'check_in_time_snapshot'
                    );

                $table->unsignedSmallInteger(
                    'check_in_open_minutes_snapshot'
                )
                    ->nullable()
                    ->after(
                        'check_out_time_snapshot'
                    );

                $table->unsignedSmallInteger(
                    'check_in_limit_minutes_snapshot'
                )
                    ->nullable()
                    ->after(
                        'check_in_open_minutes_snapshot'
                    );

                $table->unsignedSmallInteger(
                    'late_tolerance_minutes_snapshot'
                )
                    ->nullable()
                    ->after(
                        'check_in_limit_minutes_snapshot'
                    );

                $table->unsignedSmallInteger(
                    'check_out_limit_minutes_snapshot'
                )
                    ->nullable()
                    ->after(
                        'late_tolerance_minutes_snapshot'
                    );

                $table->index(
                    [
                        'schedule_source',
                        'schedule_date',
                    ],
                    'employee_schedules_source_date_index'
                );
            }
        );

        $this->backfillExistingScheduleSnapshots();
    }

    /**
     * Mengembalikan struktur jadwal harian lama.
     */
    public function down(): void
    {
        $hasWeeklySchedules = DB::table(
            'employee_schedules'
        )
            ->whereNotNull(
                'weekly_schedule_item_id'
            )
            ->exists();

        if ($hasWeeklySchedules) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan karena '
                .'terdapat jadwal harian hasil publikasi '
                .'jadwal mingguan.'
            );
        }

        $hasLeaveSchedules = DB::table(
            'employee_schedules'
        )
            ->where(
                'schedule_status',
                'leave'
            )
            ->exists();

        if ($hasLeaveSchedules) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan karena '
                .'terdapat jadwal berstatus cuti.'
            );
        }

        Schema::table(
            'employee_schedules',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'employee_schedules_source_date_index'
                );

                $table->dropUnique(
                    'employee_schedules_weekly_schedule_item_id_unique'
                );

                $table->dropConstrainedForeignId(
                    'weekly_schedule_item_id'
                );

                $table->dropColumn([
                    'schedule_source',
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

        $this->changeScheduleStatusEnum([
            'work',
            'off',
            'permit',
            'sick',
        ]);
    }

    /**
     * Mengisi snapshot untuk jadwal harian lama yang
     * masih mempunyai pola jadwal kerja.
     */
    private function backfillExistingScheduleSnapshots(): void
    {
        DB::table('employee_schedules')
            ->whereNotNull('work_schedule_id')
            ->orderBy('id')
            ->chunkById(
                100,
                static function ($employeeSchedules): void {
                    foreach ($employeeSchedules as $employeeSchedule) {
                        $workSchedule = DB::table(
                            'work_schedules'
                        )
                            ->where(
                                'id',
                                $employeeSchedule->work_schedule_id
                            )
                            ->first();

                        if ($workSchedule === null) {
                            continue;
                        }

                        DB::table('employee_schedules')
                            ->where(
                                'id',
                                $employeeSchedule->id
                            )
                            ->update([
                                'work_schedule_name_snapshot' => $workSchedule->name,

                                'check_in_time_snapshot' => $workSchedule->check_in_time,

                                'check_out_time_snapshot' => $workSchedule->check_out_time,

                                'check_in_open_minutes_snapshot' => $workSchedule
                                    ->check_in_open_minutes,

                                'check_in_limit_minutes_snapshot' => $workSchedule
                                    ->check_in_limit_minutes,

                                'late_tolerance_minutes_snapshot' => $workSchedule
                                    ->late_tolerance_minutes,

                                'check_out_limit_minutes_snapshot' => $workSchedule
                                    ->check_out_limit_minutes,
                            ]);
                    }
                }
            );
    }

    /**
     * Mengubah daftar nilai enum schedule_status.
     * Proyek menggunakan MySQL sebagai basis data utama.
     *
     * @param  list<string>  $values
     */
    private function changeScheduleStatusEnum(
        array $values
    ): void {
        $driver = DB::getDriverName();

        if (
            in_array(
                $driver,
                [
                    'mysql',
                    'mariadb',
                ],
                true
            )
        ) {
            $quotedValues = implode(
                ', ',
                array_map(
                    static fn (
                        string $value
                    ): string => "'{$value}'",
                    $values
                )
            );

            DB::statement(
                'ALTER TABLE employee_schedules '
                .'MODIFY schedule_status ENUM('
                .$quotedValues
                .') NOT NULL'
            );

            return;
        }

        Schema::table(
            'employee_schedules',
            function (Blueprint $table) use (
                $values
            ): void {
                $table->enum(
                    'schedule_status',
                    $values
                )->change();
            }
        );
    }
};
