<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan terminal pembangkit QR pada transaksi
     * presensi dan log validasi.
     */
    public function up(): void
    {
        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->foreignId(
                    'branch_terminal_id'
                )
                    ->nullable()
                    ->after('attendance_session_id')
                    ->constrained('branch_terminals')
                    ->restrictOnDelete();
            }
        );

        Schema::table(
            'validation_logs',
            function (Blueprint $table): void {
                $table->foreignId(
                    'branch_terminal_id'
                )
                    ->nullable()
                    ->after('attendance_session_id')
                    ->constrained('branch_terminals')
                    ->restrictOnDelete();
            }
        );
    }

    /**
     * Menghapus pelacakan terminal dari transaksi dan log.
     */
    public function down(): void
    {
        Schema::table(
            'validation_logs',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'branch_terminal_id'
                );
            }
        );

        Schema::table(
            'attendances',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'branch_terminal_id'
                );
            }
        );
    }
};