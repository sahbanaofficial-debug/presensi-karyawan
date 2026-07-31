<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Password awal khusus lingkungan pengembangan lokal.
     */
    private const DEFAULT_PASSWORD = 'Presensi123!';

    /**
     * Mengisi akun awal HRD, admin operasional, dan karyawan.
     */
    public function run(): void
    {
        $branch = Branch::query()
            ->where('code', 'CB02')
            ->first();

        if ($branch === null) {
            throw new RuntimeException(
                'Cabang 02 belum tersedia. Jalankan BranchSeeder terlebih dahulu.'
            );
        }

        $users = [
            [
                'branch_id' => null,
                'name' => 'HRD',
                'email' => 'hrd@presensi.test',
                'role' => 'hrd',
            ],
            [
                'branch_id' => $branch->id,
                'name' => 'Admin Operasional',
                'email' => 'admin@presensi.test',
                'role' => 'admin',
            ],
            [
                'branch_id' => null,
                'name' => 'Sahbana',
                'email' => 'sahbana@presensi.test',
                'role' => 'employee',
            ],
            [
                'branch_id' => null,
                'name' => 'Dame',
                'email' => 'dame@presensi.test',
                'role' => 'employee',
            ],
            [
                'branch_id' => null,
                'name' => 'Nadya',
                'email' => 'nadya@presensi.test',
                'role' => 'employee',
            ],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                [
                    'email' => $userData['email'],
                ],
                [
                    'branch_id' => $userData['branch_id'],
                    'name' => $userData['name'],
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'role' => $userData['role'],
                    'status' => 'active',
                    'last_login_at' => null,
                ]
            );
        }
    }
}
