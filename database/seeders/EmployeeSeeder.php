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
     * Membuat lima profil akun percobaan aktif untuk Cabang 02.
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
                'email' => 'percobaan1@presensi.test',
                'employee_number' => 'P001',
                'full_name' => 'Percobaan 1',
                'position' => 'percobaan',
                'phone_number' => null,
            ],
            [
                'email' => 'percobaan2@presensi.test',
                'employee_number' => 'P002',
                'full_name' => 'Percobaan 2',
                'position' => 'percobaan',
                'phone_number' => null,
            ],
            [
                'email' => 'percobaan3@presensi.test',
                'employee_number' => 'P003',
                'full_name' => 'Percobaan 3',
                'position' => 'percobaan',
                'phone_number' => null,
            ],
            [
                'email' => 'percobaan4@presensi.test',
                'employee_number' => 'P004',
                'full_name' => 'Percobaan 4',
                'position' => 'percobaan',
                'phone_number' => null,
            ],
            [
                'email' => 'percobaan5@presensi.test',
                'employee_number' => 'P005',
                'full_name' => 'Percobaan 5',
                'position' => 'percobaan',
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
