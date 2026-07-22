<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class EmployeeSeeder extends Seeder
{
    /**
     * Membuat tiga profil karyawan aktif untuk Cabang 02.
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

        $employees = [
            [
                'email' => 'karyawan01@presensi.test',
                'employee_number' => 'KRY-001',
                'full_name' => 'Karyawan 01',
                'position' => 'Karyawan Cabang 02',
                'phone_number' => null,
            ],
            [
                'email' => 'karyawan02@presensi.test',
                'employee_number' => 'KRY-002',
                'full_name' => 'Karyawan 02',
                'position' => 'Karyawan Cabang 02',
                'phone_number' => null,
            ],
            [
                'email' => 'karyawan03@presensi.test',
                'employee_number' => 'KRY-003',
                'full_name' => 'Karyawan 03',
                'position' => 'Karyawan Cabang 02',
                'phone_number' => null,
            ],
        ];

        foreach ($employees as $employeeData) {
            $user = User::query()
                ->where('email', $employeeData['email'])
                ->where('role', 'employee')
                ->first();

            if ($user === null) {
                throw new RuntimeException(
                    "Akun {$employeeData['email']} belum tersedia atau bukan employee. "
                    .'Jalankan UserSeeder terlebih dahulu.'
                );
            }

            Employee::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'branch_id' => $branch->id,
                    'employee_number' => $employeeData['employee_number'],
                    'full_name' => $employeeData['full_name'],
                    'position' => $employeeData['position'],
                    'phone_number' => $employeeData['phone_number'],
                    'employment_status' => 'active',
                ]
            );
        }
    }
}
