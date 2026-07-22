<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_logs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('attendance_session_id')
                ->nullable()
                ->constrained('attendance_sessions')
                ->nullOnDelete();

            $table->string('validation_type', 50);

            $table->enum('status', [
                'accepted',
                'rejected',
            ]);

            $table->string('reason', 255)
                ->nullable();

            $table->string('payload_reference', 255)
                ->nullable();

            $table->decimal('latitude', 10, 8)
                ->nullable();

            $table->decimal('longitude', 11, 8)
                ->nullable();

            $table->decimal('accuracy', 8, 2)
                ->nullable();

            $table->decimal('distance', 10, 2)
                ->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            $table->index(
                ['status', 'created_at'],
                'validation_logs_status_created_index'
            );

            $table->index(
                ['validation_type', 'created_at'],
                'validation_logs_type_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_logs');
    }
};
