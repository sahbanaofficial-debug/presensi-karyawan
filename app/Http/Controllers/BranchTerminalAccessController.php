<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\BranchTerminalLifecycleException;
use App\Http\Middleware\AuthenticateBranchTerminal;
use App\Http\Requests\ActivateBranchTerminalRequest;
use App\Models\BranchTerminal;
use App\Services\BranchTerminalLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BranchTerminalAccessController extends Controller
{
    public function activate(
        ActivateBranchTerminalRequest $request,
        BranchTerminalLifecycleService $lifecycleService
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $result = $lifecycleService->activate(
                publicId: (string) $validated[
                        'public_id'
                    ],

                activationCode: (string) $validated[
                        'activation_code'
                    ]
            );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            return $this->activationFailure(
                $exception
            );
        }

        $terminal = $result['terminal'];
        $terminal->loadMissing('branch');

        return response()->json(
            [
                'success' => true,
                'code' => 'terminal_activated',
                'message' => 'Terminal berhasil diaktifkan.',

                'data' => [
                    'terminal' => $this->terminalData(
                        $terminal
                    ),

                    /*
                     * Token perangkat hanya dikembalikan
                     * pada respons aktivasi ini.
                     */
                    'device_token' => $result['device_token'],
                ],
            ],
            201
        );
    }

    public function identity(
        Request $request
    ): JsonResponse {
        $terminal = $request->attributes->get(
            AuthenticateBranchTerminal::TERMINAL_ATTRIBUTE
        );

        if (
            ! $terminal instanceof BranchTerminal
        ) {
            return response()->json(
                [
                    'success' => false,
                    'code' => 'terminal_context_missing',

                    'message' => 'Konteks terminal tidak tersedia.',
                ],
                500
            );
        }

        $terminal->loadMissing('branch');

        return response()->json([
            'success' => true,
            'code' => 'terminal_authenticated',
            'message' => 'Terminal berhasil diautentikasi.',

            'data' => [
                'terminal' => $this->terminalData(
                    $terminal
                ),
            ],
        ]);
    }

    private function activationFailure(
        BranchTerminalLifecycleException $exception
    ): JsonResponse {
        $status = match ($exception->reason) {
            'terminal_not_found' => 404,
            'activation_expired' => 410,

            'terminal_not_pending',
            'activation_unavailable' => 409,

            default => 422,
        };

        return response()->json(
            [
                'success' => false,
                'code' => $exception->reason,
                'message' => $exception->getMessage(),
            ],
            $status
        );
    }

    /**
     * @return array{
     *     public_id: string,
     *     name: string,
     *     status: string,
     *     branch: array{
     *         code: string,
     *         name: string
     *     },
     *     activated_at: ?string,
     *     last_seen_at: ?string
     * }
     */
    private function terminalData(
        BranchTerminal $terminal
    ): array {
        return [
            'public_id' => (string) $terminal->public_id,

            'name' => (string) $terminal->name,
            'status' => (string) $terminal->status,

            'branch' => [
                'code' => (string)
                    $terminal->branch->code,

                'name' => (string)
                    $terminal->branch->name,
            ],

            'activated_at' => $terminal->activated_at
                ?->toIso8601String(),

            'last_seen_at' => $terminal->last_seen_at
                ?->toIso8601String(),
        ];
    }
}
