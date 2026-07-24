<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan informasi koreksi pada presensi
     * dan tabel riwayat koreksi.
     */
    public function up(): void
    {
        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->string(
                    'record_source',
                    20
                )
                    ->default('scanner')
                    ->after('validation_status');

                $table->foreignId(
                    'last_corrected_by'
                )
                    ->nullable()
                    ->after('record_source')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->text(
                    'last_correction_reason'
                )
                    ->nullable()
                    ->after('last_corrected_by');

                $table->timestamp(
                    'last_corrected_at'
                )
                    ->nullable()
                    ->after('last_correction_reason');

                $table->index(
                    [
                        'record_source',
                        'last_corrected_at',
                    ],
                    'attendances_source_corrected_index'
                );
            }
        );

        Schema::create(
            'attendance_corrections',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'attendance_id'
                )
                    ->constrained('attendances')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'corrected_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string(
                    'action',
                    20
                );

                $table->text('reason');

                $table->json(
                    'before_data'
                )->nullable();

                $table->json(
                    'after_data'
                );

                $table->timestamp(
                    'created_at'
                )->useCurrent();

                $table->index(
                    [
                        'attendance_id',
                        'created_at',
                    ],
                    'attendance_corrections_attendance_index'
                );

                $table->index(
                    [
                        'action',
                        'created_at',
                    ],
                    'attendance_corrections_action_index'
                );
            }
        );
    }

    /**
     * Menghapus struktur koreksi manual.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_corrections'
        );

        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'attendances_source_corrected_index'
                );

                $table->dropForeign([
                    'last_corrected_by',
                ]);

                $table->dropColumn([
                    'record_source',
                    'last_corrected_by',
                    'last_correction_reason',
                    'last_corrected_at',
                ]);
            }
        );
    }
};
