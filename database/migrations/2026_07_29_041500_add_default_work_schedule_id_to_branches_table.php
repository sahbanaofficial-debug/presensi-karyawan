<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan pola jadwal kerja default untuk setiap cabang.
     *
     * Kolom dibuat nullable agar data cabang lama tetap valid
     * sampai jadwal default ditetapkan melalui modul cabang.
     */
    public function up(): void
    {
        Schema::table(
            'branches',
            function (Blueprint $table): void {
                $table->foreignId(
                    'default_work_schedule_id'
                )
                    ->nullable()
                    ->after('id')
                    ->constrained('work_schedules')
                    ->restrictOnDelete();
            }
        );
    }

    /**
     * Menghapus hubungan jadwal kerja default cabang.
     */
    public function down(): void
    {
        Schema::table(
            'branches',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'default_work_schedule_id'
                );
            }
        );
    }
};
