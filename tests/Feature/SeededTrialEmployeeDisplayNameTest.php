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

        $expectedNames = [
            'sahbana@presensi.test' => 'Percobaan 1',
            'dame@presensi.test' => 'Percobaan 2',
            'nadya@presensi.test' => 'Percobaan 3',
        ];

        foreach ($expectedNames as $email => $displayName) {
            $user = DB::table('users')
                ->where('email', $email)
                ->first();

            $this->assertNotNull($user);
            $this->assertSame($displayName, $user->name);

            $this->assertDatabaseHas('employees', [
                'user_id' => $user->id,
                'full_name' => $displayName,
            ]);
        }
    }
}
