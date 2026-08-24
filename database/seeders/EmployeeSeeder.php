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
                'email' => 'sahbana@presensi.test',
                'employee_number' => '02',
                'full_name' => 'Percobaan 1',
                'position' => 'Kepala Cabang',
                'phone_number' => null,
            ],
            [
                'email' => 'dame@presensi.test',
                'employee_number' => '022',
                'full_name' => 'Percobaan 2',
                'position' => 'Kepala Gudang',
                'phone_number' => null,
            ],
            [
                'email' => 'nadya@presensi.test',
                'employee_number' => '0222',
                'full_name' => 'Percobaan 3',
                'position' => 'Kasir',
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
