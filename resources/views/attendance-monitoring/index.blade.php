@extends('layouts.app')

@section('title', 'Monitoring Presensi')

@section('content')
    @php
        $attendanceTypeLabels = [
            'check_in' => 'Presensi Masuk',
            'check_out' => 'Presensi Pulang',
        ];

        $attendanceTypeClasses = [
            'check_in' => 'text-bg-primary',
            'check_out' => 'text-bg-info',
        ];

        $punctualityLabels = [
            'on_time' => 'Tepat Waktu',
            'late' => 'Terlambat',
            'not_applicable' => 'Tidak Berlaku',
        ];

        $punctualityClasses = [
            'on_time' => 'text-bg-success',
            'late' => 'text-bg-danger',
            'not_applicable' => 'text-bg-secondary',
        ];

        $validationStatusLabels = [
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
        ];

        $validationStatusClasses = [
            'accepted' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ];

        $formatDate = static function (mixed $value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y');
            } catch (\Throwable) {
                return substr((string) $value, 0, 10);
            }
        };

        $formatTime = static function (mixed $value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i:s');
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('H:i:s');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDecimal = static function (
            mixed $value,
            int $precision = 2
        ): string {
            if (
                $value === null
                || $value === ''
                || ! is_numeric($value)
            ) {
                return '-';
            }

            return number_format(
                (float) $value,
                $precision,
                ',',
                '.'
            );
        };

        $filterIsActive =
            $selectedAttendanceDate !== null
            || $selectedBranchId !== null
            || $selectedEmployeeId !== null
            || $selectedAttendanceType !== ''
            || $selectedPunctualityStatus !== '';
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Monitoring Presensi
            </h1>

            <p class="page-description">
                Pantau transaksi presensi karyawan berdasarkan
                tanggal, cabang, karyawan, jenis presensi, dan
                ketepatan waktu.
            </p>
        </div>

        @if ($filterIsActive)
            <div class="mt-3 mt-md-0">
                <span class="badge text-bg-info">
                    Filter aktif
                </span>
            </div>
        @endif
    </header>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Total Transaksi
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['total'] }}
                </div>

                <div class="small text-secondary">
                    presensi
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Karyawan
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['employees'] }}
                </div>

                <div class="small text-secondary">
                    karyawan tercatat
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Presensi Masuk
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['check_in'] }}
                </div>

                <div class="small text-secondary">
                    transaksi
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Presensi Pulang
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['check_out'] }}
                </div>

                <div class="small text-secondary">
                    transaksi
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Tepat Waktu
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['on_time'] }}
                </div>

                <div class="small text-secondary">
                    presensi masuk
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Terlambat
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['late'] }}
                </div>

                <div class="small text-secondary">
                    presensi masuk
                </div>
            </section>
        </div>
    </div>

    <section class="content-card p-3 p-md-4 mb-4">
        <div class="mb-3">
            <h2 class="h5 fw-bold mb-1">
                Filter Monitoring
            </h2>

            <p class="small text-secondary mb-0">
                Kombinasikan beberapa filter untuk mempersempit
                data transaksi presensi.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('attendance-monitoring.index') }}"
        >
            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label
                        for="attendance_date"
                        class="form-label"
                    >
                        Tanggal Presensi
                    </label>

                    <input
                        type="date"
                        id="attendance_date"
                        name="attendance_date"
                        value="{{ $selectedAttendanceDate ?? '' }}"
                        class="form-control"
                    >
                </div>

                <div class="col-md-6 col-xl-3">
                    <label
                        for="branch_id"
                        class="form-label"
                    >
                        Cabang
                    </label>

                    <select
                        id="branch_id"
                        name="branch_id"
                        class="form-select"
                    >
                        <option value="">
                            Semua cabang
                        </option>

                        @foreach ($branches as $branchOption)
                            <option
                                value="{{ $branchOption->id }}"
                                @selected(
                                    $selectedBranchId !== null
                                    && (int) $selectedBranchId
                                        === (int) $branchOption->id
                                )
                            >
                                {{ $branchOption->code }}
                                —
                                {{ $branchOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label
                        for="employee_id"
                        class="form-label"
                    >
                        Karyawan
                    </label>

                    <select
                        id="employee_id"
                        name="employee_id"
                        class="form-select"
                    >
                        <option value="">
                            Semua karyawan
                        </option>

                        @foreach ($employees as $employeeOption)
                            <option
                                value="{{ $employeeOption->id }}"
                                @selected(
                                    $selectedEmployeeId !== null
                                    && (int) $selectedEmployeeId
                                        === (int) $employeeOption->id
                                )
                            >
                                {{ $employeeOption->employee_number }}
                                —
                                {{ $employeeOption->full_name }}

                                @if ($employeeOption->branch !== null)
                                    —
                                    {{ $employeeOption->branch->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label
                        for="attendance_type"
                        class="form-label"
                    >
                        Jenis Presensi
                    </label>

                    <select
                        id="attendance_type"
                        name="attendance_type"
                        class="form-select"
                    >
                        <option value="">
                            Semua jenis
                        </option>

                        <option
                            value="check_in"
                            @selected(
                                $selectedAttendanceType
                                === 'check_in'
                            )
                        >
                            Presensi Masuk
                        </option>

                        <option
                            value="check_out"
                            @selected(
                                $selectedAttendanceType
                                === 'check_out'
                            )
                        >
                            Presensi Pulang
                        </option>
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label
                        for="punctuality_status"
                        class="form-label"
                    >
                        Ketepatan Waktu
                    </label>

                    <select
                        id="punctuality_status"
                        name="punctuality_status"
                        class="form-select"
                    >
                        <option value="">
                            Semua status
                        </option>

                        <option
                            value="on_time"
                            @selected(
                                $selectedPunctualityStatus
                                === 'on_time'
                            )
                        >
                            Tepat Waktu
                        </option>

                        <option
                            value="late"
                            @selected(
                                $selectedPunctualityStatus
                                === 'late'
                            )
                        >
                            Terlambat
                        </option>

                        <option
                            value="not_applicable"
                            @selected(
                                $selectedPunctualityStatus
                                === 'not_applicable'
                            )
                        >
                            Tidak Berlaku
                        </option>
                    </select>
                </div>

                <div
                    class="col-md-6 col-xl-3
                        d-flex align-items-end"
                >
                    <div class="d-flex gap-2 w-100">
                        <button
                            type="submit"
                            class="btn btn-primary flex-grow-1"
                        >
                            Terapkan
                        </button>

                        <a
                            href="{{
                                route(
                                    'attendance-monitoring.index'
                                )
                            }}"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <section class="content-card">
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                gap-2 border-bottom p-3 p-md-4"
        >
            <div>
                <h2 class="h5 fw-bold mb-1">
                    Daftar Transaksi Presensi
                </h2>

                <p class="small text-secondary mb-0">
                    Ditemukan
                    {{ $attendances->total() }}
                    transaksi berdasarkan filter yang dipilih.
                </p>
            </div>

            @if ($filterIsActive)
                <a
                    href="{{
                        route(
                            'attendance-monitoring.index'
                        )
                    }}"
                    class="btn btn-sm btn-outline-secondary
                        align-self-start"
                >
                    Hapus Filter
                </a>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th
                            scope="col"
                            class="text-center"
                            style="width: 65px;"
                        >
                            No.
                        </th>

                        <th scope="col">
                            Karyawan
                        </th>

                        <th scope="col">
                            Cabang
                        </th>

                        <th scope="col">
                            Tanggal dan Waktu
                        </th>

                        <th scope="col">
                            Jenis
                        </th>

                        <th scope="col">
                            Ketepatan
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                        >
                            Jarak
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                        >
                            Accuracy
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Validasi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($attendances as $attendance)
                        @php
                            $attendanceType = strtolower(
                                (string) $attendance
                                    ->attendance_type
                            );

                            $punctualityStatus = strtolower(
                                (string) $attendance
                                    ->punctuality_status
                            );

                            $validationStatus = strtolower(
                                (string) $attendance
                                    ->validation_status
                            );

                            $attendanceTypeLabel =
                                $attendanceTypeLabels[
                                    $attendanceType
                                ]
                                ?? ucfirst($attendanceType);

                            $attendanceTypeClass =
                                $attendanceTypeClasses[
                                    $attendanceType
                                ]
                                ?? 'text-bg-secondary';

                            $punctualityLabel =
                                $punctualityLabels[
                                    $punctualityStatus
                                ]
                                ?? ucfirst($punctualityStatus);

                            $punctualityClass =
                                $punctualityClasses[
                                    $punctualityStatus
                                ]
                                ?? 'text-bg-secondary';

                            $validationStatusLabel =
                                $validationStatusLabels[
                                    $validationStatus
                                ]
                                ?? ucfirst($validationStatus);

                            $validationStatusClass =
                                $validationStatusClasses[
                                    $validationStatus
                                ]
                                ?? 'text-bg-secondary';
                        @endphp

                        <tr>
                            <td class="text-center text-secondary">
                                {{
                                    ($attendances->firstItem() ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                @if ($attendance->employee !== null)
                                    <div class="fw-semibold">
                                        {{
                                            $attendance
                                                ->employee
                                                ->full_name
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $attendance
                                                ->employee
                                                ->employee_number
                                        }}
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        Data karyawan tidak tersedia
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($attendance->branch !== null)
                                    <div class="fw-semibold">
                                        {{
                                            $attendance
                                                ->branch
                                                ->name
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $attendance
                                                ->branch
                                                ->code
                                        }}
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        -
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $formatDate(
                                            $attendance
                                                ->attendance_date
                                        )
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{
                                        $formatTime(
                                            $attendance
                                                ->attendance_time
                                        )
                                    }}
                                    WIB
                                </div>
                            </td>

                            <td>
                                <span
                                    class="badge {{
                                        $attendanceTypeClass
                                    }}"
                                >
                                    {{ $attendanceTypeLabel }}
                                </span>
                            </td>

                            <td>
                                <span
                                    class="badge {{
                                        $punctualityClass
                                    }}"
                                >
                                    {{ $punctualityLabel }}
                                </span>
                            </td>

                            <td class="text-end">
                                {{
                                    $formatDecimal(
                                        $attendance->distance
                                    )
                                }}
                                meter
                            </td>

                            <td class="text-end">
                                {{
                                    $formatDecimal(
                                        $attendance->accuracy
                                    )
                                }}
                                meter
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge {{
                                        $validationStatusClass
                                    }}"
                                >
                                    {{ $validationStatusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="9"
                                class="text-center py-5"
                            >
                                <div class="fw-semibold mb-1">
                                    Data presensi belum tersedia
                                </div>

                                <div class="small text-secondary">
                                    Belum terdapat transaksi atau
                                    data tidak sesuai dengan filter.
                                </div>

                                @if ($filterIsActive)
                                    <a
                                        href="{{
                                            route(
                                                'attendance-monitoring.index'
                                            )
                                        }}"
                                        class="btn btn-sm
                                            btn-outline-secondary mt-3"
                                    >
                                        Hapus Filter
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendances->hasPages())
            @php
                $startPage = max(
                    1,
                    $attendances->currentPage() - 2
                );

                $endPage = min(
                    $attendances->lastPage(),
                    $attendances->currentPage() + 2
                );
            @endphp

            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between align-items-md-center
                    gap-3 border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $attendances->firstItem() }}
                    sampai
                    {{ $attendances->lastItem() }}
                    dari
                    {{ $attendances->total() }}
                    transaksi.
                </div>

                <nav aria-label="Navigasi monitoring presensi">
                    <ul class="pagination pagination-sm mb-0">
                        <li
                            class="page-item {{
                                $attendances->onFirstPage()
                                    ? 'disabled'
                                    : ''
                            }}"
                        >
                            <a
                                href="{{
                                    $attendances
                                        ->previousPageUrl()
                                    ?? '#'
                                }}"
                                class="page-link"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        @foreach (
                            $attendances->getUrlRange(
                                $startPage,
                                $endPage
                            ) as $page => $url
                        )
                            <li
                                class="page-item {{
                                    $page
                                    === $attendances
                                        ->currentPage()
                                        ? 'active'
                                        : ''
                                }}"
                            >
                                <a
                                    href="{{ $url }}"
                                    class="page-link"
                                >
                                    {{ $page }}
                                </a>
                            </li>
                        @endforeach

                        <li
                            class="page-item {{
                                $attendances->hasMorePages()
                                    ? ''
                                    : 'disabled'
                            }}"
                        >
                            <a
                                href="{{
                                    $attendances
                                        ->nextPageUrl()
                                    ?? '#'
                                }}"
                                class="page-link"
                            >
                                Berikutnya
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
    </section>
@endsection