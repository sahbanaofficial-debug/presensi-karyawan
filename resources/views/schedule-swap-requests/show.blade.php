@extends('layouts.app')

@section('title', 'Detail Pertukaran Jadwal')

@push('styles')
    <style>
        .schedule-swap-detail-page {
            --swap-surface: var(--neutral-0);
            --swap-border: var(--neutral-200);
            --swap-muted: var(--neutral-600);
            --swap-soft: var(--brand-50);
        }

        .swap-detail-card,
        .swap-profile-card,
        .swap-schedule-card,
        .swap-reason-card,
        .swap-decision-card,
        .swap-action-card {
            overflow: hidden;
            border: 1px solid var(--swap-border);
            border-radius: var(--radius-lg);
            background: var(--swap-surface);
            box-shadow: var(--shadow-xs);
        }

        .swap-detail-card {
            margin-bottom: var(--space-5);
        }

        .swap-detail-card-header,
        .swap-section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--swap-border);
            background: var(--neutral-25);
        }

        .swap-detail-eyebrow {
            margin-bottom: var(--space-1);
            color: var(--swap-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .swap-detail-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1.125rem;
            font-weight: 800;
            letter-spacing: -0.015em;
        }

        .swap-detail-copy,
        .swap-section-copy {
            max-width: 48rem;
            margin: var(--space-1) 0 0;
            color: var(--swap-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .swap-status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            flex: 0 0 auto;
            padding: 0.6rem 0.85rem;
            border-radius: var(--radius-pill);
            font-size: 0.75rem;
            font-weight: 800;
        }

        .swap-profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-5);
        }

        .swap-profile-card {
            height: 100%;
        }

        .swap-section-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .swap-section-body {
            padding: var(--space-4);
        }

        .swap-profile-summary {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-4);
            padding-bottom: var(--space-4);
            border-bottom: 1px solid var(--neutral-100);
        }

        .swap-profile-avatar {
            display: inline-flex;
            width: 3rem;
            height: 3rem;
            flex: 0 0 3rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--swap-soft);
            font-size: 1.125rem;
            font-weight: 800;
        }

        .swap-profile-name {
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.9375rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .swap-profile-number {
            margin-top: 0.125rem;
            color: var(--swap-muted);
            font-size: 0.6875rem;
            font-weight: 700;
        }

        .swap-detail-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
        }

        .swap-detail-row {
            display: grid;
            grid-template-columns: minmax(7.5rem, 0.85fr) minmax(0, 1.15fr);
            gap: var(--space-3);
            align-items: start;
            margin: 0;
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--neutral-100);
        }

        .swap-detail-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .swap-detail-row dt,
        .swap-detail-row dd {
            margin: 0;
        }

        .swap-detail-row dt {
            color: var(--swap-muted);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .swap-detail-row dd {
            min-width: 0;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .swap-schedule-card,
        .swap-reason-card,
        .swap-decision-card,
        .swap-action-card {
            margin-bottom: var(--space-5);
        }

        .swap-schedule-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-4);
            padding: var(--space-4);
        }

        .swap-schedule-panel {
            position: relative;
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .swap-schedule-panel::before {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 0.1875rem;
            border-radius:
                var(--radius-md)
                var(--radius-md)
                0
                0;
            background: var(--brand-400);
            content: "";
        }

        .swap-schedule-heading {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-4);
        }

        .swap-schedule-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--swap-soft);
            font-size: 1rem;
        }

        .swap-schedule-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .swap-schedule-metrics {
            display: grid;
            gap: var(--space-3);
        }

        .swap-schedule-metric {
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-0);
        }

        .swap-schedule-label {
            color: var(--swap-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .swap-schedule-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .swap-schedule-meta {
            display: block;
            margin-top: 0.125rem;
            color: var(--swap-muted);
            font-size: 0.6875rem;
            line-height: 1.5;
        }

        .swap-reason-content {
            padding: var(--space-4);
        }

        .swap-reason-quote {
            position: relative;
            margin: 0;
            padding: var(--space-4);
            border-left: 0.1875rem solid var(--brand-400);
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            color: var(--neutral-800);
            background: var(--brand-50);
            font-size: 0.8125rem;
            line-height: 1.75;
            white-space: pre-line;
        }

        .swap-decision-content,
        .swap-action-content {
            padding: var(--space-4);
        }

        .swap-decision-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .swap-decision-item {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .swap-decision-label {
            color: var(--swap-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .swap-decision-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .swap-decision-meta {
            display: block;
            margin-top: var(--space-1);
            color: var(--swap-muted);
            font-size: 0.6875rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .swap-notice {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .swap-notice-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            font-size: 1rem;
        }

        .swap-notice-warning {
            border-color: #f0ddb0;
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .swap-notice-warning .swap-notice-icon {
            background: rgba(201, 130, 0, 0.09);
        }

        .swap-notice-danger {
            border-color: #efc9c3;
            color: var(--danger-700);
            background: var(--danger-50);
        }

        .swap-notice-danger .swap-notice-icon {
            background: rgba(199, 70, 50, 0.09);
        }

        .swap-notice-info {
            border-color: #cde0eb;
            color: var(--info-700);
            background: var(--info-50);
        }

        .swap-notice-info .swap-notice-icon {
            background: rgba(59, 126, 161, 0.09);
        }

        .swap-notice-neutral {
            color: var(--neutral-700);
            background: var(--neutral-50);
        }

        .swap-notice-neutral .swap-notice-icon {
            color: var(--neutral-700);
            background: var(--neutral-100);
        }

        .swap-notice-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .swap-notice-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .swap-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
        }

        .swap-action-buttons form {
            margin: 0;
        }

        @media (min-width: 768px) {
            .swap-section-body,
            .swap-schedule-grid,
            .swap-reason-content,
            .swap-decision-content,
            .swap-action-content {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .swap-profile-grid,
            .swap-schedule-grid {
                grid-template-columns: 1fr;
            }

            .swap-decision-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .swap-detail-card-header,
            .swap-section-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .swap-detail-row,
            .swap-decision-grid {
                grid-template-columns: 1fr;
            }

            .swap-action-buttons {
                flex-direction: column;
            }

            .swap-action-buttons form,
            .swap-action-buttons .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $requester = $scheduleSwapRequest->requesterEmployee;
        $requesterBranch = $requester?->branch;

        $partner = $scheduleSwapRequest->partnerEmployee;
        $partnerBranch = $partner?->branch;

        $approver = $scheduleSwapRequest->approver;

        $requestStatus = strtolower(
            (string) $scheduleSwapRequest->status
        );

        $statusLabels = [
            'pending' => 'Menunggu Keputusan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        $statusClasses = [
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ];

        $scheduleStatusLabels = [
            'work' => 'Kerja',
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

        $statusLabel = $statusLabels[$requestStatus]
            ?? ucfirst($requestStatus);

        $statusClass = $statusClasses[$requestStatus]
            ?? 'text-bg-secondary';

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l, d F Y');
            } catch (\Throwable) {
                return substr((string) $value, 0, 10);
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

        $requesterAttendanceCount = (int) (
            $requesterSchedule?->attendances_count ?? 0
        );

        $partnerAttendanceCount = (int) (
            $partnerSchedule?->attendances_count ?? 0
        );

        $isHrd = auth()->user()?->hasRole('hrd') === true;

        $canApprove =
            $requestStatus === 'pending'
            && $canBeDecided;

        $canReject =
            $requestStatus === 'pending';

        $approvalBlockedReason = null;

        if ($requestStatus !== 'pending') {
            $approvalBlockedReason =
                'Permohonan ini sudah memiliki keputusan.';
        } elseif (
            $requesterSchedule === null
            || $partnerSchedule === null
        ) {
            $approvalBlockedReason =
                'Salah satu jadwal sudah tidak tersedia.';
        } elseif (
            $requesterAttendanceCount > 0
            || $partnerAttendanceCount > 0
        ) {
            $approvalBlockedReason =
                'Pertukaran tidak dapat disetujui karena salah satu jadwal sudah memiliki data presensi.';
        }
    @endphp

    <div class="schedule-swap-detail-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Detail Pertukaran Jadwal
                </h1>

                <p class="page-description">
                    Informasi pengajuan dan keputusan pertukaran
                    jadwal antarkaryawan.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route(
                        'schedule-swap-requests.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Kembali ke Daftar
                </a>
            </div>
        </header>

        <section
            class="swap-detail-card"
            aria-labelledby="swap-overview-heading"
        >
            <div class="swap-detail-card-header">
                <div>
                    <div class="swap-detail-eyebrow">
                        Status permohonan
                    </div>

                    <h2
                        id="swap-overview-heading"
                        class="swap-detail-title"
                    >
                        Pertukaran Jadwal Karyawan
                    </h2>

                    <p class="swap-detail-copy">
                        Permohonan dicatat pada
                        {{
                            $formatDateTime(
                                $scheduleSwapRequest->created_at
                            )
                        }}
                        WIB.
                    </p>
                </div>

                <span
                    class="badge {{ $statusClass }}
                        swap-status-badge"
                >
                    {{ $statusLabel }}
                </span>
            </div>
        </section>

        <div class="swap-profile-grid">
            <section
                class="swap-profile-card"
                aria-labelledby="requester-heading"
            >
                <div class="swap-section-header">
                    <div>
                        <h2
                            id="requester-heading"
                            class="swap-section-title"
                        >
                            Karyawan Pengaju
                        </h2>

                        <p class="swap-section-copy">
                            Karyawan yang mengajukan pertukaran.
                        </p>
                    </div>

                    <span class="badge text-bg-primary">
                        Pengaju
                    </span>
                </div>

                <div class="swap-section-body">
                    @if ($requester !== null)
                        <div class="swap-profile-summary">
                            <span class="swap-profile-avatar">
                                {{
                                    mb_strtoupper(
                                        mb_substr(
                                            (string)
                                                $requester
                                                    ->full_name,
                                            0,
                                            1
                                        )
                                    )
                                }}
                            </span>

                            <div>
                                <h3 class="swap-profile-name">
                                    <a
                                        href="{{ route(
                                            'employees.show',
                                            $requester
                                        ) }}"
                                        class="text-decoration-none"
                                    >
                                        {{ $requester->full_name }}
                                    </a>
                                </h3>

                                <div class="swap-profile-number">
                                    {{ $requester->employee_number }}
                                </div>
                            </div>
                        </div>

                        <dl class="swap-detail-list">
                            <div class="swap-detail-row">
                                <dt>
                                    Jabatan
                                </dt>

                                <dd>
                                    {{ $requester->position }}
                                </dd>
                            </div>

                            <div class="swap-detail-row">
                                <dt>
                                    Cabang
                                </dt>

                                <dd>
                                    @if ($requesterBranch !== null)
                                        <div class="fw-semibold">
                                            {{
                                                $requesterBranch
                                                    ->code
                                            }}
                                        </div>

                                        <div
                                            class="small
                                                text-secondary"
                                        >
                                            {{
                                                $requesterBranch
                                                    ->name
                                            }}
                                        </div>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>

                            <div class="swap-detail-row">
                                <dt>
                                    Status Karyawan
                                </dt>

                                <dd>
                                    @if (
                                        $requester
                                            ->employment_status
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
                        </dl>
                    @else
                        <div
                            class="swap-notice swap-notice-danger"
                            role="alert"
                        >
                            <span class="swap-notice-icon">
                                <i
                                    class="bi bi-exclamation-triangle"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3 class="swap-notice-title">
                                    Data tidak tersedia
                                </h3>

                                <p class="swap-notice-copy">
                                    Data karyawan pengaju tidak
                                    tersedia.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section
                class="swap-profile-card"
                aria-labelledby="partner-heading"
            >
                <div class="swap-section-header">
                    <div>
                        <h2
                            id="partner-heading"
                            class="swap-section-title"
                        >
                            Karyawan Pasangan
                        </h2>

                        <p class="swap-section-copy">
                            Karyawan yang menjadi pasangan
                            pertukaran.
                        </p>
                    </div>

                    <span class="badge text-bg-secondary">
                        Pasangan
                    </span>
                </div>

                <div class="swap-section-body">
                    @if ($partner !== null)
                        <div class="swap-profile-summary">
                            <span class="swap-profile-avatar">
                                {{
                                    mb_strtoupper(
                                        mb_substr(
                                            (string)
                                                $partner
                                                    ->full_name,
                                            0,
                                            1
                                        )
                                    )
                                }}
                            </span>

                            <div>
                                <h3 class="swap-profile-name">
                                    <a
                                        href="{{ route(
                                            'employees.show',
                                            $partner
                                        ) }}"
                                        class="text-decoration-none"
                                    >
                                        {{ $partner->full_name }}
                                    </a>
                                </h3>

                                <div class="swap-profile-number">
                                    {{ $partner->employee_number }}
                                </div>
                            </div>
                        </div>

                        <dl class="swap-detail-list">
                            <div class="swap-detail-row">
                                <dt>
                                    Jabatan
                                </dt>

                                <dd>
                                    {{ $partner->position }}
                                </dd>
                            </div>

                            <div class="swap-detail-row">
                                <dt>
                                    Cabang
                                </dt>

                                <dd>
                                    @if ($partnerBranch !== null)
                                        <div class="fw-semibold">
                                            {{
                                                $partnerBranch
                                                    ->code
                                            }}
                                        </div>

                                        <div
                                            class="small
                                                text-secondary"
                                        >
                                            {{
                                                $partnerBranch
                                                    ->name
                                            }}
                                        </div>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>

                            <div class="swap-detail-row">
                                <dt>
                                    Status Karyawan
                                </dt>

                                <dd>
                                    @if (
                                        $partner
                                            ->employment_status
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
                        </dl>
                    @else
                        <div
                            class="swap-notice swap-notice-danger"
                            role="alert"
                        >
                            <span class="swap-notice-icon">
                                <i
                                    class="bi bi-exclamation-triangle"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3 class="swap-notice-title">
                                    Data tidak tersedia
                                </h3>

                                <p class="swap-notice-copy">
                                    Data karyawan pasangan tidak
                                    tersedia.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <section
            class="swap-schedule-card"
            aria-labelledby="swap-schedule-heading"
        >
            <div class="swap-section-header">
                <div>
                    <h2
                        id="swap-schedule-heading"
                        class="swap-section-title"
                    >
                        Jadwal yang Dipertukarkan
                    </h2>

                    <p class="swap-section-copy">
                        Jadwal yang tercatat pada tanggal
                        pengajuan.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    2 jadwal
                </span>
            </div>

            <div class="swap-schedule-grid">
                <article class="swap-schedule-panel">
                    <div class="swap-schedule-heading">
                        <span class="swap-schedule-icon">
                            <i
                                class="bi bi-person"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <h3 class="swap-schedule-title">
                            Jadwal Karyawan Pengaju
                        </h3>
                    </div>

                    <div class="swap-schedule-metrics">
                        <div class="swap-schedule-metric">
                            <div class="swap-schedule-label">
                                Tanggal Jadwal
                            </div>

                            <div class="swap-schedule-value">
                                {{
                                    $formatDate(
                                        $scheduleSwapRequest
                                            ->requester_date
                                    )
                                }}
                            </div>
                        </div>

                        @if ($requesterSchedule !== null)
                            @php
                                $requesterScheduleStatus =
                                    strtolower(
                                        (string)
                                            $requesterSchedule
                                                ->schedule_status
                                    );

                                $requesterScheduleLabel =
                                    $scheduleStatusLabels[
                                        $requesterScheduleStatus
                                    ]
                                    ?? ucfirst(
                                        $requesterScheduleStatus
                                    );

                                $requesterScheduleClass =
                                    $scheduleStatusClasses[
                                        $requesterScheduleStatus
                                    ]
                                    ?? 'text-bg-secondary';

                                $requesterWorkSchedule =
                                    $requesterSchedule
                                        ->workSchedule;
                            @endphp

                            <div class="swap-schedule-metric">
                                <div class="swap-schedule-label">
                                    Status Jadwal
                                </div>

                                <div class="swap-schedule-value">
                                    <span
                                        class="badge
                                            {{
                                                $requesterScheduleClass
                                            }}"
                                    >
                                        {{
                                            $requesterScheduleLabel
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="swap-schedule-metric">
                                <div class="swap-schedule-label">
                                    Pola Jadwal
                                </div>

                                @if (
                                    $requesterWorkSchedule !== null
                                )
                                    <div class="swap-schedule-value">
                                        {{
                                            $requesterWorkSchedule
                                                ->name
                                        }}
                                    </div>

                                    <span class="swap-schedule-meta">
                                        {{
                                            $formatTime(
                                                $requesterWorkSchedule
                                                    ->check_in_time
                                            )
                                        }}
                                        sampai
                                        {{
                                            $formatTime(
                                                $requesterWorkSchedule
                                                    ->check_out_time
                                            )
                                        }}
                                        WIB
                                    </span>
                                @else
                                    <div class="swap-schedule-value">
                                        Tidak menggunakan pola
                                        jadwal
                                    </div>
                                @endif
                            </div>

                            <div class="swap-schedule-metric">
                                <div class="swap-schedule-label">
                                    Data Presensi
                                </div>

                                <div class="swap-schedule-value">
                                    {{ $requesterAttendanceCount }}
                                    data
                                </div>
                            </div>
                        @else
                            <div
                                class="swap-notice
                                    swap-notice-danger"
                                role="alert"
                            >
                                <span class="swap-notice-icon">
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>
                                </span>

                                <div>
                                    <h3 class="swap-notice-title">
                                        Jadwal tidak tersedia
                                    </h3>

                                    <p class="swap-notice-copy">
                                        Jadwal karyawan pengaju
                                        sudah tidak tersedia.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </article>

                <article class="swap-schedule-panel">
                    <div class="swap-schedule-heading">
                        <span class="swap-schedule-icon">
                            <i
                                class="bi bi-person-check"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <h3 class="swap-schedule-title">
                            Jadwal Karyawan Pasangan
                        </h3>
                    </div>

                    <div class="swap-schedule-metrics">
                        <div class="swap-schedule-metric">
                            <div class="swap-schedule-label">
                                Tanggal Jadwal
                            </div>

                            <div class="swap-schedule-value">
                                {{
                                    $formatDate(
                                        $scheduleSwapRequest
                                            ->partner_date
                                    )
                                }}
                            </div>
                        </div>

                        @if ($partnerSchedule !== null)
                            @php
                                $partnerScheduleStatus =
                                    strtolower(
                                        (string)
                                            $partnerSchedule
                                                ->schedule_status
                                    );

                                $partnerScheduleLabel =
                                    $scheduleStatusLabels[
                                        $partnerScheduleStatus
                                    ]
                                    ?? ucfirst(
                                        $partnerScheduleStatus
                                    );

                                $partnerScheduleClass =
                                    $scheduleStatusClasses[
                                        $partnerScheduleStatus
                                    ]
                                    ?? 'text-bg-secondary';

                                $partnerWorkSchedule =
                                    $partnerSchedule
                                        ->workSchedule;
                            @endphp

                            <div class="swap-schedule-metric">
                                <div class="swap-schedule-label">
                                    Status Jadwal
                                </div>

                                <div class="swap-schedule-value">
                                    <span
                                        class="badge
                                            {{
                                                $partnerScheduleClass
                                            }}"
                                    >
                                        {{
                                            $partnerScheduleLabel
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="swap-schedule-metric">
                                <div class="swap-schedule-label">
                                    Pola Jadwal
                                </div>

                                @if (
                                    $partnerWorkSchedule !== null
                                )
                                    <div class="swap-schedule-value">
                                        {{
                                            $partnerWorkSchedule
                                                ->name
                                        }}
                                    </div>

                                    <span class="swap-schedule-meta">
                                        {{
                                            $formatTime(
                                                $partnerWorkSchedule
                                                    ->check_in_time
                                            )
                                        }}
                                        sampai
                                        {{
                                            $formatTime(
                                                $partnerWorkSchedule
                                                    ->check_out_time
                                            )
                                        }}
                                        WIB
                                    </span>
                                @else
                                    <div class="swap-schedule-value">
                                        Tidak menggunakan pola
                                        jadwal
                                    </div>
                                @endif
                            </div>

                            <div class="swap-schedule-metric">
                                <div class="swap-schedule-label">
                                    Data Presensi
                                </div>

                                <div class="swap-schedule-value">
                                    {{ $partnerAttendanceCount }}
                                    data
                                </div>
                            </div>
                        @else
                            <div
                                class="swap-notice
                                    swap-notice-danger"
                                role="alert"
                            >
                                <span class="swap-notice-icon">
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>
                                </span>

                                <div>
                                    <h3 class="swap-notice-title">
                                        Jadwal tidak tersedia
                                    </h3>

                                    <p class="swap-notice-copy">
                                        Jadwal karyawan pasangan
                                        sudah tidak tersedia.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </article>
            </div>
        </section>

        <section
            class="swap-reason-card"
            aria-labelledby="swap-reason-heading"
        >
            <div class="swap-section-header">
                <div>
                    <h2
                        id="swap-reason-heading"
                        class="swap-section-title"
                    >
                        Alasan Pertukaran
                    </h2>
                </div>
            </div>

            <div class="swap-reason-content">
                <p class="swap-reason-quote">
                    {{ $scheduleSwapRequest->reason }}
                </p>
            </div>
        </section>

        <section
            class="swap-decision-card"
            aria-labelledby="swap-decision-heading"
        >
            <div class="swap-section-header">
                <div>
                    <h2
                        id="swap-decision-heading"
                        class="swap-section-title"
                    >
                        Keputusan Permohonan
                    </h2>

                    <p class="swap-section-copy">
                        Keputusan persetujuan atau penolakan permohonan.
                    </p>
                </div>
            </div>

            <div class="swap-decision-content">
                @if ($requestStatus === 'pending')
                    <div
                        class="swap-notice swap-notice-warning"
                        role="alert"
                    >
                        <span class="swap-notice-icon">
                            <i
                                class="bi bi-hourglass-split"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="swap-notice-title">
                                Menunggu keputusan
                            </h3>

                            <p class="swap-notice-copy">
                                Permohonan masih menunggu keputusan
                                HRD.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="swap-decision-grid">
                        <article class="swap-decision-item">
                            <div class="swap-decision-label">
                                Keputusan
                            </div>

                            <div class="swap-decision-value">
                                <span
                                    class="badge
                                        {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </article>

                        <article class="swap-decision-item">
                            <div class="swap-decision-label">
                                Diputuskan Oleh
                            </div>

                            <div class="swap-decision-value">
                                @if ($approver !== null)
                                    {{ $approver->name }}

                                    <span
                                        class="swap-decision-meta"
                                    >
                                        {{ $approver->email }}
                                    </span>
                                @else
                                    Data pengguna tidak tersedia
                                @endif
                            </div>
                        </article>

                        <article class="swap-decision-item">
                            <div class="swap-decision-label">
                                Waktu Keputusan
                            </div>

                            <div class="swap-decision-value">
                                {{
                                    $formatDateTime(
                                        $scheduleSwapRequest
                                            ->approved_at
                                    )
                                }}
                                WIB
                            </div>
                        </article>
                    </div>
                @endif
            </div>
        </section>

        @if ($isHrd)
            <section
                class="swap-action-card"
                aria-labelledby="swap-action-heading"
            >
                <div class="swap-section-header">
                    <div>
                        <h2
                            id="swap-action-heading"
                            class="swap-section-title"
                        >
                            Tindakan HRD
                        </h2>

                        <p class="swap-section-copy">
                            Berikan keputusan terhadap permohonan
                            ini.
                        </p>
                    </div>
                </div>

                <div class="swap-action-content">
                    @error('decision')
                        <div
                            class="swap-notice
                                swap-notice-danger mb-4"
                            role="alert"
                        >
                            <span class="swap-notice-icon">
                                <i
                                    class="bi
                                        bi-exclamation-triangle"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3 class="swap-notice-title">
                                    Keputusan tidak valid
                                </h3>

                                <p class="swap-notice-copy">
                                    {{ $message }}
                                </p>
                            </div>
                        </div>
                    @enderror

                    @if ($requestStatus !== 'pending')
                        <div
                            class="swap-notice swap-notice-neutral"
                            role="alert"
                        >
                            <span class="swap-notice-icon">
                                <i
                                    class="bi bi-lock"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3 class="swap-notice-title">
                                    Permohonan telah diputuskan
                                </h3>

                                <p class="swap-notice-copy">
                                    Permohonan sudah diputuskan dan
                                    tidak dapat diproses kembali.
                                </p>
                            </div>
                        </div>
                    @else
                        @if ($approvalBlockedReason !== null)
                            <div
                                class="swap-notice
                                    swap-notice-warning mb-4"
                                role="alert"
                            >
                                <span class="swap-notice-icon">
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>
                                </span>

                                <div>
                                    <h3 class="swap-notice-title">
                                        Persetujuan tidak tersedia
                                    </h3>

                                    <p class="swap-notice-copy">
                                        {{
                                            $approvalBlockedReason
                                        }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="swap-action-buttons">
                            <form
                                method="POST"
                                action="{{ route(
                                    'schedule-swap-requests.decide',
                                    $scheduleSwapRequest
                                ) }}"
                                onsubmit="
                                    return confirm(
                                        'Setujui pertukaran jadwal ini?'
                                    );
                                "
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="decision"
                                    value="approved"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-success"
                                    @disabled(! $canApprove)
                                >
                                    <i
                                        class="bi bi-check2-circle
                                            me-2"
                                        aria-hidden="true"
                                    ></i>

                                    Setujui dan Tukar Jadwal
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route(
                                    'schedule-swap-requests.decide',
                                    $scheduleSwapRequest
                                ) }}"
                                onsubmit="
                                    return confirm(
                                        'Tolak permohonan pertukaran ini?'
                                    );
                                "
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="decision"
                                    value="rejected"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger"
                                    @disabled(! $canReject)
                                >
                                    <i
                                        class="bi bi-x-circle me-2"
                                        aria-hidden="true"
                                    ></i>

                                    Tolak Permohonan
                                </button>
                            </form>
                        </div>

                        @if (! $canApprove && $canReject)
                            <p
                                class="small text-secondary
                                    mt-3 mb-0"
                            >
                                Persetujuan tidak tersedia, tetapi
                                HRD masih dapat menolak permohonan
                                untuk menyelesaikan statusnya.
                            </p>
                        @endif
                    @endif
                </div>
            </section>
        @else
            <section class="swap-action-card">
                <div class="swap-action-content">
                    <div
                        class="swap-notice swap-notice-info"
                        role="alert"
                    >
                        <span class="swap-notice-icon">
                            <i
                                class="bi bi-info-circle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="swap-notice-title">
                                Akses keputusan dibatasi
                            </h3>

                            <p class="swap-notice-copy">
                                Admin operasional dapat melihat dan
                                mencatat permohonan. Keputusan
                                persetujuan atau penolakan hanya
                                dapat dilakukan oleh HRD.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
