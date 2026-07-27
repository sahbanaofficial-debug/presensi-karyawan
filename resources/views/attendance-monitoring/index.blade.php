@extends('layouts.app')

@section('title', 'Monitoring Presensi')

@push('styles')
    <style>
        .monitoring-page {
            --monitoring-surface: var(--neutral-0);
            --monitoring-border: var(--neutral-200);
            --monitoring-muted: var(--neutral-600);
            --monitoring-orange-soft: var(--brand-50);
        }

        .monitoring-header-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: var(--space-2);
        }

        .monitoring-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .monitoring-summary-card {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--monitoring-border);
            border-radius: var(--radius-lg);
            background: var(--monitoring-surface);
            box-shadow: var(--shadow-xs);
        }

        .monitoring-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--monitoring-orange-soft);
            font-size: 1rem;
        }

        .monitoring-summary-label {
            color: var(--monitoring-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .monitoring-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .monitoring-summary-copy {
            margin-top: var(--space-1);
            color: var(--monitoring-muted);
            font-size: 0.6875rem;
            line-height: 1.5;
        }

        .monitoring-filter-card,
        .monitoring-list-card {
            border: 1px solid var(--monitoring-border);
            border-radius: var(--radius-lg);
            background: var(--monitoring-surface);
            box-shadow: var(--shadow-xs);
        }

        .monitoring-filter-card {
            padding: var(--space-4);
            margin-bottom: var(--space-5);
        }

        .monitoring-filter-heading {
            margin-bottom: var(--space-4);
        }

        .monitoring-filter-title,
        .monitoring-list-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .monitoring-filter-copy,
        .monitoring-list-copy {
            margin: var(--space-1) 0 0;
            color: var(--monitoring-muted);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .monitoring-list-card {
            overflow: hidden;
        }

        .monitoring-list-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--monitoring-border);
            background: var(--neutral-25);
        }

        .monitoring-employee-name {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .monitoring-employee-number {
            display: block;
            margin-top: var(--space-1);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .monitoring-branch-code {
            display: block;
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.035em;
            text-transform: uppercase;
        }

        .monitoring-branch-name {
            display: block;
            margin-top: var(--space-1);
            color: var(--neutral-700);
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .monitoring-date {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .monitoring-time {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            margin-top: var(--space-1);
            color: var(--monitoring-muted);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .monitoring-time i {
            color: var(--brand-600);
        }

        .monitoring-type-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: 0.4375rem 0.6875rem;
            border: 1px solid transparent;
            border-radius: var(--radius-pill);
            font-size: 0.6875rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .monitoring-type-check-in {
            border-color: var(--brand-200);
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .monitoring-type-check-out {
            border-color: var(--neutral-200);
            color: var(--neutral-800);
            background: var(--neutral-50);
        }

        .monitoring-measurement {
            display: inline-flex;
            align-items: baseline;
            justify-content: flex-end;
            gap: 0.25rem;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .monitoring-measurement-unit {
            color: var(--monitoring-muted);
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .monitoring-action-group {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-2);
        }

        .monitoring-mobile-list {
            display: none;
        }

        .monitoring-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--monitoring-border);
        }

        .monitoring-mobile-card:last-child {
            border-bottom: 0;
        }

        .monitoring-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .monitoring-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .monitoring-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--monitoring-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .monitoring-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .monitoring-mobile-metric {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .monitoring-mobile-metric-label {
            color: var(--monitoring-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .monitoring-mobile-metric-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .monitoring-mobile-statuses {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
        }

        .monitoring-empty-state {
            padding: var(--space-8) var(--space-5);
            text-align: center;
        }

        .monitoring-empty-icon {
            display: inline-flex;
            width: 3.75rem;
            height: 3.75rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--monitoring-orange-soft);
            font-size: 1.5rem;
        }

        .monitoring-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .monitoring-empty-copy {
            max-width: 32rem;
            margin: var(--space-2) auto var(--space-4);
            color: var(--monitoring-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .monitoring-pagination {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border-top: 1px solid var(--monitoring-border);
        }

        @media (min-width: 768px) {
            .monitoring-filter-card {
                padding: var(--space-5);
            }

            .monitoring-pagination {
                flex-direction: row;
                align-items: center;
                padding: var(--space-4) var(--space-5);
            }
        }

        @media (max-width: 1399.98px) {
            .monitoring-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 1199.98px) {
            .monitoring-desktop-table {
                display: none;
            }

            .monitoring-mobile-list {
                display: block;
            }
        }

        @media (max-width: 767.98px) {
            .monitoring-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .monitoring-header-actions {
                width: 100%;
            }

            .monitoring-header-actions .btn {
                width: 100%;
            }

            .monitoring-summary-grid {
                grid-template-columns: 1fr;
            }

            .monitoring-list-header {
                padding: var(--space-4);
            }

            .monitoring-mobile-grid {
                grid-template-columns: 1fr;
            }

            .monitoring-action-group {
                width: 100%;
            }

            .monitoring-action-group .btn {
                flex: 1;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $canCorrectAttendance =
            auth()->user()?->role === 'hrd';

        $attendanceTypeLabels = [
            'check_in' => 'Presensi Masuk',
            'check_out' => 'Presensi Pulang',
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

        $formatTime = static function (
            mixed $value
        ): string {
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

    <div class="monitoring-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Monitoring Presensi
                </h1>

                <p class="page-description">
                    Pantau transaksi berdasarkan tanggal, cabang,
                    karyawan, jenis presensi, ketepatan waktu,
                    jarak, dan accuracy GPS.
                </p>
            </div>

            <div class="monitoring-header-actions mt-3 mt-md-0">
                @if ($filterIsActive)
                    <span class="badge text-bg-warning">
                        Filter aktif
                    </span>
                @endif

                @if ($canCorrectAttendance)
                    <a
                        href="{{ route(
                            'attendance-corrections.create'
                        ) }}"
                        class="btn btn-primary"
                    >
                        <i
                            class="bi bi-plus-lg me-2"
                            aria-hidden="true"
                        ></i>

                        Tambah Presensi Manual
                    </a>
                @endif
            </div>
        </header>

        <section
            class="monitoring-summary-grid"
            aria-label="Ringkasan monitoring presensi"
        >
            <article class="monitoring-summary-card">
                <span class="monitoring-summary-icon">
                    <i
                        class="bi bi-receipt"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="monitoring-summary-label">
                    Total transaksi
                </div>

                <div class="monitoring-summary-value">
                    {{ (int) $summary['total'] }}
                </div>

                <div class="monitoring-summary-copy">
                    Transaksi presensi terpilih.
                </div>
            </article>

            <article class="monitoring-summary-card">
                <span class="monitoring-summary-icon">
                    <i
                        class="bi bi-people"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="monitoring-summary-label">
                    Karyawan
                </div>

                <div class="monitoring-summary-value">
                    {{ (int) $summary['employees'] }}
                </div>

                <div class="monitoring-summary-copy">
                    Karyawan yang tercatat.
                </div>
            </article>

            <article class="monitoring-summary-card">
                <span class="monitoring-summary-icon">
                    <i
                        class="bi bi-box-arrow-in-right"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="monitoring-summary-label">
                    Presensi masuk
                </div>

                <div class="monitoring-summary-value">
                    {{ (int) $summary['check_in'] }}
                </div>

                <div class="monitoring-summary-copy">
                    Transaksi masuk.
                </div>
            </article>

            <article class="monitoring-summary-card">
                <span class="monitoring-summary-icon">
                    <i
                        class="bi bi-box-arrow-right"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="monitoring-summary-label">
                    Presensi pulang
                </div>

                <div class="monitoring-summary-value">
                    {{ (int) $summary['check_out'] }}
                </div>

                <div class="monitoring-summary-copy">
                    Transaksi pulang.
                </div>
            </article>

            <article class="monitoring-summary-card">
                <span class="monitoring-summary-icon">
                    <i
                        class="bi bi-check-circle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="monitoring-summary-label">
                    Tepat waktu
                </div>

                <div class="monitoring-summary-value">
                    {{ (int) $summary['on_time'] }}
                </div>

                <div class="monitoring-summary-copy">
                    Presensi masuk tepat waktu.
                </div>
            </article>

            <article class="monitoring-summary-card">
                <span class="monitoring-summary-icon">
                    <i
                        class="bi bi-clock-history"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="monitoring-summary-label">
                    Terlambat
                </div>

                <div class="monitoring-summary-value">
                    {{ (int) $summary['late'] }}
                </div>

                <div class="monitoring-summary-copy">
                    Presensi masuk terlambat.
                </div>
            </article>
        </section>

        <section
            class="monitoring-filter-card"
            aria-labelledby="monitoring-filter-heading"
        >
            <div class="monitoring-filter-heading">
                <h2
                    id="monitoring-filter-heading"
                    class="monitoring-filter-title"
                >
                    Filter Monitoring
                </h2>

                <p class="monitoring-filter-copy">
                    Kombinasikan beberapa parameter untuk
                    mempersempit transaksi presensi.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route(
                    'attendance-monitoring.index'
                ) }}"
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
                            value="{{
                                $selectedAttendanceDate ?? ''
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
                                    —
                                    {{
                                        $employeeOption
                                            ->full_name
                                    }}

                                    @if (
                                        $employeeOption->branch
                                        !== null
                                    )
                                        —
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
                                    'attendance-monitoring.index'
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
            class="monitoring-list-card"
            aria-labelledby="monitoring-list-heading"
        >
            <div class="monitoring-list-header">
                <div>
                    <h2
                        id="monitoring-list-heading"
                        class="monitoring-list-title"
                    >
                        Daftar Transaksi Presensi
                    </h2>

                    <p class="monitoring-list-copy">
                        Ditemukan {{ $attendances->total() }}
                        transaksi berdasarkan filter yang dipilih.
                    </p>
                </div>

                @if ($filterIsActive)
                    <a
                        href="{{ route(
                            'attendance-monitoring.index'
                        ) }}"
                        class="btn btn-sm
                            btn-outline-secondary"
                    >
                        Hapus Filter
                    </a>
                @else
                    <span class="badge text-bg-secondary">
                        Halaman {{ $attendances->currentPage() }}
                    </span>
                @endif
            </div>

            @if ($attendances->isEmpty())
                <div class="monitoring-empty-state">
                    <span class="monitoring-empty-icon">
                        <i
                            class="bi bi-clipboard2-x"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="monitoring-empty-title">
                        Data presensi belum tersedia
                    </h3>

                    <p class="monitoring-empty-copy">
                        Belum terdapat transaksi atau data tidak
                        sesuai dengan tanggal, cabang, karyawan,
                        jenis, dan ketepatan waktu yang dipilih.
                    </p>

                    @if ($filterIsActive)
                        <a
                            href="{{ route(
                                'attendance-monitoring.index'
                            ) }}"
                            class="btn btn-outline-primary"
                        >
                            Hapus Filter
                        </a>
                    @endif
                </div>
            @else
                <div
                    class="monitoring-desktop-table
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

                                @if ($canCorrectAttendance)
                                    <th
                                        scope="col"
                                        class="text-end"
                                    >
                                        Tindakan
                                    </th>
                                @endif
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $attendances as $attendance
                            )
                                @php
                                    $attendanceType =
                                        strtolower(
                                            (string)
                                                $attendance
                                                    ->attendance_type
                                        );

                                    $punctualityStatus =
                                        strtolower(
                                            (string)
                                                $attendance
                                                    ->punctuality_status
                                        );

                                    $validationStatus =
                                        strtolower(
                                            (string)
                                                $attendance
                                                    ->validation_status
                                        );

                                    $attendanceTypeLabel =
                                        $attendanceTypeLabels[
                                            $attendanceType
                                        ]
                                        ?? ucfirst($attendanceType);

                                    $attendanceTypeClass =
                                        $attendanceType
                                        === 'check_in'
                                            ? 'monitoring-type-check-in'
                                            : 'monitoring-type-check-out';

                                    $attendanceTypeIcon =
                                        $attendanceType
                                        === 'check_in'
                                            ? 'bi-box-arrow-in-right'
                                            : 'bi-box-arrow-right';

                                    $punctualityLabel =
                                        $punctualityLabels[
                                            $punctualityStatus
                                        ]
                                        ?? ucfirst(
                                            $punctualityStatus
                                        );

                                    $punctualityClass =
                                        $punctualityClasses[
                                            $punctualityStatus
                                        ]
                                        ?? 'text-bg-secondary';

                                    $validationStatusLabel =
                                        $validationStatusLabels[
                                            $validationStatus
                                        ]
                                        ?? ucfirst(
                                            $validationStatus
                                        );

                                    $validationStatusClass =
                                        $validationStatusClasses[
                                            $validationStatus
                                        ]
                                        ?? 'text-bg-secondary';
                                @endphp

                                <tr>
                                    <td
                                        class="text-center
                                            text-secondary"
                                    >
                                        {{
                                            ($attendances
                                                ->firstItem()
                                                ?? 0)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        @if (
                                            $attendance->employee
                                            !== null
                                        )
                                            <span
                                                class="monitoring-employee-name"
                                            >
                                                {{
                                                    $attendance
                                                        ->employee
                                                        ->full_name
                                                }}
                                            </span>

                                            <span
                                                class="monitoring-employee-number"
                                            >
                                                {{
                                                    $attendance
                                                        ->employee
                                                        ->employee_number
                                                }}
                                            </span>
                                        @else
                                            <span
                                                class="text-secondary
                                                    small"
                                            >
                                                Data karyawan tidak
                                                tersedia
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (
                                            $attendance->branch
                                            !== null
                                        )
                                            <span
                                                class="monitoring-branch-code"
                                            >
                                                {{
                                                    $attendance
                                                        ->branch
                                                        ->code
                                                }}
                                            </span>

                                            <span
                                                class="monitoring-branch-name"
                                            >
                                                {{
                                                    $attendance
                                                        ->branch
                                                        ->name
                                                }}
                                            </span>
                                        @else
                                            <span
                                                class="text-secondary"
                                            >
                                                -
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span
                                            class="monitoring-date"
                                        >
                                            {{
                                                $formatDate(
                                                    $attendance
                                                        ->attendance_date
                                                )
                                            }}
                                        </span>

                                        <span
                                            class="monitoring-time"
                                        >
                                            <i
                                                class="bi bi-clock"
                                                aria-hidden="true"
                                            ></i>

                                            {{
                                                $formatTime(
                                                    $attendance
                                                        ->attendance_time
                                                )
                                            }}
                                            WIB
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="monitoring-type-badge
                                                {{ $attendanceTypeClass }}"
                                        >
                                            <i
                                                class="bi
                                                    {{ $attendanceTypeIcon }}"
                                                aria-hidden="true"
                                            ></i>

                                            {{ $attendanceTypeLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                {{ $punctualityClass }}"
                                        >
                                            {{ $punctualityLabel }}
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <span
                                            class="monitoring-measurement"
                                        >
                                            {{
                                                $formatDecimal(
                                                    $attendance
                                                        ->distance
                                                )
                                            }}

                                            <span
                                                class="monitoring-measurement-unit"
                                            >
                                                meter
                                            </span>
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <span
                                            class="monitoring-measurement"
                                        >
                                            {{
                                                $formatDecimal(
                                                    $attendance
                                                        ->accuracy
                                                )
                                            }}

                                            <span
                                                class="monitoring-measurement-unit"
                                            >
                                                meter
                                            </span>
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span
                                            class="badge
                                                {{ $validationStatusClass }}"
                                        >
                                            {{
                                                $validationStatusLabel
                                            }}
                                        </span>
                                    </td>

                                    @if ($canCorrectAttendance)
                                        <td
                                            class="text-end
                                                text-nowrap"
                                        >
                                            <div
                                                class="monitoring-action-group"
                                            >
                                                <a
                                                    href="{{ route(
                                                        'attendance-corrections.edit',
                                                        [
                                                            'attendance' =>
                                                                $attendance
                                                                    ->getKey(),
                                                        ]
                                                    ) }}"
                                                    class="btn btn-sm
                                                        btn-outline-primary"
                                                >
                                                    <i
                                                        class="bi
                                                            bi-pencil-square
                                                            me-1"
                                                        aria-hidden="true"
                                                    ></i>

                                                    Koreksi
                                                </a>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="monitoring-mobile-list">
                    @foreach ($attendances as $attendance)
                        @php
                            $attendanceType =
                                strtolower(
                                    (string)
                                        $attendance
                                            ->attendance_type
                                );

                            $punctualityStatus =
                                strtolower(
                                    (string)
                                        $attendance
                                            ->punctuality_status
                                );

                            $validationStatus =
                                strtolower(
                                    (string)
                                        $attendance
                                            ->validation_status
                                );

                            $attendanceTypeLabel =
                                $attendanceTypeLabels[
                                    $attendanceType
                                ]
                                ?? ucfirst($attendanceType);

                            $attendanceTypeClass =
                                $attendanceType === 'check_in'
                                    ? 'monitoring-type-check-in'
                                    : 'monitoring-type-check-out';

                            $attendanceTypeIcon =
                                $attendanceType === 'check_in'
                                    ? 'bi-box-arrow-in-right'
                                    : 'bi-box-arrow-right';

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

                        <article class="monitoring-mobile-card">
                            <div class="monitoring-mobile-top">
                                <div>
                                    @if (
                                        $attendance->employee
                                        !== null
                                    )
                                        <h3
                                            class="monitoring-employee-name
                                                mb-0"
                                        >
                                            {{
                                                $attendance
                                                    ->employee
                                                    ->full_name
                                            }}
                                        </h3>

                                        <span
                                            class="monitoring-employee-number"
                                        >
                                            {{
                                                $attendance
                                                    ->employee
                                                    ->employee_number
                                            }}
                                        </span>
                                    @else
                                        <span
                                            class="text-secondary
                                                small"
                                        >
                                            Data karyawan tidak
                                            tersedia
                                        </span>
                                    @endif
                                </div>

                                <span
                                    class="badge
                                        {{ $validationStatusClass }}"
                                >
                                    {{ $validationStatusLabel }}
                                </span>
                            </div>

                            <div class="monitoring-mobile-section">
                                <div
                                    class="monitoring-mobile-statuses"
                                >
                                    <span
                                        class="monitoring-type-badge
                                            {{ $attendanceTypeClass }}"
                                    >
                                        <i
                                            class="bi
                                                {{ $attendanceTypeIcon }}"
                                            aria-hidden="true"
                                        ></i>

                                        {{ $attendanceTypeLabel }}
                                    </span>

                                    <span
                                        class="badge
                                            {{ $punctualityClass }}"
                                    >
                                        {{ $punctualityLabel }}
                                    </span>
                                </div>
                            </div>

                            <div class="monitoring-mobile-section">
                                <div class="monitoring-mobile-grid">
                                    <div class="monitoring-mobile-metric">
                                        <div
                                            class="monitoring-mobile-metric-label"
                                        >
                                            Cabang
                                        </div>

                                        <div
                                            class="monitoring-mobile-metric-value"
                                        >
                                            @if (
                                                $attendance->branch
                                                !== null
                                            )
                                                {{
                                                    $attendance
                                                        ->branch
                                                        ->code
                                                }}
                                                —
                                                {{
                                                    $attendance
                                                        ->branch
                                                        ->name
                                                }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>

                                    <div class="monitoring-mobile-metric">
                                        <div
                                            class="monitoring-mobile-metric-label"
                                        >
                                            Tanggal dan waktu
                                        </div>

                                        <div
                                            class="monitoring-mobile-metric-value"
                                        >
                                            {{
                                                $formatDate(
                                                    $attendance
                                                        ->attendance_date
                                                )
                                            }}
                                            <br>
                                            {{
                                                $formatTime(
                                                    $attendance
                                                        ->attendance_time
                                                )
                                            }}
                                            WIB
                                        </div>
                                    </div>

                                    <div class="monitoring-mobile-metric">
                                        <div
                                            class="monitoring-mobile-metric-label"
                                        >
                                            Jarak
                                        </div>

                                        <div
                                            class="monitoring-mobile-metric-value"
                                        >
                                            {{
                                                $formatDecimal(
                                                    $attendance
                                                        ->distance
                                                )
                                            }}
                                            meter
                                        </div>
                                    </div>

                                    <div class="monitoring-mobile-metric">
                                        <div
                                            class="monitoring-mobile-metric-label"
                                        >
                                            Accuracy
                                        </div>

                                        <div
                                            class="monitoring-mobile-metric-value"
                                        >
                                            {{
                                                $formatDecimal(
                                                    $attendance
                                                        ->accuracy
                                                )
                                            }}
                                            meter
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($canCorrectAttendance)
                                <div class="monitoring-mobile-section">
                                    <div
                                        class="monitoring-action-group"
                                    >
                                        <a
                                            href="{{ route(
                                                'attendance-corrections.edit',
                                                [
                                                    'attendance' =>
                                                        $attendance
                                                            ->getKey(),
                                                ]
                                            ) }}"
                                            class="btn btn-sm
                                                btn-outline-primary"
                                        >
                                            <i
                                                class="bi
                                                    bi-pencil-square
                                                    me-1"
                                                aria-hidden="true"
                                            ></i>

                                            Koreksi
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
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

                    <div class="monitoring-pagination">
                        <div class="small text-secondary">
                            Menampilkan
                            {{ $attendances->firstItem() }}
                            sampai
                            {{ $attendances->lastItem() }}
                            dari
                            {{ $attendances->total() }}
                            transaksi.
                        </div>

                        <nav
                            aria-label="Navigasi monitoring presensi"
                        >
                            <ul
                                class="pagination
                                    pagination-sm mb-0"
                            >
                                <li
                                    class="page-item {{
                                        $attendances
                                            ->onFirstPage()
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
                                        $attendances
                                            ->hasMorePages()
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
            @endif
        </section>
    </div>
@endsection
