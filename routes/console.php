<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Otomatisasi Jadwal Harian Default Cabang
|--------------------------------------------------------------------------
|
| Jadwal harian karyawan aktif dibentuk dari pola kerja default cabang
| sebelum generator sesi presensi otomatis dijalankan.
|
*/
Schedule::command(
    'employee-schedules:generate-branch-default'
)
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

/*
|--------------------------------------------------------------------------
| Otomatisasi Sesi Presensi
|--------------------------------------------------------------------------
|
| Scheduler memeriksa roster pada tanggal server setiap lima menit.
| Command dan service bersifat idempotent. Overlap dikunci agar dua
| proses scheduler tidak membentuk sesi pada waktu yang bersamaan.
|
*/
Schedule::command(
    'attendance-sessions:generate-automatic'
)
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

/*
|--------------------------------------------------------------------------
| Lifecycle Sesi Presensi Otomatis
|--------------------------------------------------------------------------
|
| Sesi otomatis aktif yang telah melewati end_time ditandai expired
| tanpa menunggu pemindaian karyawan atau halaman manajemen dibuka.
|
*/
Schedule::command(
    'attendance-sessions:expire-automatic'
)
    ->everyMinute()
    ->withoutOverlapping(10);
