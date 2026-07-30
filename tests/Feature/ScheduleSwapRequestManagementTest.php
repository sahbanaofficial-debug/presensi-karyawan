<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\ScheduleSwapRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ScheduleSwapRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeSequence = 0;

    private int $workScheduleSequence = 0;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('schedule-swap-requests.index'))
            ->assertRedirect(route('login'));

        $this->get(route('schedule-swap-requests.create'))
            ->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_schedule_swap_module(): void
    {
        $branch = Branch::factory()->create([
            'code' => 'SWAP-EMPLOYEE-ACCESS',
        ]);

        $employee = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-ACCESS-403',
                'full_name' => 'Karyawan Pengujian Akses',
                'employment_status' => 'active',
            ]
        );

        $employeeUser = User::query()->findOrFail(
            $employee->user_id
        );

        $this->actingAs($employeeUser)
            ->get(route('schedule-swap-requests.index'))
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->get(route('schedule-swap-requests.create'))
            ->assertForbidden();
    }

    public function test_hrd_and_admin_can_open_index_and_create_pages(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'code' => 'SWAP-ACCESS',
        ]);

        $admin = $this->createAdmin(
            $branch
        );

        $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-ACCESS-001',
                'full_name' => 'Karyawan Swap Akses Satu',
            ]
        );

        $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-ACCESS-002',
                'full_name' => 'Karyawan Swap Akses Dua',
            ]
        );

        foreach ([$hrd, $admin] as $user) {
            $this->actingAs($user)
                ->get(route('schedule-swap-requests.index'))
                ->assertOk()
                ->assertViewIs('schedule-swap-requests.index')
                ->assertSee('Pertukaran Jadwal');

            $this->actingAs($user)
                ->get(route('schedule-swap-requests.create'))
                ->assertOk()
                ->assertViewIs('schedule-swap-requests.create')
                ->assertSee('Catat Permohonan Pertukaran Jadwal')
                ->assertSee('EMP-SWAP-ACCESS-001')
                ->assertSee('EMP-SWAP-ACCESS-002');
        }
    }

    public function test_hrd_can_store_valid_schedule_swap_request(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();

        $requester = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-HRD-001',
                'full_name' => 'Pengaju Pertukaran HRD',
            ]
        );

        $partner = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-HRD-002',
                'full_name' => 'Pasangan Pertukaran HRD',
            ]
        );

        $requesterWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Pengaju HRD',
        ]);

        $partnerWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Pasangan HRD',
        ]);

        $this->createEmployeeSchedule(
            $requester,
            $requesterWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-01']
        );

        $this->createEmployeeSchedule(
            $partner,
            $partnerWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-02']
        );

        $response = $this->actingAs($hrd)
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $requester->id,
                    'partner_employee_id' => $partner->id,
                    'requester_date' => '2026-10-01',
                    'partner_date' => '2026-10-02',
                    'reason' => ' Keperluan keluarga pengaju ',
                ]
            );

        $scheduleSwapRequest = ScheduleSwapRequest::query()->firstOrFail();

        $response
            ->assertRedirect(
                route('schedule-swap-requests.show', $scheduleSwapRequest)
            )
            ->assertSessionHas(
                'success',
                'Permohonan pertukaran jadwal berhasil dicatat.'
            );

        $this->assertSame($requester->id, $scheduleSwapRequest->requester_employee_id);
        $this->assertSame($partner->id, $scheduleSwapRequest->partner_employee_id);
        $this->assertSame('2026-10-01', $scheduleSwapRequest->requester_date->format('Y-m-d'));
        $this->assertSame('2026-10-02', $scheduleSwapRequest->partner_date->format('Y-m-d'));
        $this->assertSame('Keperluan keluarga pengaju', $scheduleSwapRequest->reason);
        $this->assertSame('pending', $scheduleSwapRequest->status);
        $this->assertNull($scheduleSwapRequest->approved_by);
        $this->assertNull($scheduleSwapRequest->approved_at);
    }

    public function test_admin_can_store_valid_schedule_swap_request(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();

        $admin = $this->createAdmin(
            $branch
        );

        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            $requester,
            $workSchedule,
            $hrd,
            ['schedule_date' => '2026-10-03']
        );

        $this->createEmployeeSchedule(
            $partner,
            $workSchedule,
            $hrd,
            ['schedule_date' => '2026-10-04']
        );

        $response = $this->actingAs($admin)
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $requester->id,
                    'partner_employee_id' => $partner->id,
                    'requester_date' => '2026-10-03',
                    'partner_date' => '2026-10-04',
                    'reason' => 'Permohonan dicatat oleh admin',
                ]
            );

        $scheduleSwapRequest = ScheduleSwapRequest::query()->firstOrFail();

        $response->assertRedirect(
            route('schedule-swap-requests.show', $scheduleSwapRequest)
        );

        $this->assertSame('pending', $scheduleSwapRequest->status);
        $this->assertSame('Permohonan dicatat oleh admin', $scheduleSwapRequest->reason);
    }

    public function test_requester_and_partner_must_be_different(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $employee = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            $employee,
            $workSchedule,
            $hrd,
            ['schedule_date' => '2026-10-05']
        );

        $this->actingAs($hrd)
            ->from(route('schedule-swap-requests.create'))
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $employee->id,
                    'partner_employee_id' => $employee->id,
                    'requester_date' => '2026-10-05',
                    'partner_date' => '2026-10-05',
                    'reason' => 'Pengajuan tidak valid',
                ]
            )
            ->assertRedirect(route('schedule-swap-requests.create'))
            ->assertSessionHasErrors(['partner_employee_id']);

        $this->assertDatabaseCount('schedule_swap_requests', 0);
    }

    public function test_both_employees_must_have_schedules_on_selected_dates(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            $requester,
            $workSchedule,
            $hrd,
            ['schedule_date' => '2026-10-06']
        );

        $this->actingAs($hrd)
            ->from(route('schedule-swap-requests.create'))
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $requester->id,
                    'partner_employee_id' => $partner->id,
                    'requester_date' => '2026-10-06',
                    'partner_date' => '2026-10-07',
                    'reason' => 'Pasangan belum mempunyai jadwal',
                ]
            )
            ->assertRedirect(route('schedule-swap-requests.create'))
            ->assertSessionHasErrors(['partner_date']);

        $this->assertDatabaseCount('schedule_swap_requests', 0);
    }

    public function test_inactive_employee_is_rejected(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $requester = $this->createEmployee($branch);
        $inactivePartner = $this->createEmployee(
            $branch,
            ['employment_status' => 'inactive']
        );
        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            $requester,
            $workSchedule,
            $hrd,
            ['schedule_date' => '2026-10-08']
        );

        $this->createEmployeeSchedule(
            $inactivePartner,
            $workSchedule,
            $hrd,
            ['schedule_date' => '2026-10-09']
        );

        $this->actingAs($hrd)
            ->from(route('schedule-swap-requests.create'))
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $requester->id,
                    'partner_employee_id' => $inactivePartner->id,
                    'requester_date' => '2026-10-08',
                    'partner_date' => '2026-10-09',
                    'reason' => 'Pasangan tidak aktif',
                ]
            )
            ->assertRedirect(route('schedule-swap-requests.create'))
            ->assertSessionHasErrors(['partner_employee_id']);

        $this->assertDatabaseCount('schedule_swap_requests', 0);
    }

    public function test_duplicate_pending_request_is_rejected_in_both_directions(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);
        $workSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            $requester,
            $workSchedule,
            $hrd,
            ['schedule_date' => '2026-10-10']
        );

        $this->createEmployeeSchedule(
            $partner,
            $workSchedule,
            $hrd,
            ['schedule_date' => '2026-10-11']
        );

        $this->createSwapRequest(
            $requester,
            $partner,
            [
                'requester_date' => '2026-10-10',
                'partner_date' => '2026-10-11',
                'status' => 'pending',
            ]
        );

        $this->actingAs($hrd)
            ->from(route('schedule-swap-requests.create'))
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $requester->id,
                    'partner_employee_id' => $partner->id,
                    'requester_date' => '2026-10-10',
                    'partner_date' => '2026-10-11',
                    'reason' => 'Permohonan ganda searah',
                ]
            )
            ->assertRedirect(route('schedule-swap-requests.create'))
            ->assertSessionHasErrors(['partner_date']);

        $this->actingAs($hrd)
            ->from(route('schedule-swap-requests.create'))
            ->post(
                route('schedule-swap-requests.store'),
                [
                    'requester_employee_id' => $partner->id,
                    'partner_employee_id' => $requester->id,
                    'requester_date' => '2026-10-11',
                    'partner_date' => '2026-10-10',
                    'reason' => 'Permohonan ganda terbalik',
                ]
            )
            ->assertRedirect(route('schedule-swap-requests.create'))
            ->assertSessionHasErrors(['partner_date']);

        $this->assertDatabaseCount('schedule_swap_requests', 1);
    }

    public function test_hrd_and_admin_can_open_detail_page(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'code' => 'SWAP-DETAIL',
            'name' => 'Cabang Detail Pertukaran',
        ]);

        $admin = $this->createAdmin(
            $branch
        );

        $requester = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-DETAIL-001',
                'full_name' => 'Pengaju Detail Pertukaran',
            ]
        );

        $partner = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-DETAIL-002',
                'full_name' => 'Pasangan Detail Pertukaran',
            ]
        );

        $requesterWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Detail Pengaju',
        ]);

        $partnerWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Detail Pasangan',
        ]);

        $this->createEmployeeSchedule(
            $requester,
            $requesterWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-12']
        );

        $this->createEmployeeSchedule(
            $partner,
            $partnerWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-13']
        );

        $scheduleSwapRequest = $this->createSwapRequest(
            $requester,
            $partner,
            [
                'requester_date' => '2026-10-12',
                'partner_date' => '2026-10-13',
                'reason' => 'Alasan detail pertukaran',
            ]
        );

        $this->actingAs($hrd)
            ->get(route('schedule-swap-requests.show', $scheduleSwapRequest))
            ->assertOk()
            ->assertViewIs('schedule-swap-requests.show')
            ->assertSee('Pengaju Detail Pertukaran')
            ->assertSee('Pasangan Detail Pertukaran')
            ->assertSee('Pola Detail Pengaju')
            ->assertSee('Pola Detail Pasangan')
            ->assertSee('Alasan detail pertukaran')
            ->assertSee('Setujui dan Tukar Jadwal');

        $this->actingAs($admin)
            ->get(route('schedule-swap-requests.show', $scheduleSwapRequest))
            ->assertOk()
            ->assertSee('Pengaju Detail Pertukaran')
            ->assertSee('Pasangan Detail Pertukaran')
            ->assertSee('Keputusan persetujuan atau penolakan')
            ->assertDontSee('Setujui dan Tukar Jadwal');
    }

    public function test_hrd_can_approve_and_swap_employee_schedules(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);

        $requesterWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Kerja Pengaju',
            'check_in_time' => '08:45:00',
            'check_out_time' => '17:00:00',
        ]);

        $requesterSchedule = $this->createEmployeeSchedule(
            $requester,
            $requesterWorkSchedule,
            $hrd,
            [
                'schedule_date' => '2026-10-14',
                'schedule_status' => 'work',
                'notes' => 'Catatan tetap milik pengaju',
            ]
        );

        $partnerSchedule = $this->createEmployeeSchedule(
            $partner,
            null,
            $hrd,
            [
                'schedule_date' => '2026-10-15',
                'schedule_status' => 'off',
                'notes' => 'Catatan tetap milik pasangan',
            ]
        );

        $scheduleSwapRequest = $this->createSwapRequest(
            $requester,
            $partner,
            [
                'requester_date' => '2026-10-14',
                'partner_date' => '2026-10-15',
            ]
        );

        $response = $this->actingAs($hrd)
            ->patch(
                route('schedule-swap-requests.decide', $scheduleSwapRequest),
                ['decision' => ' APPROVED ']
            );

        $response
            ->assertRedirect(
                route('schedule-swap-requests.show', $scheduleSwapRequest)
            )
            ->assertSessionHas(
                'success',
                'Permohonan pertukaran jadwal berhasil disetujui dan jadwal kedua karyawan telah diperbarui.'
            );

        $requesterSchedule->refresh();
        $partnerSchedule->refresh();
        $scheduleSwapRequest->refresh();

        $this->assertNull($requesterSchedule->work_schedule_id);
        $this->assertSame('off', $requesterSchedule->schedule_status);
        $this->assertSame('Catatan tetap milik pengaju', $requesterSchedule->notes);
        $this->assertSame($hrd->id, $requesterSchedule->approved_by);
        $this->assertSame($requesterWorkSchedule->id, $partnerSchedule->work_schedule_id);
        $this->assertSame('work', $partnerSchedule->schedule_status);
        $this->assertSame('Catatan tetap milik pasangan', $partnerSchedule->notes);
        $this->assertSame($hrd->id, $partnerSchedule->approved_by);
        $this->assertSame('approved', $scheduleSwapRequest->status);
        $this->assertSame($hrd->id, $scheduleSwapRequest->approved_by);
        $this->assertNotNull($scheduleSwapRequest->approved_at);
    }

    public function test_hrd_can_reject_without_changing_schedules(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);

        $requesterWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Penolakan Pengaju',
        ]);

        $partnerWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Penolakan Pasangan',
        ]);

        $requesterSchedule = $this->createEmployeeSchedule(
            $requester,
            $requesterWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-16']
        );

        $partnerSchedule = $this->createEmployeeSchedule(
            $partner,
            $partnerWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-17']
        );

        $scheduleSwapRequest = $this->createSwapRequest(
            $requester,
            $partner,
            [
                'requester_date' => '2026-10-16',
                'partner_date' => '2026-10-17',
            ]
        );

        $response = $this->actingAs($hrd)
            ->patch(
                route('schedule-swap-requests.decide', $scheduleSwapRequest),
                ['decision' => 'rejected']
            );

        $response
            ->assertRedirect(
                route('schedule-swap-requests.show', $scheduleSwapRequest)
            )
            ->assertSessionHas(
                'success',
                'Permohonan pertukaran jadwal berhasil ditolak.'
            );

        $requesterSchedule->refresh();
        $partnerSchedule->refresh();
        $scheduleSwapRequest->refresh();

        $this->assertSame($requesterWorkSchedule->id, $requesterSchedule->work_schedule_id);
        $this->assertSame('work', $requesterSchedule->schedule_status);
        $this->assertSame($partnerWorkSchedule->id, $partnerSchedule->work_schedule_id);
        $this->assertSame('work', $partnerSchedule->schedule_status);
        $this->assertSame('rejected', $scheduleSwapRequest->status);
        $this->assertSame($hrd->id, $scheduleSwapRequest->approved_by);
        $this->assertNotNull($scheduleSwapRequest->approved_at);
    }

    public function test_admin_cannot_decide_schedule_swap_request(): void
    {
        $branch = Branch::factory()->create();

        $admin = $this->createAdmin(
            $branch
        );

        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);
        $scheduleSwapRequest = $this->createSwapRequest($requester, $partner);

        $this->actingAs($admin)
            ->patch(
                route('schedule-swap-requests.decide', $scheduleSwapRequest),
                ['decision' => 'approved']
            )
            ->assertForbidden();

        $scheduleSwapRequest->refresh();

        $this->assertSame('pending', $scheduleSwapRequest->status);
        $this->assertNull($scheduleSwapRequest->approved_by);
        $this->assertNull($scheduleSwapRequest->approved_at);
    }

    public function test_decided_request_cannot_be_decided_again(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);
        $requesterWorkSchedule = $this->createWorkSchedule();
        $partnerWorkSchedule = $this->createWorkSchedule();

        $this->createEmployeeSchedule(
            $requester,
            $requesterWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-18']
        );

        $this->createEmployeeSchedule(
            $partner,
            $partnerWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-19']
        );

        $scheduleSwapRequest = $this->createSwapRequest(
            $requester,
            $partner,
            [
                'requester_date' => '2026-10-18',
                'partner_date' => '2026-10-19',
            ]
        );

        $this->actingAs($hrd)
            ->patch(
                route('schedule-swap-requests.decide', $scheduleSwapRequest),
                ['decision' => 'approved']
            )
            ->assertRedirect(
                route('schedule-swap-requests.show', $scheduleSwapRequest)
            );

        $this->actingAs($hrd)
            ->from(route('schedule-swap-requests.show', $scheduleSwapRequest))
            ->patch(
                route('schedule-swap-requests.decide', $scheduleSwapRequest),
                ['decision' => 'rejected']
            )
            ->assertRedirect(
                route('schedule-swap-requests.show', $scheduleSwapRequest)
            )
            ->assertSessionHasErrors(['decision']);

        $scheduleSwapRequest->refresh();
        $this->assertSame('approved', $scheduleSwapRequest->status);
    }

    public function test_schedule_with_attendance_cannot_be_approved(): void
    {
        $hrd = $this->createHrd();

        $branch = Branch::factory()->create([
            'latitude' => '3.59519600',
            'longitude' => '98.67222600',
            'geofence_radius' => '30.00',
        ]);

        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);

        $requesterWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Dengan Presensi',
        ]);

        $partnerWorkSchedule = $this->createWorkSchedule([
            'name' => 'Pola Pasangan Presensi',
        ]);

        $requesterSchedule = $this->createEmployeeSchedule(
            $requester,
            $requesterWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-20']
        );

        $partnerSchedule = $this->createEmployeeSchedule(
            $partner,
            $partnerWorkSchedule,
            $hrd,
            ['schedule_date' => '2026-10-21']
        );

        $this->createAttendance(
            $requesterSchedule,
            $requester,
            $branch,
            $hrd
        );

        $scheduleSwapRequest = $this->createSwapRequest(
            $requester,
            $partner,
            [
                'requester_date' => '2026-10-20',
                'partner_date' => '2026-10-21',
            ]
        );

        $this->actingAs($hrd)
            ->from(route('schedule-swap-requests.show', $scheduleSwapRequest))
            ->patch(
                route('schedule-swap-requests.decide', $scheduleSwapRequest),
                ['decision' => 'approved']
            )
            ->assertRedirect(
                route('schedule-swap-requests.show', $scheduleSwapRequest)
            )
            ->assertSessionHasErrors(['decision']);

        $requesterSchedule->refresh();
        $partnerSchedule->refresh();
        $scheduleSwapRequest->refresh();

        $this->assertSame($requesterWorkSchedule->id, $requesterSchedule->work_schedule_id);
        $this->assertSame($partnerWorkSchedule->id, $partnerSchedule->work_schedule_id);
        $this->assertSame('pending', $scheduleSwapRequest->status);
    }

    public function test_search_and_status_filter_return_matching_request(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();

        $alpha = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-ALPHA',
                'full_name' => 'Karyawan Alpha Pertukaran',
            ]
        );

        $beta = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-BETA',
                'full_name' => 'Karyawan Beta Pertukaran',
            ]
        );

        $gamma = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-GAMMA',
                'full_name' => 'Karyawan Gamma Pertukaran',
            ]
        );

        $delta = $this->createEmployee(
            $branch,
            [
                'employee_number' => 'EMP-SWAP-DELTA',
                'full_name' => 'Karyawan Delta Pertukaran',
            ]
        );

        $this->createSwapRequest(
            $alpha,
            $beta,
            [
                'reason' => 'Permohonan Alpha masih menunggu',
                'status' => 'pending',
            ]
        );

        $this->createSwapRequest(
            $gamma,
            $delta,
            [
                'reason' => 'Permohonan Gamma sudah selesai',
                'status' => 'approved',
                'approved_by' => $hrd->id,
                'approved_at' => now(),
            ]
        );

        $this->actingAs($hrd)
            ->get(
                route(
                    'schedule-swap-requests.index',
                    [
                        'search' => 'Alpha',
                        'status' => 'pending',
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas('search', 'Alpha')
            ->assertViewHas('selectedStatus', 'pending')
            ->assertSee('EMP-SWAP-ALPHA')
            ->assertSee('Karyawan Alpha Pertukaran')
            ->assertSee('EMP-SWAP-BETA')
            ->assertDontSee('EMP-SWAP-GAMMA')
            ->assertDontSee('EMP-SWAP-DELTA');
    }

    public function test_index_uses_fifteen_items_per_page(): void
    {
        $hrd = $this->createHrd();
        $branch = Branch::factory()->create();
        $requester = $this->createEmployee($branch);
        $partner = $this->createEmployee($branch);
        $startingDate = CarbonImmutable::parse('2026-11-01');

        for ($index = 0; $index < 16; $index++) {
            $this->createSwapRequest(
                $requester,
                $partner,
                [
                    'requester_date' => $startingDate
                        ->addDays($index)
                        ->toDateString(),
                    'partner_date' => $startingDate
                        ->addDays($index + 1)
                        ->toDateString(),
                    'reason' => sprintf(
                        'Permohonan pagination %02d',
                        $index + 1
                    ),
                ]
            );
        }

        $this->actingAs($hrd)
            ->get(route('schedule-swap-requests.index'))
            ->assertOk()
            ->assertViewHas(
                'scheduleSwapRequests',
                static function ($scheduleSwapRequests): bool {
                    return $scheduleSwapRequests->perPage() === 15
                        && $scheduleSwapRequests->currentPage() === 1
                        && $scheduleSwapRequests->total() === 16
                        && $scheduleSwapRequests->count() === 15;
                }
            );

        $this->actingAs($hrd)
            ->get(
                route(
                    'schedule-swap-requests.index',
                    ['page' => 2]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'scheduleSwapRequests',
                static function ($scheduleSwapRequests): bool {
                    return $scheduleSwapRequests->perPage() === 15
                        && $scheduleSwapRequests->currentPage() === 2
                        && $scheduleSwapRequests->total() === 16
                        && $scheduleSwapRequests->count() === 1;
                }
            );
    }

    private function createHrd(): User
    {
        return User::factory()->create([
            'role' => 'hrd',
            'status' => 'active',
        ]);
    }

    private function createAdmin(
        Branch $branch
    ): User {
        return User::factory()->create([
            'branch_id' => $branch->id,
            'role' => 'admin',
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

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::query()->create(
            array_replace(
                [
                    'user_id' => $employeeUser->id,
                    'branch_id' => $branch->id,
                    'employee_number' => sprintf(
                        'EMP-SWAP-%03d',
                        $this->employeeSequence
                    ),
                    'full_name' => sprintf(
                        'Karyawan Pertukaran %03d',
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
                        'Pola Pertukaran %03d',
                        $this->workScheduleSequence
                    ),
                    'check_in_time' => '08:45:00',
                    'check_out_time' => '17:00:00',
                    'check_in_open_minutes' => 30,
                    'late_tolerance_minutes' => 5,
                    'check_out_limit_minutes' => 60,
                    'status' => 'active',
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmployeeSchedule(
        Employee $employee,
        ?WorkSchedule $workSchedule,
        User $approver,
        array $overrides = []
    ): EmployeeSchedule {
        return EmployeeSchedule::query()->create(
            array_replace(
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $workSchedule?->id,
                    'schedule_date' => '2026-10-30',
                    'schedule_status' => $workSchedule === null
                        ? 'off'
                        : 'work',
                    'approved_by' => $approver->id,
                    'notes' => null,
                ],
                $overrides
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSwapRequest(
        Employee $requester,
        Employee $partner,
        array $overrides = []
    ): ScheduleSwapRequest {
        return ScheduleSwapRequest::query()->create(
            array_replace(
                [
                    'requester_employee_id' => $requester->id,
                    'partner_employee_id' => $partner->id,
                    'requester_date' => '2026-10-30',
                    'partner_date' => '2026-10-31',
                    'reason' => 'Keperluan pertukaran jadwal',
                    'status' => 'pending',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                $overrides
            )
        );
    }

    private function createAttendance(
        EmployeeSchedule $employeeSchedule,
        Employee $employee,
        Branch $branch,
        User $creator
    ): void {
        $scheduleDate = $employeeSchedule
            ->schedule_date
            ->format('Y-m-d');

        $now = now();

        $attendanceSessionId = DB::table('attendance_sessions')
            ->insertGetId([
                'public_id' => (string) Str::uuid(),
                'branch_id' => $branch->id,
                'attendance_type' => 'check_in',
                'session_date' => $scheduleDate,
                'start_time' => "{$scheduleDate} 08:15:00",
                'end_time' => "{$scheduleDate} 09:30:00",
                'encrypted_secret' => 'encrypted-secret-for-schedule-swap-test',
                'status' => 'closed',
                'created_by' => $creator->id,
                'closed_at' => "{$scheduleDate} 09:30:00",
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        DB::table('attendances')->insert([
            'employee_id' => $employee->id,
            'attendance_session_id' => $attendanceSessionId,
            'employee_schedule_id' => $employeeSchedule->id,
            'branch_id' => $branch->id,
            'attendance_type' => 'check_in',
            'attendance_date' => $scheduleDate,
            'attendance_time' => "{$scheduleDate} 08:45:00",
            'latitude' => '3.59519600',
            'longitude' => '98.67222600',
            'accuracy' => '10.00',
            'distance' => '5.00',
            'geofence_radius' => '30.00',
            'attendance_status' => 'present',
            'punctuality_status' => 'on_time',
            'validation_status' => 'accepted',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
