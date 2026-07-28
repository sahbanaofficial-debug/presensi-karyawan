<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\BranchTerminalLifecycleException;
use App\Services\BranchTerminalLifecycleService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateBranchTerminal
{
    public const TERMINAL_ATTRIBUTE =
        'branch_terminal';

    public function __construct(
        private readonly BranchTerminalLifecycleService $lifecycleService
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $publicId = trim(
            (string) $request->header(
                'X-Terminal-ID',
                ''
            )
        );

        $deviceToken = trim(
            (string) $request->header(
                'X-Terminal-Token',
                ''
            )
        );

        if (
            $publicId === ''
            || $deviceToken === ''
        ) {
            return $this->failureResponse(
                code: 'terminal_credentials_missing',
                message: 'Identitas dan token perangkat terminal wajib dikirim.',
                status: 401
            );
        }

        try {
            $terminal =
                $this->lifecycleService
                    ->authenticate(
                        publicId: $publicId,
                        deviceToken: $deviceToken
                    );
        } catch (
            BranchTerminalLifecycleException $exception
        ) {
            $status =
                $exception->reason
                === 'terminal_inactive'
                    ? 403
                    : 401;

            return $this->failureResponse(
                code: $exception->reason,
                message: $exception->getMessage(),
                status: $status
            );
        }

        $request->attributes->set(
            self::TERMINAL_ATTRIBUTE,
            $terminal
        );

        return $next($request);
    }

    private function failureResponse(
        string $code,
        string $message,
        int $status
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'code' => $code,
                'message' => $message,
            ],
            $status
        );
    }
}
