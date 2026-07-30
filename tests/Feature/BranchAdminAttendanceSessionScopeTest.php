<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class BranchAdminAttendanceSessionScopeTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_assigned_admin_index_and_create_page_only_expose_own_branch(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-20 08:30:00',
                'Asia/Jakarta'
            )
        );

        $ownBranch = $this->createBranch();
        $otherBranch = $this->createBranch();
        $hrd = $this->createUser('hrd');

        $ownSession = $this->createSession(
            branch: $ownBranch,
            creator: $hrd,
            sessionDate: '2026-08-20'
        );

        $this->createSession(
            branch: $otherBranch,
            creator: $hrd,
            sessionDate: '2026-08-20'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->get(route('attendance-sessions.index'))
            ->assertOk()
            ->assertViewHas(
                'attendanceSessions',
                static function (
                    LengthAwarePaginator $sessions
                ) use ($ownSession): bool {
                    return $sessions->total() === 1
                        && $sessions->count() === 1
                        && (int) $sessions
                            ->first()
                            ?->id
                            === (int) $ownSession->id;
                }
            )
            ->assertSeeText($ownBranch->name)
            ->assertDontSeeText($otherBranch->name);

        $this->get(route('attendance-sessions.create'))
            ->assertOk()
            ->assertViewHas(
                'branches',
                static function (
                    $branches
                ) use (
                    $ownBranch,
                    $otherBranch
                ): bool {
                    return $branches->count() === 1
                        && (int) $branches
                            ->first()
                            ?->id
                            === (int) $ownBranch->id
                        && ! $branches->contains(
                            'id',
                            $otherBranch->id
                        );
                }
            )
            ->assertSeeText($ownBranch->name)
            ->assertDontSeeText($otherBranch->name);
    }

    public function test_assigned_admin_can_use_own_session_operations(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-21 08:30:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();
        $admin = $this->createUser(
            role: 'admin',
            branch: $branch
        );

        $session = $this->createSession(
            branch: $branch,
            creator: $admin,
            sessionDate: '2026-08-21'
        );

        $this->actingAs($admin)
            ->get(
                route(
                    'attendance-sessions.show',
                    $session
                )
            )
            ->assertOk();

        $this->getJson(
            route(
                'attendance-sessions.payload',
                $session
            )
        )
            ->assertOk()
            ->assertJsonPath(
                'data.public_id',
                $session->public_id
            );

        $this->patch(
            route(
                'attendance-sessions.close',
                $session
            )
        )
            ->assertRedirect(
                route(
                    'attendance-sessions.show',
                    $session
                )
            );

        $this->assertDatabaseHas(
            'attendance_sessions',
            [
                'id' => $session->id,
                'branch_id' => $branch->id,
                'status' => 'closed',
            ]
        );
    }

    public function test_assigned_admin_cannot_open_or_read_payload_from_another_branch_session(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-22 08:30:00',
                'Asia/Jakarta'
            )
        );

        $ownBranch = $this->createBranch();
        $otherBranch = $this->createBranch();
        $hrd = $this->createUser('hrd');

        $otherSession = $this->createSession(
            branch: $otherBranch,
            creator: $hrd,
            sessionDate: '2026-08-22'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->get(
                route(
                    'attendance-sessions.show',
                    $otherSession
                )
            )
            ->assertForbidden();

        $this->getJson(
            route(
                'attendance-sessions.payload',
                $otherSession
            )
        )
            ->assertForbidden();
    }

    public function test_assigned_admin_cannot_close_another_branch_session(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-23 08:30:00',
                'Asia/Jakarta'
            )
        );

        $ownBranch = $this->createBranch();
        $otherBranch = $this->createBranch();
        $hrd = $this->createUser('hrd');

        $otherSession = $this->createSession(
            branch: $otherBranch,
            creator: $hrd,
            sessionDate: '2026-08-23'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->patch(
                route(
                    'attendance-sessions.close',
                    $otherSession
                )
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'attendance_sessions',
            [
                'id' => $otherSession->id,
                'status' => 'active',
                'closed_at' => null,
            ]
        );
    }

    public function test_assigned_admin_cannot_store_session_for_another_branch(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-24 08:00:00',
                'Asia/Jakarta'
            )
        );

        $ownBranch = $this->createBranch();
        $otherBranch = $this->createBranch();

        $admin = $this->createUser(
            role: 'admin',
            branch: $ownBranch
        );

        $this->actingAs($admin)
            ->post(
                route('attendance-sessions.store'),
                [
                    'branch_id' => $otherBranch->id,
                    'attendance_type' => 'check_in',
                    'session_date' => '2026-08-24',
                    'start_time' => '08:15',
                    'end_time' => '09:30',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'attendance_sessions',
            [
                'branch_id' => $otherBranch->id,
                'session_date' => '2026-08-24',
            ]
        );
    }

    public function test_unassigned_admin_is_forbidden_from_session_pages_and_operations(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-25 08:30:00',
                'Asia/Jakarta'
            )
        );

        $branch = $this->createBranch();
        $hrd = $this->createUser('hrd');

        $session = $this->createSession(
            branch: $branch,
            creator: $hrd,
            sessionDate: '2026-08-25'
        );

        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('attendance-sessions.index'))
            ->assertForbidden();

        $this->get(route('attendance-sessions.create'))
            ->assertForbidden();

        $this->get(
            route(
                'attendance-sessions.show',
                $session
            )
        )
            ->assertForbidden();

        $this->getJson(
            route(
                'attendance-sessions.payload',
                $session
            )
        )
            ->assertForbidden();

        $this->patch(
            route(
                'attendance-sessions.close',
                $session
            )
        )
            ->assertForbidden();

        $this->post(
            route('attendance-sessions.store'),
            [
                'branch_id' => $branch->id,
                'attendance_type' => 'check_in',
                'session_date' => '2026-08-25',
                'start_time' => '09:30',
                'end_time' => '10:30',
            ]
        )
            ->assertForbidden();
    }

    public function test_admin_assigned_to_inactive_branch_is_forbidden_from_session_pages_and_operations(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-26 08:30:00',
                'Asia/Jakarta'
            )
        );

        $inactiveBranch = $this->createBranch([
            'status' => 'inactive',
        ]);

        $activeBranch = $this->createBranch();
        $hrd = $this->createUser('hrd');

        $session = $this->createSession(
            branch: $activeBranch,
            creator: $hrd,
            sessionDate: '2026-08-26'
        );

        $admin = $this->createUser(
            role: 'admin',
            branch: $inactiveBranch
        );

        $this->actingAs($admin)
            ->get(route('attendance-sessions.index'))
            ->assertForbidden();

        $this->get(route('attendance-sessions.create'))
            ->assertForbidden();

        $this->get(
            route(
                'attendance-sessions.show',
                $session
            )
        )
            ->assertForbidden();

        $this->getJson(
            route(
                'attendance-sessions.payload',
                $session
            )
        )
            ->assertForbidden();

        $this->patch(
            route(
                'attendance-sessions.close',
                $session
            )
        )
            ->assertForbidden();

        $this->post(
            route('attendance-sessions.store'),
            [
                'branch_id' => $activeBranch->id,
                'attendance_type' => 'check_out',
                'session_date' => '2026-08-26',
                'start_time' => '16:30',
                'end_time' => '18:00',
            ]
        )
            ->assertForbidden();
    }

    public function test_hrd_retains_cross_branch_session_access(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 08:30:00',
                'Asia/Jakarta'
            )
        );

        $firstBranch = $this->createBranch();
        $secondBranch = $this->createBranch();
        $hrd = $this->createUser('hrd');

        $firstSession = $this->createSession(
            branch: $firstBranch,
            creator: $hrd,
            sessionDate: '2026-08-27'
        );

        $secondSession = $this->createSession(
            branch: $secondBranch,
            creator: $hrd,
            sessionDate: '2026-08-27'
        );

        $this->actingAs($hrd)
            ->get(route('attendance-sessions.index'))
            ->assertOk()
            ->assertViewHas(
                'attendanceSessions',
                static fn (
                    LengthAwarePaginator $sessions
                ): bool => $sessions->total() === 2
            )
            ->assertSeeText($firstBranch->name)
            ->assertSeeText($secondBranch->name);

        $this->get(route('attendance-sessions.create'))
            ->assertOk()
            ->assertViewHas(
                'branches',
                static fn ($branches): bool => $branches->count() === 2
            );

        $this->get(
            route(
                'attendance-sessions.show',
                $secondSession
            )
        )
            ->assertOk();

        $this->getJson(
            route(
                'attendance-sessions.payload',
                $firstSession
            )
        )
            ->assertOk();

        $this->patch(
            route(
                'attendance-sessions.close',
                $secondSession
            )
        )
            ->assertRedirect(
                route(
                    'attendance-sessions.show',
                    $secondSession
                )
            );
    }

    private function createUser(
        string $role,
        ?Branch $branch = null
    ): User {
        return User::factory()->create([
            'branch_id' => $branch?->id,
            'role' => $role,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBranch(
        array $overrides = []
    ): Branch {
        $this->sequence++;

        return Branch::factory()->create(
            array_replace(
                [
                    'code' => sprintf(
                        'BR-ASS-%03d',
                        $this->sequence
                    ),
                    'name' => sprintf(
                        'Cabang Scope Sesi %03d',
                        $this->sequence
                    ),
                    'address' => 'Jalan Scope Sesi',
                    'latitude' => 3.595196,
                    'longitude' => 98.672226,
                    'geofence_radius' => 30.0,
                    'maximum_accuracy' => 20.0,
                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSession(
        Branch $branch,
        User $creator,
        string $sessionDate,
        array $overrides = []
    ): AttendanceSession {
        return AttendanceSession::query()->create(
            array_replace(
                [
                    'branch_id' => $branch->id,
                    'attendance_type' => 'check_in',
                    'session_date' => $sessionDate,
                    'start_time' => "{$sessionDate} 08:00:00",
                    'end_time' => "{$sessionDate} 09:30:00",
                    'encrypted_secret' => 'JBSWY3DPEHPK3PXP',
                    'status' => 'active',
                    'created_by' => $creator->id,
                    'closed_at' => null,
                ],
                $overrides
            )
        );
    }
}
