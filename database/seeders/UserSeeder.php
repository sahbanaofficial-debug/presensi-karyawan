<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        $users = [
            [
                'name' => 'HRD',
                'email' => 'hrd@presensi.test',
                'role' => 'hrd',
            ],
            [
                'name' => 'Admin Operasional',
                'email' => 'admin@presensi.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Karyawan 01',
                'email' => 'karyawan01@presensi.test',
                'role' => 'employee',
            ],
            [
                'name' => 'Karyawan 02',
                'email' => 'karyawan02@presensi.test',
                'role' => 'employee',
            ],
            [
                'name' => 'Karyawan 03',
                'email' => 'karyawan03@presensi.test',
                'role' => 'employee',
            ],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                [
                    'email' => $userData['email'],
                ],
                [
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
