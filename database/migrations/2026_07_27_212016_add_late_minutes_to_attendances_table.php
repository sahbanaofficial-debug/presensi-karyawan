<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan jumlah menit keterlambatan presensi masuk.
     */
    public function up(): void
    {
        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->unsignedSmallInteger(
                    'late_minutes'
                )
                    ->nullable()
                    ->after('punctuality_status');
            }
        );
    }

    /**
     * Menghapus jumlah menit keterlambatan.
     */
    public function down(): void
    {
        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'late_minutes'
                );
            }
        );
    }
};
