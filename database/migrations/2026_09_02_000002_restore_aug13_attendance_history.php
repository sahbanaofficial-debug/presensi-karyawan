<?php

declare(strict_types=1);

use App\Support\Aug13AttendanceHistoryRestorer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        app(Aug13AttendanceHistoryRestorer::class)->restore();
    }

    public function down(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        throw new RuntimeException(
            'Riwayat presensi 13 Agustus tidak boleh dihapus '
            .'melalui rollback otomatis.'
        );
    }
};
