@extends('layouts.app')

@section('title', 'Log Validasi Presensi')

@section('content')
    @php
        $validationTypeLabels = [
            'attendance_accepted' =>
                'Presensi Diterima',

            'session_not_found' =>
                'Sesi Tidak Ditemukan',

            'session_inactive' =>
                'Sesi Tidak Aktif',

            'session_not_started' =>
                'Sesi Belum Dimulai',

            'session_expired' =>
                'Sesi Sudah Berakhir',

            'session_date_mismatch' =>
                'Tanggal Sesi Tidak Sesuai',

            'totp_invalid' =>
                'Token TOTP Tidak Valid',

            'employee_not_found' =>
                'Data Karyawan Tidak Ditemukan',

            'branch_mismatch' =>
                'Cabang Tidak Sesuai',

            'branch_inactive' =>
                'Cabang Tidak Aktif',

            'branch_geofence_not_configured' =>
                'Geofence Belum Dikonfigurasi',

            'employee_schedule_not_found' =>
                'Jadwal Karyawan Tidak Ditemukan',

            'schedule_not_working' =>
                'Status Jadwal Bukan Hari Kerja',

            'check_in_too_early' =>
                'Presensi Masuk Terlalu Awal',

            'check_in_too_late' =>
                'Presensi Masuk Melewati Batas',

            'check_out_too_early' =>
                'Presensi Pulang Terlalu Awal',

            'check_out_too_late' =>
                'Presensi Pulang Melewati Batas',

            'location_accuracy_too_low' =>
                'Accuracy Lokasi Tidak Memenuhi Syarat',

            'outside_geofence' =>
                'Lokasi di Luar Geofence',

            'duplicate_attendance' =>
                'Presensi Ganda',
        ];

        $statusLabels = [
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
        ];

        $statusClasses = [
            'accepted' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ];

        $attendanceTypeLabels = [
            'check_in' => 'Presensi Masuk',
            'check_out' => 'Presensi Pulang',
        ];

        $locationValidationTypes = [
            'location_accuracy_too_low',
            'outside_geofence',
            'branch_mismatch',
        ];

        $qrValidationTypes = [
            'session_not_found',
            'session_inactive',
            'session_not_started',
            'session_expired',
            'session_date_mismatch',
            'totp_invalid',
        ];

        $scheduleValidationTypes = [
            'employee_schedule_not_found',
            'schedule_not_working',
            'check_in_too_early',
            'check_in_too_late',
            'check_out_too_early',
            'check_out_too_late',
            'duplicate_attendance',
        ];

        $formatDateTime = static function (
            mixed $value
        ): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat(
                        'd F Y, H:i:s'
                    );
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDate = static function (
            mixed $value
        ): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y');
            } catch (\Throwable) {
                return substr(
                    (string) $value,
                    0,
                    10
                );
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

        $validationTypeLabel = static function (
            mixed $value
        ) use (
            $validationTypeLabels
        ): string {
            $type = strtolower(
                trim((string) $value)
            );

            return $validationTypeLabels[$type]
                ?? \Illuminate\Support\Str::headline(
                    $type
                );
        };

        $validationCategory = static function (
            mixed $value
        ) use (
            $locationValidationTypes,
            $qrValidationTypes,
            $scheduleValidationTypes
        ): array {
            $type = strtolower(
                trim((string) $value)
            );

            if (
                in_array(
                    $type,
                    $locationValidationTypes,
                    true
                )
            ) {
                return [
                    'label' => 'Lokasi',
                    'class' => 'text-bg-warning',
                ];
            }

            if (
                in_array(
                    $type,
                    $qrValidationTypes,
                    true
                )
            ) {
                return [
                    'label' => 'QR dan TOTP',
                    'class' => 'text-bg-info',
                ];
            }

            if (
                in_array(
                    $type,
                    $scheduleValidationTypes,
                    true
                )
            ) {
                return [
                    'label' => 'Jadwal',
                    'class' => 'text-bg-secondary',
                ];
            }

            if ($type === 'attendance_accepted') {
                return [
                    'label' => 'Transaksi',
                    'class' => 'text-bg-success',
                ];
            }

            return [
                'label' => 'Lainnya',
                'class' => 'text-bg-dark',
            ];
        };

        $filterIsActive =
            $selectedValidationDate !== null
            || $selectedBranchId !== null
            || $selectedEmployeeId !== null
            || $selectedStatus !== ''
            || $selectedValidationType !== '';
    @endphp

    <header
        class="page-header d-md-flex
            align-items-start justify-content-between
            gap-3"
    >
        <div>
            <h1 class="page-title">
                Log Validasi Presensi
            </h1>

            <p class="page-description">
                Periksa hasil validasi transaksi presensi,
                penolakan QR Code, ketidaksesuaian lokasi,
                dan pelanggaran jadwal.
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
                    Total Log
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['total'] }}
                </div>

                <div class="small text-secondary">
                    catatan
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Diterima
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['accepted'] }}
                </div>

                <div class="small text-secondary">
                    transaksi
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Ditolak
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['rejected'] }}
                </div>

                <div class="small text-secondary">
                    percobaan
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Validasi Lokasi
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['location'] }}
                </div>

                <div class="small text-secondary">
                    catatan
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    QR dan TOTP
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['qr'] }}
                </div>

                <div class="small text-secondary">
                    catatan
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-2">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Validasi Jadwal
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['schedule'] }}
                </div>

                <div class="small text-secondary">
                    catatan
                </div>
            </section>
        </div>
    </div>

    <section class="content-card p-3 p-md-4 mb-4">
        <div class="mb-3">
            <h2 class="h5 fw-bold mb-1">
                Filter Log Validasi
            </h2>

            <p class="small text-secondary mb-0">
                Gunakan filter untuk menelusuri transaksi
                atau percobaan presensi tertentu.
            </p>
        </div>

        <form
            method="GET"
            action="{{
                route(
                    'attendance-validation-logs.index'
                )
            }}"
        >
            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label
                        for="validation_date"
                        class="form-label"
                    >
                        Tanggal Validasi
                    </label>

                    <input
                        type="date"
                        id="validation_date"
                        name="validation_date"
                        value="{{
                            $selectedValidationDate ?? ''
                        }}"
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
                                |
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
                                {{
                                    $employeeOption
                                        ->employee_number
                                }}
                                |
                                {{
                                    $employeeOption
                                        ->full_name
                                }}

                                @if ($employeeOption->branch !== null)
                                    |
                                    {{
                                        $employeeOption
                                            ->branch
                                            ->name
                                    }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label
                        for="status"
                        class="form-label"
                    >
                        Status Validasi
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-select"
                    >
                        <option value="">
                            Semua status
                        </option>

                        <option
                            value="accepted"
                            @selected(
                                $selectedStatus
                                === 'accepted'
                            )
                        >
                            Diterima
                        </option>

                        <option
                            value="rejected"
                            @selected(
                                $selectedStatus
                                === 'rejected'
                            )
                        >
                            Ditolak
                        </option>
                    </select>
                </div>

                <div class="col-md-8 col-xl-6">
                    <label
                        for="validation_type"
                        class="form-label"
                    >
                        Jenis Validasi
                    </label>

                    <select
                        id="validation_type"
                        name="validation_type"
                        class="form-select"
                    >
                        <option value="">
                            Semua jenis validasi
                        </option>

                        @foreach (
                            $availableValidationTypes
                            as $validationTypeOption
                        )
                            <option
                                value="{{
                                    $validationTypeOption
                                }}"
                                @selected(
                                    $selectedValidationType
                                    === $validationTypeOption
                                )
                            >
                                {{
                                    $validationTypeLabel(
                                        $validationTypeOption
                                    )
                                }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="col-md-4 col-xl-3
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
                                    'attendance-validation-logs.index'
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
                justify-content-between
                align-items-md-center gap-2
                border-bottom p-3 p-md-4"
        >
            <div>
                <h2 class="h5 fw-bold mb-1">
                    Daftar Log Validasi
                </h2>

                <p class="small text-secondary mb-0">
                    Ditemukan
                    {{ $logs->total() }}
                    catatan berdasarkan filter yang dipilih.
                </p>
            </div>

            @if ($filterIsActive)
                <a
                    href="{{
                        route(
                            'attendance-validation-logs.index'
                        )
                    }}"
                    class="btn btn-sm
                        btn-outline-secondary
                        align-self-start"
                >
                    Hapus Filter
                </a>
            @endif
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover
                    align-middle mb-0"
            >
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
                            Waktu
                        </th>

                        <th scope="col">
                            Karyawan
                        </th>

                        <th scope="col">
                            Cabang dan Sesi
                        </th>

                        <th scope="col">
                            Jenis Validasi
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Status
                        </th>

                        <th scope="col">
                            Data Lokasi
                        </th>

                        <th scope="col">
                            Alasan dan Referensi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $currentStatus = strtolower(
                                trim(
                                    (string) $log->status
                                )
                            );

                            $currentValidationType =
                                strtolower(
                                    trim(
                                        (string) $log
                                            ->validation_type
                                    )
                                );

                            $statusLabel =
                                $statusLabels[
                                    $currentStatus
                                ]
                                ?? \Illuminate\Support\Str::headline(
                                    $currentStatus
                                );

                            $statusClass =
                                $statusClasses[
                                    $currentStatus
                                ]
                                ?? 'text-bg-secondary';

                            $category =
                                $validationCategory(
                                    $currentValidationType
                                );

                            $employeeDisplayName =
                                $log->employee_name
                                ?: $log->user_name
                                ?: 'Pengguna tidak ditemukan';

                            $employeeDisplayNumber =
                                $log->employee_number
                                ?: $log->user_email
                                ?: '-';

                            $attendanceType =
                                strtolower(
                                    trim(
                                        (string) (
                                            $log->attendance_type
                                            ?? ''
                                        )
                                    )
                                );

                            $attendanceTypeLabel =
                                $attendanceTypeLabels[
                                    $attendanceType
                                ]
                                ?? '-';

                            $sessionShortId =
                                $log->session_public_id
                                    ? \Illuminate\Support\Str::limit(
                                        (string) $log
                                            ->session_public_id,
                                        12,
                                        '...'
                                    )
                                    : '-';

                            $payloadReference =
                                $log->payload_reference
                                    ? \Illuminate\Support\Str::limit(
                                        (string) $log
                                            ->payload_reference,
                                        24,
                                        '...'
                                    )
                                    : '-';
                        @endphp

                        <tr>
                            <td
                                class="text-center
                                    text-secondary"
                            >
                                {{
                                    ($logs->firstItem() ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $formatDateTime(
                                            $log->created_at
                                        )
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    WIB
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $employeeDisplayName }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $employeeDisplayNumber }}
                                </div>

                                @if ($log->position)
                                    <div class="small text-secondary">
                                        {{ $log->position }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $log->branch_name
                                        ?: 'Cabang tidak diketahui'
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{
                                        $log->branch_code
                                        ?: '-'
                                    }}
                                </div>

                                <div class="small mt-2">
                                    <span
                                        class="badge
                                            text-bg-light
                                            border"
                                        title="{{
                                            $log->session_public_id
                                            ?: ''
                                        }}"
                                    >
                                        Sesi:
                                        {{ $sessionShortId }}
                                    </span>
                                </div>

                                @if ($log->session_date)
                                    <div
                                        class="small
                                            text-secondary mt-1"
                                    >
                                        {{
                                            $formatDate(
                                                $log->session_date
                                            )
                                        }}
                                        |
                                        {{ $attendanceTypeLabel }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="mb-2">
                                    <span
                                        class="badge {{
                                            $category['class']
                                        }}"
                                    >
                                        {{
                                            $category['label']
                                        }}
                                    </span>
                                </div>

                                <div class="fw-semibold">
                                    {{
                                        $validationTypeLabel(
                                            $currentValidationType
                                        )
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $currentValidationType }}
                                </div>
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge {{
                                        $statusClass
                                    }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td>
                                <div class="small">
                                    <span class="fw-semibold">
                                        Latitude:
                                    </span>

                                    {{
                                        $formatDecimal(
                                            $log->latitude,
                                            6
                                        )
                                    }}
                                </div>

                                <div class="small">
                                    <span class="fw-semibold">
                                        Longitude:
                                    </span>

                                    {{
                                        $formatDecimal(
                                            $log->longitude,
                                            6
                                        )
                                    }}
                                </div>

                                <div class="small mt-2">
                                    <span class="fw-semibold">
                                        Accuracy:
                                    </span>

                                    {{
                                        $formatDecimal(
                                            $log->accuracy
                                        )
                                    }}
                                    meter
                                </div>

                                <div class="small">
                                    <span class="fw-semibold">
                                        Jarak:
                                    </span>

                                    {{
                                        $formatDecimal(
                                            $log->distance
                                        )
                                    }}
                                    meter
                                </div>
                            </td>

                            <td>
                                <div class="small mb-2">
                                    <span class="fw-semibold">
                                        Alasan:
                                    </span>

                                    {{
                                        $log->reason
                                        ?: 'Tidak ada keterangan'
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    <span class="fw-semibold">
                                        Referensi payload:
                                    </span>

                                    <code
                                        title="{{
                                            $log->payload_reference
                                            ?: ''
                                        }}"
                                    >
                                        {{ $payloadReference }}
                                    </code>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="text-center py-5"
                            >
                                <div class="fw-semibold mb-1">
                                    Log validasi belum tersedia
                                </div>

                                <div class="small text-secondary">
                                    Belum ada transaksi atau data
                                    tidak sesuai dengan filter.
                                </div>

                                @if ($filterIsActive)
                                    <a
                                        href="{{
                                            route(
                                                'attendance-validation-logs.index'
                                            )
                                        }}"
                                        class="btn btn-sm
                                            btn-outline-secondary
                                            mt-3"
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

        @if ($logs->hasPages())
            @php
                $startPage = max(
                    1,
                    $logs->currentPage() - 2
                );

                $endPage = min(
                    $logs->lastPage(),
                    $logs->currentPage() + 2
                );
            @endphp

            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between
                    align-items-md-center gap-3
                    border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $logs->firstItem() }}
                    sampai
                    {{ $logs->lastItem() }}
                    dari
                    {{ $logs->total() }}
                    catatan.
                </div>

                <nav
                    aria-label="
                        Navigasi log validasi presensi
                    "
                >
                    <ul
                        class="pagination
                            pagination-sm mb-0"
                    >
                        <li
                            class="page-item {{
                                $logs->onFirstPage()
                                    ? 'disabled'
                                    : ''
                            }}"
                        >
                            <a
                                href="{{
                                    $logs
                                        ->previousPageUrl()
                                    ?? '#'
                                }}"
                                class="page-link"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        @foreach (
                            $logs->getUrlRange(
                                $startPage,
                                $endPage
                            ) as $page => $url
                        )
                            <li
                                class="page-item {{
                                    $page
                                    === $logs
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
                                $logs->hasMorePages()
                                    ? ''
                                    : 'disabled'
                            }}"
                        >
                            <a
                                href="{{
                                    $logs
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