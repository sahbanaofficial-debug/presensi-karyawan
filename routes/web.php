<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\ScheduleSwapRequestController;
use App\Http\Controllers\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get(
        '/login',
        [AuthenticatedSessionController::class, 'create']
    )->name('login');

    Route::post(
        '/login',
        [AuthenticatedSessionController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Modul Khusus HRD
    |--------------------------------------------------------------------------
    |
    | HRD dapat mengelola cabang, pola jadwal kerja,
    | dan jadwal harian karyawan.
    |
    */
    Route::middleware('role:hrd')->group(function (): void {
        Route::resource(
            'branches',
            BranchController::class
        );

        Route::resource(
            'work-schedules',
            WorkScheduleController::class
        );

        Route::resource(
            'employee-schedules',
            EmployeeScheduleController::class
        );

        Route::patch(
            '/schedule-swap-requests/{schedule_swap_request}/decision',
            [
                ScheduleSwapRequestController::class,
                'decide',
            ]
        )->name('schedule-swap-requests.decide');
    });

    /*
    |--------------------------------------------------------------------------
    | Modul Data Karyawan
    |--------------------------------------------------------------------------
    |
    | HRD dapat melihat, menambah, dan mengubah data karyawan.
    | Admin hanya dapat melihat daftar dan detail karyawan.
    |
    */
    Route::prefix('employees')
        ->name('employees.')
        ->group(function (): void {
            Route::middleware('role:hrd')
                ->group(function (): void {
                    Route::get(
                        '/create',
                        [EmployeeController::class, 'create']
                    )->name('create');

                    Route::post(
                        '/',
                        [EmployeeController::class, 'store']
                    )->name('store');

                    Route::get(
                        '/{employee}/edit',
                        [EmployeeController::class, 'edit']
                    )->name('edit');

                    Route::match(
                        ['put', 'patch'],
                        '/{employee}',
                        [EmployeeController::class, 'update']
                    )->name('update');
                });

            Route::middleware('role:hrd,admin')
                ->group(function (): void {
                    Route::get(
                        '/',
                        [EmployeeController::class, 'index']
                    )->name('index');

                    Route::get(
                        '/{employee}',
                        [EmployeeController::class, 'show']
                    )->name('show');
                });
        });

    /*
    |--------------------------------------------------------------------------
    | Modul Pertukaran Jadwal
    |--------------------------------------------------------------------------
    |
    | HRD dan admin dapat melihat serta mencatat permohonan.
    | Keputusan persetujuan atau penolakan hanya dilakukan HRD.
    |
    */
    Route::middleware('role:hrd,admin')
        ->prefix('schedule-swap-requests')
        ->name('schedule-swap-requests.')
        ->group(function (): void {
            Route::get(
                '/',
                [
                    ScheduleSwapRequestController::class,
                    'index',
                ]
            )->name('index');

            Route::get(
                '/create',
                [
                    ScheduleSwapRequestController::class,
                    'create',
                ]
            )->name('create');

            Route::post(
                '/',
                [
                    ScheduleSwapRequestController::class,
                    'store',
                ]
            )->name('store');

            Route::get(
                '/{schedule_swap_request}',
                [
                    ScheduleSwapRequestController::class,
                    'show',
                ]
            )->name('show');
        });
});

Route::post(
    '/logout',
    [AuthenticatedSessionController::class, 'destroy']
)
    ->middleware('auth')
    ->name('logout');