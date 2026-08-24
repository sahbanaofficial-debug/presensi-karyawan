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
     * Mengisi akun awal HRD, admin operasional, dan karyawan.
     */
    public function run(): void
    {
        $initialPassword = (string) config('presensi.initial_password', '');

        if (mb_strlen($initialPassword) < 12) {
            throw new RuntimeException(
                'PRESENSI_INITIAL_PASSWORD wajib diisi minimal 12 karakter '
                .'sebelum menjalankan DatabaseSeeder.'
            );
        }

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
                'name' => 'Percobaan 1',
                'email' => 'sahbana@presensi.test',
                'role' => 'employee',
            ],
            [
                'branch_id' => null,
                'name' => 'Percobaan 2',
                'email' => 'dame@presensi.test',
                'role' => 'employee',
            ],
            [
                'branch_id' => null,
                'name' => 'Percobaan 3',
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
                    'password' => Hash::make($initialPassword),
                    'role' => $userData['role'],
                    'status' => 'active',
                    'last_login_at' => null,
                ]
            );
        }
    }
}
