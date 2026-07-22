@extends('layouts.app')

@section('title', 'Detail Pola Jadwal')

@section('content')
    @php
        $toTime = static function ($value): ?\DateTimeImmutable {
            if ($value === null || $value === '') {
                return null;
            }

            if ($value instanceof \DateTimeInterface) {
                return \DateTimeImmutable::createFromInterface(
                    $value
                );
            }

            $time = trim((string) $value);

            foreach (['!H:i:s', '!H:i'] as $format) {
                $parsedTime = \DateTimeImmutable::createFromFormat(
                    $format,
                    $time
                );

                if ($parsedTime !== false) {
                    return $parsedTime;
                }
            }

            return null;
        };

        $formatTime = static function (
            ?\DateTimeInterface $time
        ): string {
            return $time?->format('H:i') ?? '-';
        };

        $checkInTime = $toTime(
            $workSchedule->check_in_time
        );

        $checkOutTime = $toTime(
            $workSchedule->check_out_time
        );

        $checkInOpenMinutes = (int) (
            $workSchedule->check_in_open_minutes ?? 0
        );

        $lateToleranceMinutes = (int) (
            $workSchedule->late_tolerance_minutes ?? 0
        );

        $checkOutLimitMinutes = (int) (
            $workSchedule->check_out_limit_minutes ?? 0
        );

        $checkInOpenTime = $checkInTime?->modify(
            "-{$checkInOpenMinutes} minutes"
        );

        $lateToleranceLimit = $checkInTime?->modify(
            "+{$lateToleranceMinutes} minutes"
        );

        $checkOutLimitTime = $checkOutTime?->modify(
            "+{$checkOutLimitMinutes} minutes"
        );

        $usageCount = (int) (
            $workSchedule->employee_schedules_count ?? 0
        );
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Detail Pola Jadwal
            </h1>

            <p class="page-description">
                Informasi jam kerja dan aturan waktu presensi
                {{ $workSchedule->name }}.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a
                href="{{ route('work-schedules.index') }}"
                class="btn btn-outline-secondary"
            >
                Daftar Pola Jadwal
            </a>

            <a
                href="{{
                    route(
                        'work-schedules.edit',
                        $workSchedule
                    )
                }}"
                class="btn btn-primary"
            >
                Edit Pola Jadwal
            </a>
        </div>
    </header>

    <section class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="content-card h-100 p-3">
                <div class="small text-secondary mb-1">
                    Jam Masuk
                </div>

                <div class="fs-3 fw-bold">
                    {{ $formatTime($checkInTime) }}
                </div>

                <div class="small text-secondary">
                    WIB
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="content-card h-100 p-3">
                <div class="small text-secondary mb-1">
                    Jam Pulang
                </div>

                <div class="fs-3 fw-bold">
                    {{ $formatTime($checkOutTime) }}
                </div>

                <div class="small text-secondary">
                    WIB
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="content-card h-100 p-3">
                <div class="small text-secondary mb-1">
                    Digunakan
                </div>

                <div class="fs-3 fw-bold">
                    {{ $usageCount }}
                </div>

                <div class="small text-secondary">
                    Jadwal harian karyawan
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="content-card h-100 p-3">
                <div class="small text-secondary mb-1">
                    Status
                </div>

                <div class="mt-2">
                    @if ($workSchedule->status === 'active')
                        <span class="badge text-bg-success fs-6">
                            Aktif
                        </span>
                    @else
                        <span class="badge text-bg-secondary fs-6">
                            Tidak aktif
                        </span>
                    @endif
                </div>

                <div class="small text-secondary mt-2">
                    Status pola jadwal
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-7">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Informasi Pola Jadwal
                    </h2>

                    <p class="small text-secondary mb-0">
                        Identitas dan konfigurasi utama jadwal kerja.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-6 mb-2">
                            Nama Jadwal
                        </dt>

                        <dd class="col-sm-6 mb-3">
                            <span class="fw-semibold">
                                {{ $workSchedule->name }}
                            </span>
                        </dd>

                        <dt class="col-sm-6 mb-2">
                            Jam Masuk
                        </dt>

                        <dd class="col-sm-6 mb-3">
                            {{ $formatTime($checkInTime) }} WIB
                        </dd>

                        <dt class="col-sm-6 mb-2">
                            Jam Pulang
                        </dt>

                        <dd class="col-sm-6 mb-3">
                            {{ $formatTime($checkOutTime) }} WIB
                        </dd>

                        <dt class="col-sm-6 mb-2">
                            Pembukaan Presensi Masuk
                        </dt>

                        <dd class="col-sm-6 mb-3">
                            {{ $checkInOpenMinutes }} menit sebelum
                            jam masuk
                        </dd>

                        <dt class="col-sm-6 mb-2">
                            Toleransi Keterlambatan
                        </dt>

                        <dd class="col-sm-6 mb-3">
                            {{ $lateToleranceMinutes }} menit
                            setelah jam masuk
                        </dd>

                        <dt class="col-sm-6 mb-2">
                            Batas Presensi Pulang
                        </dt>

                        <dd class="col-sm-6 mb-3">
                            {{ $checkOutLimitMinutes }} menit
                            setelah jam pulang
                        </dd>

                        <dt class="col-sm-6 mb-2">
                            Status
                        </dt>

                        <dd class="col-sm-6 mb-3">
                            @if (
                                $workSchedule->status === 'active'
                            )
                                <span class="badge text-bg-success">
                                    Aktif
                                </span>
                            @else
                                <span class="badge text-bg-secondary">
                                    Tidak aktif
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-6 mb-2">
                            Dibuat
                        </dt>

                        <dd class="col-sm-6 mb-3">
                            @if ($workSchedule->created_at !== null)
                                {{
                                    $workSchedule->created_at
                                        ->timezone('Asia/Jakarta')
                                        ->format('d-m-Y H:i')
                                }}
                                WIB
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-6 mb-2">
                            Terakhir Diperbarui
                        </dt>

                        <dd class="col-sm-6 mb-0">
                            @if ($workSchedule->updated_at !== null)
                                {{
                                    $workSchedule->updated_at
                                        ->timezone('Asia/Jakarta')
                                        ->format('d-m-Y H:i')
                                }}
                                WIB
                            @else
                                -
                            @endif
                        </dd>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="content-card mb-4">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Rentang Presensi Masuk
                    </h2>

                    <p class="small text-secondary mb-0">
                        Perhitungan berdasarkan konfigurasi jadwal.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-7 mb-2">
                            Presensi Dibuka
                        </dt>

                        <dd class="col-sm-5 mb-3 text-sm-end">
                            {{
                                $formatTime(
                                    $checkInOpenTime
                                )
                            }}
                            WIB
                        </dd>

                        <dt class="col-sm-7 mb-2">
                            Jam Masuk
                        </dt>

                        <dd class="col-sm-5 mb-3 text-sm-end">
                            {{ $formatTime($checkInTime) }}
                            WIB
                        </dd>

                        <dt class="col-sm-7 mb-2">
                            Batas Toleransi
                        </dt>

                        <dd class="col-sm-5 mb-0 text-sm-end">
                            {{
                                $formatTime(
                                    $lateToleranceLimit
                                )
                            }}
                            WIB
                        </dd>
                    </dl>

                    <div class="alert alert-info small mt-4 mb-0">
                        Presensi setelah batas toleransi tetap dapat
                        dicatat, tetapi diberikan status terlambat.
                    </div>
                </div>
            </section>

            <section class="content-card mb-4">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Rentang Presensi Pulang
                    </h2>

                    <p class="small text-secondary mb-0">
                        Presensi pulang hanya sah dalam rentang waktu
                        yang ditentukan.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-7 mb-2">
                            Presensi Pulang Dibuka
                        </dt>

                        <dd class="col-sm-5 mb-3 text-sm-end">
                            {{ $formatTime($checkOutTime) }}
                            WIB
                        </dd>

                        <dt class="col-sm-7 mb-2">
                            Batas Akhir
                        </dt>

                        <dd class="col-sm-5 mb-0 text-sm-end">
                            {{
                                $formatTime(
                                    $checkOutLimitTime
                                )
                            }}
                            WIB
                        </dd>
                    </dl>

                    <div class="alert alert-warning small mt-4 mb-0">
                        Percobaan presensi pulang sebelum jam pulang
                        tidak dianggap sebagai presensi pulang yang
                        sah.
                    </div>
                </div>
            </section>

            <section class="content-card">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Penghapusan Pola Jadwal
                    </h2>

                    <p class="small text-secondary mb-0">
                        Pola jadwal hanya dapat dihapus apabila belum
                        digunakan.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    @if ($usageCount > 0)
                        <div
                            class="alert alert-warning mb-0"
                            role="alert"
                        >
                            Pola jadwal ini telah digunakan pada
                            {{ $usageCount }} jadwal karyawan dan
                            tidak dapat dihapus.
                        </div>
                    @else
                        <p class="text-secondary small">
                            Pola jadwal belum digunakan pada jadwal
                            karyawan. Penghapusan akan menghilangkan
                            data secara permanen.
                        </p>

                        <form
                            method="POST"
                            action="{{
                                route(
                                    'work-schedules.destroy',
                                    $workSchedule
                                )
                            }}"
                            onsubmit="
                                return confirm(
                                    'Hapus pola jadwal ini secara permanen?'
                                );
                            "
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                            >
                                Hapus Pola Jadwal
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection