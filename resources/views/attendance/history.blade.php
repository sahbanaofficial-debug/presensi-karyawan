@extends('layouts.app')

@section('title', 'Riwayat Presensi')

@push('styles')
    <style>
        .attendance-history-page {
            --history-surface: var(--neutral-0);
            --history-border: var(--neutral-200);
            --history-muted: var(--neutral-600);
            --history-soft: var(--brand-50);
        }

        .history-profile-card,
        .history-filter-card,
        .history-list-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--history-border);
            border-radius: var(--radius-lg);
            background: var(--history-surface);
            box-shadow: var(--shadow-xs);
        }

        .history-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--history-border);
            background: var(--neutral-25);
        }

        .history-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .history-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--history-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .history-profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-4);
            padding: var(--space-4);
        }

        .history-profile-panel {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .history-profile-heading {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-4);
        }

        .history-profile-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--history-soft);
            font-size: 1rem;
        }

        .history-profile-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .history-detail-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
        }

        .history-detail-row {
            display: grid;
            grid-template-columns: minmax(8rem, 0.85fr) minmax(0, 1.15fr);
            gap: var(--space-3);
            align-items: start;
            margin: 0;
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--neutral-100);
        }

        .history-detail-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .history-detail-row dt,
        .history-detail-row dd {
            margin: 0;
        }

        .history-detail-row dt {
            color: var(--history-muted);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .history-detail-row dd {
            min-width: 0;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .history-profile-warning {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border: 1px solid #f0ddb0;
            border-radius: var(--radius-md);
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .history-profile-warning-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: rgba(201, 130, 0, 0.09);
            font-size: 1rem;
        }

        .history-profile-warning-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .history-profile-warning-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .history-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .history-summary-card {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--history-border);
            border-radius: var(--radius-lg);
            background: var(--history-surface);
            box-shadow: var(--shadow-xs);
        }

        .history-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--history-soft);
            font-size: 1rem;
        }

        .history-summary-label {
            color: var(--history-muted);
            font-size: 0.6875rem;
            font-weight: 700;
        }

        .history-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.2;
        }

        .history-summary-meta {
            margin-top: var(--space-1);
            color: var(--history-muted);
            font-size: 0.6875rem;
        }

        .history-filter-body {
            padding: var(--space-4);
        }

        .history-filter-actions {
            display: flex;
            gap: var(--space-2);
        }

        .history-desktop-table {
            display: block;
        }

        .history-mobile-list {
            display: none;
        }

        .history-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--history-border);
        }

        .history-mobile-card:last-child {
            border-bottom: 0;
        }

        .history-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .history-mobile-date {
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .history-mobile-time {
            margin-top: var(--space-1);
            color: var(--history-muted);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .history-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
            margin-top: var(--space-4);
        }

        .history-mobile-metric {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .history-mobile-label {
            color: var(--history-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .history-mobile-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .history-empty-state {
            padding: var(--space-8) var(--space-5);
            color: var(--history-muted);
            text-align: center;
        }

        .history-empty-icon {
            display: inline-flex;
            width: 3.5rem;
            height: 3.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--history-soft);
            font-size: 1.375rem;
        }

        .history-empty-title {
            margin: 0 0 var(--space-1);
            color: var(--neutral-900);
            font-size: 0.9375rem;
            font-weight: 800;
        }

        .history-empty-copy {
            margin: 0;
            font-size: 0.75rem;
            line-height: 1.65;
        }

        .history-pagination {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border-top: 1px solid var(--history-border);
        }

        @media (min-width: 768px) {
            .history-profile-grid,
            .history-filter-body,
            .history-pagination {
                padding: var(--space-5);
            }

            .history-pagination {
                flex-direction: row;
                align-items: center;
            }
        }

        @media (max-width: 1199.98px) {
            .history-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .history-desktop-table {
                display: none;
            }

            .history-mobile-list {
                display: block;
            }
        }

        @media (max-width: 767.98px) {
            .history-profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .history-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .history-summary-grid,
            .history-mobile-grid {
                grid-template-columns: 1fr;
            }

            .history-detail-row {
                grid-template-columns: 1fr;
            }

            .history-filter-actions {
                flex-direction: column;
            }

            .history-filter-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

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

        $attendanceStatusLabels = [
            'present' => 'Hadir',
        ];

        $validationStatusLabels = [
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
        ];

        $validationStatusClasses = [
            'accepted' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ];

        $formatDate = static function ($value): string {
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

        $formatTime = static function ($value): string {
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
    @endphp

    <div class="attendance-history-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Riwayat Presensi
                </h1>

                <p class="page-description">
                    Lihat catatan presensi masuk dan pulang
                    milik akun Anda.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route('attendance.create') }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-qr-code-scan me-2"
                        aria-hidden="true"
                    ></i>

                    Buka Pemindai
                </a>
            </div>
        </header>

        <section
            class="history-profile-card"
            aria-labelledby="employee-profile-heading"
        >
            <div class="history-card-header">
                <div>
                    <h2
                        id="employee-profile-heading"
                        class="history-card-title"
                    >
                        Profil Presensi
                    </h2>

                    <p class="history-card-copy">
                        Identitas karyawan dan cabang penempatan
                        yang terhubung dengan akun Anda.
                    </p>
                </div>
            </div>

            <div class="history-profile-grid">
                <article class="history-profile-panel">
                    <div class="history-profile-heading">
                        <span class="history-profile-icon">
                            <i
                                class="bi bi-person-vcard"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <h3 class="history-profile-title">
                            Data Karyawan
                        </h3>
                    </div>

                    <dl class="history-detail-list">
                        <div class="history-detail-row">
                            <dt>
                                Nomor Karyawan
                            </dt>

                            <dd>
                                {{ $employee->employee_number }}
                            </dd>
                        </div>

                        <div class="history-detail-row">
                            <dt>
                                Nama
                            </dt>

                            <dd>
                                {{ $employee->full_name }}
                            </dd>
                        </div>

                        <div class="history-detail-row">
                            <dt>
                                Jabatan
                            </dt>

                            <dd>
                                {{ $employee->position }}
                            </dd>
                        </div>
                    </dl>
                </article>

                <article class="history-profile-panel">
                    <div class="history-profile-heading">
                        <span class="history-profile-icon">
                            <i
                                class="bi bi-building"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <h3 class="history-profile-title">
                            Cabang Penempatan
                        </h3>
                    </div>

                    @if ($branch !== null)
                        <dl class="history-detail-list">
                            <div class="history-detail-row">
                                <dt>
                                    Kode Cabang
                                </dt>

                                <dd>
                                    {{ $branch->code }}
                                </dd>
                            </div>

                            <div class="history-detail-row">
                                <dt>
                                    Nama Cabang
                                </dt>

                                <dd>
                                    {{ $branch->name }}
                                </dd>
                            </div>

                            <div class="history-detail-row">
                                <dt>
                                    Alamat
                                </dt>

                                <dd>
                                    {{ $branch->address ?? '-' }}
                                </dd>
                            </div>
                        </dl>
                    @else
                        <div
                            class="history-profile-warning"
                            role="alert"
                        >
                            <span
                                class="history-profile-warning-icon"
                            >
                                <i
                                    class="bi
                                        bi-exclamation-triangle"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3
                                    class="history-profile-warning-title"
                                >
                                    Data cabang tidak tersedia
                                </h3>

                                <p
                                    class="history-profile-warning-copy"
                                >
                                    Data cabang penempatan tidak
                                    tersedia.
                                </p>
                            </div>
                        </div>
                    @endif
                </article>
            </div>
        </section>

        <div class="history-summary-grid">
            <section class="history-summary-card">
                <span class="history-summary-icon">
                    <i
                        class="bi bi-collection"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="history-summary-label">
                    Seluruh Presensi
                </div>

                <div class="history-summary-value">
                    {{ (int) $summary['total'] }}
                </div>

                <div class="history-summary-meta">
                    transaksi
                </div>
            </section>

            <section class="history-summary-card">
                <span class="history-summary-icon">
                    <i
                        class="bi bi-box-arrow-in-right"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="history-summary-label">
                    Presensi Masuk
                </div>

                <div class="history-summary-value">
                    {{ (int) $summary['check_in'] }}
                </div>

                <div class="history-summary-meta">
                    transaksi
                </div>
            </section>

            <section class="history-summary-card">
                <span class="history-summary-icon">
                    <i
                        class="bi bi-box-arrow-right"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="history-summary-label">
                    Presensi Pulang
                </div>

                <div class="history-summary-value">
                    {{ (int) $summary['check_out'] }}
                </div>

                <div class="history-summary-meta">
                    transaksi
                </div>
            </section>

            <section class="history-summary-card">
                <span class="history-summary-icon">
                    <i
                        class="bi bi-clock-history"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="history-summary-label">
                    Keterlambatan
                </div>

                <div class="history-summary-value">
                    {{ (int) $summary['late'] }}
                </div>

                <div class="history-summary-meta">
                    presensi masuk
                </div>
            </section>
        </div>

        <section
            class="history-filter-card"
            aria-labelledby="attendance-filter-heading"
        >
            <div class="history-card-header">
                <div>
                    <h2
                        id="attendance-filter-heading"
                        class="history-card-title"
                    >
                        Filter Riwayat
                    </h2>

                    <p class="history-card-copy">
                        Batasi daftar berdasarkan jenis dan
                        tanggal presensi.
                    </p>
                </div>

                @if (
                    $selectedAttendanceType !== ''
                    || $selectedAttendanceDate !== null
                )
                    <span class="badge text-bg-info">
                        Filter aktif
                    </span>
                @endif
            </div>

            <div class="history-filter-body">
                <form
                    method="GET"
                    action="{{ route('attendance.history') }}"
                >
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
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

                        <div class="col-md-4">
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
                                    $selectedAttendanceDate
                                    ?? ''
                                }}"
                                class="form-control"
                            >
                        </div>

                        <div class="col-md-3">
                            <div class="history-filter-actions">
                                <button
                                    type="submit"
                                    class="btn btn-primary
                                        flex-grow-1"
                                >
                                    Terapkan
                                </button>

                                <a
                                    href="{{ route(
                                        'attendance.history'
                                    ) }}"
                                    class="btn
                                        btn-outline-secondary"
                                >
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section
            class="history-list-card"
            aria-labelledby="attendance-history-list-heading"
        >
            <div class="history-card-header">
                <div>
                    <h2
                        id="attendance-history-list-heading"
                        class="history-card-title"
                    >
                        Daftar Riwayat
                    </h2>

                    <p class="history-card-copy">
                        Ditemukan
                        {{ $attendances->total() }}
                        transaksi presensi.
                    </p>
                </div>
            </div>

            @if ($attendances->isNotEmpty())
                <div
                    class="history-desktop-table
                        table-responsive"
                >
                    <table
                        class="table table-hover align-middle"
                    >
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
                                    Tanggal dan Waktu
                                </th>

                                <th scope="col">
                                    Jenis
                                </th>

                                <th scope="col">
                                    Status Kehadiran
                                </th>

                                <th scope="col">
                                    Ketepatan Waktu
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

                                    $attendanceStatus =
                                        strtolower(
                                            (string)
                                                $attendance
                                                    ->attendance_status
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
                                        ?? ucfirst(
                                            $attendanceType
                                        );

                                    $attendanceTypeClass =
                                        $attendanceTypeClasses[
                                            $attendanceType
                                        ]
                                        ?? 'text-bg-secondary';

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

                                    $attendanceStatusLabel =
                                        $attendanceStatusLabels[
                                            $attendanceStatus
                                        ]
                                        ?? ucfirst(
                                            $attendanceStatus
                                        );

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
                                            (
                                                $attendances
                                                    ->firstItem()
                                                ?? 0
                                            )
                                            + $loop->index
                                        }}
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

                                        <div
                                            class="small
                                                text-secondary"
                                        >
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
                                            class="badge
                                                {{
                                                    $attendanceTypeClass
                                                }}"
                                        >
                                            {{
                                                $attendanceTypeLabel
                                            }}
                                        </span>
                                    </td>

                                    <td>
                                        {{
                                            $attendanceStatusLabel
                                        }}
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                {{
                                                    $punctualityClass
                                                }}"
                                        >
                                            {{
                                                $punctualityLabel
                                            }}
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        {{
                                            $formatDecimal(
                                                $attendance
                                                    ->distance
                                            )
                                        }}
                                        meter
                                    </td>

                                    <td class="text-end">
                                        {{
                                            $formatDecimal(
                                                $attendance
                                                    ->accuracy
                                            )
                                        }}
                                        meter
                                    </td>

                                    <td class="text-center">
                                        <span
                                            class="badge
                                                {{
                                                    $validationStatusClass
                                                }}"
                                        >
                                            {{
                                                $validationStatusLabel
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="history-mobile-list">
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

                            $attendanceStatus =
                                strtolower(
                                    (string)
                                        $attendance
                                            ->attendance_status
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
                                $attendanceTypeClasses[
                                    $attendanceType
                                ]
                                ?? 'text-bg-secondary';

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

                            $attendanceStatusLabel =
                                $attendanceStatusLabels[
                                    $attendanceStatus
                                ]
                                ?? ucfirst(
                                    $attendanceStatus
                                );

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

                        <article class="history-mobile-card">
                            <div class="history-mobile-top">
                                <div>
                                    <div class="history-mobile-date">
                                        {{
                                            $formatDate(
                                                $attendance
                                                    ->attendance_date
                                            )
                                        }}
                                    </div>

                                    <div class="history-mobile-time">
                                        {{
                                            $formatTime(
                                                $attendance
                                                    ->attendance_time
                                            )
                                        }}
                                        WIB
                                    </div>
                                </div>

                                <span
                                    class="badge
                                        {{ $attendanceTypeClass }}"
                                >
                                    {{ $attendanceTypeLabel }}
                                </span>
                            </div>

                            <div class="history-mobile-grid">
                                <div class="history-mobile-metric">
                                    <div
                                        class="history-mobile-label"
                                    >
                                        Status Kehadiran
                                    </div>

                                    <div
                                        class="history-mobile-value"
                                    >
                                        {{
                                            $attendanceStatusLabel
                                        }}
                                    </div>
                                </div>

                                <div class="history-mobile-metric">
                                    <div
                                        class="history-mobile-label"
                                    >
                                        Ketepatan Waktu
                                    </div>

                                    <div
                                        class="history-mobile-value"
                                    >
                                        <span
                                            class="badge
                                                {{
                                                    $punctualityClass
                                                }}"
                                        >
                                            {{
                                                $punctualityLabel
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div class="history-mobile-metric">
                                    <div
                                        class="history-mobile-label"
                                    >
                                        Jarak
                                    </div>

                                    <div
                                        class="history-mobile-value"
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

                                <div class="history-mobile-metric">
                                    <div
                                        class="history-mobile-label"
                                    >
                                        Accuracy
                                    </div>

                                    <div
                                        class="history-mobile-value"
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

                                <div class="history-mobile-metric">
                                    <div
                                        class="history-mobile-label"
                                    >
                                        Validasi
                                    </div>

                                    <div
                                        class="history-mobile-value"
                                    >
                                        <span
                                            class="badge
                                                {{
                                                    $validationStatusClass
                                                }}"
                                        >
                                            {{
                                                $validationStatusLabel
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="history-empty-state">
                    <span class="history-empty-icon">
                        <i
                            class="bi bi-clock-history"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="history-empty-title">
                        Riwayat presensi belum tersedia
                    </h3>

                    <p class="history-empty-copy">
                        Belum terdapat transaksi atau data tidak
                        sesuai dengan filter.
                    </p>

                    <a
                        href="{{ route('attendance.history') }}"
                        class="btn btn-sm
                            btn-outline-secondary mt-3"
                    >
                        Reset Filter
                    </a>
                </div>
            @endif

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

                <div class="history-pagination">
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
                        aria-label="Navigasi riwayat presensi"
                    >
                        <ul
                            class="pagination pagination-sm
                                mb-0"
                        >
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
    </div>
@endsection
