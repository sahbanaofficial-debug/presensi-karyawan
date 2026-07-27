@extends('layouts.app')

@section('title', 'Detail Jadwal Harian')

@push('styles')
    <style>
        .employee-schedule-detail-page {
            --schedule-detail-surface: var(--neutral-0);
            --schedule-detail-border: var(--neutral-200);
            --schedule-detail-muted: var(--neutral-600);
            --schedule-detail-soft: var(--brand-50);
        }

        .schedule-detail-overview-card,
        .schedule-detail-card,
        .schedule-detail-danger-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--schedule-detail-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-detail-surface);
            box-shadow: var(--shadow-xs);
        }

        .schedule-detail-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--schedule-detail-border);
            background: var(--neutral-25);
        }

        .schedule-detail-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .schedule-detail-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--schedule-detail-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .schedule-detail-overview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .schedule-detail-overview-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .schedule-detail-overview-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--schedule-detail-soft);
            font-size: 1rem;
        }

        .schedule-detail-overview-label {
            color: var(--schedule-detail-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .schedule-detail-overview-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .schedule-detail-main-grid,
        .schedule-detail-secondary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-5);
        }

        .schedule-detail-card-body {
            padding: var(--space-4);
        }

        .schedule-detail-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
        }

        .schedule-detail-row {
            display: grid;
            grid-template-columns: minmax(9rem, 0.85fr) minmax(0, 1.15fr);
            gap: var(--space-3);
            align-items: start;
            margin: 0;
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--neutral-100);
        }

        .schedule-detail-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .schedule-detail-row dt,
        .schedule-detail-row dd {
            margin: 0;
        }

        .schedule-detail-row dt {
            color: var(--schedule-detail-muted);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .schedule-detail-row dd {
            min-width: 0;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .schedule-detail-work-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .schedule-detail-work-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .schedule-detail-work-item.is-wide {
            grid-column: span 3;
        }

        .schedule-detail-work-label {
            color: var(--schedule-detail-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .schedule-detail-work-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.5;
        }

        .schedule-detail-notice,
        .schedule-detail-danger-notice {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border-radius: var(--radius-md);
        }

        .schedule-detail-notice {
            border: 1px solid var(--neutral-200);
            color: var(--neutral-700);
            background: var(--neutral-50);
        }

        .schedule-detail-danger-notice {
            border: 1px solid #efc9c3;
            color: var(--danger-700);
            background: var(--danger-50);
        }

        .schedule-detail-notice-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.45);
            font-size: 1rem;
        }

        .schedule-detail-notice-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .schedule-detail-notice-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .schedule-detail-attendance-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4);
        }

        .schedule-detail-attendance-value {
            color: var(--neutral-900);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.2;
        }

        .schedule-detail-danger-card {
            border-color: #efc9c3;
        }

        .schedule-detail-danger-header {
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid #efc9c3;
            background: var(--danger-50);
        }

        .schedule-detail-danger-title {
            margin: 0;
            color: var(--danger-700);
            font-size: 1rem;
            font-weight: 800;
        }

        .schedule-detail-danger-body {
            padding: var(--space-4);
        }

        @media (min-width: 768px) {
            .schedule-detail-overview-grid,
            .schedule-detail-card-body,
            .schedule-detail-attendance-card,
            .schedule-detail-danger-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .schedule-detail-overview-grid,
            .schedule-detail-main-grid,
            .schedule-detail-secondary-grid,
            .schedule-detail-work-grid {
                grid-template-columns: 1fr;
            }

            .schedule-detail-work-item.is-wide {
                grid-column: auto;
            }
        }

        @media (max-width: 575.98px) {
            .schedule-detail-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .schedule-detail-row {
                grid-template-columns: 1fr;
            }

            .schedule-detail-attendance-card {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $employee = $employeeSchedule->employee;
        $employeeUser = $employee?->user;
        $branch = $employee?->branch;
        $workSchedule = $employeeSchedule->workSchedule;

        $scheduleStatus = $employeeSchedule->schedule_status;

        $statusLabels = [
            'work' => 'Kerja',
            'off' => 'Libur',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $statusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];

        $statusDescriptions = [
            'work' => 'Karyawan dijadwalkan bekerja menggunakan pola jadwal yang ditetapkan.',
            'off' => 'Karyawan mendapatkan hari libur pada tanggal tersebut.',
            'permit' => 'Karyawan tercatat memiliki izin pada tanggal tersebut.',
            'sick' => 'Karyawan tercatat sakit pada tanggal tersebut.',
        ];

        $statusLabel = $statusLabels[$scheduleStatus]
            ?? ucfirst((string) $scheduleStatus);

        $statusClass = $statusClasses[$scheduleStatus]
            ?? 'text-bg-secondary';

        $statusDescription = $statusDescriptions[$scheduleStatus]
            ?? 'Status jadwal tidak dikenali.';

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l, d F Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDateTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y H:i');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }

            $time = (string) $value;

            return strlen($time) >= 5
                ? substr($time, 0, 5)
                : $time;
        };

        $attendanceCount = (int) (
            $employeeSchedule->attendances_count ?? 0
        );
    @endphp

    <div class="employee-schedule-detail-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Detail Jadwal Harian
                </h1>

                <p class="page-description">
                    Informasi penetapan jadwal harian karyawan.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <a
                    href="{{ route(
                        'employee-schedules.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Kembali
                </a>

                <a
                    href="{{ route(
                        'employee-schedules.edit',
                        $employeeSchedule
                    ) }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-pencil-square me-2"
                        aria-hidden="true"
                    ></i>

                    Edit Jadwal
                </a>
            </div>
        </header>

        <section
            class="schedule-detail-overview-card"
            aria-labelledby="schedule-overview-heading"
        >
            <div class="schedule-detail-card-header">
                <div>
                    <h2
                        id="schedule-overview-heading"
                        class="schedule-detail-card-title"
                    >
                        Ringkasan Jadwal
                    </h2>

                    <p class="schedule-detail-card-copy">
                        Status, tanggal, dan karyawan yang menerima
                        penetapan jadwal.
                    </p>
                </div>

                <span class="badge {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="schedule-detail-overview-grid">
                <article class="schedule-detail-overview-item">
                    <span class="schedule-detail-overview-icon">
                        <i
                            class="bi bi-calendar3"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-detail-overview-label">
                        Tanggal Jadwal
                    </div>

                    <div class="schedule-detail-overview-value">
                        {{
                            $formatDate(
                                $employeeSchedule->schedule_date
                            )
                        }}
                    </div>
                </article>

                <article class="schedule-detail-overview-item">
                    <span class="schedule-detail-overview-icon">
                        <i
                            class="bi bi-person"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-detail-overview-label">
                        Karyawan
                    </div>

                    <div class="schedule-detail-overview-value">
                        {{ $employee?->full_name ?? '-' }}
                    </div>
                </article>

                <article class="schedule-detail-overview-item">
                    <span class="schedule-detail-overview-icon">
                        <i
                            class="bi bi-info-circle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-detail-overview-label">
                        Keterangan Status
                    </div>

                    <div class="schedule-detail-overview-value">
                        {{ $statusDescription }}
                    </div>
                </article>
            </div>
        </section>

        <div class="schedule-detail-main-grid">
            <section
                class="schedule-detail-card"
                aria-labelledby="scheduled-employee-heading"
            >
                <div class="schedule-detail-card-header">
                    <div>
                        <h2
                            id="scheduled-employee-heading"
                            class="schedule-detail-card-title"
                        >
                            Data Karyawan
                        </h2>

                        <p class="schedule-detail-card-copy">
                            Karyawan yang menerima penetapan jadwal.
                        </p>
                    </div>
                </div>

                <div class="schedule-detail-card-body">
                    @if ($employee !== null)
                        <dl class="schedule-detail-list">
                            <div class="schedule-detail-row">
                                <dt>
                                    Nomor Karyawan
                                </dt>

                                <dd>
                                    {{ $employee->employee_number }}
                                </dd>
                            </div>

                            <div class="schedule-detail-row">
                                <dt>
                                    Nama Lengkap
                                </dt>

                                <dd>
                                    <a
                                        href="{{ route(
                                            'employees.show',
                                            $employee
                                        ) }}"
                                        class="fw-semibold
                                            text-decoration-none"
                                    >
                                        {{ $employee->full_name }}
                                    </a>
                                </dd>
                            </div>

                            <div class="schedule-detail-row">
                                <dt>
                                    Jabatan
                                </dt>

                                <dd>
                                    {{ $employee->position }}
                                </dd>
                            </div>

                            <div class="schedule-detail-row">
                                <dt>
                                    Status Karyawan
                                </dt>

                                <dd>
                                    @if (
                                        $employee->employment_status
                                        === 'active'
                                    )
                                        <span
                                            class="badge
                                                text-bg-success"
                                        >
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="badge
                                                text-bg-secondary"
                                        >
                                            Tidak Aktif
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div class="schedule-detail-row">
                                <dt>
                                    Email Akun
                                </dt>

                                <dd>
                                    {{ $employeeUser?->email ?? '-' }}
                                </dd>
                            </div>

                            <div class="schedule-detail-row">
                                <dt>
                                    Status Akun
                                </dt>

                                <dd>
                                    @if (
                                        $employeeUser?->status
                                        === 'active'
                                    )
                                        <span
                                            class="badge
                                                text-bg-success"
                                        >
                                            Aktif
                                        </span>
                                    @elseif ($employeeUser !== null)
                                        <span
                                            class="badge
                                                text-bg-secondary"
                                        >
                                            Tidak Aktif
                                        </span>
                                    @else
                                        <span class="text-secondary">
                                            Akun tidak tersedia
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    @else
                        <div
                            class="schedule-detail-danger-notice"
                            role="alert"
                        >
                            <span
                                class="schedule-detail-notice-icon"
                            >
                                <i
                                    class="bi bi-x-octagon"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3
                                    class="schedule-detail-notice-title"
                                >
                                    Data karyawan tidak tersedia
                                </h3>

                                <p
                                    class="schedule-detail-notice-copy"
                                >
                                    Data karyawan tidak tersedia.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section
                class="schedule-detail-card"
                aria-labelledby="schedule-branch-heading"
            >
                <div class="schedule-detail-card-header">
                    <div>
                        <h2
                            id="schedule-branch-heading"
                            class="schedule-detail-card-title"
                        >
                            Data Cabang
                        </h2>

                        <p class="schedule-detail-card-copy">
                            Lokasi kerja karyawan.
                        </p>
                    </div>
                </div>

                <div class="schedule-detail-card-body">
                    @if ($branch !== null)
                        <dl class="schedule-detail-list">
                            <div class="schedule-detail-row">
                                <dt>
                                    Kode Cabang
                                </dt>

                                <dd>
                                    {{ $branch->code }}
                                </dd>
                            </div>

                            <div class="schedule-detail-row">
                                <dt>
                                    Nama Cabang
                                </dt>

                                <dd>
                                    <a
                                        href="{{ route(
                                            'branches.show',
                                            $branch
                                        ) }}"
                                        class="fw-semibold
                                            text-decoration-none"
                                    >
                                        {{ $branch->name }}
                                    </a>
                                </dd>
                            </div>

                            <div class="schedule-detail-row">
                                <dt>
                                    Alamat
                                </dt>

                                <dd>
                                    {{ $branch->address ?: '-' }}
                                </dd>
                            </div>

                            <div class="schedule-detail-row">
                                <dt>
                                    Status Cabang
                                </dt>

                                <dd>
                                    @if (
                                        $branch->status === 'active'
                                    )
                                        <span
                                            class="badge
                                                text-bg-success"
                                        >
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="badge
                                                text-bg-secondary"
                                        >
                                            Tidak Aktif
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    @else
                        <div
                            class="schedule-detail-notice"
                            role="alert"
                        >
                            <span
                                class="schedule-detail-notice-icon"
                            >
                                <i
                                    class="bi
                                        bi-exclamation-triangle"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3
                                    class="schedule-detail-notice-title"
                                >
                                    Cabang tidak tersedia
                                </h3>

                                <p
                                    class="schedule-detail-notice-copy"
                                >
                                    Data cabang tidak tersedia.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <section
            class="schedule-detail-card"
            aria-labelledby="work-schedule-policy-heading"
        >
            <div class="schedule-detail-card-header">
                <div>
                    <h2
                        id="work-schedule-policy-heading"
                        class="schedule-detail-card-title"
                    >
                        Pola dan Ketentuan Jam Kerja
                    </h2>

                    <p class="schedule-detail-card-copy">
                        Ketentuan waktu presensi berdasarkan pola
                        jadwal.
                    </p>
                </div>
            </div>

            <div class="schedule-detail-card-body">
                @if (
                    $scheduleStatus === 'work'
                    && $workSchedule !== null
                )
                    <div class="schedule-detail-work-grid">
                        <article
                            class="schedule-detail-work-item is-wide"
                        >
                            <div class="schedule-detail-work-label">
                                Nama Pola Jadwal
                            </div>

                            <div class="schedule-detail-work-value">
                                <a
                                    href="{{ route(
                                        'work-schedules.show',
                                        $workSchedule
                                    ) }}"
                                    class="text-decoration-none"
                                >
                                    {{ $workSchedule->name }}
                                </a>

                                @if (
                                    $workSchedule->status
                                    === 'inactive'
                                )
                                    <span
                                        class="badge
                                            text-bg-warning ms-2"
                                    >
                                        Pola jadwal tidak aktif
                                    </span>
                                @endif
                            </div>
                        </article>

                        <article class="schedule-detail-work-item">
                            <div class="schedule-detail-work-label">
                                Jam Masuk
                            </div>

                            <div class="schedule-detail-work-value">
                                {{
                                    $formatTime(
                                        $workSchedule
                                            ->check_in_time
                                    )
                                }}
                                WIB
                            </div>
                        </article>

                        <article class="schedule-detail-work-item">
                            <div class="schedule-detail-work-label">
                                Jam Pulang
                            </div>

                            <div class="schedule-detail-work-value">
                                {{
                                    $formatTime(
                                        $workSchedule
                                            ->check_out_time
                                    )
                                }}
                                WIB
                            </div>
                        </article>

                        <article class="schedule-detail-work-item">
                            <div class="schedule-detail-work-label">
                                Presensi Masuk Dibuka
                            </div>

                            <div class="schedule-detail-work-value">
                                {{
                                    $workSchedule
                                        ->check_in_open_minutes
                                }}
                                menit sebelum jam masuk
                            </div>
                        </article>

                        <article class="schedule-detail-work-item">
                            <div class="schedule-detail-work-label">
                                Toleransi Keterlambatan
                            </div>

                            <div class="schedule-detail-work-value">
                                {{
                                    $workSchedule
                                        ->late_tolerance_minutes
                                }}
                                menit
                            </div>
                        </article>

                        <article class="schedule-detail-work-item">
                            <div class="schedule-detail-work-label">
                                Batas Presensi Pulang
                            </div>

                            <div class="schedule-detail-work-value">
                                {{
                                    $workSchedule
                                        ->check_out_limit_minutes
                                }}
                                menit setelah jam pulang
                            </div>
                        </article>
                    </div>
                @elseif ($scheduleStatus === 'work')
                    <div
                        class="schedule-detail-danger-notice"
                        role="alert"
                    >
                        <span class="schedule-detail-notice-icon">
                            <i
                                class="bi bi-x-octagon"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3
                                class="schedule-detail-notice-title"
                            >
                                Pola jadwal tidak tersedia
                            </h3>

                            <p
                                class="schedule-detail-notice-copy"
                            >
                                Status jadwal adalah kerja, tetapi
                                pola jadwal tidak tersedia.
                            </p>
                        </div>
                    </div>
                @else
                    <div
                        class="schedule-detail-notice"
                        role="status"
                    >
                        <span class="schedule-detail-notice-icon">
                            <i
                                class="bi bi-info-circle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3
                                class="schedule-detail-notice-title"
                            >
                                Pola jadwal tidak digunakan
                            </h3>

                            <p
                                class="schedule-detail-notice-copy"
                            >
                                Status
                                {{ strtolower($statusLabel) }}
                                tidak menggunakan pola jadwal kerja.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <div class="schedule-detail-secondary-grid">
            <section
                class="schedule-detail-card"
                aria-labelledby="schedule-notes-heading"
            >
                <div class="schedule-detail-card-header">
                    <div>
                        <h2
                            id="schedule-notes-heading"
                            class="schedule-detail-card-title"
                        >
                            Keterangan Jadwal
                        </h2>
                    </div>
                </div>

                <div class="schedule-detail-card-body">
                    @if (
                        $employeeSchedule->notes !== null
                        && trim(
                            (string) $employeeSchedule->notes
                        ) !== ''
                    )
                        <p class="mb-0">
                            {{ $employeeSchedule->notes }}
                        </p>
                    @else
                        <span class="text-secondary">
                            Tidak ada keterangan.
                        </span>
                    @endif
                </div>
            </section>

            <section
                class="schedule-detail-card"
                aria-labelledby="schedule-history-heading"
            >
                <div class="schedule-detail-card-header">
                    <div>
                        <h2
                            id="schedule-history-heading"
                            class="schedule-detail-card-title"
                        >
                            Persetujuan dan Riwayat
                        </h2>
                    </div>
                </div>

                <div class="schedule-detail-card-body">
                    <dl class="schedule-detail-list">
                        <div class="schedule-detail-row">
                            <dt>
                                Ditetapkan Oleh
                            </dt>

                            <dd>
                                @if ($approver !== null)
                                    <div class="fw-semibold">
                                        {{ $approver->name }}
                                    </div>

                                    <div
                                        class="small
                                            text-secondary"
                                    >
                                        {{ $approver->email }}
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        Data pengguna tidak tersedia
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="schedule-detail-row">
                            <dt>
                                Dibuat
                            </dt>

                            <dd>
                                {{
                                    $formatDateTime(
                                        $employeeSchedule
                                            ->created_at
                                    )
                                }}
                                WIB
                            </dd>
                        </div>

                        <div class="schedule-detail-row">
                            <dt>
                                Terakhir Diubah
                            </dt>

                            <dd>
                                {{
                                    $formatDateTime(
                                        $employeeSchedule
                                            ->updated_at
                                    )
                                }}
                                WIB
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>

        <section
            class="schedule-detail-card"
            aria-labelledby="schedule-attendance-heading"
        >
            <div class="schedule-detail-attendance-card">
                <div>
                    <h2
                        id="schedule-attendance-heading"
                        class="schedule-detail-card-title"
                    >
                        Data Presensi
                    </h2>

                    <p class="schedule-detail-card-copy">
                        Jumlah data presensi yang menggunakan
                        jadwal ini.
                    </p>
                </div>

                <div class="text-md-end">
                    <div class="schedule-detail-attendance-value">
                        {{ $attendanceCount }}
                    </div>

                    <div class="small text-secondary">
                        data presensi
                    </div>
                </div>
            </div>
        </section>

        <section
            class="schedule-detail-danger-card"
            aria-labelledby="delete-schedule-heading"
        >
            <div class="schedule-detail-danger-header">
                <h2
                    id="delete-schedule-heading"
                    class="schedule-detail-danger-title"
                >
                    Hapus Jadwal Harian
                </h2>
            </div>

            <div class="schedule-detail-danger-body">
                @if ($attendanceCount > 0)
                    <div
                        class="schedule-detail-danger-notice
                            mb-4"
                        role="alert"
                    >
                        <span class="schedule-detail-notice-icon">
                            <i
                                class="bi bi-lock"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3
                                class="schedule-detail-notice-title"
                            >
                                Penghapusan tidak tersedia
                            </h3>

                            <p
                                class="schedule-detail-notice-copy"
                            >
                                Jadwal tidak dapat dihapus karena
                                sudah digunakan oleh data presensi.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-danger"
                        disabled
                    >
                        Hapus Jadwal
                    </button>
                @else
                    <div
                        class="schedule-detail-danger-notice
                            mb-4"
                        role="alert"
                    >
                        <span class="schedule-detail-notice-icon">
                            <i
                                class="bi
                                    bi-exclamation-triangle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3
                                class="schedule-detail-notice-title"
                            >
                                Tindakan permanen
                            </h3>

                            <p
                                class="schedule-detail-notice-copy"
                            >
                                Jadwal dapat dihapus karena belum
                                memiliki data presensi. Tindakan ini
                                tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>

                    <form
                        method="POST"
                        action="{{ route(
                            'employee-schedules.destroy',
                            $employeeSchedule
                        ) }}"
                        onsubmit="
                            return confirm(
                                'Hapus jadwal harian ini?'
                            );
                        "
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-outline-danger"
                        >
                            <i
                                class="bi bi-trash me-2"
                                aria-hidden="true"
                            ></i>

                            Hapus Jadwal
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
