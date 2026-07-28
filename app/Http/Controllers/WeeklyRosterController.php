<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeeklyScheduleRequest;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Services\WeeklyRosterService;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WeeklyRosterController extends Controller
{
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
