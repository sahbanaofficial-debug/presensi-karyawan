<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')
                ->unique();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->enum('attendance_type', [
                'check_in',
                'check_out',
            ]);

            $table->date('session_date');

            $table->dateTime('start_time');

            $table->dateTime('end_time');

            $table->text('encrypted_secret');

            $table->enum('status', [
                'active',
                'closed',
                'expired',
            ])->default('active');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('closed_at')
                ->nullable();

            $table->timestamps();

            $table->index(
                [
                    'branch_id',
                    'session_date',
                    'attendance_type',
                    'status',
                ],
                'attendance_sessions_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
