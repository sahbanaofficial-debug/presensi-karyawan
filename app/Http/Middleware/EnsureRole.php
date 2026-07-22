<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRole
{
    /**
     * Memastikan pengguna memiliki salah satu peran yang diizinkan.
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (
            $roles === []
            || ! in_array($user->role, $roles, true)
        ) {
            abort(
                403,
                'Anda tidak memiliki hak akses untuk membuka halaman ini.'
            );
        }

        return $next($request);
    }
}
