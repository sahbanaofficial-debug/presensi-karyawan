<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\TerminalDynamicQrException;
use App\Http\Middleware\AuthenticateBranchTerminal;
use App\Models\BranchTerminal;
use App\Services\TerminalDynamicQrPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;

final class TerminalDynamicQrController extends Controller
{
    public function show(
        Request $request,
        TerminalDynamicQrPayloadService $payloadService
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

        try {
            $payload =
                $payloadService->payloadFor(
                    $terminal
                );
        } catch (
            TerminalDynamicQrException $exception
        ) {
            return $this->domainFailure(
                $exception
            );
        } catch (JsonException) {
            return response()->json(
                [
                    'success' => false,

                    'code' => 'qr_payload_encoding_failed',

                    'message' => 'Payload QR tidak dapat dibentuk.',
                ],
                500
            );
        }

        $available =
            $payload['available'];

        return response()->json([
            'success' => true,

            'code' => $available
                ? 'terminal_qr_payload_ready'
                : 'terminal_qr_unavailable',

            'message' => $available
                ? 'Payload QR terminal tersedia.'
                : 'Belum ada sesi presensi otomatis aktif untuk terminal.',

            'data' => $payload,
        ]);
    }

    private function domainFailure(
        TerminalDynamicQrException $exception
    ): JsonResponse {
        $status = match ($exception->reason) {
            'terminal_not_found' => 404,
            'terminal_inactive' => 403,

            'terminal_branch_unavailable' => 409,

            default => 500,
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
}
