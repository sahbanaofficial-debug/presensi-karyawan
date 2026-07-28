<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeeklyAttendanceSessionLifecycleCommandTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_command_expires_elapsed_active_automatic_session(): void
    {
        $this->travelTo(
            '2026-11-02 18:00:01'
        );

        $branch = Branch::factory()->create();

        $session = $this->createAutomaticSession(
            branch: $branch,
            sessionDate: '2026-11-02',
            startTime: '07:30:00',
            endTime: '18:00:00'
        );

        $this->artisan(
            'attendance-sessions:expire-automatic'
        )
            ->expectsOutputToContain(
                'Sesi otomatis kedaluwarsa: 1.'
            )
            ->assertSuccessful();

        $this->assertTrue(
            $session->fresh()->isExpired()
        );

        $this->assertDatabaseHas(
            'attendance_sessions',
            [
                'id' => $session->id,
                'status' => 'expired',
                'closed_at' => null,
            ]
        );
    }

    public function test_session_remains_active_exactly_at_end_time(): void
    {
        $this->travelTo(
            '2026-11-03 18:00:00'
        );

        $branch = Branch::factory()->create();

        $session = $this->createAutomaticSession(
            branch: $branch,
            sessionDate: '2026-11-03',
            startTime: '07:30:00',
            endTime: '18:00:00'
        );

        $this->artisan(
            'attendance-sessions:expire-automatic'
        )
            ->expectsOutputToContain(
                'Sesi otomatis kedaluwarsa: 0.'
            )
            ->assertSuccessful();

        $this->assertTrue(
            $session->fresh()->isActive()
        );
    }

    public function test_elapsed_manual_and_closed_sessions_are_not_changed(): void
    {
        $this->travelTo(
            '2026-11-04 18:00:01'
        );

        $branch = Branch::factory()->create();

        $creator = User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);

        $manualSession =
            AttendanceSession::query()->create([
                'branch_id' => $branch->id,
                'weekly_schedule_id' => null,
                'attendance_type' => 'check_in',
                'session_source' => 'manual',
                'automation_key' => null,
                'session_date' => '2026-11-04',

                'start_time' => '2026-11-04 07:30:00',

                'end_time' => '2026-11-04 18:00:00',

                'encrypted_secret' => 'JBSWY3DPEHPK3PXP',

                'status' => 'active',
                'created_by' => $creator->id,
                'closed_at' => null,
            ]);

        $closedAutomatic =
            $this->createAutomaticSession(
                branch: $branch,
                sessionDate: '2026-11-04',
                startTime: '07:30:00',
                endTime: '18:00:00',
                status: 'closed'
            );

        $closedAutomatic->update([
            'closed_at' => '2026-11-04 17:00:00',
        ]);

        $this->artisan(
            'attendance-sessions:expire-automatic'
        )
            ->expectsOutputToContain(
                'Sesi otomatis kedaluwarsa: 0.'
            )
            ->assertSuccessful();

        $this->assertTrue(
            $manualSession->fresh()->isActive()
        );

        $this->assertTrue(
            $closedAutomatic->fresh()->isClosed()
        );

        $this->assertNotNull(
            $closedAutomatic->fresh()->closed_at
        );
    }

    public function test_command_is_idempotent_after_session_is_expired(): void
    {
        $this->travelTo(
            '2026-11-05 18:00:01'
        );

        $branch = Branch::factory()->create();

        $session = $this->createAutomaticSession(
            branch: $branch,
            sessionDate: '2026-11-05',
            startTime: '07:30:00',
            endTime: '18:00:00'
        );

        $this->artisan(
            'attendance-sessions:expire-automatic'
        )
            ->expectsOutputToContain(
                'Sesi otomatis kedaluwarsa: 1.'
            )
            ->assertSuccessful();

        $this->artisan(
            'attendance-sessions:expire-automatic'
        )
            ->expectsOutputToContain(
                'Sesi otomatis kedaluwarsa: 0.'
            )
            ->assertSuccessful();

        $this->assertTrue(
            $session->fresh()->isExpired()
        );

        $this->assertDatabaseCount(
            'attendance_sessions',
            1
        );
    }

    public function test_scheduler_runs_expiry_every_minute_with_mutex(): void
    {
        $schedule = $this->app->make(
            Schedule::class
        );

        $event = collect(
            $schedule->events()
        )->first(
            static fn (
                object $scheduledEvent
            ): bool => str_contains(
                (string) $scheduledEvent->command,
                'attendance-sessions:expire-automatic'
            )
        );

        $this->assertNotNull($event);

        $this->assertSame(
            '* * * * *',
            $event->expression
        );

        $this->assertTrue(
            $event->withoutOverlapping
        );
    }

    private function createAutomaticSession(
        Branch $branch,
        string $sessionDate,
        string $startTime,
        string $endTime,
        string $status = 'active'
    ): AttendanceSession {
        $this->sequence++;

        return AttendanceSession::query()->create([
            'branch_id' => $branch->id,
            'weekly_schedule_id' => null,

            'attendance_type' => AttendanceSession::TYPE_AUTO,

            'session_source' => AttendanceSession::SOURCE_AUTOMATIC,

            'automation_key' => sprintf(
                'AUTO-LIFECYCLE:%d:%s:%03d',
                $branch->id,
                $sessionDate,
                $this->sequence
            ),

            'session_date' => $sessionDate,

            'start_time' => "{$sessionDate} {$startTime}",

            'end_time' => "{$sessionDate} {$endTime}",

            'encrypted_secret' => 'JBSWY3DPEHPK3PXP',

            'status' => $status,
            'created_by' => null,
            'closed_at' => null,
        ]);
    }
}
