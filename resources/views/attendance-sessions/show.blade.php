@extends('layouts.app')

@section('title', 'Detail Sesi Presensi')

@push('styles')
    <style>
        .attendance-session-detail-page {
            --session-surface: var(--neutral-0);
            --session-border: var(--neutral-200);
            --session-muted: var(--neutral-600);
            --session-soft: var(--brand-50);
        }

        .session-overview-card,
        .session-qr-card,
        .session-info-card,
        .session-branch-card,
        .session-action-card {
            overflow: hidden;
            border: 1px solid var(--session-border);
            border-radius: var(--radius-lg);
            background: var(--session-surface);
            box-shadow: var(--shadow-xs);
        }

        .session-overview-card,
        .session-branch-card,
        .session-action-card {
            margin-bottom: var(--space-5);
        }

        .session-overview-header,
        .session-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--session-border);
            background: var(--neutral-25);
        }

        .session-overview-eyebrow {
            margin-bottom: var(--space-2);
            color: var(--session-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .session-overview-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1.125rem;
            font-weight: 800;
            letter-spacing: -0.015em;
            line-height: 1.4;
        }

        .session-overview-copy,
        .session-card-copy {
            max-width: 48rem;
            margin: var(--space-1) 0 0;
            color: var(--session-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .session-overview-body {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .session-overview-metric {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .session-overview-metric-icon {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--session-soft);
            font-size: 0.9375rem;
        }

        .session-overview-metric-label {
            color: var(--session-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .session-overview-metric-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .session-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(20rem, 0.85fr);
            gap: var(--space-4);
            margin-bottom: var(--space-5);
        }

        .session-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .session-card-body {
            padding: var(--space-4);
        }

        .session-status-notice {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .session-status-notice.alert-success,
        .session-status-notice.alert-warning,
        .session-status-notice.alert-danger {
            margin-bottom: var(--space-4);
        }

        .session-status-icon {
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

        .qr-stage {
            display: flex;
            min-height: 22rem;
            align-items: center;
            justify-content: center;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-25);
        }

        .qr-code-container {
            display: flex;
            min-height: 18rem;
            align-items: center;
            justify-content: center;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-0);
        }

        .qr-code-container img,
        .qr-code-container canvas {
            max-width: 100%;
            height: auto !important;
        }

        .session-token-panel {
            margin-top: var(--space-4);
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
            text-align: center;
        }

        .session-token-label {
            color: var(--session-muted);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .token-value {
            margin: var(--space-2) 0;
            color: var(--neutral-900);
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;
            font-size: clamp(1.75rem, 6vw, 3rem);
            font-weight: 800;
            letter-spacing: 0.35rem;
            line-height: 1.2;
        }

        .session-token-countdown {
            color: var(--session-muted);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .session-detail-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
        }

        .session-detail-row {
            display: grid;
            grid-template-columns: minmax(8rem, 0.9fr) minmax(0, 1.1fr);
            gap: var(--space-3);
            align-items: start;
            margin: 0;
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--neutral-100);
        }

        .session-detail-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .session-detail-row dt,
        .session-detail-row dd {
            margin: 0;
        }

        .session-detail-row dt {
            color: var(--session-muted);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .session-detail-row dd {
            min-width: 0;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .session-identifier {
            overflow-wrap: anywhere;
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;
            font-size: 0.75rem;
        }

        .session-branch-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-4);
            padding: var(--space-4);
        }

        .session-branch-panel {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .session-branch-panel-title {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin: 0 0 var(--space-4);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .session-branch-panel-icon {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 2.25rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--session-soft);
            font-size: 0.9375rem;
        }

        .session-action-body {
            padding: var(--space-4);
        }

        .session-action-warning,
        .session-action-neutral,
        .session-branch-error {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border-radius: var(--radius-md);
        }

        .session-action-warning {
            margin-bottom: var(--space-4);
            border: 1px solid #f0ddb0;
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .session-action-neutral {
            border: 1px solid var(--neutral-200);
            color: var(--neutral-700);
            background: var(--neutral-50);
        }

        .session-branch-error {
            margin: var(--space-4);
            border: 1px solid #efc9c3;
            color: var(--danger-700);
            background: var(--danger-50);
        }

        .session-action-icon {
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

        .session-action-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .session-action-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        @media (min-width: 768px) {
            .session-overview-body,
            .session-card-body,
            .session-branch-grid,
            .session-action-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .session-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .session-overview-body,
            .session-branch-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .session-overview-header,
            .session-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .session-detail-row {
                grid-template-columns: 1fr;
            }

            .qr-stage {
                min-height: 18rem;
            }

            .qr-code-container {
                min-height: 15rem;
            }

            .token-value {
                letter-spacing: 0.2rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $branch = $attendanceSession->branch;
        $creator = $attendanceSession->creator;

        $attendanceType = strtolower(
            (string) $attendanceSession->attendance_type
        );

        $sessionStatus = strtolower(
            (string) $attendanceSession->status
        );

        $attendanceTypeLabels = [
            'check_in' => 'Presensi Masuk',
            'check_out' => 'Presensi Pulang',
        ];

        $attendanceTypeClasses = [
            'check_in' => 'text-bg-primary',
            'check_out' => 'text-bg-info',
        ];

        $statusLabels = [
            'active' => 'Aktif',
            'closed' => 'Ditutup',
            'expired' => 'Kedaluwarsa',
        ];

        $statusClasses = [
            'active' => 'text-bg-success',
            'closed' => 'text-bg-secondary',
            'expired' => 'text-bg-danger',
        ];

        $attendanceTypeLabel =
            $attendanceTypeLabels[$attendanceType]
            ?? ucfirst($attendanceType);

        $attendanceTypeClass =
            $attendanceTypeClasses[$attendanceType]
            ?? 'text-bg-secondary';

        $statusLabel =
            $statusLabels[$sessionStatus]
            ?? ucfirst($sessionStatus);

        $statusClass =
            $statusClasses[$sessionStatus]
            ?? 'text-bg-secondary';

        $creatorRoleLabel = match ($creator?->role) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            default => 'Pengguna',
        };

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

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('H:i');
            } catch (\Throwable) {
                return substr((string) $value, 0, 5);
            }
        };

        $initialQrPayload = null;

        if ($currentToken !== null) {
            $initialQrPayload = json_encode(
                [
                    'session' => (string) $attendanceSession->public_id,
                    'token' => $currentToken,
                ],
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
            );
        }

        $currentMoment = now(
            config('app.timezone')
        );

        $hasNotStarted =
            $attendanceSession->isActive()
            && $currentMoment->lessThan(
                $attendanceSession->start_time
            );

        $sessionStateLabel = match (true) {
            $attendanceSession->isClosed() =>
                'Sesi telah ditutup secara manual.',

            $attendanceSession->isExpired() =>
                'Sesi telah melewati waktu berakhir.',

            $hasNotStarted =>
                'Sesi aktif, tetapi belum memasuki waktu mulai.',

            $isUsable =>
                'Sesi sedang berlangsung dan dapat digunakan.',

            default =>
                'Sesi tidak dapat digunakan saat ini.',
        };
    @endphp

    <div class="attendance-session-detail-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Detail Sesi Presensi
                </h1>

                <p class="page-description">
                    Informasi sesi dan QR Code dinamis
                    berbasis TOTP.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <a
                    href="{{ route(
                        'attendance-sessions.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Kembali ke Daftar
                </a>

                <a
                    href="{{ route(
                        'attendance-sessions.create'
                    ) }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-plus-lg me-2"
                        aria-hidden="true"
                    ></i>

                    Buka Sesi Baru
                </a>
            </div>
        </header>

        <section
            class="session-overview-card"
            aria-labelledby="session-overview-heading"
        >
            <div class="session-overview-header">
                <div>
                    <div class="session-overview-eyebrow">
                        Ringkasan sesi
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge {{ $attendanceTypeClass }}">
                            {{ $attendanceTypeLabel }}
                        </span>

                        <span class="badge {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <h2
                        id="session-overview-heading"
                        class="session-overview-title"
                    >
                        {{ $branch?->code ?? 'Cabang tidak tersedia' }}

                        @if ($branch !== null)
                            |
                            {{ $branch->name }}
                        @endif
                    </h2>

                    <p class="session-overview-copy">
                        {{ $sessionStateLabel }}
                    </p>
                </div>
            </div>

            <div class="session-overview-body">
                <article class="session-overview-metric">
                    <span class="session-overview-metric-icon">
                        <i
                            class="bi bi-person-check"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="session-overview-metric-label">
                        Presensi Diterima
                    </div>

                    <div class="session-overview-metric-value">
                        {{
                            (int) $attendanceSession
                                ->attendances_count
                        }}
                        data
                    </div>
                </article>

                <article class="session-overview-metric">
                    <span class="session-overview-metric-icon">
                        <i
                            class="bi bi-calendar3"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="session-overview-metric-label">
                        Tanggal Sesi
                    </div>

                    <div class="session-overview-metric-value">
                        {{
                            $formatDate(
                                $attendanceSession->session_date
                            )
                        }}
                    </div>
                </article>

                <article class="session-overview-metric">
                    <span class="session-overview-metric-icon">
                        <i
                            class="bi bi-clock"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="session-overview-metric-label">
                        Rentang Waktu
                    </div>

                    <div class="session-overview-metric-value">
                        {{
                            $formatTime(
                                $attendanceSession->start_time
                            )
                        }}
                        –
                        {{
                            $formatTime(
                                $attendanceSession->end_time
                            )
                        }}
                        WIB
                    </div>
                </article>
            </div>
        </section>

        <div class="session-main-grid">
            <section
                class="session-qr-card"
                aria-labelledby="dynamic-qr-heading"
            >
                <div class="session-card-header">
                    <div>
                        <h2
                            id="dynamic-qr-heading"
                            class="session-card-title"
                        >
                            QR Code Dinamis
                        </h2>

                        <p class="session-card-copy">
                            QR Code diperbarui mengikuti periode
                            token TOTP.
                        </p>
                    </div>

                    <span class="badge text-bg-light border">
                        TOTP
                    </span>
                </div>

                <div class="session-card-body">
                    <div
                        id="qr-status-alert"
                        class="session-status-notice alert {{
                            $isUsable
                                ? 'alert-success'
                                : 'alert-warning'
                        }}"
                        role="status"
                    >
                        <span class="session-status-icon">
                            <i
                                class="bi {{
                                    $isUsable
                                        ? 'bi-check2-circle'
                                        : 'bi-exclamation-triangle'
                                }}"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <span>
                            @if ($isUsable)
                                QR Code aktif dan dapat dipindai.
                            @elseif ($hasNotStarted)
                                Sesi belum memasuki waktu mulai.
                            @else
                                QR Code tidak aktif.
                            @endif
                        </span>
                    </div>

                    <div class="qr-stage">
                        <div
                            id="qr-code-container"
                            class="qr-code-container"
                        >
                            <div
                                id="qr-placeholder"
                                class="text-center
                                    text-secondary px-3"
                            >
                                @if ($isUsable)
                                    Memuat QR Code...
                                @else
                                    QR Code tidak tersedia.
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="session-token-panel">
                        <div class="session-token-label">
                            Token saat ini
                        </div>

                        <div
                            id="token-value"
                            class="token-value"
                        >
                            {{ $currentToken ?? '------' }}
                        </div>

                        <div class="session-token-countdown">
                            Token diperbarui dalam
                            <span
                                id="token-countdown"
                                class="fw-semibold"
                            >
                                {{
                                    $tokenSecondsRemaining !== null
                                        ? $tokenSecondsRemaining
                                            .' detik'
                                        : '-'
                                }}
                            </span>
                        </div>

                        <button
                            type="button"
                            id="refresh-qr-button"
                            class="btn btn-sm
                                btn-outline-primary mt-3"
                            @disabled(
                                ! $attendanceSession->isActive()
                            )
                        >
                            <i
                                class="bi bi-arrow-clockwise me-2"
                                aria-hidden="true"
                            ></i>

                            Perbarui QR Sekarang
                        </button>
                    </div>
                </div>
            </section>

            <section
                class="session-info-card"
                aria-labelledby="session-information-heading"
            >
                <div class="session-card-header">
                    <div>
                        <h2
                            id="session-information-heading"
                            class="session-card-title"
                        >
                            Informasi Sesi
                        </h2>
                    </div>
                </div>

                <div class="session-card-body">
                    <dl class="session-detail-list">
                        <div class="session-detail-row">
                            <dt>
                                UUID Publik
                            </dt>

                            <dd class="session-identifier">
                                {{ $attendanceSession->public_id }}
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Jenis Presensi
                            </dt>

                            <dd>
                                {{ $attendanceTypeLabel }}
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Tanggal Sesi
                            </dt>

                            <dd>
                                {{
                                    $formatDate(
                                        $attendanceSession
                                            ->session_date
                                    )
                                }}
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Waktu Mulai
                            </dt>

                            <dd>
                                {{
                                    $formatTime(
                                        $attendanceSession
                                            ->start_time
                                    )
                                }}
                                WIB
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Waktu Berakhir
                            </dt>

                            <dd>
                                {{
                                    $formatTime(
                                        $attendanceSession
                                            ->end_time
                                    )
                                }}
                                WIB
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Status
                            </dt>

                            <dd>
                                <span
                                    class="badge
                                        {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Pembuat Sesi
                            </dt>

                            <dd>
                                @if ($creator !== null)
                                    <div class="fw-semibold">
                                        {{ $creator->name }}
                                    </div>

                                    <div
                                        class="small
                                            text-secondary"
                                    >
                                        {{ $creatorRoleLabel }}
                                    </div>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Dibuat Pada
                            </dt>

                            <dd>
                                {{
                                    $formatDateTime(
                                        $attendanceSession
                                            ->created_at
                                    )
                                }}
                                WIB
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Ditutup Pada
                            </dt>

                            <dd>
                                @if (
                                    $attendanceSession
                                        ->closed_at !== null
                                )
                                    {{
                                        $formatDateTime(
                                            $attendanceSession
                                                ->closed_at
                                        )
                                    }}
                                    WIB
                                @else
                                    -
                                @endif
                            </dd>
                        </div>

                        <div class="session-detail-row">
                            <dt>
                                Log Validasi
                            </dt>

                            <dd>
                                {{
                                    (int) $attendanceSession
                                        ->validation_logs_count
                                }}
                                log
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>

        <section
            class="session-branch-card"
            aria-labelledby="session-branch-heading"
        >
            <div class="session-card-header">
                <div>
                    <h2
                        id="session-branch-heading"
                        class="session-card-title"
                    >
                        Informasi Cabang dan Geofence
                    </h2>

                    <p class="session-card-copy">
                        Konfigurasi lokasi yang digunakan untuk
                        validasi presensi sesi ini.
                    </p>
                </div>
            </div>

            @if ($branch !== null)
                <div class="session-branch-grid">
                    <article class="session-branch-panel">
                        <h3 class="session-branch-panel-title">
                            <span
                                class="session-branch-panel-icon"
                            >
                                <i
                                    class="bi bi-building"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            Identitas Cabang
                        </h3>

                        <dl class="session-detail-list">
                            <div class="session-detail-row">
                                <dt>
                                    Kode Cabang
                                </dt>

                                <dd>
                                    {{ $branch->code }}
                                </dd>
                            </div>

                            <div class="session-detail-row">
                                <dt>
                                    Nama Cabang
                                </dt>

                                <dd>
                                    {{ $branch->name }}
                                </dd>
                            </div>

                            <div class="session-detail-row">
                                <dt>
                                    Alamat
                                </dt>

                                <dd>
                                    {{ $branch->address }}
                                </dd>
                            </div>
                        </dl>
                    </article>

                    <article class="session-branch-panel">
                        <h3 class="session-branch-panel-title">
                            <span
                                class="session-branch-panel-icon"
                            >
                                <i
                                    class="bi bi-geo-alt"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            Konfigurasi Geofence
                        </h3>

                        <dl class="session-detail-list">
                            <div class="session-detail-row">
                                <dt>
                                    Latitude
                                </dt>

                                <dd>
                                    {{ $branch->latitude }}
                                </dd>
                            </div>

                            <div class="session-detail-row">
                                <dt>
                                    Longitude
                                </dt>

                                <dd>
                                    {{ $branch->longitude }}
                                </dd>
                            </div>

                            <div class="session-detail-row">
                                <dt>
                                    Radius Geofence
                                </dt>

                                <dd>
                                    {{ $branch->geofence_radius }}
                                    meter
                                </dd>
                            </div>

                            <div class="session-detail-row">
                                <dt>
                                    Batas Akurasi
                                </dt>

                                <dd>
                                    {{ $branch->maximum_accuracy }}
                                    meter
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>
            @else
                <div
                    class="session-branch-error"
                    role="alert"
                >
                    <span class="session-action-icon">
                        <i
                            class="bi bi-exclamation-triangle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h3 class="session-action-title">
                            Data cabang tidak tersedia
                        </h3>

                        <p class="session-action-copy">
                            Data cabang tidak tersedia.
                        </p>
                    </div>
                </div>
            @endif
        </section>

        <section
            class="session-action-card"
            aria-labelledby="session-action-heading"
        >
            <div class="session-card-header">
                <div>
                    <h2
                        id="session-action-heading"
                        class="session-card-title"
                    >
                        Tindakan Sesi
                    </h2>

                    <p class="session-card-copy">
                        Kelola status operasional sesi presensi.
                    </p>
                </div>
            </div>

            <div class="session-action-body">
                @if ($attendanceSession->isActive())
                    <div class="session-action-warning">
                        <span class="session-action-icon">
                            <i
                                class="bi bi-exclamation-triangle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="session-action-title">
                                QR Code akan langsung berhenti
                            </h3>

                            <p class="session-action-copy">
                                Menutup sesi akan menghentikan
                                penggunaan QR Code secara langsung.
                            </p>
                        </div>
                    </div>

                    <form
                        method="POST"
                        action="{{ route(
                            'attendance-sessions.close',
                            $attendanceSession
                        ) }}"
                        onsubmit="
                            return confirm(
                                'Tutup sesi presensi ini?'
                            );
                        "
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            <i
                                class="bi bi-stop-circle me-2"
                                aria-hidden="true"
                            ></i>

                            Tutup Sesi Presensi
                        </button>
                    </form>
                @else
                    <div
                        class="session-action-neutral"
                        role="status"
                    >
                        <span class="session-action-icon">
                            <i
                                class="bi bi-lock"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="session-action-title">
                                Sesi tidak aktif
                            </h3>

                            <p class="session-action-copy">
                                Sesi sudah tidak aktif dan tidak
                                dapat ditutup kembali.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script
        src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"
    ></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const payloadEndpoint = @json(
                route(
                    'attendance-sessions.payload',
                    $attendanceSession
                )
            );

            const initialPayload = @json($initialQrPayload);
            const initialToken = @json($currentToken);

            let secondsRemaining = Number(
                @json($tokenSecondsRemaining ?? 0)
            );

            let pollingEnabled = @json(
                $attendanceSession->isActive()
            );

            let requestInProgress = false;

            const qrContainer = document.getElementById(
                'qr-code-container'
            );

            const tokenElement = document.getElementById(
                'token-value'
            );

            const countdownElement = document.getElementById(
                'token-countdown'
            );

            const statusAlert = document.getElementById(
                'qr-status-alert'
            );

            const refreshButton = document.getElementById(
                'refresh-qr-button'
            );

            const setStatus = function (
                message,
                alertClass
            ) {
                if (statusAlert === null) {
                    return;
                }

                statusAlert.className =
                    'session-status-notice alert '
                    + alertClass;

                statusAlert.textContent = message;
            };

            const clearQrCode = function (message) {
                if (qrContainer === null) {
                    return;
                }

                qrContainer.innerHTML = '';

                const placeholder =
                    document.createElement('div');

                placeholder.className =
                    'text-center text-secondary px-3';

                placeholder.textContent =
                    message ?? 'QR Code tidak tersedia.';

                qrContainer.appendChild(placeholder);
            };

            const renderQrCode = function (payload) {
                if (qrContainer === null) {
                    return;
                }

                if (typeof QRCode === 'undefined') {
                    clearQrCode(
                        'Pustaka QR Code tidak dapat dimuat.'
                    );

                    setStatus(
                        'QR Code tidak dapat dibuat pada browser.',
                        'alert-danger'
                    );

                    return;
                }

                qrContainer.innerHTML = '';

                new QRCode(
                    qrContainer,
                    {
                        text: payload,
                        width: 280,
                        height: 280,
                        correctLevel:
                            QRCode.CorrectLevel.M,
                    }
                );
            };

            const updateCountdown = function () {
                if (countdownElement === null) {
                    return;
                }

                if (! pollingEnabled) {
                    countdownElement.textContent = '-';

                    return;
                }

                if (secondsRemaining > 0) {
                    countdownElement.textContent =
                        secondsRemaining + ' detik';

                    return;
                }

                countdownElement.textContent =
                    'memperbarui...';
            };

            const stopPolling = function (
                message,
                alertClass
            ) {
                pollingEnabled = false;
                secondsRemaining = 0;

                clearQrCode(message);

                if (tokenElement !== null) {
                    tokenElement.textContent = '------';
                }

                updateCountdown();

                setStatus(
                    message,
                    alertClass
                );

                if (refreshButton !== null) {
                    refreshButton.disabled = true;
                }
            };

            const fetchPayload = async function () {
                if (
                    ! pollingEnabled
                    || requestInProgress
                ) {
                    return;
                }

                requestInProgress = true;

                try {
                    const response = await fetch(
                        payloadEndpoint,
                        {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            cache: 'no-store',
                        }
                    );

                    const body = await response
                        .json()
                        .catch(function () {
                            return {};
                        });

                    if (! response.ok) {
                        const message =
                            body.message
                            ?? 'Payload QR tidak dapat dimuat.';

                        if (
                            response.status === 409
                            || response.status === 410
                        ) {
                            stopPolling(
                                message,
                                'alert-danger'
                            );

                            return;
                        }

                        if (response.status === 422) {
                            clearQrCode(message);

                            if (tokenElement !== null) {
                                tokenElement.textContent =
                                    '------';
                            }

                            secondsRemaining = 5;

                            setStatus(
                                message,
                                'alert-warning'
                            );

                            updateCountdown();

                            return;
                        }

                        throw new Error(message);
                    }

                    const data = body.data ?? {};

                    if (
                        typeof data.qr_payload !== 'string'
                        || typeof data.token !== 'string'
                    ) {
                        throw new Error(
                            'Format payload QR tidak valid.'
                        );
                    }

                    renderQrCode(data.qr_payload);

                    if (tokenElement !== null) {
                        tokenElement.textContent =
                            data.token;
                    }

                    secondsRemaining = Math.max(
                        1,
                        Number(data.expires_in ?? 1)
                    );

                    setStatus(
                        'QR Code aktif dan dapat dipindai.',
                        'alert-success'
                    );

                    updateCountdown();
                } catch (error) {
                    secondsRemaining = 5;

                    setStatus(
                        error instanceof Error
                            ? error.message
                            : 'Terjadi kesalahan saat memperbarui QR Code.',
                        'alert-danger'
                    );

                    updateCountdown();
                } finally {
                    requestInProgress = false;
                }
            };

            if (
                initialPayload !== null
                && initialToken !== null
            ) {
                renderQrCode(initialPayload);

                if (tokenElement !== null) {
                    tokenElement.textContent =
                        initialToken;
                }
            }

            if (refreshButton !== null) {
                refreshButton.addEventListener(
                    'click',
                    function () {
                        secondsRemaining = 0;
                        fetchPayload();
                    }
                );
            }

            updateCountdown();

            if (pollingEnabled) {
                fetchPayload();
            }

            window.setInterval(
                function () {
                    if (! pollingEnabled) {
                        return;
                    }

                    if (secondsRemaining > 0) {
                        secondsRemaining--;
                        updateCountdown();
                    }

                    if (
                        secondsRemaining <= 0
                        && ! requestInProgress
                    ) {
                        fetchPayload();
                    }
                },
                1000
            );
        });
    </script>
@endpush
