<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 100)
                ->unique();

            $table->time('check_in_time');

            $table->time('check_out_time');

            $table->unsignedSmallInteger('check_in_open_minutes')
                ->default(30);

            $table->unsignedSmallInteger('late_tolerance_minutes')
                ->default(5);

            $table->unsignedSmallInteger('check_out_limit_minutes')
                ->default(60);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
