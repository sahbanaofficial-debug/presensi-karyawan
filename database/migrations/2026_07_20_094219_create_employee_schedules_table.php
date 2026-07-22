<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_schedules', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('work_schedule_id')
                ->nullable()
                ->constrained('work_schedules')
                ->restrictOnDelete();

            $table->date('schedule_date');

            $table->enum('schedule_status', [
                'work',
                'off',
                'permit',
                'sick',
            ]);

            $table->foreignId('approved_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['employee_id', 'schedule_date'],
                'employee_schedules_employee_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};
