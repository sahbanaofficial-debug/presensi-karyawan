<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat detail roster mingguan per karyawan dan tanggal.
     */
    public function up(): void
    {
        Schema::create(
            'weekly_schedule_items',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('weekly_schedule_id')
                    ->constrained('weekly_schedules')
                    ->cascadeOnDelete();

                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->restrictOnDelete();

                $table->foreignId('work_schedule_id')
                    ->nullable()
                    ->constrained('work_schedules')
                    ->restrictOnDelete();

                $table->date('schedule_date');

                $table->string('schedule_status', 20);

                $table->text('notes')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'employee_id',
                        'schedule_date',
                    ],
                    'weekly_schedule_items_employee_date_unique'
                );

                $table->index(
                    [
                        'weekly_schedule_id',
                        'schedule_date',
                    ],
                    'weekly_schedule_items_week_date_index'
                );
            }
        );
    }

    /**
     * Menghapus detail roster mingguan.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_schedule_items');
    }
};
