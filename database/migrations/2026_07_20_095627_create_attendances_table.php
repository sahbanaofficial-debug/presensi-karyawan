<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('attendance_session_id')
                ->constrained('attendance_sessions')
                ->restrictOnDelete();

            $table->foreignId('employee_schedule_id')
                ->constrained('employee_schedules')
                ->restrictOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->enum('attendance_type', [
                'check_in',
                'check_out',
            ]);

            $table->date('attendance_date');

            $table->dateTime('attendance_time');

            $table->decimal('latitude', 10, 8);

            $table->decimal('longitude', 11, 8);

            $table->decimal('accuracy', 8, 2);

            $table->decimal('distance', 10, 2);

            $table->decimal('geofence_radius', 8, 2);

            $table->enum('attendance_status', [
                'present',
            ])->default('present');

            $table->enum('punctuality_status', [
                'on_time',
                'late',
                'not_applicable',
            ]);

            $table->enum('validation_status', [
                'accepted',
            ])->default('accepted');

            $table->timestamps();

            $table->unique(
                ['employee_schedule_id', 'attendance_type'],
                'attendances_schedule_type_unique'
            );

            $table->index(
                ['employee_id', 'attendance_date'],
                'attendances_employee_date_index'
            );

            $table->index(
                ['branch_id', 'attendance_date'],
                'attendances_branch_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
