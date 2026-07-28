<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\StoreWeeklyScheduleRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class WeeklyRosterRequestTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT =
        '/_tests/weekly-rosters';

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Route::post(
            self::ENDPOINT,
            static function (
                StoreWeeklyScheduleRequest $request
            ): JsonResponse {
                return response()->json(
                    [
                        'data' => $request->validated(),
                    ],
                    201
                );
            }
        )->middleware('web');
    }

    public function test_guest_cannot_submit_weekly_roster(): void
    {
        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $this->postJson(
            self::ENDPOINT,
            $this->validPayload(
                $branch,
                $employee,
                $workSchedule
            )
        )->assertForbidden();
    }

    public function test_admin_and_employee_cannot_submit_weekly_roster(): void
    {
        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        foreach (
            [
                'admin',
                'employee',
            ] as $role
        ) {
            $user = $this->createUser($role);

            $this->actingAs($user)
                ->postJson(
                    self::ENDPOINT,
                    $this->validPayload(
                        $branch,
                        $employee,
                        $workSchedule
                    )
                )
                ->assertForbidden();
        }
    }

    public function test_hrd_can_submit_valid_and_normalized_weekly_roster(): void
    {
        $hrd = $this->createUser('hrd');

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $this->actingAs($hrd)
            ->postJson(
                self::ENDPOINT,
                $this->validPayload(
                    $branch,
                    $employee,
                    $workSchedule
                )
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.branch_id',
                $branch->id
            )
            ->assertJsonPath(
                'data.week_start_date',
                '2026-08-03'
            )
            ->assertJsonPath(
                'data.items.0.employee_id',
                $employee->id
            )
            ->assertJsonPath(
                'data.items.0.work_schedule_id',
                $workSchedule->id
            )
            ->assertJsonPath(
                'data.items.0.schedule_status',
                'work'
            )
            ->assertJsonPath(
                'data.items.0.notes',
                'Shift utama'
            )
            ->assertJsonPath(
                'data.items.1.work_schedule_id',
                null
            )
            ->assertJsonPath(
                'data.items.1.schedule_status',
                'off'
            );
    }

    public function test_week_start_date_must_be_monday(): void
    {
        $hrd = $this->createUser('hrd');

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $payload = $this->validPayload(
            $branch,
            $employee,
            $workSchedule
        );

        $payload['week_start_date'] =
            '2026-08-04';

        $this->actingAs($hrd)
            ->postJson(
                self::ENDPOINT,
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'week_start_date',
            ]);
    }

    public function test_item_dates_must_remain_inside_selected_week(): void
    {
        $hrd = $this->createUser('hrd');

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $payload = $this->validPayload(
            $branch,
            $employee,
            $workSchedule
        );

        $payload['items'][0]['schedule_date'] =
            '2026-08-10';

        $this->actingAs($hrd)
            ->postJson(
                self::ENDPOINT,
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.schedule_date',
            ]);
    }

    public function test_employee_must_be_active_and_assigned_to_selected_branch(): void
    {
        $hrd = $this->createUser('hrd');

        $selectedBranch =
            Branch::factory()->create([
                'status' => 'active',
            ]);

        $otherBranch =
            Branch::factory()->create([
                'status' => 'active',
            ]);

        $inactiveEmployee =
            $this->createEmployee(
                $selectedBranch,
                [
                    'employment_status' => 'inactive',
                ]
            );

        $otherBranchEmployee =
            $this->createEmployee(
                $otherBranch
            );

        $workSchedule =
            $this->createWorkSchedule();

        $this->actingAs($hrd)
            ->postJson(
                self::ENDPOINT,
                $this->validPayload(
                    $selectedBranch,
                    $inactiveEmployee,
                    $workSchedule
                )
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.employee_id',
            ]);

        $this->actingAs($hrd)
            ->postJson(
                self::ENDPOINT,
                $this->validPayload(
                    $selectedBranch,
                    $otherBranchEmployee,
                    $workSchedule
                )
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.employee_id',
            ]);
    }

    public function test_work_and_nonwork_schedule_configuration_is_enforced(): void
    {
        $hrd = $this->createUser('hrd');

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $workWithoutPattern =
            $this->validPayload(
                $branch,
                $employee,
                $workSchedule
            );

        $workWithoutPattern['items'][0]['work_schedule_id'] = null;

        $this->actingAs($hrd)
            ->postJson(
                self::ENDPOINT,
                $workWithoutPattern
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.work_schedule_id',
            ]);

        $offWithPattern =
            $this->validPayload(
                $branch,
                $employee,
                $workSchedule
            );

        $offWithPattern['items'][1]['work_schedule_id'] =
                $workSchedule->id;

        $this->actingAs($hrd)
            ->postJson(
                self::ENDPOINT,
                $offWithPattern
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.1.work_schedule_id',
            ]);
    }

    public function test_duplicate_employee_and_date_is_rejected_inside_payload(): void
    {
        $hrd = $this->createUser('hrd');

        $branch = Branch::factory()->create([
            'status' => 'active',
        ]);

        $employee = $this->createEmployee(
            $branch
        );

        $workSchedule =
            $this->createWorkSchedule();

        $payload = $this->validPayload(
            $branch,
            $employee,
            $workSchedule
        );

        $payload['items'][1]['schedule_date'] =
            '2026-08-03';

        $this->actingAs($hrd)
            ->postJson(
                self::ENDPOINT,
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.1.schedule_date',
            ]);
    }

    private function createUser(
        string $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmployee(
        Branch $branch,
        array $overrides = []
    ): Employee {
        $this->employeeSequence++;

        $user = $this->createUser('employee');

        return Employee::query()->create(
            array_replace(
                [
                    'user_id' => $user->id,

                    'branch_id' => $branch->id,

                    'employee_number' => sprintf(
                        'EMP-WEEKLY-%03d',
                        $this->employeeSequence
                    ),

                    'full_name' => sprintf(
                        'Karyawan Roster %03d',
                        $this->employeeSequence
                    ),

                    'position' => 'Karyawan',

                    'phone_number' => null,

                    'employment_status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWorkSchedule(
        array $overrides = []
    ): WorkSchedule {
        $this->workScheduleSequence++;

        return WorkSchedule::query()->create(
            array_replace(
                [
                    'name' => sprintf(
                        'Pola Roster %03d',
                        $this->workScheduleSequence
                    ),

                    'check_in_time' => '08:45:00',

                    'check_out_time' => '17:00:00',

                    'check_in_open_minutes' => 30,

                    'late_tolerance_minutes' => 5,

                    'check_in_limit_minutes' => 30,

                    'check_out_limit_minutes' => 60,

                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(
        Branch $branch,
        Employee $employee,
        WorkSchedule $workSchedule
    ): array {
        return [
            'branch_id' => sprintf(
                ' %d ',
                $branch->id
            ),

            'week_start_date' => ' 2026-08-03 ',

            'items' => [
                [
                    'employee_id' => sprintf(
                        ' %d ',
                        $employee->id
                    ),

                    'work_schedule_id' => sprintf(
                        ' %d ',
                        $workSchedule->id
                    ),

                    'schedule_date' => ' 2026-08-03 ',

                    'schedule_status' => ' WORK ',

                    'notes' => ' Shift utama ',
                ],

                [
                    'employee_id' => $employee->id,

                    'work_schedule_id' => '',

                    'schedule_date' => '2026-08-04',

                    'schedule_status' => ' OFF ',

                    'notes' => ' Libur terjadwal ',
                ],
            ],
        ];
    }
}
