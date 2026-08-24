<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SeededTrialEmployeeDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_trial_accounts_use_numbered_display_names(): void
    {
        config([
            'presensi.initial_password' => 'PresensiAwal123!',
        ]);

        $this->seed(DatabaseSeeder::class);

        $expectedAccounts = [
            'percobaan1@presensi.test' => [
                'name' => 'Percobaan 1',
                'employee_number' => 'P001',
            ],
            'percobaan2@presensi.test' => [
                'name' => 'Percobaan 2',
                'employee_number' => 'P002',
            ],
            'percobaan3@presensi.test' => [
                'name' => 'Percobaan 3',
                'employee_number' => 'P003',
            ],
            'percobaan4@presensi.test' => [
                'name' => 'Percobaan 4',
                'employee_number' => 'P004',
            ],
            'percobaan5@presensi.test' => [
                'name' => 'Percobaan 5',
                'employee_number' => 'P005',
            ],
        ];

        foreach ($expectedAccounts as $email => $expected) {
            $user = DB::table('users')
                ->where('email', $email)
                ->first();

            $this->assertNotNull($user);
            $this->assertSame($expected['name'], $user->name);

            $this->assertDatabaseHas('employees', [
                'user_id' => $user->id,
                'employee_number' => $expected['employee_number'],
                'full_name' => $expected['name'],
                'position' => 'percobaan',
            ]);
        }

        $this->assertDatabaseMissing('users', [
            'email' => 'sahbana@presensi.test',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'dame@presensi.test',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'nadya@presensi.test',
        ]);
    }

    public function test_migration_restores_final_accounts_from_legacy_data(): void
    {
        config([
            'presensi.initial_password' => 'PresensiAwal123!',
        ]);

        $this->seed(DatabaseSeeder::class);

        $legacyAccounts = [
            'percobaan1@presensi.test' => [
                'email' => 'sahbana@presensi.test',
                'employee_number' => '02',
                'position' => 'Kepala Cabang',
            ],
            'percobaan2@presensi.test' => [
                'email' => 'dame@presensi.test',
                'employee_number' => '022',
                'position' => 'Kepala Gudang',
            ],
            'percobaan3@presensi.test' => [
                'email' => 'nadya@presensi.test',
                'employee_number' => '0222',
                'position' => 'Kasir',
            ],
        ];

        foreach ($legacyAccounts as $finalEmail => $legacy) {
            $userId = DB::table('users')
                ->where('email', $finalEmail)
                ->value('id');

            DB::table('users')
                ->where('id', $userId)
                ->update(['email' => $legacy['email']]);

            DB::table('employees')
                ->where('user_id', $userId)
                ->update([
                    'employee_number' => $legacy['employee_number'],
                    'position' => $legacy['position'],
                ]);
        }

        $newUserIds = DB::table('users')
            ->whereIn('email', [
                'percobaan4@presensi.test',
                'percobaan5@presensi.test',
            ])
            ->pluck('id');

        DB::table('employees')
            ->whereIn('user_id', $newUserIds)
            ->delete();

        DB::table('users')
            ->whereIn('id', $newUserIds)
            ->delete();

        $migration = require database_path(
            'migrations/2026_08_24_000002_restore_final_five_trial_accounts.php'
        );

        $migration->up();

        foreach (range(1, 5) as $number) {
            $email = sprintf(
                'percobaan%d@presensi.test',
                $number
            );
            $name = sprintf('Percobaan %d', $number);
            $employeeNumber = sprintf('P%03d', $number);

            $userId = DB::table('users')
                ->where('email', $email)
                ->value('id');

            $this->assertNotNull($userId);
            $this->assertDatabaseHas('employees', [
                'user_id' => $userId,
                'employee_number' => $employeeNumber,
                'full_name' => $name,
                'position' => 'percobaan',
            ]);
        }
    }
}
