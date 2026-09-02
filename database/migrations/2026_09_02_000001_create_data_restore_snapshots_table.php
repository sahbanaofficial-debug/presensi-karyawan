<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'data_restore_snapshots',
            function (Blueprint $table): void {
                $table->id();
                $table->string('restoration_key', 100)->unique();
                $table->json('snapshot_before');
                $table->json('result_summary')->nullable();
                $table->timestamp('restored_at')->nullable();
                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('data_restore_snapshots');
    }
};
