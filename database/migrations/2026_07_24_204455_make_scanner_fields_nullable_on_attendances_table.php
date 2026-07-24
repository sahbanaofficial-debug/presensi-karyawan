<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengizinkan presensi manual tidak memiliki
     * sesi QR dan data lokasi perangkat.
     */
    public function up(): void
    {
        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'attendance_session_id',
                ]);
            }
        );

        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->unsignedBigInteger(
                    'attendance_session_id'
                )
                    ->nullable()
                    ->change();

                $table->decimal(
                    'latitude',
                    10,
                    8
                )
                    ->nullable()
                    ->change();

                $table->decimal(
                    'longitude',
                    11,
                    8
                )
                    ->nullable()
                    ->change();

                $table->decimal(
                    'accuracy',
                    8,
                    2
                )
                    ->nullable()
                    ->change();

                $table->decimal(
                    'distance',
                    10,
                    2
                )
                    ->nullable()
                    ->change();

                $table->decimal(
                    'geofence_radius',
                    8,
                    2
                )
                    ->nullable()
                    ->change();

                $table->foreign(
                    'attendance_session_id'
                )
                    ->references('id')
                    ->on('attendance_sessions')
                    ->restrictOnDelete();
            }
        );
    }

    /**
     * Mengembalikan seluruh field pemindaian
     * menjadi wajib diisi.
     */
    public function down(): void
    {
        $hasManualScannerData = DB::table(
            'attendances'
        )
            ->where(
                static function ($query): void {
                    $query
                        ->whereNull(
                            'attendance_session_id'
                        )
                        ->orWhereNull('latitude')
                        ->orWhereNull('longitude')
                        ->orWhereNull('accuracy')
                        ->orWhereNull('distance')
                        ->orWhereNull(
                            'geofence_radius'
                        );
                }
            )
            ->exists();

        if ($hasManualScannerData) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan karena '
                .'terdapat presensi manual tanpa sesi '
                .'atau data lokasi.'
            );
        }

        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'attendance_session_id',
                ]);
            }
        );

        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->unsignedBigInteger(
                    'attendance_session_id'
                )
                    ->nullable(false)
                    ->change();

                $table->decimal(
                    'latitude',
                    10,
                    8
                )
                    ->nullable(false)
                    ->change();

                $table->decimal(
                    'longitude',
                    11,
                    8
                )
                    ->nullable(false)
                    ->change();

                $table->decimal(
                    'accuracy',
                    8,
                    2
                )
                    ->nullable(false)
                    ->change();

                $table->decimal(
                    'distance',
                    10,
                    2
                )
                    ->nullable(false)
                    ->change();

                $table->decimal(
                    'geofence_radius',
                    8,
                    2
                )
                    ->nullable(false)
                    ->change();

                $table->foreign(
                    'attendance_session_id'
                )
                    ->references('id')
                    ->on('attendance_sessions')
                    ->restrictOnDelete();
            }
        );
    }
};
