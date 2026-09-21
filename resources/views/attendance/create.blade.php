@extends('layouts.app')

@section('title', 'Presensi Karyawan')

@push('styles')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >

    <style>
        /*
         * Attendance workspace
         * Uses the global PT Gadai Ogan Baru design tokens
         * defined in layouts/app.blade.php.
         */
        .attendance-page {
            --attendance-orange: var(--brand-500);
            --attendance-orange-dark: var(--brand-700);
            --attendance-orange-soft: var(--brand-50);
            --attendance-border: var(--neutral-200);
            --attendance-muted: var(--neutral-600);
            --attendance-surface: var(--neutral-0);
        }

        .attendance-progress {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: var(--space-5);
            overflow: hidden;
            border: 1px solid var(--attendance-border);
            border-radius: var(--radius-lg);
            background: var(--attendance-surface);
            box-shadow: var(--shadow-xs);
        }

        .attendance-progress-step {
            position: relative;
            display: flex;
            min-width: 0;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .attendance-progress-step + .attendance-progress-step {
            border-left: 1px solid var(--attendance-border);
        }

        .attendance-progress-number {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 2.25rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--attendance-orange-dark);
            background: var(--attendance-orange-soft);
            font-size: 0.8125rem;
            font-weight: 800;
        }

        .attendance-progress-title {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .attendance-progress-copy {
            display: block;
            margin-top: var(--space-1);
            color: var(--attendance-muted);
            font-size: 0.6875rem;
            line-height: 1.45;
        }

        .attendance-section-header {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
        }

        .attendance-section-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--attendance-orange-dark);
            background: var(--attendance-orange-soft);
            font-size: 1rem;
        }

        .attendance-section-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            line-height: 1.4;
        }

        .attendance-section-copy {
            margin: var(--space-1) 0 0;
            color: var(--attendance-muted);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .employee-summary-card,
        .schedule-summary-card,
        .attendance-overview-card,
        .geofence-workspace,
        .scanner-panel,
        .usage-panel {
            border-color: var(--attendance-border);
            box-shadow: var(--shadow-xs);
        }

        .employee-summary-card > .border-bottom,
        .schedule-summary-card > .border-bottom,
        .attendance-overview-card > .border-bottom,
        .geofence-workspace > .border-bottom,
        .scanner-panel > .border-bottom {
            background: var(--neutral-25);
        }

        .employee-summary-card dl,
        .schedule-summary-card dl {
            margin: 0;
        }

        .employee-summary-card dt,
        .schedule-summary-card dt {
            color: var(--attendance-muted);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .employee-summary-card dd,
        .schedule-summary-card dd {
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 700;
        }

        .attendance-status-card {
            position: relative;
            height: 100%;
            min-height: 11rem;
            padding: var(--space-4);
            overflow: hidden;
            border: 1px solid var(--attendance-border);
            border-radius: var(--radius-md);
            background: var(--attendance-surface);
        }

        .attendance-status-card::before {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 0.1875rem;
            background: var(--brand-300);
            content: "";
        }

        .attendance-status-card .small {
            line-height: 1.65;
        }

        .scanner-wrapper {
            max-width: 42rem;
            margin-right: auto;
            margin-left: auto;
        }

        .scanner-stage {
            position: relative;
            min-height: 23rem;
            overflow: hidden;
            border: 1px solid #dce3e9;
            border-radius: var(--radius-lg);
            background: #17212b;
        }

        .scanner-stage::after {
            position: absolute;
            inset: 12%;
            border-radius: 1.2rem;
            background:
                linear-gradient(#fff, #fff) top left / 3rem 0.3rem no-repeat,
                linear-gradient(#fff, #fff) top left / 0.3rem 3rem no-repeat,
                linear-gradient(#fff, #fff) top right / 3rem 0.3rem no-repeat,
                linear-gradient(#fff, #fff) top right / 0.3rem 3rem no-repeat,
                linear-gradient(#fff, #fff) bottom left / 3rem 0.3rem no-repeat,
                linear-gradient(#fff, #fff) bottom left / 0.3rem 3rem no-repeat,
                linear-gradient(#fff, #fff) bottom right / 3rem 0.3rem no-repeat,
                linear-gradient(#fff, #fff) bottom right / 0.3rem 3rem no-repeat;
            content: "";
            pointer-events: none;
        }

        #qr-reader {
            width: 100%;
            min-height: 23rem;
        }

        #qr-reader video {
            display: block;
            width: 100% !important;
            max-width: 100%;
            min-height: 23rem;
            object-fit: cover;
            border-radius: var(--radius-lg);
        }

        #qr-reader__dashboard {
            padding: var(--space-3) !important;
        }

        #qr-reader__dashboard button {
            min-height: 2.5rem;
            padding: 0.5rem 0.875rem;
            border: 1px solid var(--brand-500);
            border-radius: var(--radius-md);
            color: var(--neutral-0);
            background: var(--brand-500);
            font-family: inherit;
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .scanner-empty-state {
            position: relative;
            z-index: 1;
            display: flex;
            max-width: 22rem;
            flex-direction: column;
            align-items: center;
            gap: var(--space-2);
        }

        .scanner-empty-icon {
            display: inline-flex;
            width: 3.25rem;
            height: 3.25rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-2);
            border-radius: var(--radius-lg);
            color: var(--attendance-orange-dark);
            background: var(--attendance-orange-soft);
            font-size: 1.375rem;
        }

        .scanner-empty-title {
            color: #fff;
            font-size: 0.9375rem;
            font-weight: 800;
        }

        .scanner-empty-copy {
            color: #d9e2ea;
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .scanner-tip {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0 0 1rem;
            color: var(--attendance-muted);
            font-size: 0.875rem;
        }

        .scanner-tip i {
            color: var(--attendance-orange-dark);
            font-size: 1.1rem;
        }

        .attendance-result-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            border: 1px solid;
            border-radius: var(--radius-lg);
        }

        .attendance-result-card.is-success {
            border-color: #a9e7cd;
            background: #ecfdf5;
            color: #065f46;
        }

        .attendance-result-card.is-error {
            border-color: #f4b4ae;
            background: #fff3f2;
            color: #9f2b20;
        }

        .attendance-result-icon {
            display: inline-flex;
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 2.75rem;
            align-items: center;
            justify-content: center;
            border-radius: 0.8rem;
            background: rgba(255, 255, 255, 0.75);
            font-size: 1.3rem;
        }

        .attendance-result-title {
            margin: 0 0 0.25rem;
            font-size: 1.1rem;
            font-weight: 800;
        }

        .attendance-result-details {
            display: grid;
            gap: 0.2rem;
            margin-top: 0.6rem;
            font-size: 0.875rem;
            font-weight: 700;
        }

        .location-value {
            overflow-wrap: anywhere;
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;
            font-size: 0.8125rem;
        }

        .processing-spinner {
            width: 1rem;
            height: 1rem;
            border-width: 0.15rem;
        }

        .geofence-map {
            width: 100%;
            min-height: 29rem;
            overflow: hidden;
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-lg);
            background: var(--neutral-100);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.45);
        }

        .geofence-summary {
            border: 1px solid var(--attendance-border);
            border-radius: var(--radius-lg);
            background: var(--attendance-surface);
        }

        .geofence-metric {
            height: 100%;
            min-height: 6.25rem;
            padding: var(--space-3);
            border: 1px solid var(--attendance-border);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .geofence-metric-label {
            margin-bottom: var(--space-2);
            color: var(--attendance-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .geofence-metric-value {
            overflow-wrap: anywhere;
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .geofence-status-panel {
            padding: var(--space-3);
            border: 1px solid var(--attendance-border);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .geofence-legend {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-3);
            color: var(--attendance-muted);
            font-size: 0.75rem;
        }

        .geofence-legend-item {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
        }

        .geofence-legend-dot {
            width: 0.6875rem;
            height: 0.6875rem;
            flex: 0 0 0.6875rem;
            border-radius: var(--radius-pill);
        }

        .geofence-legend-dot.branch {
            background: var(--brand-500);
        }

        .geofence-legend-dot.device {
            background: var(--neutral-800);
        }

        .geofence-legend-dot.radius {
            border: 2px solid var(--success-500);
            background: var(--success-50);
        }

        .leaflet-container {
            font-family: inherit;
        }

        .leaflet-control-zoom a {
            color: var(--neutral-800);
        }

        .leaflet-popup-content-wrapper,
        .leaflet-popup-tip {
            color: var(--neutral-900);
            background: var(--neutral-0);
        }

        .attendance-page .alert {
            margin-bottom: var(--space-4);
        }

        .attendance-page .btn i {
            font-size: 0.9375rem;
        }

        .usage-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
            padding: 0;
            counter-reset: usage-counter;
            list-style: none;
        }

        .usage-list li {
            position: relative;
            min-height: 2rem;
            padding-left: 2.75rem;
            color: var(--attendance-muted);
            font-size: 0.8125rem;
            line-height: 1.6;
            counter-increment: usage-counter;
        }

        .usage-list li::before {
            position: absolute;
            top: 0;
            left: 0;
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            color: var(--attendance-orange-dark);
            background: var(--attendance-orange-soft);
            content: counter(usage-counter);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        @media (max-width: 991.98px) {
            .geofence-map {
                min-height: 24rem;
            }
        }

        @media (max-width: 767.98px) {
            .attendance-progress {
                grid-template-columns: 1fr;
            }

            .attendance-progress-step + .attendance-progress-step {
                border-top: 1px solid var(--attendance-border);
                border-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .attendance-progress-step {
                padding: var(--space-3);
            }

            .geofence-map {
                min-height: 20rem;
            }

            .scanner-stage,
            #qr-reader,
            #qr-reader video {
                min-height: 19rem;
            }

            .geofence-metric {
                min-height: 5.75rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="attendance-page">
    @php
        $scheduleStatusLabels = [
            'work' => 'Hari Kerja',
            'off' => 'Libur',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $scheduleStatusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];

        $scheduleStatus = strtolower(
            (string) ($employeeSchedule?->schedule_status ?? '')
        );

        $scheduleStatusLabel =
            $scheduleStatusLabels[$scheduleStatus]
            ?? 'Jadwal Tidak Tersedia';

        $scheduleStatusClass =
            $scheduleStatusClasses[$scheduleStatus]
            ?? 'text-bg-secondary';

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('d-m-Y');
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
                    ->format('d-m-Y H:i:s');
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

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('H:i');
            } catch (\Throwable) {
                return substr((string) $value, 0, 5);
            }
        };

        $nextAttendanceLabel = match (true) {
            $checkInAttendance === null =>
                'Presensi Masuk',

            $checkOutAttendance === null =>
                'Presensi Pulang',

            default =>
                'Presensi Hari Ini Selesai',
        };

        $scannerStatusClass =
            $canScan
                ? 'text-bg-success'
                : 'text-bg-secondary';

        $attendanceSourceLabel = static function (
            $attendance
        ): string {
            return match (
                (string) ($attendance?->record_source ?? '')
            ) {
                'scanner' => 'Scanner QR + Geofence',
                'manual' => 'Manual oleh HRD',
                default => 'Tidak diketahui',
            };
        };

        $attendanceDistanceLabel = static function (
            $attendance
        ): string {
            if (
                $attendance === null
                || $attendance->record_source !== 'scanner'
                || $attendance->distance === null
            ) {
                return 'Tidak tersedia';
            }

            return number_format(
                (float) $attendance->distance,
                2,
                ',',
                '.'
            ) . ' meter';
        };
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Presensi Karyawan
            </h1>

            <p class="page-description">
                Pindai QR Code dinamis dan kirim lokasi
                perangkat untuk mencatat presensi.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <span
                class="badge {{ $scannerStatusClass }}
                    px-3 py-2"
            >
                {{ $nextAttendanceLabel }}
            </span>
        </div>
    </header>

    <section
        class="attendance-progress"
        aria-label="Tahapan presensi"
    >
        <div class="attendance-progress-step">
            <span class="attendance-progress-number">
                1
            </span>

            <span>
                <span class="attendance-progress-title">
                    Validasi lokasi
                </span>

                <span class="attendance-progress-copy">
                    Periksa GPS, akurasi, dan radius geofence.
                </span>
            </span>
        </div>

        <div class="attendance-progress-step">
            <span class="attendance-progress-number">
                2
            </span>

            <span>
                <span class="attendance-progress-title">
                    Pindai QR
                </span>

                <span class="attendance-progress-copy">
                    Gunakan QR dinamis yang aktif di cabang.
                </span>
            </span>
        </div>

        <div class="attendance-progress-step">
            <span class="attendance-progress-number">
                3
            </span>

            <span>
                <span class="attendance-progress-title">
                    Validasi server
                </span>

                <span class="attendance-progress-copy">
                    Sistem memeriksa TOTP, jadwal, dan duplikasi.
                </span>
            </span>
        </div>
    </section>

    @if (! $canScan)
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Pemindai presensi tidak tersedia
            </div>

            <div>
                {{
                    $scanBlockReason
                    ?? 'Presensi tidak dapat dilakukan saat ini.'
                }}
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <section class="content-card h-100 employee-summary-card">
                <div class="border-bottom p-3 p-md-4">
                    <div class="attendance-section-header">
                        <span class="attendance-section-icon">
                            <i
                                class="bi bi-person-badge"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h2 class="attendance-section-title">
                                Data Karyawan
                            </h2>

                            <p class="attendance-section-copy">
                                Identitas dan ketentuan lokasi akun.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 mb-2">
                            Nomor Karyawan
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->employee_number }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Nama
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->full_name }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Jabatan
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->position }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Cabang
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            @if ($branch !== null)
                                <div class="fw-semibold">
                                    {{ $branch->code }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $branch->name }}
                                </div>
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Radius Geofence
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            @if ($branch !== null)
                                {{
                                    number_format(
                                        (float) $branch
                                            ->geofence_radius,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}
                                meter
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Batas Akurasi
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            @if ($branch !== null)
                                {{
                                    number_format(
                                        (float) $branch
                                            ->maximum_accuracy,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}
                                meter
                            @else
                                -
                            @endif
                        </dd>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-xl-6">
            <section class="content-card h-100 schedule-summary-card">
                <div class="border-bottom p-3 p-md-4">
                    <div class="attendance-section-header">
                        <span class="attendance-section-icon">
                            <i
                                class="bi bi-calendar2-check"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h2 class="attendance-section-title">
                                Jadwal Hari Ini
                            </h2>

                            <p class="attendance-section-copy">
                                Waktu operasional presensi berdasarkan jadwal.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 mb-2">
                            Tanggal
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatDate(
                                    $employeeSchedule
                                        ?->schedule_date
                                    ?? $currentMoment
                                )
                            }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Status Jadwal
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            <span
                                class="badge {{
                                    $scheduleStatusClass
                                }}"
                            >
                                {{ $scheduleStatusLabel }}
                            </span>
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Pola Jadwal
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $employeeSchedule
                                    ?->workSchedule
                                    ?->name
                                ?? '-'
                            }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Presensi Masuk Dibuka
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'check_in_opens_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Jadwal Masuk
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'scheduled_check_in_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Batas Tepat Waktu
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'late_limit_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Batas Presensi Masuk
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'check_in_limit_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Jadwal Pulang
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'scheduled_check_out_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Batas Presensi Pulang
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'check_out_limit_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>
                    </dl>
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4 attendance-overview-card">
        <div class="border-bottom p-3 p-md-4">
            <div class="attendance-section-header">
                <span class="attendance-section-icon">
                    <i
                        class="bi bi-clock-history"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h2 class="attendance-section-title">
                        Status Presensi Hari Ini
                    </h2>

                    <p class="attendance-section-copy">
                        Waktu yang ditampilkan berasal dari waktu server.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="attendance-status-card">
                        <div
                            class="d-flex justify-content-between
                                align-items-start gap-2 mb-3"
                        >
                            <div class="fw-semibold">
                                Presensi Masuk
                            </div>

                            @if ($checkInAttendance !== null)
                                <span class="badge text-bg-success">
                                    Tercatat
                                </span>
                            @else
                                <span
                                    class="badge text-bg-secondary"
                                >
                                    Belum
                                </span>
                            @endif
                        </div>

                        @if ($checkInAttendance !== null)
                            <div class="mb-2">
                                {{
                                    $formatDateTime(
                                        $checkInAttendance
                                            ->attendance_time
                                    )
                                }}
                                WIB
                            </div>

                            <div class="small text-secondary">
                                Status:
                                {{
                                    $checkInAttendance
                                        ->punctuality_status
                                    === 'late'
                                        ? 'Terlambat'
                                        : 'Tepat Waktu'
                                }}
                            </div>

                            <div class="small text-secondary">
                                Sumber:
                                {{
                                    $attendanceSourceLabel(
                                        $checkInAttendance
                                    )
                                }}
                            </div>

                            <div class="small text-secondary">
                                Jarak:
                                {{
                                    $attendanceDistanceLabel(
                                        $checkInAttendance
                                    )
                                }}
                            </div>
                        @else
                            <div class="small text-secondary">
                                Presensi masuk belum tercatat.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="attendance-status-card">
                        <div
                            class="d-flex justify-content-between
                                align-items-start gap-2 mb-3"
                        >
                            <div class="fw-semibold">
                                Presensi Pulang
                            </div>

                            @if ($checkOutAttendance !== null)
                                <span class="badge text-bg-success">
                                    Tercatat
                                </span>
                            @else
                                <span
                                    class="badge text-bg-secondary"
                                >
                                    Belum
                                </span>
                            @endif
                        </div>

                        @if ($checkOutAttendance !== null)
                            <div class="mb-2">
                                {{
                                    $formatDateTime(
                                        $checkOutAttendance
                                            ->attendance_time
                                    )
                                }}
                                WIB
                            </div>

                            <div class="small text-secondary">
                                Status: Presensi pulang diterima
                            </div>

                            <div class="small text-secondary">
                                Sumber:
                                {{
                                    $attendanceSourceLabel(
                                        $checkOutAttendance
                                    )
                                }}
                            </div>

                            <div class="small text-secondary">
                                Jarak:
                                {{
                                    $attendanceDistanceLabel(
                                        $checkOutAttendance
                                    )
                                }}
                            </div>
                        @else
                            <div class="small text-secondary">
                                Presensi pulang belum tercatat.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        id="geofence-validation-section"
        class="content-card mb-4 geofence-workspace"
    >
        <div
            class="border-bottom p-3 p-md-4 d-flex flex-column
                flex-md-row align-items-md-center
                justify-content-between gap-3"
        >
            <div>
                <div class="attendance-section-header">
                    <span class="attendance-section-icon">
                        <i
                            class="bi bi-geo-alt"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h2 class="attendance-section-title">
                            Validasi Lokasi dan Peta Geofence
                        </h2>

                        <p class="attendance-section-copy">
                            Pemeriksaan awal posisi, akurasi GPS, dan
                            estimasi jarak sebelum pemindaian QR.
                            Keputusan akhir tetap dilakukan oleh server.
                        </p>
                    </div>
                </div>
            </div>

            <span
                id="geofence-status-badge"
                class="badge text-bg-secondary px-3 py-2"
            >
                Belum diperiksa
            </span>
        </div>

        <div class="p-3 p-md-4">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-5">
                    <div class="geofence-summary h-100 p-3 p-md-4">
                        <div
                            id="geofence-location-alert"
                            class="alert alert-info"
                            role="status"
                            aria-live="polite"
                        >
                            Klik Periksa Lokasi untuk mengambil posisi
                            perangkat dan menghitung estimasi jarak.
                        </div>

                        <div class="d-grid gap-2 d-sm-flex mb-4">
                            <button
                                type="button"
                                id="check-location-button"
                                class="btn btn-primary"
                                @disabled(! $canScan)
                            >
                                <i
                                    class="bi bi-crosshair me-2"
                                    aria-hidden="true"
                                ></i>
                                Periksa Lokasi
                            </button>

                            <button
                                type="button"
                                id="recenter-map-button"
                                class="btn btn-outline-secondary"
                                disabled
                            >
                                <i
                                    class="bi bi-arrows-fullscreen me-2"
                                    aria-hidden="true"
                                ></i>
                                Pusatkan Peta
                            </button>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="geofence-metric">
                                    <div class="geofence-metric-label">
                                        Latitude Perangkat
                                    </div>

                                    <div
                                        id="location-latitude"
                                        class="geofence-metric-value
                                            location-value"
                                    >
                                        -
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="geofence-metric">
                                    <div class="geofence-metric-label">
                                        Longitude Perangkat
                                    </div>

                                    <div
                                        id="location-longitude"
                                        class="geofence-metric-value
                                            location-value"
                                    >
                                        -
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="geofence-metric">
                                    <div class="geofence-metric-label">
                                        Akurasi GPS
                                    </div>

                                    <div
                                        id="location-accuracy"
                                        class="geofence-metric-value"
                                    >
                                        -
                                    </div>

                                    <div
                                        id="location-accuracy-status"
                                        class="small text-secondary mt-1"
                                    >
                                        Batas:
                                        {{
                                            $branch !== null
                                                ? number_format(
                                                    (float) $branch
                                                        ->maximum_accuracy,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) . ' meter'
                                                : '-'
                                        }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="geofence-metric">
                                    <div class="geofence-metric-label">
                                        Estimasi Jarak
                                    </div>

                                    <div
                                        id="location-distance"
                                        class="geofence-metric-value"
                                    >
                                        -
                                    </div>

                                    <div class="small text-secondary mt-1">
                                        Radius:
                                        <span id="location-radius">
                                            {{
                                                $branch !== null
                                                    ? number_format(
                                                        (float) $branch
                                                            ->geofence_radius,
                                                        2,
                                                        ',',
                                                        '.'
                                                    ) . ' meter'
                                                    : '-'
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="geofence-status-panel mb-4"
                            aria-live="polite"
                        >
                            <div class="small text-secondary mb-1">
                                Status Geofence
                            </div>

                            <div
                                id="location-status"
                                class="fw-bold"
                            >
                                Belum diperiksa
                            </div>
                        </div>

                        <div class="geofence-legend">
                            <span class="geofence-legend-item">
                                <span
                                    class="geofence-legend-dot branch"
                                ></span>
                                Titik Cabang
                            </span>

                            <span class="geofence-legend-item">
                                <span
                                    class="geofence-legend-dot device"
                                ></span>
                                Lokasi Perangkat
                            </span>

                            <span class="geofence-legend-item">
                                <span
                                    class="geofence-legend-dot radius"
                                ></span>
                                Radius Geofence
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div
                        id="geofence-map"
                        class="geofence-map"
                        role="region"
                        aria-label="Peta lokasi cabang dan perangkat"
                    ></div>

                    <p class="small text-secondary mt-2 mb-0">
                        Peta digunakan sebagai visualisasi. Data
                        presensi tetap divalidasi ulang oleh server
                        menggunakan Formula Haversine.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="content-card scanner-panel">
        <div class="border-bottom p-3 p-md-4">
            <div class="attendance-section-header">
                <span class="attendance-section-icon">
                    <i
                        class="bi bi-qr-code-scan"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h2 class="attendance-section-title">
                        Pemindai QR Code
                    </h2>

                    <p class="attendance-section-copy">
                        Kamera tersedia setelah lokasi awal memenuhi
                        ketentuan geofence.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <div class="scanner-wrapper">
                <p class="scanner-tip">
                    <i class="bi bi-bounding-box-circles" aria-hidden="true"></i>
                    Posisikan seluruh QR terminal di dalam bingkai kamera.
                </p>
                <div
                    id="scanner-alert"
                    class="alert {{
                        $canScan
                            ? 'alert-info'
                            : 'alert-warning'
                    }}"
                    role="status"
                    aria-live="polite"
                >
                    @if ($canScan)
                        Periksa lokasi terlebih dahulu. Kamera hanya
                        dapat digunakan setelah lokasi awal memenuhi
                        ketentuan geofence.
                    @else
                        {{
                            $scanBlockReason
                            ?? 'Pemindai tidak tersedia.'
                        }}
                    @endif
                </div>

                <div
                    id="qr-reader"
                    class="scanner-stage mb-3"
                >
                    <div
                        id="scanner-placeholder"
                        class="d-flex align-items-center
                            justify-content-center
                            text-center h-100 p-4"
                        style="min-height: 340px;"
                    >
                        <div class="scanner-empty-state">
                            <span class="scanner-empty-icon">
                                <i
                                    class="bi bi-qr-code-scan"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <span class="scanner-empty-title">
                                Kamera belum dinyalakan
                            </span>

                            <span class="scanner-empty-copy">
                                Periksa lokasi terlebih dahulu.
                                Kamera akan tersedia setelah posisi
                                memenuhi radius dan batas akurasi.
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row
                        justify-content-center gap-2 mb-4"
                >
                    <button
                        type="button"
                        id="start-scanner-button"
                        class="btn btn-primary"
                        @disabled(! $canScan)
                    >
                        <i
                            class="bi bi-camera me-2"
                            aria-hidden="true"
                        ></i>
                        Mulai Kamera dan Pindai QR
                    </button>

                    <button
                        type="button"
                        id="stop-scanner-button"
                        class="btn btn-outline-secondary"
                        disabled
                    >
                        <i
                            class="bi bi-camera-video-off me-2"
                            aria-hidden="true"
                        ></i>
                        Matikan Kamera
                    </button>

                    <button
                        type="button"
                        id="reset-scanner-button"
                        class="btn btn-outline-primary"
                        disabled
                    >
                        <i
                            class="bi bi-arrow-clockwise me-2"
                            aria-hidden="true"
                        ></i>
                        Pindai Ulang
                    </button>
                </div>

                <div
                    id="processing-indicator"
                    class="alert alert-light border d-none"
                    role="status"
                >
                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="spinner-border
                                processing-spinner"
                            aria-hidden="true"
                        ></span>

                        <span id="processing-message">
                            Memproses presensi...
                        </span>
                    </div>
                </div>

                <div
                    id="attendance-result"
                    class="d-none"
                    aria-live="polite"
                ></div>
            </div>
        </div>
    </section>

    <section class="content-card p-3 p-md-4 mt-4 usage-panel">
        <div class="attendance-section-header mb-4">
            <span class="attendance-section-icon">
                <i
                    class="bi bi-info-circle"
                    aria-hidden="true"
                ></i>
            </span>

            <div>
                <h2 class="attendance-section-title">
                    Ketentuan Penggunaan
                </h2>

                <p class="attendance-section-copy">
                    Ikuti tahapan berikut agar validasi berjalan stabil.
                </p>
            </div>
        </div>

        <ol class="usage-list">
            <li class="mb-2">
                Izinkan browser mengakses kamera dan lokasi
                perangkat.
            </li>

            <li class="mb-2">
                Pindai QR Code yang sedang aktif pada cabang
                penempatan.
            </li>

            <li class="mb-2">
                Tetap berada di dalam area toko atau halaman
                depan ruko yang termasuk radius geofence.
            </li>

            <li class="mb-2">
                Tunggu sampai informasi lokasi diperoleh dan
                server menyelesaikan validasi.
            </li>

            <li>
                Jangan menutup halaman selama transaksi sedang
                diproses.
            </li>
        </ol>
    </section>
    </div>
@endsection

@push('scripts')
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>

    <script
        src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"
    ></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canScan = @json($canScan);

            const scanBlockReason = @json(
                $scanBlockReason
            );

            const attendanceEndpoint = @json(
                route('attendance.store', [], false)
            );

            const csrfToken = @json(csrf_token());
            const employeeDisplayName = @json($employee->full_name);

            const toNullableNumber = function (value) {
                if (
                    value === null
                    || value === undefined
                    || value === ''
                ) {
                    return null;
                }

                const parsedValue = Number(value);

                return Number.isFinite(parsedValue)
                    ? parsedValue
                    : null;
            };

            const branchLatitude = toNullableNumber(
                @json($branch?->latitude)
            );

            const branchLongitude = toNullableNumber(
                @json($branch?->longitude)
            );

            const geofenceRadius = toNullableNumber(
                @json($branch?->geofence_radius)
            );

            const maximumAccuracy = toNullableNumber(
                @json($branch?->maximum_accuracy)
            );

            const branchCode = @json(
                (string) ($branch?->code ?? '-')
            );

            const branchName = @json(
                (string) ($branch?->name ?? '-')
            );

            const hasMapConfiguration =
                branchLatitude !== null
                && branchLatitude >= -90
                && branchLatitude <= 90
                && branchLongitude !== null
                && branchLongitude >= -180
                && branchLongitude <= 180
                && geofenceRadius !== null
                && geofenceRadius > 0
                && maximumAccuracy !== null
                && maximumAccuracy > 0;

            const startButton = document.getElementById(
                'start-scanner-button'
            );

            const stopButton = document.getElementById(
                'stop-scanner-button'
            );

            const resetButton = document.getElementById(
                'reset-scanner-button'
            );

            const scannerAlert = document.getElementById(
                'scanner-alert'
            );

            const processingIndicator =
                document.getElementById(
                    'processing-indicator'
                );

            const processingMessage =
                document.getElementById(
                    'processing-message'
                );

            const attendanceResult =
                document.getElementById(
                    'attendance-result'
                );

            const latitudeElement =
                document.getElementById(
                    'location-latitude'
                );

            const longitudeElement =
                document.getElementById(
                    'location-longitude'
                );

            const accuracyElement =
                document.getElementById(
                    'location-accuracy'
                );

            const distanceElement =
                document.getElementById(
                    'location-distance'
                );

            const locationStatusElement =
                document.getElementById(
                    'location-status'
                );

            const accuracyStatusElement =
                document.getElementById(
                    'location-accuracy-status'
                );

            const geofenceStatusBadge =
                document.getElementById(
                    'geofence-status-badge'
                );

            const geofenceLocationAlert =
                document.getElementById(
                    'geofence-location-alert'
                );

            const checkLocationButton =
                document.getElementById(
                    'check-location-button'
                );

            const recenterMapButton =
                document.getElementById(
                    'recenter-map-button'
                );

            const mapElement =
                document.getElementById(
                    'geofence-map'
                );

            let qrScanner = null;
            let scannerRunning = false;
            let requestInProgress = false;
            let attendanceAccepted = false;
            let locationCheckInProgress = false;
            let locationReady = false;
            let latestCoordinates = null;
            let latestDistance = null;
            let geofenceMap = null;
            let branchMarker = null;
            let deviceMarker = null;
            let geofenceCircle = null;
            let distanceLine = null;

            const setScannerAlert = function (
                message,
                alertClass
            ) {
                if (scannerAlert === null) {
                    return;
                }

                scannerAlert.className =
                    'alert ' + alertClass;

                scannerAlert.textContent = message;
            };

            const setProcessing = function (
                isProcessing,
                message
            ) {
                if (processingIndicator !== null) {
                    processingIndicator.classList.toggle(
                        'd-none',
                        ! isProcessing
                    );
                }

                if (
                    processingMessage !== null
                    && typeof message === 'string'
                ) {
                    processingMessage.textContent = message;
                }
            };

            const updateButtons = function () {
                if (startButton !== null) {
                    startButton.disabled =
                        ! canScan
                        || ! locationReady
                        || scannerRunning
                        || requestInProgress
                        || locationCheckInProgress
                        || attendanceAccepted;
                }

                if (stopButton !== null) {
                    stopButton.disabled =
                        ! scannerRunning
                        || requestInProgress;
                }

                if (resetButton !== null) {
                    resetButton.disabled =
                        ! canScan
                        || scannerRunning
                        || requestInProgress
                        || locationCheckInProgress
                        || attendanceAccepted;
                }

                if (checkLocationButton !== null) {
                    checkLocationButton.disabled =
                        ! canScan
                        || ! hasMapConfiguration
                        || scannerRunning
                        || requestInProgress
                        || locationCheckInProgress
                        || attendanceAccepted;
                }

                if (recenterMapButton !== null) {
                    recenterMapButton.disabled =
                        geofenceMap === null;
                }
            };

            const displayResult = function (
                success,
                message,
                data
            ) {
                if (attendanceResult === null) {
                    return;
                }

                attendanceResult.className =
                    success
                        ? 'attendance-result-card is-success'
                        : 'attendance-result-card is-error';

                attendanceResult.innerHTML = '';

                const icon = document.createElement('span');
                icon.className = 'attendance-result-icon';
                icon.setAttribute('aria-hidden', 'true');
                icon.innerHTML = success
                    ? '<i class="bi bi-check-circle-fill"></i>'
                    : '<i class="bi bi-exclamation-circle-fill"></i>';

                const content = document.createElement('div');
                content.className = 'min-w-0';

                const title =
                    document.createElement('h3');

                title.className = 'attendance-result-title';

                title.textContent =
                    success
                        ? 'Presensi berhasil dicatat'
                        : 'Presensi belum berhasil';

                const messageElement =
                    document.createElement('div');

                messageElement.textContent = message;

                content.appendChild(title);
                content.appendChild(messageElement);

                if (
                    success
                    && data !== null
                    && typeof data === 'object'
                ) {
                    const details =
                        document.createElement('div');

                    details.className =
                        'attendance-result-details';

                    const attendanceType =
                        data.attendance_type === 'check_out'
                            ? 'Presensi Pulang'
                            : 'Presensi Masuk';

                    const attendanceAt = new Date(
                        data.attendance_time ?? ''
                    );

                    const timeLabel = Number.isNaN(
                        attendanceAt.getTime()
                    )
                        ? 'Waktu server tercatat'
                        : new Intl.DateTimeFormat(
                            'id-ID',
                            {
                                timeZone: 'Asia/Jakarta',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false,
                            }
                        ).format(attendanceAt) + ' WIB';

                    const punctualityLabel =
                        data.punctuality_status === 'late'
                            ? 'Terlambat'
                            : data.punctuality_status === 'on_time'
                                ? 'Tepat waktu'
                                : 'Tercatat';

                    [
                        employeeDisplayName,
                        attendanceType
                            + ' · '
                            + timeLabel
                            + ' · '
                            + punctualityLabel,
                    ].forEach(function (value) {
                        const line = document.createElement('span');
                        line.textContent = value;
                        details.appendChild(line);
                    });

                    content.appendChild(details);
                }

                attendanceResult.appendChild(icon);
                attendanceResult.appendChild(content);
            };

            const clearResult = function () {
                if (attendanceResult === null) {
                    return;
                }

                attendanceResult.className = 'd-none';
                attendanceResult.innerHTML = '';
            };

            const setGeofenceVisualStatus = function (
                status,
                message,
                badgeClass,
                alertClass
            ) {
                if (locationStatusElement !== null) {
                    locationStatusElement.textContent = status;
                }

                if (geofenceStatusBadge !== null) {
                    geofenceStatusBadge.className =
                        'badge ' + badgeClass + ' px-3 py-2';

                    geofenceStatusBadge.textContent = status;
                }

                if (geofenceLocationAlert !== null) {
                    geofenceLocationAlert.className =
                        'alert ' + alertClass;

                    geofenceLocationAlert.textContent = message;
                }
            };

            const updateLocationDisplay = function (
                coordinates,
                distance
            ) {
                if (latitudeElement !== null) {
                    latitudeElement.textContent =
                        coordinates.latitude.toFixed(8);
                }

                if (longitudeElement !== null) {
                    longitudeElement.textContent =
                        coordinates.longitude.toFixed(8);
                }

                if (accuracyElement !== null) {
                    accuracyElement.textContent =
                        coordinates.accuracy.toFixed(2)
                        + ' meter';
                }

                if (distanceElement !== null) {
                    distanceElement.textContent =
                        distance.toFixed(2)
                        + ' meter';
                }

                if (accuracyStatusElement !== null) {
                    const accuracyIsValid =
                        coordinates.accuracy
                        <= maximumAccuracy;

                    accuracyStatusElement.className =
                        accuracyIsValid
                            ? 'small text-success mt-1'
                            : 'small text-danger mt-1';

                    accuracyStatusElement.textContent =
                        accuracyIsValid
                            ? 'Akurasi memenuhi batas '
                                + maximumAccuracy.toFixed(2)
                                + ' meter.'
                            : 'Akurasi melebihi batas '
                                + maximumAccuracy.toFixed(2)
                                + ' meter.';
                }
            };

            const clearLocationDisplay = function () {
                if (latitudeElement !== null) {
                    latitudeElement.textContent = '-';
                }

                if (longitudeElement !== null) {
                    longitudeElement.textContent = '-';
                }

                if (accuracyElement !== null) {
                    accuracyElement.textContent = '-';
                }

                if (distanceElement !== null) {
                    distanceElement.textContent = '-';
                }

                if (accuracyStatusElement !== null) {
                    accuracyStatusElement.className =
                        'small text-secondary mt-1';

                    accuracyStatusElement.textContent =
                        hasMapConfiguration
                            ? 'Batas: '
                                + maximumAccuracy.toFixed(2)
                                + ' meter'
                            : 'Batas: -';
                }

                if (! canScan) {
                    setGeofenceVisualStatus(
                        'Pemeriksaan Lokasi Tidak Tersedia',
                        scanBlockReason
                            ?? 'Pemeriksaan lokasi tidak tersedia saat ini.',
                        'text-bg-secondary',
                        'alert-warning'
                    );

                    return;
                }

                setGeofenceVisualStatus(
                    'Belum diperiksa',
                    'Klik Periksa Lokasi untuk mengambil posisi '
                        + 'perangkat dan menghitung estimasi jarak.',
                    'text-bg-secondary',
                    'alert-info'
                );
            };

            const toRadians = function (degrees) {
                return degrees * Math.PI / 180;
            };

            const calculateHaversineDistance = function (
                originLatitude,
                originLongitude,
                destinationLatitude,
                destinationLongitude
            ) {
                const earthRadiusMeters = 6371000;

                const latitudeDifference = toRadians(
                    destinationLatitude - originLatitude
                );

                const longitudeDifference = toRadians(
                    destinationLongitude - originLongitude
                );

                const originLatitudeRadians =
                    toRadians(originLatitude);

                const destinationLatitudeRadians =
                    toRadians(destinationLatitude);

                const latitudeComponent =
                    Math.sin(latitudeDifference / 2);

                const longitudeComponent =
                    Math.sin(longitudeDifference / 2);

                const haversine =
                    (latitudeComponent ** 2)
                    + Math.cos(originLatitudeRadians)
                    * Math.cos(destinationLatitudeRadians)
                    * (longitudeComponent ** 2);

                const normalizedHaversine = Math.min(
                    1,
                    Math.max(0, haversine)
                );

                const centralAngle = 2 * Math.atan2(
                    Math.sqrt(normalizedHaversine),
                    Math.sqrt(1 - normalizedHaversine)
                );

                return earthRadiusMeters * centralAngle;
            };

            const initializeMap = function () {
                if (
                    mapElement === null
                    || ! hasMapConfiguration
                    || typeof L === 'undefined'
                ) {
                    if (mapElement !== null) {
                        mapElement.innerHTML =
                            '<div class="d-flex align-items-center '
                            + 'justify-content-center h-100 p-4 '
                            + 'text-center text-secondary">'
                            + 'Peta tidak dapat ditampilkan karena '
                            + 'konfigurasi lokasi cabang atau pustaka '
                            + 'peta belum tersedia.</div>';
                    }

                    return;
                }

                geofenceMap = L.map(
                    mapElement,
                    {
                        zoomControl: true,
                        scrollWheelZoom: false,
                    }
                ).setView(
                    [
                        branchLatitude,
                        branchLongitude,
                    ],
                    18
                );

                L.tileLayer(
                    'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                    {
                        maxZoom: 19,
                        attribution:
                            '&copy; OpenStreetMap contributors',
                    }
                ).addTo(geofenceMap);

                geofenceCircle = L.circle(
                    [
                        branchLatitude,
                        branchLongitude,
                    ],
                    {
                        radius: geofenceRadius,
                        color: '#198754',
                        fillColor: '#198754',
                        fillOpacity: 0.12,
                        weight: 2,
                    }
                ).addTo(geofenceMap);

                const branchPopupContent =
                    document.createElement('div');

                const branchPopupTitle =
                    document.createElement('strong');

                branchPopupTitle.textContent =
                    'Lokasi Cabang';

                const branchPopupDetail =
                    document.createElement('div');

                branchPopupDetail.textContent =
                    branchCode
                    + ' - '
                    + branchName;

                const branchPopupRadius =
                    document.createElement('div');

                branchPopupRadius.textContent =
                    'Radius '
                    + geofenceRadius.toFixed(2)
                    + ' meter';

                branchPopupContent.appendChild(
                    branchPopupTitle
                );

                branchPopupContent.appendChild(
                    branchPopupDetail
                );

                branchPopupContent.appendChild(
                    branchPopupRadius
                );

                branchMarker = L.circleMarker(
                    [
                        branchLatitude,
                        branchLongitude,
                    ],
                    {
                        radius: 8,
                        color: '#ffffff',
                        fillColor: '#e56a1f',
                        fillOpacity: 1,
                        weight: 3,
                    }
                )
                    .addTo(geofenceMap)
                    .bindPopup(branchPopupContent);

                geofenceMap.fitBounds(
                    geofenceCircle.getBounds(),
                    {
                        padding: [24, 24],
                    }
                );

                window.setTimeout(
                    function () {
                        geofenceMap.invalidateSize();
                    },
                    100
                );
            };

            const updateMapLocation = function (
                coordinates
            ) {
                if (geofenceMap === null) {
                    return;
                }

                const devicePosition = [
                    coordinates.latitude,
                    coordinates.longitude,
                ];

                if (deviceMarker !== null) {
                    geofenceMap.removeLayer(deviceMarker);
                }

                if (distanceLine !== null) {
                    geofenceMap.removeLayer(distanceLine);
                }

                deviceMarker = L.circleMarker(
                    devicePosition,
                    {
                        radius: 8,
                        color: '#ffffff',
                        fillColor: '#353b41',
                        fillOpacity: 1,
                        weight: 3,
                    }
                )
                    .addTo(geofenceMap)
                    .bindPopup(
                        '<strong>Lokasi Perangkat</strong><br>'
                        + 'Akurasi '
                        + coordinates.accuracy.toFixed(2)
                        + ' meter'
                    );

                distanceLine = L.polyline(
                    [
                        [
                            branchLatitude,
                            branchLongitude,
                        ],
                        devicePosition,
                    ],
                    {
                        color: '#7a828b',
                        weight: 2,
                        dashArray: '6, 6',
                    }
                ).addTo(geofenceMap);

                const visibleLayers = L.featureGroup([
                    geofenceCircle,
                    branchMarker,
                    deviceMarker,
                ]);

                geofenceMap.fitBounds(
                    visibleLayers.getBounds(),
                    {
                        padding: [32, 32],
                        maxZoom: 19,
                    }
                );
            };

            const evaluateLocation = function (
                coordinates
            ) {
                if (! hasMapConfiguration) {
                    throw new Error(
                        'Konfigurasi geofence cabang belum lengkap.'
                    );
                }

                const distance =
                    calculateHaversineDistance(
                        branchLatitude,
                        branchLongitude,
                        coordinates.latitude,
                        coordinates.longitude
                    );

                const accuracyIsValid =
                    coordinates.accuracy
                    <= maximumAccuracy;

                const isInsideGeofence =
                    distance <= geofenceRadius;

                latestCoordinates = coordinates;
                latestDistance = distance;
                locationReady =
                    accuracyIsValid
                    && isInsideGeofence;

                updateLocationDisplay(
                    coordinates,
                    distance
                );

                updateMapLocation(
                    coordinates
                );

                if (! accuracyIsValid) {
                    setGeofenceVisualStatus(
                        'Akurasi GPS Tidak Memadai',
                        'Akurasi lokasi '
                            + coordinates.accuracy.toFixed(2)
                            + ' meter melebihi batas '
                            + maximumAccuracy.toFixed(2)
                            + ' meter. Berpindah ke area terbuka '
                            + 'dan periksa kembali.',
                        'text-bg-warning',
                        'alert-warning'
                    );
                } else if (! isInsideGeofence) {
                    setGeofenceVisualStatus(
                        'Di Luar Geofence',
                        'Perangkat berada sekitar '
                            + distance.toFixed(2)
                            + ' meter dari cabang, melebihi radius '
                            + geofenceRadius.toFixed(2)
                            + ' meter.',
                        'text-bg-danger',
                        'alert-danger'
                    );
                } else {
                    setGeofenceVisualStatus(
                        'Di Dalam Geofence',
                        'Lokasi awal memenuhi syarat. Perangkat '
                            + 'berada sekitar '
                            + distance.toFixed(2)
                            + ' meter dari cabang dan kamera '
                            + 'sudah dapat digunakan.',
                        'text-bg-success',
                        'alert-success'
                    );
                }

                updateButtons();

                return {
                    distance: distance,
                    accuracyIsValid: accuracyIsValid,
                    isInsideGeofence: isInsideGeofence,
                    ready: locationReady,
                };
            };

            const checkCurrentLocation = async function () {
                if (
                    ! canScan
                    || ! hasMapConfiguration
                    || locationCheckInProgress
                    || requestInProgress
                    || attendanceAccepted
                ) {
                    return;
                }

                locationCheckInProgress = true;
                locationReady = false;
                updateButtons();

                setGeofenceVisualStatus(
                    'Memeriksa Lokasi',
                    'GPS sedang mencari pembacaan terbaik. '
                        + 'Tunggu beberapa detik dan tetap berada '
                        + 'di area terbuka.',
                    'text-bg-info',
                    'alert-info'
                );

                try {
                    const coordinates =
                        await getBestCurrentLocation(
                            function (bestCoordinates) {
                                const previewDistance =
                                    calculateHaversineDistance(
                                        branchLatitude,
                                        branchLongitude,
                                        bestCoordinates.latitude,
                                        bestCoordinates.longitude
                                    );

                                updateLocationDisplay(
                                    bestCoordinates,
                                    previewDistance
                                );

                                updateMapLocation(
                                    bestCoordinates
                                );

                                setGeofenceVisualStatus(
                                    'Meningkatkan Akurasi GPS',
                                    'Pembacaan terbaik sementara: '
                                        + bestCoordinates.accuracy
                                            .toFixed(2)
                                        + ' meter. Target maksimal: '
                                        + maximumAccuracy.toFixed(2)
                                        + ' meter.',
                                    'text-bg-info',
                                    'alert-info'
                                );
                            }
                        );

                    evaluateLocation(
                        coordinates
                    );
                } catch (error) {
                    const message =
                        error instanceof Error
                            ? error.message
                            : 'Lokasi perangkat tidak dapat diperoleh.';

                    setGeofenceVisualStatus(
                        'Lokasi Tidak Tersedia',
                        message,
                        'text-bg-danger',
                        'alert-danger'
                    );
                } finally {
                    locationCheckInProgress = false;
                    updateButtons();
                }
            };

            const recenterMap = function () {
                if (geofenceMap === null) {
                    return;
                }

                if (
                    latestCoordinates !== null
                    && deviceMarker !== null
                ) {
                    const visibleLayers = L.featureGroup([
                        geofenceCircle,
                        branchMarker,
                        deviceMarker,
                    ]);

                    geofenceMap.fitBounds(
                        visibleLayers.getBounds(),
                        {
                            padding: [32, 32],
                            maxZoom: 19,
                        }
                    );

                    return;
                }

                geofenceMap.fitBounds(
                    geofenceCircle.getBounds(),
                    {
                        padding: [24, 24],
                    }
                );
            };

            const firstValidationMessage = function (
                responseBody
            ) {
                if (
                    responseBody === null
                    || typeof responseBody !== 'object'
                ) {
                    return null;
                }

                const errors = responseBody.errors;

                if (
                    errors === null
                    || typeof errors !== 'object'
                ) {
                    return null;
                }

                for (const messages of Object.values(errors)) {
                    if (
                        Array.isArray(messages)
                        && typeof messages[0] === 'string'
                    ) {
                        return messages[0];
                    }

                    if (typeof messages === 'string') {
                        return messages;
                    }
                }

                return null;
            };

            const locationErrorMessage = function (
                error
            ) {
                if (error === null || error === undefined) {
                    return 'Lokasi perangkat tidak dapat diperoleh.';
                }

                switch (error.code) {
                    case 1:
                        return 'Izin lokasi ditolak. Aktifkan izin lokasi pada browser.';

                    case 2:
                        return 'Posisi perangkat tidak tersedia. Aktifkan GPS dan coba kembali.';

                    case 3:
                        return 'Pengambilan lokasi melewati batas waktu. Coba kembali.';

                    default:
                        return 'Lokasi perangkat tidak dapat diperoleh.';
                }
            };

            const getBestCurrentLocation = function (
                onProgress = null
            ) {
                return new Promise(function (
                    resolve,
                    reject
                ) {
                    if (
                        typeof navigator.geolocation
                        === 'undefined'
                    ) {
                        reject(
                            new Error(
                                'Browser tidak mendukung pengambilan lokasi.'
                            )
                        );

                        return;
                    }

                    const maximumWaitMilliseconds = 30000;
                    let bestCoordinates = null;
                    let watchId = null;
                    let settled = false;

                    const cleanup = function () {
                        if (watchId !== null) {
                            navigator.geolocation.clearWatch(
                                watchId
                            );
                        }

                        window.clearTimeout(timeoutId);
                    };

                    const resolveBestCoordinates = function () {
                        if (settled) {
                            return;
                        }

                        settled = true;
                        cleanup();

                        if (bestCoordinates !== null) {
                            resolve(bestCoordinates);

                            return;
                        }

                        reject(
                            new Error(
                                'Lokasi perangkat belum berhasil diperoleh.'
                            )
                        );
                    };

                    const timeoutId = window.setTimeout(
                        resolveBestCoordinates,
                        maximumWaitMilliseconds
                    );

                    watchId = navigator.geolocation
                        .watchPosition(
                            function (position) {
                                const coordinates = {
                                    latitude:
                                        position.coords.latitude,

                                    longitude:
                                        position.coords.longitude,

                                    accuracy:
                                        position.coords.accuracy,
                                };

                                if (
                                    ! Number.isFinite(
                                        coordinates.latitude
                                    )
                                    || ! Number.isFinite(
                                        coordinates.longitude
                                    )
                                    || ! Number.isFinite(
                                        coordinates.accuracy
                                    )
                                    || coordinates.accuracy <= 0
                                ) {
                                    return;
                                }

                                if (
                                    bestCoordinates === null
                                    || coordinates.accuracy
                                        < bestCoordinates.accuracy
                                ) {
                                    bestCoordinates = coordinates;

                                    if (
                                        typeof onProgress
                                        === 'function'
                                    ) {
                                        onProgress(
                                            bestCoordinates
                                        );
                                    }
                                }

                                if (
                                    hasMapConfiguration
                                    && bestCoordinates.accuracy
                                        <= maximumAccuracy
                                ) {
                                    resolveBestCoordinates();
                                }
                            },

                            function (error) {
                                if (
                                    error !== null
                                    && error.code === 1
                                ) {
                                    if (settled) {
                                        return;
                                    }

                                    settled = true;
                                    cleanup();

                                    reject(
                                        new Error(
                                            locationErrorMessage(
                                                error
                                            )
                                        )
                                    );
                                }
                            },

                            {
                                enableHighAccuracy: true,
                                maximumAge: 0,
                                timeout: maximumWaitMilliseconds,
                            }
                        );
                });
            };

            const stopScanner = async function () {
                if (
                    qrScanner === null
                    || ! scannerRunning
                ) {
                    scannerRunning = false;
                    updateButtons();

                    return;
                }

                try {
                    await qrScanner.stop();
                } catch (error) {
                    console.error(
                        'Kamera gagal dihentikan.',
                        error
                    );
                } finally {
                    scannerRunning = false;
                    updateButtons();
                }
            };

            const submitAttendance = async function (
                qrPayload,
                coordinates
            ) {
                const response = await fetch(
                    attendanceEndpoint,
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrfToken,

                            'X-Requested-With':
                                'XMLHttpRequest',
                        },

                        credentials: 'same-origin',

                        cache: 'no-store',

                        body: JSON.stringify({
                            qr_payload: qrPayload,

                            latitude:
                                coordinates.latitude,

                            longitude:
                                coordinates.longitude,

                            accuracy:
                                coordinates.accuracy,
                        }),
                    }
                );

                const responseBody =
                    await response
                        .json()
                        .catch(function () {
                            return {};
                        });

                if (! response.ok) {
                    const validationMessage =
                        firstValidationMessage(
                            responseBody
                        );

                    throw new Error(
                        validationMessage
                        ?? responseBody.message
                        ?? 'Presensi tidak dapat diproses.'
                    );
                }

                return responseBody;
            };

            const processScannedPayload = async function (
                decodedText
            ) {
                if (
                    requestInProgress
                    || attendanceAccepted
                ) {
                    return;
                }

                const qrPayload =
                    String(decodedText ?? '').trim();

                if (qrPayload === '') {
                    return;
                }

                requestInProgress = true;
                updateButtons();
                clearResult();

                await stopScanner();

                setScannerAlert(
                    'QR Code terbaca. Mengambil lokasi perangkat...',
                    'alert-info'
                );

                setProcessing(
                    true,
                    'Mengambil lokasi perangkat...'
                );

                try {
                    const coordinates =
                        await getBestCurrentLocation(
                            function (bestCoordinates) {
                                setProcessing(
                                    true,
                                    'Meningkatkan akurasi GPS: '
                                        + bestCoordinates.accuracy
                                            .toFixed(2)
                                        + ' meter...'
                                );
                            }
                        );

                    evaluateLocation(
                        coordinates
                    );

                    setProcessing(
                        true,
                        'Memvalidasi presensi pada server...'
                    );

                    const responseBody =
                        await submitAttendance(
                            qrPayload,
                            coordinates
                        );

                    attendanceAccepted = true;

                    displayResult(
                        true,
                        responseBody.message
                        ?? 'Presensi berhasil dicatat.',
                        responseBody.data ?? null
                    );

                    setScannerAlert(
                        'Presensi berhasil dicatat.',
                        'alert-success'
                    );

                    setProcessing(false);

                    window.setTimeout(
                        function () {
                            window.location.reload();
                        },
                        2500
                    );
                } catch (error) {
                    const message =
                        error instanceof Error
                            ? error.message
                            : 'Presensi tidak dapat diproses.';

                    displayResult(
                        false,
                        message,
                        null
                    );

                    setScannerAlert(
                        message,
                        'alert-danger'
                    );

                    setProcessing(false);
                } finally {
                    requestInProgress = false;
                    updateButtons();
                }
            };

            const startScanner = async function () {
                if (
                    ! canScan
                    || scannerRunning
                    || requestInProgress
                    || attendanceAccepted
                ) {
                    return;
                }

                if (! locationReady) {
                    setScannerAlert(
                        'Periksa lokasi dan pastikan perangkat '
                            + 'berada di dalam geofence terlebih dahulu.',
                        'alert-warning'
                    );

                    return;
                }

                clearResult();

                setScannerAlert(
                    'Meminta izin kamera...',
                    'alert-info'
                );

                if (
                    typeof Html5Qrcode === 'undefined'
                    || typeof Html5QrcodeSupportedFormats
                        === 'undefined'
                ) {
                    setScannerAlert(
                        'Pustaka pemindai QR tidak dapat dimuat.',
                        'alert-danger'
                    );

                    return;
                }

                if (qrScanner === null) {
                    qrScanner = new Html5Qrcode(
                        'qr-reader',
                        {
                            formatsToSupport: [
                                Html5QrcodeSupportedFormats
                                    .QR_CODE,
                            ],
                        }
                    );
                }

                try {
                    await qrScanner.start(
                        {
                            facingMode: 'environment',
                        },

                        {
                            fps: 10,

                            qrbox: {
                                width: 250,
                                height: 250,
                            },

                            aspectRatio: 1.0,

                            disableFlip: false,
                        },

                        function (
                            decodedText
                        ) {
                            processScannedPayload(
                                decodedText
                            );
                        },

                        function () {
                            /*
                             * Kesalahan per frame diabaikan
                             * karena normal ketika QR belum
                             * berada di depan kamera.
                             */
                        }
                    );

                    scannerRunning = true;

                    setScannerAlert(
                        'Kamera aktif. Arahkan kamera ke QR Code.',
                        'alert-success'
                    );
                } catch (error) {
                    scannerRunning = false;

                    setScannerAlert(
                        error instanceof Error
                            ? error.message
                            : 'Kamera tidak dapat dinyalakan.',
                        'alert-danger'
                    );
                } finally {
                    updateButtons();
                }
            };

            const resetScanner = async function () {
                await stopScanner();

                requestInProgress = false;
                attendanceAccepted = false;
                locationReady = false;
                latestCoordinates = null;
                latestDistance = null;

                if (
                    geofenceMap !== null
                    && deviceMarker !== null
                ) {
                    geofenceMap.removeLayer(deviceMarker);
                    deviceMarker = null;
                }

                if (
                    geofenceMap !== null
                    && distanceLine !== null
                ) {
                    geofenceMap.removeLayer(distanceLine);
                    distanceLine = null;
                }

                clearResult();
                clearLocationDisplay();
                setProcessing(false);

                setScannerAlert(
                    'Periksa lokasi terlebih dahulu sebelum '
                        + 'menyalakan kamera.',
                    'alert-info'
                );

                recenterMap();

                updateButtons();
            };

            if (
                canScan
                && checkLocationButton !== null
            ) {
                checkLocationButton.addEventListener(
                    'click',
                    checkCurrentLocation
                );
            }

            if (recenterMapButton !== null) {
                recenterMapButton.addEventListener(
                    'click',
                    recenterMap
                );
            }

            if (startButton !== null) {
                startButton.addEventListener(
                    'click',
                    startScanner
                );
            }

            if (stopButton !== null) {
                stopButton.addEventListener(
                    'click',
                    async function () {
                        await stopScanner();

                        setScannerAlert(
                            'Kamera telah dimatikan.',
                            'alert-secondary'
                        );
                    }
                );
            }

            if (resetButton !== null) {
                resetButton.addEventListener(
                    'click',
                    resetScanner
                );
            }

            window.addEventListener(
                'beforeunload',
                function () {
                    if (
                        qrScanner !== null
                        && scannerRunning
                    ) {
                        qrScanner.stop().catch(
                            function () {
                                return null;
                            }
                        );
                    }
                }
            );

            initializeMap();
            clearLocationDisplay();
            updateButtons();
        });
    </script>
@endpush
