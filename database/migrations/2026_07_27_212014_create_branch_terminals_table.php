<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat identitas perangkat terminal QR setiap cabang.
     */
    public function up(): void
    {
        Schema::create(
            'branch_terminals',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid('public_id')
                    ->unique();

                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();

                $table->string('name', 100);

                $table->char(
                    'device_token_hash',
                    64
                )
                    ->nullable()
                    ->unique();

                $table->char(
                    'activation_code_hash',
                    64
                )
                    ->nullable();

                $table->timestamp(
                    'activation_expires_at'
                )
                    ->nullable();

                $table->timestamp('activated_at')
                    ->nullable();

                $table->timestamp('last_seen_at')
                    ->nullable();

                $table->string('status', 20)
                    ->default('pending');

                $table->foreignId('created_by')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('revoked_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('revoked_at')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'branch_id',
                        'status',
                    ],
                    'branch_terminals_branch_status_index'
                );

                $table->index(
                    'activation_expires_at',
                    'branch_terminals_activation_expiry_index'
                );
            }
        );
    }

    /**
     * Menghapus terminal QR cabang.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_terminals');
    }
};
