<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mengembalikan nama tampilan akun pengujian pada database berjalan.
     */
    public function up(): void
    {
        $this->renameTrialEmployees([
            'sahbana@presensi.test' => 'Percobaan 1',
            'dame@presensi.test' => 'Percobaan 2',
            'nadya@presensi.test' => 'Percobaan 3',
        ]);
    }

    /**
     * Mengembalikan nama bawaan sebelumnya apabila migrasi dibatalkan.
     */
    public function down(): void
    {
        $this->renameTrialEmployees([
            'sahbana@presensi.test' => 'Sahbana',
            'dame@presensi.test' => 'Dame',
            'nadya@presensi.test' => 'Nadya',
        ]);
    }

    /**
     * Memperbarui nama akun dan profil karyawan secara konsisten.
     *
     * @param  array<string, string>  $displayNames
     */
    private function renameTrialEmployees(array $displayNames): void
    {
        foreach ($displayNames as $email => $displayName) {
            $userId = DB::table('users')
                ->where('email', $email)
                ->value('id');

            if ($userId === null) {
                continue;
            }

            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'name' => $displayName,
                    'updated_at' => now(),
                ]);

            DB::table('employees')
                ->where('user_id', $userId)
                ->update([
                    'full_name' => $displayName,
                    'updated_at' => now(),
                ]);
        }
    }
};
