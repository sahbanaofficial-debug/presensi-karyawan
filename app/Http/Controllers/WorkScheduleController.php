<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkScheduleRequest;
use App\Http\Requests\UpdateWorkScheduleRequest;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WorkScheduleController extends Controller
{
    private const ITEMS_PER_PAGE = 10;

    /**
     * Menampilkan daftar pola jadwal kerja.
     */
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->query('search', '')
        );

        $status = strtolower(
            trim((string) $request->query('status', ''))
        );

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $workSchedules = WorkSchedule::query()
            ->withCount('employeeSchedules')
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
            )
            ->when(
                $status !== '',
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->orderBy('check_in_time')
            ->orderBy('name')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        return view('work-schedules.index', [
            'workSchedules' => $workSchedules,
            'search' => $search,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Menampilkan formulir penambahan pola jadwal.
     */
    public function create(): View
    {
        return view('work-schedules.create');
    }

    /**
     * Menyimpan pola jadwal kerja baru.
     */
    public function store(
        StoreWorkScheduleRequest $request
    ): RedirectResponse {
        $workSchedule = WorkSchedule::query()->create(
            $request->validated()
        );

        return redirect()
            ->route(
                'work-schedules.show',
                $workSchedule
            )
            ->with(
                'success',
                'Pola jadwal kerja berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan detail pola jadwal kerja.
     */
    public function show(
        WorkSchedule $workSchedule
    ): View {
        $workSchedule->loadCount(
            'employeeSchedules'
        );

        return view('work-schedules.show', [
            'workSchedule' => $workSchedule,
        ]);
    }

    /**
     * Menampilkan formulir perubahan pola jadwal.
     */
    public function edit(
        WorkSchedule $workSchedule
    ): View {
        return view('work-schedules.edit', [
            'workSchedule' => $workSchedule,
        ]);
    }

    /**
     * Memperbarui pola jadwal kerja.
     */
    public function update(
        UpdateWorkScheduleRequest $request,
        WorkSchedule $workSchedule
    ): RedirectResponse {
        $workSchedule->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'work-schedules.show',
                $workSchedule
            )
            ->with(
                'success',
                'Pola jadwal kerja berhasil diperbarui.'
            );
    }

    /**
     * Menghapus pola jadwal yang belum pernah digunakan.
     */
    public function destroy(
        WorkSchedule $workSchedule
    ): RedirectResponse {
        if (
            $workSchedule
                ->employeeSchedules()
                ->exists()
        ) {
            return redirect()
                ->route(
                    'work-schedules.show',
                    $workSchedule
                )
                ->with(
                    'error',
                    'Pola jadwal kerja tidak dapat dihapus karena sudah digunakan pada jadwal karyawan.'
                );
        }

        $workSchedule->delete();

        return redirect()
            ->route('work-schedules.index')
            ->with(
                'success',
                'Pola jadwal kerja berhasil dihapus.'
            );
    }
}
