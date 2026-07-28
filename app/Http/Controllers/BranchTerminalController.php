<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\BranchTerminalLifecycleException;
use App\Http\Requests\RegisterBranchTerminalRequest;
use App\Models\Branch;
use App\Models\BranchTerminal;
use App\Models\User;
use App\Services\BranchTerminalLifecycleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class BranchTerminalController extends Controller
{
    private const ITEMS_PER_PAGE = 15;

    public function index(
        Request $request
    ): View {
        $search = trim(
            (string) $request->query(
                'search',
                ''
            )
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
                    BranchTerminal::STATUS_PENDING,
                    BranchTerminal::STATUS_ACTIVE,
                    BranchTerminal::STATUS_REVOKED,
                ],
                true
            )
        ) {
            $status = '';
        }

        $branchId = $this->validBranchIdOrNull(
            $request->query(
                'branch_id'
            )
        );

        $terminals = BranchTerminal::query()
            ->with('branch')
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $searchQuery
                        ) use ($search): void {
                            $searchQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'public_id',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'branch',
                                    function (
                                        Builder $branchQuery
                                    ) use ($search): void {
                                        $branchQuery
                                            ->where(
                                                'code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $status !== '',
                function (
                    Builder $query
                ) use ($status): void {
                    $query->where(
                        'status',
                        $status
                    );
                }
            )
            ->when(
                $branchId !== null,
                function (
                    Builder $query
                ) use ($branchId): void {
                    $query->where(
                        'branch_id',
                        $branchId
                    );
                }
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(
                self::ITEMS_PER_PAGE
            )
            ->withQueryString();

        return view(
            'branch-terminals.index',
            [
                'terminals' => $terminals,

                'branches' => $this->allBranches(),

                'search' => $search,
                'status' => $status,
                'branchId' => $branchId,
            ]
        );
    }

    public function create(): View
    {
        return view(
            'branch-terminals.create',
            [
                'branches' => $this->registrableBranches(),
            ]
        );
    }

    public function store(
        RegisterBranchTerminalRequest $request,
        BranchTerminalLifecycleService $lifecycleService
    ): RedirectResponse {
        $actor = $this->authenticatedUser(
            $request
        );

        $branch = Branch::query()->findOrFail(
            (int) $request->validated(
                'branch_id'
            )
        );

        try {
            $result = $lifecycleService->register(
                branch: $branch,
                creator: $actor,

                name: (string)
                    $request->validated(
                        'name'
                    )
            );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'terminal' => $exception->getMessage(),
                ]);
        }

        $terminal = $result['terminal'];

        return redirect()
            ->route(
                'branch-terminals.show',
                $terminal
            )
            ->with(
                'success',
                'Terminal berhasil didaftarkan.'
            )
            ->with(
                'activation_code',
                $result['activation_code']
            )
            ->with(
                'activation_expires_at',
                $terminal
                    ->activation_expires_at
                    ?->format(
                        'd M Y H:i:s'
                    )
            );
    }

    public function show(
        BranchTerminal $branchTerminal
    ): View {
        $branchTerminal->load('branch');

        return view(
            'branch-terminals.show',
            [
                'terminal' => $branchTerminal,

                'creator' => User::query()->find(
                    $branchTerminal
                        ->created_by
                ),

                'revoker' => $branchTerminal
                    ->revoked_by === null
                        ? null
                        : User::query()->find(
                            $branchTerminal
                                ->revoked_by
                        ),

                'attendanceCount' => DB::table('attendances')
                    ->where(
                        'branch_terminal_id',
                        $branchTerminal
                            ->getKey()
                    )
                    ->count(),

                'validationLogCount' => DB::table('validation_logs')
                    ->where(
                        'branch_terminal_id',
                        $branchTerminal
                            ->getKey()
                    )
                    ->count(),
            ]
        );
    }

    public function renewActivation(
        Request $request,
        BranchTerminal $branchTerminal,
        BranchTerminalLifecycleService $lifecycleService
    ): RedirectResponse {
        $this->authenticatedUser(
            $request
        );

        try {
            $result =
                $lifecycleService
                    ->renewActivation(
                        $branchTerminal
                    );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            return redirect()
                ->route(
                    'branch-terminals.show',
                    $branchTerminal
                )
                ->with(
                    'error',
                    $exception->getMessage()
                );
        }

        $terminal = $result['terminal'];

        return redirect()
            ->route(
                'branch-terminals.show',
                $terminal
            )
            ->with(
                'success',
                'Kode aktivasi terminal berhasil diperbarui.'
            )
            ->with(
                'activation_code',
                $result['activation_code']
            )
            ->with(
                'activation_expires_at',
                $terminal
                    ->activation_expires_at
                    ?->format(
                        'd M Y H:i:s'
                    )
            );
    }

    public function revoke(
        Request $request,
        BranchTerminal $branchTerminal,
        BranchTerminalLifecycleService $lifecycleService
    ): RedirectResponse {
        $actor = $this->authenticatedUser(
            $request
        );

        try {
            $terminal = $lifecycleService
                ->revoke(
                    branchTerminal: $branchTerminal,

                    revoker: $actor
                );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            return redirect()
                ->route(
                    'branch-terminals.show',
                    $branchTerminal
                )
                ->with(
                    'error',
                    $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'branch-terminals.show',
                $terminal
            )
            ->with(
                'success',
                'Akses terminal berhasil dicabut.'
            );
    }

    /**
     * @return Collection<int, Branch>
     */
    private function allBranches()
    {
        return Branch::query()
            ->orderBy('code')
            ->get();
    }

    /**
     * @return Collection<int, Branch>
     */
    private function registrableBranches()
    {
        return Branch::query()
            ->where(
                'status',
                'active'
            )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(
                'geofence_radius',
                '>',
                0
            )
            ->where(
                'maximum_accuracy',
                '>',
                0
            )
            ->orderBy('code')
            ->get();
    }

    private function validBranchIdOrNull(
        mixed $value
    ): ?int {
        if (
            ! is_string($value)
            && ! is_int($value)
        ) {
            return null;
        }

        $normalized = trim(
            (string) $value
        );

        if (
            $normalized === ''
            || preg_match(
                '/^\d+$/',
                $normalized
            ) !== 1
        ) {
            return null;
        }

        $branchId = (int) $normalized;

        return Branch::query()
            ->whereKey($branchId)
            ->exists()
                ? $branchId
                : null;
    }

    private function authenticatedUser(
        Request $request
    ): User {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        return $user;
    }
}
