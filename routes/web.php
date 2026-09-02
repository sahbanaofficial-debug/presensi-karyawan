<?php

declare(strict_types=1);

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AttendanceHistoryController;
use App\Http\Controllers\AttendanceMonitoringController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\AttendanceValidationLogController;
use App\Http\Controllers\Auth\AccountPasswordController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchTerminalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\ScheduleSwapRequestController;
use App\Http\Controllers\WeeklyRosterController;
use App\Http\Controllers\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

/*
|--------------------------------------------------------------------------
| Halaman Operasional Terminal Cabang
|--------------------------------------------------------------------------
|
| Halaman perangkat tidak menggunakan sesi pengguna. Aktivasi dan
| pengambilan QR dilakukan melalui API terminal stateless.
|
*/
Route::view(
    '/terminal',
    'terminal.index'
)->name('terminal.display');

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
    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');

    Route::get(
        '/account/password',
        [AccountPasswordController::class, 'edit']
    )->name('account.password.edit');

    Route::put(
        '/account/password',
        [AccountPasswordController::class, 'update']
    )
        ->middleware('throttle:5,1')
        ->name('account.password.update');
    /*
     |--------------------------------------------------------------------------
     | Presensi Karyawan
     |--------------------------------------------------------------------------
     */
    Route::middleware('role:employee')
        ->prefix('attendance')
        ->name('attendance.')
        ->group(function (): void {
            Route::get(
                '/history',
                [
                    AttendanceHistoryController::class,
                    'index',
                ]
            )->name('history');

            Route::get(
                '/',
                [
                    AttendanceController::class,
                    'create',
                ]
            )->name('create');

            Route::post(
                '/',
                [
                    AttendanceController::class,
                    'store',
                ]
            )
                ->middleware('throttle:10,1')
                ->name('store');
        });
    /*
    |--------------------------------------------------------------------------
    | Monitoring Presensi
    |--------------------------------------------------------------------------
    |
    | HRD dan admin dapat memantau seluruh transaksi presensi
    | berdasarkan tanggal, cabang, karyawan, jenis, dan ketepatan waktu.
    |
    */
    Route::get(
        '/attendance-monitoring',
        [
            AttendanceMonitoringController::class,
            'index',
        ]
    )
        ->middleware('role:hrd,admin')
        ->name('attendance-monitoring.index');
    Route::get(
        '/attendance-reports',
        [
            AttendanceReportController::class,
            'index',
        ]
    )
        ->middleware('role:hrd,admin')
        ->name('attendance-reports.index');
    /*
    |--------------------------------------------------------------------------
    | Log Validasi Presensi
    |--------------------------------------------------------------------------
    |
    | HRD dapat melihat transaksi yang diterima, ditolak,
    | dan percobaan presensi yang tidak sesuai aturan.
    |
    */
    Route::get(
        '/attendance-validation-logs',
        [
            AttendanceValidationLogController::class,
            'index',
        ]
    )
        ->middleware('role:hrd')
        ->name('attendance-validation-logs.index');
    /*
    |--------------------------------------------------------------------------
    | Koreksi Manual Presensi
    |--------------------------------------------------------------------------
    |
    | HRD dapat membuat presensi manual dan mengoreksi transaksi
    | presensi yang sudah tersimpan. Setiap tindakan dicatat
    | dalam riwayat audit.
    |
    */
    Route::middleware('role:hrd')
        ->prefix('attendance-corrections')
        ->name('attendance-corrections.')
        ->group(function (): void {
            Route::get(
                '/create',
                [
                    AttendanceCorrectionController::class,
                    'create',
                ]
            )->name('create');

            Route::post(
                '/',
                [
                    AttendanceCorrectionController::class,
                    'store',
                ]
            )->name('store');

            Route::get(
                '/{attendance}/edit',
                [
                    AttendanceCorrectionController::class,
                    'edit',
                ]
            )
                ->whereNumber('attendance')
                ->name('edit');

            Route::put(
                '/{attendance}',
                [
                    AttendanceCorrectionController::class,
                    'update',
                ]
            )
                ->whereNumber('attendance')
                ->name('update');
        });
    Route::post(
        '/logout',
        [AuthenticatedSessionController::class, 'destroy']
    )
        ->middleware('auth')
        ->name('logout');
    /*
    |--------------------------------------------------------------------------
    | Modul Khusus HRD
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | Manajemen Terminal Cabang
    |--------------------------------------------------------------------------
    |
    | HRD dan admin cabang dapat mendaftarkan terminal, memperbarui kode aktivasi,
    | melihat status penggunaan, dan mencabut akses perangkat.
    |
    */
    Route::middleware('role:hrd,admin')
        ->prefix('branch-terminals')
        ->name('branch-terminals.')
        ->group(function (): void {
            Route::get(
                '/',
                [
                    BranchTerminalController::class,
                    'index',
                ]
            )->name('index');

            Route::get(
                '/create',
                [
                    BranchTerminalController::class,
                    'create',
                ]
            )->name('create');

            Route::post(
                '/',
                [
                    BranchTerminalController::class,
                    'store',
                ]
            )->name('store');

            Route::patch(
                '/{branch_terminal}/renew-activation',
                [
                    BranchTerminalController::class,
                    'renewActivation',
                ]
            )
                ->whereNumber('branch_terminal')
                ->name('renew-activation');

            Route::patch(
                '/{branch_terminal}/revoke',
                [
                    BranchTerminalController::class,
                    'revoke',
                ]
            )
                ->whereNumber('branch_terminal')
                ->name('revoke');

            Route::get(
                '/{branch_terminal}',
                [
                    BranchTerminalController::class,
                    'show',
                ]
            )
                ->whereNumber('branch_terminal')
                ->name('show');
        });
    /*
    |--------------------------------------------------------------------------
    | Modul Sesi Presensi dan QR Code Dinamis
    |--------------------------------------------------------------------------
    |
    | HRD dan admin dapat membuka, melihat, menampilkan payload QR,
    | dan menutup sesi presensi.
    |
    */
    Route::middleware('role:hrd,admin')
        ->prefix('attendance-sessions')
        ->name('attendance-sessions.')
        ->group(function (): void {
            Route::get(
                '/',
                [
                    AttendanceSessionController::class,
                    'index',
                ]
            )->name('index');

            Route::get(
                '/create',
                [
                    AttendanceSessionController::class,
                    'create',
                ]
            )->name('create');

            Route::post(
                '/',
                [
                    AttendanceSessionController::class,
                    'store',
                ]
            )->name('store');

            Route::get(
                '/{attendance_session}/payload',
                [
                    AttendanceSessionController::class,
                    'payload',
                ]
            )
                ->middleware('throttle:120,1')
                ->name('payload');

            Route::patch(
                '/{attendance_session}/close',
                [
                    AttendanceSessionController::class,
                    'close',
                ]
            )->name('close');

            Route::get(
                '/{attendance_session}',
                [
                    AttendanceSessionController::class,
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

/*
|--------------------------------------------------------------------------
| Backend Roster Mingguan
|--------------------------------------------------------------------------
|
| HRD dan Admin Cabang dapat menyimpan roster sebagai draft
| dan memublikasikannya menjadi jadwal harian karyawan.
|
*/
Route::middleware([
    'auth',
    'active',
    'role:hrd,admin',
])
    ->prefix('weekly-rosters')
    ->name('weekly-rosters.')
    ->group(function (): void {
        Route::get(
            '/',
            [
                WeeklyRosterController::class,
                'index',
            ]
        )->name('index');

        Route::get(
            '/create',
            [
                WeeklyRosterController::class,
                'create',
            ]
        )->name('create');

        Route::post(
            '/',
            [
                WeeklyRosterController::class,
                'store',
            ]
        )->name('store');

        Route::patch(
            '/{weekly_schedule}/publish',
            [
                WeeklyRosterController::class,
                'publish',
            ]
        )
            ->whereNumber('weekly_schedule')
            ->name('publish');

        Route::get(
            '/{weekly_schedule}',
            [
                WeeklyRosterController::class,
                'show',
            ]
        )
            ->whereNumber('weekly_schedule')
            ->name('show');
    });
