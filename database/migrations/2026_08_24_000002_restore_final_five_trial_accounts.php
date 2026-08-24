<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Memulihkan lima akun percobaan yang digunakan pada pengujian final.
     */
    public function up(): void
    {
        $branchId = DB::table('branches')
            ->where('code', 'CB02')
            ->value('id');

        if ($branchId === null) {
            // Pada instalasi baru, cabang dan akun final dibuat oleh seeder.
            return;
        }

        $accounts = [
            [
                'legacy_email' => 'sahbana@presensi.test',
                'email' => 'percobaan1@presensi.test',
                'name' => 'Percobaan 1',
                'employee_number' => 'P001',
            ],
            [
                'legacy_email' => 'dame@presensi.test',
                'email' => 'percobaan2@presensi.test',
                'name' => 'Percobaan 2',
                'employee_number' => 'P002',
            ],
            [
                'legacy_email' => 'nadya@presensi.test',
                'email' => 'percobaan3@presensi.test',
                'name' => 'Percobaan 3',
                'employee_number' => 'P003',
            ],
            [
                'legacy_email' => null,
                'email' => 'percobaan4@presensi.test',
                'name' => 'Percobaan 4',
                'employee_number' => 'P004',
            ],
            [
                'legacy_email' => null,
                'email' => 'percobaan5@presensi.test',
                'name' => 'Percobaan 5',
                'employee_number' => 'P005',
            ],
        ];

        DB::transaction(function () use ($accounts, $branchId): void {
            $initialPassword = null;

            foreach ($accounts as $account) {
                $user = DB::table('users')
                    ->where('email', $account['email'])
                    ->first();

                if (
                    $user === null
                    && $account['legacy_email'] !== null
                ) {
                    $user = DB::table('users')
                        ->where('email', $account['legacy_email'])
                        ->first();
                }

                if ($user === null) {
                    $initialPassword ??= $this->initialPassword();

                    $userId = DB::table('users')->insertGetId([
                        'branch_id' => null,
                        'name' => $account['name'],
                        'email' => $account['email'],
                        'email_verified_at' => now(),
                        'password' => Hash::make($initialPassword),
                        'role' => 'employee',
                        'status' => 'active',
                        'last_login_at' => null,
                        'remember_token' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $userId = (int) $user->id;

                    DB::table('users')
                        ->where('id', $userId)
                        ->update([
                            'branch_id' => null,
                            'name' => $account['name'],
                            'email' => $account['email'],
                            'role' => 'employee',
                            'status' => 'active',
                            'updated_at' => now(),
                        ]);
                }

                $employee = DB::table('employees')
                    ->where('user_id', $userId)
                    ->first();

                $employeeData = [
                    'branch_id' => $branchId,
                    'employee_number' => $account['employee_number'],
                    'full_name' => $account['name'],
                    'position' => 'percobaan',
                    'phone_number' => null,
                    'employment_status' => 'active',
                    'updated_at' => now(),
                ];

                if ($employee === null) {
                    DB::table('employees')->insert([
                        'user_id' => $userId,
                        ...$employeeData,
                        'created_at' => now(),
                    ]);
                } else {
                    DB::table('employees')
                        ->where('id', $employee->id)
                        ->update($employeeData);
                }
            }
        });
    }

    /**
     * Data akun tidak dihapus saat rollback agar riwayat presensi tetap aman.
     */
    public function down(): void
    {
        // Sengaja tidak menghapus akun yang mungkin sudah memiliki transaksi.
    }

    private function initialPassword(): string
    {
        $password = (string) config('presensi.initial_password', '');

        if (mb_strlen($password) < 12) {
            throw new RuntimeException(
                'PRESENSI_INITIAL_PASSWORD wajib diisi minimal 12 karakter.'
            );
        }

        return $password;
    }
};
