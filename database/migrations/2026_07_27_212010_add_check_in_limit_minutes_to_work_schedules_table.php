<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan batas akhir presensi masuk setelah
     * waktu masuk yang dijadwalkan.
     */
    public function up(): void
    {
        Schema::table(
            'work_schedules',
            function (Blueprint $table): void {
                $table->unsignedSmallInteger(
                    'check_in_limit_minutes'
                )
                    ->default(30)
                    ->after('check_in_open_minutes');
            }
        );
    }

    /**
     * Menghapus konfigurasi batas akhir presensi masuk.
     */
    public function down(): void
    {
        Schema::table(
            'work_schedules',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'check_in_limit_minutes'
                );
            }
        );
    }
};