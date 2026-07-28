<?php

declare(strict_types=1);

use App\Http\Controllers\BranchTerminalAccessController;
use Illuminate\Support\Facades\Route;

Route::prefix('terminal')
    ->name('terminal.')
    ->group(function (): void {
        Route::post(
            '/activate',
            [
                BranchTerminalAccessController::class,
                'activate',
            ]
        )
            ->middleware('throttle:5,1')
            ->name('activate');

        Route::get(
            '/identity',
            [
                BranchTerminalAccessController::class,
                'identity',
            ]
        )
            ->middleware([
                'throttle:120,1',
                'branch-terminal',
            ])
            ->name('identity');
    });
