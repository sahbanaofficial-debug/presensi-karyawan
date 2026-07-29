<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'employee_schedules',
            function (Blueprint $table): void {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->change();
            }
        );
    }

    public function down(): void
    {
        if (
            DB::table('employee_schedules')
                ->whereNull('approved_by')
                ->exists()
        ) {
            throw new RuntimeException(
                'Rollback ditolak karena terdapat jadwal harian tanpa approver.'
            );
        }

        Schema::table(
            'employee_schedules',
            function (Blueprint $table): void {
                $table->foreignId('approved_by')
                    ->nullable(false)
                    ->change();
            }
        );
    }
};
