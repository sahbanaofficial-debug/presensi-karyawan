<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat header jadwal mingguan per cabang.
     */
    public function up(): void
    {
        Schema::create(
            'weekly_schedules',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();

                $table->date('week_start_date');

                $table->date('week_end_date');

                $table->string('status', 20)
                    ->default('draft');

                $table->foreignId('created_by')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('published_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('published_at')
                    ->nullable();

                $table->text('notes')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'branch_id',
                        'week_start_date',
                    ],
                    'weekly_schedules_branch_week_unique'
                );

                $table->index(
                    [
                        'branch_id',
                        'status',
                        'week_start_date',
                    ],
                    'weekly_schedules_lookup_index'
                );
            }
        );
    }

    /**
     * Menghapus header jadwal mingguan.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_schedules');
    }
};