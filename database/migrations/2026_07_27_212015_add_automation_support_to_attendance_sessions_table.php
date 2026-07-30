<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan sesi otomatis dengan mode penentuan
     * masuk atau pulang oleh server.
     */
    public function up(): void
    {
        $this->changeAttendanceTypeEnum([
            'check_in',
            'check_out',
            'auto',
        ]);

        Schema::table(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'created_by',
                ]);
            }
        );

        Schema::table(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->change();

                $table->foreign(
                    'created_by'
                )
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();
            }
        );

        Schema::table(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table->foreignId(
                    'weekly_schedule_id'
                )
                    ->nullable()
                    ->after('branch_id')
                    ->constrained(
                        'weekly_schedules'
                    )
                    ->restrictOnDelete();

                $table->string(
                    'session_source',
                    20
                )
                    ->default('manual')
                    ->after('attendance_type');

                $table->string(
                    'automation_key',
                    100
                )
                    ->nullable()
                    ->after('session_source')
                    ->unique();

                $table->index(
                    [
                        'session_source',
                        'session_date',
                        'status',
                    ],
                    'attendance_sessions_source_date_status_index'
                );
            }
        );
    }

    /**
     * Mengembalikan struktur sesi manual lama.
     */
    public function down(): void
    {
        $hasAutomaticSessions = DB::table(
            'attendance_sessions'
        )
            ->where(
                static function ($query): void {
                    $query
                        ->where(
                            'attendance_type',
                            'auto'
                        )
                        ->orWhere(
                            'session_source',
                            'automatic'
                        )
                        ->orWhereNotNull(
                            'automation_key'
                        )
                        ->orWhereNotNull(
                            'weekly_schedule_id'
                        )
                        ->orWhereNull(
                            'created_by'
                        );
                }
            )
            ->exists();

        if ($hasAutomaticSessions) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan karena '
                .'terdapat sesi presensi otomatis.'
            );
        }

        Schema::table(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'attendance_sessions_source_date_status_index'
                );

                $table->dropUnique(
                    'attendance_sessions_automation_key_unique'
                );

                $table->dropConstrainedForeignId(
                    'weekly_schedule_id'
                );

                $table->dropColumn([
                    'session_source',
                    'automation_key',
                ]);
            }
        );

        Schema::table(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'created_by',
                ]);
            }
        );

        Schema::table(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table->foreignId(
                    'created_by'
                )
                    ->nullable(false)
                    ->change();

                $table->foreign(
                    'created_by'
                )
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();
            }
        );

        $this->changeAttendanceTypeEnum([
            'check_in',
            'check_out',
        ]);
    }

    /**
     * Mengubah daftar nilai enum attendance_type.
     * Proyek menggunakan MySQL sebagai basis data utama.
     *
     * @param  list<string>  $values
     */
    private function changeAttendanceTypeEnum(
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
                'ALTER TABLE attendance_sessions '
                .'MODIFY attendance_type ENUM('
                .$quotedValues
                .') NOT NULL'
            );

            return;
        }

        Schema::table(
            'attendance_sessions',
            function (Blueprint $table) use (
                $values
            ): void {
                $table->enum(
                    'attendance_type',
                    $values
                )->change();
            }
        );
    }
};
