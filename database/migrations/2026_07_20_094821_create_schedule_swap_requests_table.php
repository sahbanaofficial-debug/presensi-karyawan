<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_swap_requests', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('requester_employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('partner_employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->date('requester_date');

            $table->date('partner_date');

            $table->text('reason');

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('approved_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_swap_requests');
    }
};
