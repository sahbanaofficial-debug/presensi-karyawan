<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_logs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->restrictOnDelete();

            $table->foreignId('changed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->json('before_data');

            $table->json('after_data');

            $table->text('reason');

            $table->timestamp('created_at')
                ->useCurrent();

            $table->index(
                ['attendance_id', 'created_at'],
                'correction_logs_attendance_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_logs');
    }
};
