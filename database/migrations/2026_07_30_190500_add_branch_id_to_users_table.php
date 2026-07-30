<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghubungkan akun admin operasional dengan satu cabang.
     *
     * Kolom nullable menjaga akun HRD, karyawan, dan admin lama
     * tetap valid sampai penugasan cabang dilakukan.
     */
    public function up(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->restrictOnDelete();
            }
        );
    }

    /**
     * Menghapus hubungan akun pengguna dengan cabang.
     */
    public function down(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'branch_id'
                );
            }
        );
    }
};
