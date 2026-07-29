<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BranchDefaultWorkScheduleFoundationTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_branches_table_has_nullable_default_work_schedule_foreign_key(): void
    {
        $this->assertTrue(
            Schema::hasColumn(
                'branches',
                'default_work_schedule_id'
            )
        );

        $branch = $this->createBranch();

        $this->assertNull(
            $branch->default_work_schedule_id
        );

        $this->assertFalse(
            $branch->hasDefaultWorkSchedule()
        );

        $this->assertNull(
            $branch->defaultWorkSchedule
        );
    }

    public function test_branch_can_use_work_schedule_as_default(): void
    {
        $workSchedule = $this->createWorkSchedule();

        $branch = $this->createBranch(
            $workSchedule
        );

        $branch->load('defaultWorkSchedule');

        $this->assertSame(
            $workSchedule->id,
            $branch->default_work_schedule_id
        );

        $this->assertTrue(
            $branch->hasDefaultWorkSchedule()
        );

        $this->assertTrue(
            $branch->defaultWorkSchedule->is(
                $workSchedule
            )
        );

        $this->assertTrue(
            $workSchedule
                ->defaultBranches()
                ->whereKey($branch->id)
                ->exists()
        );
    }

    public function test_default_work_schedule_relationship_types_are_explicit(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Branch)->defaultWorkSchedule()
        );

        $this->assertInstanceOf(
            HasMany::class,
            (new WorkSchedule)->defaultBranches()
        );
    }

    public function test_default_work_schedule_cannot_be_deleted_while_used_by_branch(): void
    {
        $workSchedule = $this->createWorkSchedule();

        $branch = $this->createBranch(
            $workSchedule
        );

        try {
            $workSchedule->delete();

            $this->fail(
                'Work schedule default seharusnya dilindungi foreign key.'
            );
        } catch (QueryException) {
            $this->assertDatabaseHas(
                'work_schedules',
                [
                    'id' => $workSchedule->id,
                ]
            );

            $this->assertDatabaseHas(
                'branches',
                [
                    'id' => $branch->id,
                    'default_work_schedule_id' => $workSchedule->id,
                ]
            );
        }
    }

    private function createWorkSchedule(): WorkSchedule
    {
        $this->sequence++;

        return WorkSchedule::query()->create([
            'name' => sprintf(
                'Jadwal Default %03d',
                $this->sequence
            ),
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'check_in_open_minutes' => 30,
            'check_in_limit_minutes' => 30,
            'late_tolerance_minutes' => 5,
            'check_out_limit_minutes' => 60,
            'status' => 'active',
        ]);
    }

    private function createBranch(
        ?WorkSchedule $defaultWorkSchedule = null
    ): Branch {
        $this->sequence++;

        return Branch::query()->create([
            'default_work_schedule_id' => $defaultWorkSchedule?->id,
            'code' => sprintf(
                'DF-%03d',
                $this->sequence
            ),
            'name' => sprintf(
                'Cabang Default %03d',
                $this->sequence
            ),
            'address' => 'Alamat pengujian',
            'latitude' => 3.53775250,
            'longitude' => 98.68468940,
            'geofence_radius' => 30.00,
            'maximum_accuracy' => 25.00,
            'status' => 'active',
        ]);
    }
}
