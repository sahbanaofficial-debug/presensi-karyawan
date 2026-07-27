@extends('layouts.app')

@section('title', 'Log Validasi Presensi')

@push('styles')
    <style>
        .validation-log-page {
            --validation-surface: var(--neutral-0);
            --validation-border: var(--neutral-200);
            --validation-muted: var(--neutral-600);
            --validation-orange-soft: var(--brand-50);
        }

        .validation-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .validation-summary-card {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--validation-border);
            border-radius: var(--radius-lg);
            background: var(--validation-surface);
            box-shadow: var(--shadow-xs);
        }

        .validation-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--validation-orange-soft);
            font-size: 1rem;
        }

        .validation-summary-label {
            color: var(--validation-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .validation-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .validation-summary-copy {
            margin-top: var(--space-1);
            color: var(--validation-muted);
            font-size: 0.6875rem;
            line-height: 1.5;
        }

        .validation-filter-card,
        .validation-list-card {
            border: 1px solid var(--validation-border);
            border-radius: var(--radius-lg);
            background: var(--validation-surface);
            box-shadow: var(--shadow-xs);
        }

        .validation-filter-card {
            padding: var(--space-4);
            margin-bottom: var(--space-5);
        }

        .validation-filter-heading {
            margin-bottom: var(--space-4);
        }

        .validation-filter-title,
        .validation-list-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .validation-filter-copy,
        .validation-list-copy {
            margin: var(--space-1) 0 0;
            color: var(--validation-muted);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .validation-list-card {
            overflow: hidden;
        }

        .validation-list-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--validation-border);
            background: var(--neutral-25);
        }

        .validation-date {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .validation-timezone {
            display: block;
            margin-top: var(--space-1);
            color: var(--validation-muted);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .validation-person-name {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .validation-person-number {
            display: block;
            margin-top: var(--space-1);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .validation-person-position {
            display: block;
            margin-top: var(--space-1);
            color: var(--validation-muted);
            font-size: 0.6875rem;
            line-height: 1.45;
        }

        .validation-branch-code {
            display: block;
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.035em;
            text-transform: uppercase;
        }

        .validation-branch-name {
            display: block;
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.45;
        }

        .validation-session-chip {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            max-width: 100%;
            margin-top: var(--space-2);
            padding: 0.375rem 0.625rem;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-pill);
            color: var(--neutral-700);
            background: var(--neutral-50);
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;
            font-size: 0.625rem;
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .validation-session-meta {
            display: block;
            margin-top: var(--space-2);
            color: var(--validation-muted);
            font-size: 0.6875rem;
            line-height: 1.45;
        }

        .validation-type-name {
            display: block;
            margin-top: var(--space-2);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .validation-type-code {
            display: block;
            margin-top: var(--space-1);
            color: var(--validation-muted);
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;
            font-size: 0.625rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .validation-location-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-2);
            min-width: 13rem;
        }

        .validation-location-item {
            min-width: 0;
        }

        .validation-location-label {
            color: var(--validation-muted);
            font-size: 0.5625rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .validation-location-value {
            display: block;
            margin-top: 0.125rem;
            color: var(--neutral-900);
            font-size: 0.6875rem;
            font-weight: 700;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .validation-reason {
            color: var(--neutral-800);
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.55;
        }

        .validation-reference {
            display: block;
            margin-top: var(--space-2);
            color: var(--validation-muted);
            font-size: 0.625rem;
            line-height: 1.45;
        }

        .validation-reference code {
            color: var(--brand-700);
            font-size: inherit;
            overflow-wrap: anywhere;
        }

        .validation-mobile-list {
            display: none;
        }

        .validation-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--validation-border);
        }

        .validation-mobile-card:last-child {
            border-bottom: 0;
        }

        .validation-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .validation-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .validation-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--validation-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .validation-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .validation-mobile-metric {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .validation-mobile-metric-label {
            color: var(--validation-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .validation-mobile-metric-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .validation-empty-state {
            padding: var(--space-8) var(--space-5);
            text-align: center;
        }

        .validation-empty-icon {
            display: inline-flex;
            width: 3.75rem;
            height: 3.75rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--validation-orange-soft);
            font-size: 1.5rem;
        }

        .validation-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .validation-empty-copy {
            max-width: 32rem;
            margin: var(--space-2) auto var(--space-4);
            color: var(--validation-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .validation-pagination {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border-top: 1px solid var(--validation-border);
        }

        @media (min-width: 768px) {
            .validation-filter-card {
                padding: var(--space-5);
            }

            .validation-pagination {
                flex-direction: row;
                align-items: center;
                padding: var(--space-4) var(--space-5);
            }
        }

        @media (max-width: 1399.98px) {
            .validation-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 1199.98px) {
            .validation-desktop-table {
                display: none;
            }

            .validation-mobile-list {
                display: block;
            }
        }

        @media (max-width: 767.98px) {
            .validation-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .validation-summary-grid {
                grid-template-columns: 1fr;
            }

            .validation-list-header {
                padding: var(--space-4);
            }

            .validation-mobile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

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
                    'icon' => 'bi-geo-alt',
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
                    'class' => 'text-bg-primary',
                    'icon' => 'bi-qr-code-scan',
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
                    'icon' => 'bi-calendar2-check',
                ];
            }

            if ($type === 'attendance_accepted') {
                return [
                    'label' => 'Transaksi',
                    'class' => 'text-bg-success',
                    'icon' => 'bi-check-circle',
                ];
            }

            return [
                'label' => 'Lainnya',
                'class' => 'text-bg-secondary',
                'icon' => 'bi-info-circle',
            ];
        };

        $filterIsActive =
            $selectedValidationDate !== null
            || $selectedBranchId !== null
            || $selectedEmployeeId !== null
            || $selectedStatus !== ''
            || $selectedValidationType !== '';
    @endphp

    <div class="validation-log-page">
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
                    Periksa hasil validasi transaksi, penolakan
                    QR Code, ketidaksesuaian lokasi, dan
                    pelanggaran jadwal.
                </p>
            </div>

            @if ($filterIsActive)
                <div class="mt-3 mt-md-0">
                    <span class="badge text-bg-warning">
                        Filter aktif
                    </span>
                </div>
            @endif
        </header>

        <section
            class="validation-summary-grid"
            aria-label="Ringkasan log validasi presensi"
        >
            <article class="validation-summary-card">
                <span class="validation-summary-icon">
                    <i
                        class="bi bi-journal-text"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="validation-summary-label">
                    Total Log
                </div>

                <div class="validation-summary-value">
                    {{ (int) $summary['total'] }}
                </div>

                <div class="validation-summary-copy">
                    Seluruh catatan validasi.
                </div>
            </article>

            <article class="validation-summary-card">
                <span class="validation-summary-icon">
                    <i
                        class="bi bi-check-circle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="validation-summary-label">
                    Diterima
                </div>

                <div class="validation-summary-value">
                    {{ (int) $summary['accepted'] }}
                </div>

                <div class="validation-summary-copy">
                    Transaksi berhasil diterima.
                </div>
            </article>

            <article class="validation-summary-card">
                <span class="validation-summary-icon">
                    <i
                        class="bi bi-x-circle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="validation-summary-label">
                    Ditolak
                </div>

                <div class="validation-summary-value">
                    {{ (int) $summary['rejected'] }}
                </div>

                <div class="validation-summary-copy">
                    Percobaan yang ditolak.
                </div>
            </article>

            <article class="validation-summary-card">
                <span class="validation-summary-icon">
                    <i
                        class="bi bi-geo-alt"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="validation-summary-label">
                    Validasi Lokasi
                </div>

                <div class="validation-summary-value">
                    {{ (int) $summary['location'] }}
                </div>

                <div class="validation-summary-copy">
                    Catatan geofence dan GPS.
                </div>
            </article>

            <article class="validation-summary-card">
                <span class="validation-summary-icon">
                    <i
                        class="bi bi-qr-code-scan"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="validation-summary-label">
                    QR dan TOTP
                </div>

                <div class="validation-summary-value">
                    {{ (int) $summary['qr'] }}
                </div>

                <div class="validation-summary-copy">
                    Catatan sesi dan token.
                </div>
            </article>

            <article class="validation-summary-card">
                <span class="validation-summary-icon">
                    <i
                        class="bi bi-calendar2-check"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="validation-summary-label">
                    Validasi Jadwal
                </div>

                <div class="validation-summary-value">
                    {{ (int) $summary['schedule'] }}
                </div>

                <div class="validation-summary-copy">
                    Catatan aturan jadwal.
                </div>
            </article>
        </section>

        <section
            class="validation-filter-card"
            aria-labelledby="validation-filter-heading"
        >
            <div class="validation-filter-heading">
                <h2
                    id="validation-filter-heading"
                    class="validation-filter-title"
                >
                    Filter Log Validasi
                </h2>

                <p class="validation-filter-copy">
                    Gunakan filter untuk menelusuri transaksi
                    atau percobaan presensi tertentu.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route(
                    'attendance-validation-logs.index'
                ) }}"
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

                            @foreach (
                                $branches as $branchOption
                            )
                                <option
                                    value="{{ $branchOption->id }}"
                                    @selected(
                                        $selectedBranchId !== null
                                        && (int) $selectedBranchId
                                            === (int)
                                                $branchOption->id
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

                            @foreach (
                                $employees as $employeeOption
                            )
                                <option
                                    value="{{ $employeeOption->id }}"
                                    @selected(
                                        $selectedEmployeeId !== null
                                        && (int)
                                            $selectedEmployeeId
                                            === (int)
                                                $employeeOption->id
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

                                    @if (
                                        $employeeOption->branch
                                        !== null
                                    )
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
                        <div class="d-grid d-sm-flex gap-2 w-100">
                            <button
                                type="submit"
                                class="btn btn-primary flex-fill"
                            >
                                <i
                                    class="bi bi-funnel me-2"
                                    aria-hidden="true"
                                ></i>

                                Terapkan
                            </button>

                            <a
                                href="{{ route(
                                    'attendance-validation-logs.index'
                                ) }}"
                                class="btn
                                    btn-outline-secondary
                                    flex-fill"
                            >
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section
            class="validation-list-card"
            aria-labelledby="validation-list-heading"
        >
            <div class="validation-list-header">
                <div>
                    <h2
                        id="validation-list-heading"
                        class="validation-list-title"
                    >
                        Daftar Log Validasi
                    </h2>

                    <p class="validation-list-copy">
                        Ditemukan {{ $logs->total() }}
                        catatan berdasarkan filter yang dipilih.
                    </p>
                </div>

                @if ($filterIsActive)
                    <a
                        href="{{ route(
                            'attendance-validation-logs.index'
                        ) }}"
                        class="btn btn-sm
                            btn-outline-secondary"
                    >
                        Hapus Filter
                    </a>
                @else
                    <span class="badge text-bg-secondary">
                        Halaman {{ $logs->currentPage() }}
                    </span>
                @endif
            </div>

            @if ($logs->isEmpty())
                <div class="validation-empty-state">
                    <span class="validation-empty-icon">
                        <i
                            class="bi bi-shield-x"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="validation-empty-title">
                        Log validasi belum tersedia
                    </h3>

                    <p class="validation-empty-copy">
                        Belum ada transaksi atau data tidak sesuai
                        dengan tanggal, cabang, karyawan, status,
                        dan jenis validasi yang dipilih.
                    </p>

                    @if ($filterIsActive)
                        <a
                            href="{{ route(
                                'attendance-validation-logs.index'
                            ) }}"
                            class="btn btn-outline-primary"
                        >
                            Hapus Filter
                        </a>
                    @endif
                </div>
            @else
                <div
                    class="validation-desktop-table
                        table-responsive"
                >
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th
                                    scope="col"
                                    class="text-center"
                                    style="width: 4rem;"
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
                            @foreach ($logs as $log)
                                @php
                                    $currentStatus =
                                        strtolower(
                                            trim(
                                                (string)
                                                    $log->status
                                            )
                                        );

                                    $currentValidationType =
                                        strtolower(
                                            trim(
                                                (string)
                                                    $log
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
                                                    $log
                                                        ->attendance_type
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
                                                (string)
                                                    $log
                                                        ->session_public_id,
                                                12,
                                                '...'
                                            )
                                            : '-';

                                    $payloadReference =
                                        $log->payload_reference
                                            ? \Illuminate\Support\Str::limit(
                                                (string)
                                                    $log
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
                                            ($logs->firstItem()
                                                ?? 0)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        <span class="validation-date">
                                            {{
                                                $formatDateTime(
                                                    $log->created_at
                                                )
                                            }}
                                        </span>

                                        <span
                                            class="validation-timezone"
                                        >
                                            WIB
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="validation-person-name"
                                        >
                                            {{
                                                $employeeDisplayName
                                            }}
                                        </span>

                                        <span
                                            class="validation-person-number"
                                        >
                                            {{
                                                $employeeDisplayNumber
                                            }}
                                        </span>

                                        @if ($log->position)
                                            <span
                                                class="validation-person-position"
                                            >
                                                {{ $log->position }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span
                                            class="validation-branch-code"
                                        >
                                            {{
                                                $log->branch_code
                                                ?: '-'
                                            }}
                                        </span>

                                        <span
                                            class="validation-branch-name"
                                        >
                                            {{
                                                $log->branch_name
                                                ?: 'Cabang tidak diketahui'
                                            }}
                                        </span>

                                        <span
                                            class="validation-session-chip"
                                            title="{{
                                                $log
                                                    ->session_public_id
                                                ?: ''
                                            }}"
                                        >
                                            <i
                                                class="bi bi-qr-code"
                                                aria-hidden="true"
                                            ></i>

                                            {{ $sessionShortId }}
                                        </span>

                                        @if ($log->session_date)
                                            <span
                                                class="validation-session-meta"
                                            >
                                                {{
                                                    $formatDate(
                                                        $log
                                                            ->session_date
                                                    )
                                                }}
                                                |
                                                {{
                                                    $attendanceTypeLabel
                                                }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                {{ $category['class'] }}"
                                        >
                                            <i
                                                class="bi
                                                    {{ $category['icon'] }}
                                                    me-1"
                                                aria-hidden="true"
                                            ></i>

                                            {{ $category['label'] }}
                                        </span>

                                        <span
                                            class="validation-type-name"
                                        >
                                            {{
                                                $validationTypeLabel(
                                                    $currentValidationType
                                                )
                                            }}
                                        </span>

                                        <span
                                            class="validation-type-code"
                                        >
                                            {{
                                                $currentValidationType
                                            }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span
                                            class="badge
                                                {{ $statusClass }}"
                                        >
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <div
                                            class="validation-location-grid"
                                        >
                                            <div
                                                class="validation-location-item"
                                            >
                                                <span
                                                    class="validation-location-label"
                                                >
                                                    Latitude
                                                </span>

                                                <span
                                                    class="validation-location-value"
                                                >
                                                    {{
                                                        $formatDecimal(
                                                            $log
                                                                ->latitude,
                                                            6
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                class="validation-location-item"
                                            >
                                                <span
                                                    class="validation-location-label"
                                                >
                                                    Longitude
                                                </span>

                                                <span
                                                    class="validation-location-value"
                                                >
                                                    {{
                                                        $formatDecimal(
                                                            $log
                                                                ->longitude,
                                                            6
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                class="validation-location-item"
                                            >
                                                <span
                                                    class="validation-location-label"
                                                >
                                                    Accuracy
                                                </span>

                                                <span
                                                    class="validation-location-value"
                                                >
                                                    {{
                                                        $formatDecimal(
                                                            $log
                                                                ->accuracy
                                                        )
                                                    }}
                                                    meter
                                                </span>
                                            </div>

                                            <div
                                                class="validation-location-item"
                                            >
                                                <span
                                                    class="validation-location-label"
                                                >
                                                    Jarak
                                                </span>

                                                <span
                                                    class="validation-location-value"
                                                >
                                                    {{
                                                        $formatDecimal(
                                                            $log
                                                                ->distance
                                                        )
                                                    }}
                                                    meter
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="validation-reason">
                                            {{
                                                $log->reason
                                                ?: 'Tidak ada keterangan'
                                            }}
                                        </div>

                                        <span
                                            class="validation-reference"
                                        >
                                            Referensi payload:
                                            <code
                                                title="{{
                                                    $log
                                                        ->payload_reference
                                                    ?: ''
                                                }}"
                                            >
                                                {{
                                                    $payloadReference
                                                }}
                                            </code>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="validation-mobile-list">
                    @foreach ($logs as $log)
                        @php
                            $currentStatus =
                                strtolower(
                                    trim(
                                        (string) $log->status
                                    )
                                );

                            $currentValidationType =
                                strtolower(
                                    trim(
                                        (string)
                                            $log->validation_type
                                    )
                                );

                            $statusLabel =
                                $statusLabels[$currentStatus]
                                ?? \Illuminate\Support\Str::headline(
                                    $currentStatus
                                );

                            $statusClass =
                                $statusClasses[$currentStatus]
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
                                        (string)
                                            $log
                                                ->session_public_id,
                                        18,
                                        '...'
                                    )
                                    : '-';

                            $payloadReference =
                                $log->payload_reference
                                    ? \Illuminate\Support\Str::limit(
                                        (string)
                                            $log
                                                ->payload_reference,
                                        32,
                                        '...'
                                    )
                                    : '-';
                        @endphp

                        <article class="validation-mobile-card">
                            <div class="validation-mobile-top">
                                <div>
                                    <span
                                        class="badge
                                            {{ $category['class'] }}"
                                    >
                                        <i
                                            class="bi
                                                {{ $category['icon'] }}
                                                me-1"
                                            aria-hidden="true"
                                        ></i>

                                        {{ $category['label'] }}
                                    </span>

                                    <h3
                                        class="validation-type-name
                                            mb-0"
                                    >
                                        {{
                                            $validationTypeLabel(
                                                $currentValidationType
                                            )
                                        }}
                                    </h3>

                                    <span
                                        class="validation-type-code"
                                    >
                                        {{ $currentValidationType }}
                                    </span>
                                </div>

                                <span
                                    class="badge
                                        {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="validation-mobile-section">
                                <div class="validation-mobile-grid">
                                    <div class="validation-mobile-metric">
                                        <div
                                            class="validation-mobile-metric-label"
                                        >
                                            Waktu
                                        </div>

                                        <div
                                            class="validation-mobile-metric-value"
                                        >
                                            {{
                                                $formatDateTime(
                                                    $log
                                                        ->created_at
                                                )
                                            }}
                                            WIB
                                        </div>
                                    </div>

                                    <div class="validation-mobile-metric">
                                        <div
                                            class="validation-mobile-metric-label"
                                        >
                                            Karyawan
                                        </div>

                                        <div
                                            class="validation-mobile-metric-value"
                                        >
                                            {{
                                                $employeeDisplayName
                                            }}
                                            <br>
                                            <span
                                                class="text-secondary"
                                            >
                                                {{
                                                    $employeeDisplayNumber
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="validation-mobile-metric">
                                        <div
                                            class="validation-mobile-metric-label"
                                        >
                                            Cabang
                                        </div>

                                        <div
                                            class="validation-mobile-metric-value"
                                        >
                                            {{
                                                $log->branch_code
                                                ?: '-'
                                            }}
                                            |
                                            {{
                                                $log->branch_name
                                                ?: 'Cabang tidak diketahui'
                                            }}
                                        </div>
                                    </div>

                                    <div class="validation-mobile-metric">
                                        <div
                                            class="validation-mobile-metric-label"
                                        >
                                            Sesi
                                        </div>

                                        <div
                                            class="validation-mobile-metric-value"
                                        >
                                            {{ $sessionShortId }}

                                            @if ($log->session_date)
                                                <br>
                                                <span
                                                    class="text-secondary"
                                                >
                                                    {{
                                                        $formatDate(
                                                            $log
                                                                ->session_date
                                                        )
                                                    }}
                                                    |
                                                    {{
                                                        $attendanceTypeLabel
                                                    }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="validation-mobile-section">
                                <div class="validation-mobile-label">
                                    Data Lokasi
                                </div>

                                <div class="validation-mobile-grid">
                                    <div class="validation-mobile-metric">
                                        <div
                                            class="validation-mobile-metric-label"
                                        >
                                            Latitude
                                        </div>

                                        <div
                                            class="validation-mobile-metric-value"
                                        >
                                            {{
                                                $formatDecimal(
                                                    $log->latitude,
                                                    6
                                                )
                                            }}
                                        </div>
                                    </div>

                                    <div class="validation-mobile-metric">
                                        <div
                                            class="validation-mobile-metric-label"
                                        >
                                            Longitude
                                        </div>

                                        <div
                                            class="validation-mobile-metric-value"
                                        >
                                            {{
                                                $formatDecimal(
                                                    $log->longitude,
                                                    6
                                                )
                                            }}
                                        </div>
                                    </div>

                                    <div class="validation-mobile-metric">
                                        <div
                                            class="validation-mobile-metric-label"
                                        >
                                            Accuracy
                                        </div>

                                        <div
                                            class="validation-mobile-metric-value"
                                        >
                                            {{
                                                $formatDecimal(
                                                    $log->accuracy
                                                )
                                            }}
                                            meter
                                        </div>
                                    </div>

                                    <div class="validation-mobile-metric">
                                        <div
                                            class="validation-mobile-metric-label"
                                        >
                                            Jarak
                                        </div>

                                        <div
                                            class="validation-mobile-metric-value"
                                        >
                                            {{
                                                $formatDecimal(
                                                    $log->distance
                                                )
                                            }}
                                            meter
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="validation-mobile-section">
                                <div class="validation-mobile-label">
                                    Alasan dan Referensi
                                </div>

                                <div class="validation-reason">
                                    {{
                                        $log->reason
                                        ?: 'Tidak ada keterangan'
                                    }}
                                </div>

                                <span
                                    class="validation-reference"
                                >
                                    Referensi payload:
                                    <code
                                        title="{{
                                            $log
                                                ->payload_reference
                                            ?: ''
                                        }}"
                                    >
                                        {{ $payloadReference }}
                                    </code>
                                </span>
                            </div>
                        </article>
                    @endforeach
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

                    <div class="validation-pagination">
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
            @endif
        </section>
    </div>
@endsection
