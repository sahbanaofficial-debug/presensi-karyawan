<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeeklyScheduleRequest;
use App\Models\Branch;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WorkSchedule;
use App\Services\WeeklyRosterService;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class WeeklyRosterController extends Controller
{
    private const ITEMS_PER_PAGE = 15;

    /**
     * Menampilkan daftar roster mingguan.
     */
    public function index(Request $request): View
    {
        $branchId = $this->positiveIntegerOrNull(
            $request->query('branch_id')
        );

        $status = strtolower(
            trim(
                (string) $request->query(
                    'status',
                    ''
                )
            )
        );

        if (
            ! in_array(
                $status,
                [
                    'draft',
                    'published',
                ],
                true
            )
        ) {
            $status = '';
        }

        $weekStartDate = $this->validDateOrNull(
            (string) $request->query(
                'week_start_date',
                ''
            )
        );

        $weeklySchedules =
            WeeklySchedule::query()
                ->with([
                    'branch:id,code,name,status',
                    'creator:id,name',
                    'publisher:id,name',
                ])
                ->withCount('items')
                ->when(
                    $branchId !== null,
                    fn ($query) => $query->where(
                        'branch_id',
                        $branchId
                    )
                )
                ->when(
                    $status !== '',
                    fn ($query) => $query->where(
                        'status',
                        $status
                    )
                )
                ->when(
                    $weekStartDate !== null,
                    fn ($query) => $query->whereDate(
                        'week_start_date',
                        $weekStartDate
                    )
                )
                ->orderByDesc('week_start_date')
                ->orderByDesc('id')
                ->paginate(self::ITEMS_PER_PAGE)
                ->withQueryString();

        $branches = Branch::query()
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
                'status',
            ]);

        return view('weekly-rosters.index', [
            'weeklySchedules' => $weeklySchedules,

            'branches' => $branches,

            'selectedBranchId' => $branchId,

            'selectedStatus' => $status,

            'selectedWeekStartDate' => $weekStartDate,
        ]);
    }

    /**
     * Menampilkan form penyusunan roster.
     */
    public function create(): View
    {
        $branches = Branch::query()
            ->where('status', 'active')
            ->with([
                'employees' => static function (
                    $query
                ): void {
                    $query
                        ->where(
                            'employment_status',
                            'active'
                        )
                        ->orderBy('employee_number')
                        ->select([
                            'id',
                            'branch_id',
                            'employee_number',
                            'full_name',
                            'position',
                        ]);
                },
            ])
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
            ]);

        $workSchedules =
            WorkSchedule::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'check_in_time',
                    'check_out_time',
                ]);

        $weekStartDate =
            CarbonImmutable::now(
                (string) config(
                    'app.timezone',
                    'Asia/Jakarta'
                )
            )
                ->startOfWeek()
                ->toDateString();

        return view('weekly-rosters.create', [
            'branches' => $branches,

            'workSchedules' => $workSchedules,

            'weekStartDate' => $weekStartDate,
        ]);
    }

    /**
     * Menyimpan roster mingguan sebagai draft.
     */
    public function store(
        StoreWeeklyScheduleRequest $request,
        WeeklyRosterService $weeklyRosterService
    ): JsonResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $weeklySchedule =
            $weeklyRosterService->createDraft(
                $request->validated(),
                $user
            );

        return response()->json(
            [
                'message' => 'Roster mingguan berhasil disimpan sebagai draft.',

                'data' => $this->summary($weeklySchedule),
            ],
            201
        );
    }

    /**
     * Menampilkan detail roster mingguan.
     */
    public function show(
        WeeklySchedule $weeklySchedule
    ): View {
        $weeklySchedule->load([
            'branch:id,code,name,status',

            'creator:id,name',

            'publisher:id,name',

            'items' => static function (
                $query
            ): void {
                $query
                    ->orderBy('schedule_date')
                    ->orderBy('employee_id');
            },

            'items.employee:id,branch_id,employee_number,full_name,position,employment_status',

            'items.workSchedule:id,name,status',

            'items.employeeSchedule:id,weekly_schedule_item_id',
        ]);

        return view('weekly-rosters.show', [
            'weeklySchedule' => $weeklySchedule,
        ]);
    }

    /**
     * Memublikasikan draft menjadi jadwal harian.
     */
    public function publish(
        Request $request,
        WeeklySchedule $weeklySchedule,
        WeeklyRosterService $weeklyRosterService
    ): JsonResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $publishedSchedule =
            $weeklyRosterService->publish(
                $weeklySchedule,
                $user
            );

        return response()->json([
            'message' => 'Roster mingguan berhasil dipublikasikan.',

            'data' => $this->summary($publishedSchedule),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(
        WeeklySchedule $weeklySchedule
    ): array {
        $itemsCount =
            $weeklySchedule->relationLoaded('items')
                ? $weeklySchedule->items->count()
                : $weeklySchedule->items()->count();

        return [
            'id' => (int) $weeklySchedule->getKey(),

            'branch_id' => (int) $weeklySchedule->branch_id,

            'week_start_date' => $this->dateString(
                $weeklySchedule->week_start_date
            ),

            'week_end_date' => $this->dateString(
                $weeklySchedule->week_end_date
            ),

            'status' => (string) $weeklySchedule->status,

            'created_by' => (int) $weeklySchedule->created_by,

            'published_by' => $weeklySchedule->published_by === null
                    ? null
                    : (int) $weeklySchedule
                        ->published_by,

            'published_at' => $weeklySchedule->published_at
                ?->toIso8601String(),

            'items_count' => $itemsCount,
        ];
    }

    /**
     * Mengubah input menjadi ID positif.
     */
    private function positiveIntegerOrNull(
        mixed $value
    ): ?int {
        if (is_int($value)) {
            return $value > 0
                ? $value
                : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if (
            $normalized === ''
            || ! ctype_digit($normalized)
        ) {
            return null;
        }

        $integer = (int) $normalized;

        return $integer > 0
            ? $integer
            : null;
    }

    /**
     * Membaca tanggal Y-m-d secara ketat.
     */
    private function validDateOrNull(
        string $value
    ): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date =
                DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $value,
                    new DateTimeZone(
                        (string) config(
                            'app.timezone',
                            'Asia/Jakarta'
                        )
                    )
                );
        } catch (Throwable) {
            return null;
        }

        if ($date === false) {
            return null;
        }

        $errors =
            DateTimeImmutable::getLastErrors();

        if (
            $errors !== false
            && (
                $errors['warning_count'] > 0
                || $errors['error_count'] > 0
            )
        ) {
            return null;
        }

        return $date->format('Y-m-d') === $value
            ? $value
            : null;
    }

    private function dateString(
        mixed $value
    ): string {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr(
            (string) $value,
            0,
            10
        );
    }
}
