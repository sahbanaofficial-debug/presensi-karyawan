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
}
