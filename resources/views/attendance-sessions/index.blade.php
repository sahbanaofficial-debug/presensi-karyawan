@extends('layouts.app')

@section('title', 'Sesi Presensi')

@push('styles')
    <style>
        .attendance-session-page {
            --session-surface: var(--neutral-0);
            --session-border: var(--neutral-200);
            --session-muted: var(--neutral-600);
            --session-orange-soft: var(--brand-50);
        }

        .attendance-session-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .attendance-session-summary-card {
            padding: var(--space-4);
            border: 1px solid var(--session-border);
            border-radius: var(--radius-lg);
            background: var(--session-surface);
            box-shadow: var(--shadow-xs);
        }

        .attendance-session-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--session-orange-soft);
            font-size: 1rem;
        }

        .attendance-session-summary-label {
            color: var(--session-muted);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .attendance-session-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .attendance-session-summary-copy {
            margin-top: var(--space-1);
            color: var(--session-muted);
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .attendance-session-toolbar {
            border: 1px solid var(--session-border);
            border-radius: var(--radius-lg);
            background: var(--session-surface);
            box-shadow: var(--shadow-xs);
        }

        .attendance-session-search-control {
            position: relative;
        }

        .attendance-session-search-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: var(--space-3);
            color: var(--neutral-500);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .attendance-session-search-control .form-control {
            padding-left: 2.75rem;
        }

        .attendance-session-list-card {
            overflow: hidden;
            border: 1px solid var(--session-border);
            border-radius: var(--radius-lg);
            background: var(--session-surface);
            box-shadow: var(--shadow-xs);
        }

        .attendance-session-list-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--session-border);
            background: var(--neutral-25);
        }

        .attendance-session-list-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .attendance-session-list-copy {
            margin: var(--space-1) 0 0;
            color: var(--session-muted);
            font-size: 0.75rem;
        }

        .attendance-session-branch-code {
            display: block;
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.035em;
            text-transform: uppercase;
        }

        .attendance-session-branch-name {
            display: block;
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.45;
        }

        .attendance-session-type {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: 0.4375rem 0.6875rem;
            border: 1px solid transparent;
            border-radius: var(--radius-pill);
            font-size: 0.6875rem;
            font-weight: 800;
            line-height: 1;
        }

        .attendance-session-type-check-in {
            border-color: var(--brand-200);
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .attendance-session-type-check-out {
            border-color: var(--neutral-200);
            color: var(--neutral-800);
            background: var(--neutral-50);
        }

        .attendance-session-uuid {
            display: block;
            max-width: 14rem;
            margin-top: var(--space-2);
            color: var(--session-muted);
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;
            font-size: 0.6875rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .attendance-session-date {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .attendance-session-time {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            margin-top: var(--space-1);
            color: var(--neutral-700);
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .attendance-session-time i {
            color: var(--brand-600);
        }

        .attendance-session-closed {
            display: block;
            margin-top: var(--space-2);
            color: var(--session-muted);
            font-size: 0.6875rem;
            line-height: 1.45;
        }

        .attendance-session-creator-name {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .attendance-session-creator-role {
            display: block;
            margin-top: var(--space-1);
            color: var(--session-muted);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .attendance-session-count {
            display: inline-flex;
            min-width: 2.25rem;
            min-height: 2.25rem;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-pill);
            color: var(--neutral-800);
            background: var(--neutral-50);
            font-size: 0.75rem;
            font-weight: 800;
        }

        .attendance-session-action-group {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-2);
        }

        .attendance-session-mobile-list {
            display: none;
        }

        .attendance-session-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--session-border);
        }

        .attendance-session-mobile-card:last-child {
            border-bottom: 0;
        }

        .attendance-session-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .attendance-session-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .attendance-session-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--session-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .attendance-session-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .attendance-session-mobile-metric {
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .attendance-session-mobile-metric-label {
            color: var(--session-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .attendance-session-mobile-metric-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.5;
        }

        .attendance-session-empty-state {
            padding: var(--space-8) var(--space-5);
            text-align: center;
        }

        .attendance-session-empty-icon {
            display: inline-flex;
            width: 3.75rem;
            height: 3.75rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--session-orange-soft);
            font-size: 1.5rem;
        }

        .attendance-session-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .attendance-session-empty-copy {
            max-width: 32rem;
            margin: var(--space-2) auto var(--space-4);
            color: var(--session-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .attendance-session-pagination {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border-top: 1px solid var(--session-border);
        }

        @media (min-width: 768px) {
            .attendance-session-pagination {
                flex-direction: row;
                align-items: center;
                padding: var(--space-4) var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .attendance-session-summary-grid {
                grid-template-columns: 1fr;
            }

            .attendance-session-desktop-table {
                display: none;
            }

            .attendance-session-mobile-list {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .attendance-session-list-header {
                padding: var(--space-4);
            }

            .attendance-session-mobile-grid {
                grid-template-columns: 1fr;
            }

            .attendance-session-action-group {
                width: 100%;
            }

            .attendance-session-action-group .btn {
                flex: 1;
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

        $filterActive =
            $search !== ''
            || $selectedAttendanceType !== ''
            || $selectedStatus !== ''
            || $selectedSessionDate !== null;
    @endphp

    <div class="attendance-session-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Sesi Presensi
                </h1>

                <p class="page-description">
                    Kelola sesi masuk dan pulang dengan QR Code
                    dinamis berbasis TOTP.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route('attendance-sessions.create') }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-qr-code me-2"
                        aria-hidden="true"
                    ></i>

                    Buka Sesi Presensi
                </a>
            </div>
        </header>

        <section
            class="attendance-session-summary-grid"
            aria-label="Ringkasan sesi presensi"
        >
            <article class="attendance-session-summary-card">
                <span class="attendance-session-summary-icon">
                    <i
                        class="bi bi-qr-code-scan"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="attendance-session-summary-label">
                    Total sesi
                </div>

                <div class="attendance-session-summary-value">
                    {{ $attendanceSessions->total() }}
                </div>

                <div class="attendance-session-summary-copy">
                    Sesi yang sesuai dengan filter aktif.
                </div>
            </article>

            <article class="attendance-session-summary-card">
                <span class="attendance-session-summary-icon">
                    <i
                        class="bi bi-list-check"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="attendance-session-summary-label">
                    Data pada halaman
                </div>

                <div class="attendance-session-summary-value">
                    {{ $attendanceSessions->count() }}
                </div>

                <div class="attendance-session-summary-copy">
                    Jumlah sesi pada halaman saat ini.
                </div>
            </article>

            <article class="attendance-session-summary-card">
                <span class="attendance-session-summary-icon">
                    <i
                        class="bi bi-funnel"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="attendance-session-summary-label">
                    Status filter
                </div>

                <div class="attendance-session-summary-value">
                    {{ $filterActive ? 'Aktif' : 'Semua' }}
                </div>

                <div class="attendance-session-summary-copy">
                    {{
                        $filterActive
                            ? 'Daftar telah disaring.'
                            : 'Menampilkan seluruh sesi.'
                    }}
                </div>
            </article>
        </section>

        <section
            class="attendance-session-toolbar
                p-3 p-md-4 mb-4"
        >
            <div class="mb-3">
                <h2 class="section-title">
                    Filter sesi presensi
                </h2>

                <p class="section-description">
                    Cari berdasarkan cabang, UUID, jenis presensi,
                    status, atau tanggal sesi.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('attendance-sessions.index') }}"
            >
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-xl-4">
                        <label
                            for="search"
                            class="form-label"
                        >
                            Pencarian
                        </label>

                        <div
                            class="attendance-session-search-control"
                        >
                            <i
                                class="bi bi-search
                                    attendance-session-search-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="search"
                                id="search"
                                name="search"
                                value="{{ $search }}"
                                class="form-control"
                                placeholder="Kode cabang, nama cabang, atau UUID"
                            >
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-2">
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

                    <div class="col-md-6 col-xl-2">
                        <label
                            for="status"
                            class="form-label"
                        >
                            Status
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
                                value="active"
                                @selected(
                                    $selectedStatus === 'active'
                                )
                            >
                                Aktif
                            </option>

                            <option
                                value="closed"
                                @selected(
                                    $selectedStatus === 'closed'
                                )
                            >
                                Ditutup
                            </option>

                            <option
                                value="expired"
                                @selected(
                                    $selectedStatus === 'expired'
                                )
                            >
                                Kedaluwarsa
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label
                            for="session_date"
                            class="form-label"
                        >
                            Tanggal Sesi
                        </label>

                        <input
                            type="date"
                            id="session_date"
                            name="session_date"
                            value="{{ $selectedSessionDate }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-xl-2">
                        <div class="d-grid d-sm-flex gap-2">
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
                                    'attendance-sessions.index'
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
            class="attendance-session-list-card"
            aria-labelledby="attendance-session-list-heading"
        >
            <div class="attendance-session-list-header">
                <div>
                    <h2
                        id="attendance-session-list-heading"
                        class="attendance-session-list-title"
                    >
                        Daftar Sesi Presensi
                    </h2>

                    <p class="attendance-session-list-copy">
                        Ditemukan
                        {{ $attendanceSessions->total() }}
                        sesi presensi.
                    </p>
                </div>

                @if ($filterActive)
                    <span class="badge text-bg-warning">
                        Filter aktif
                    </span>
                @else
                    <span class="badge text-bg-secondary">
                        Halaman
                        {{ $attendanceSessions->currentPage() }}
                    </span>
                @endif
            </div>

            @if ($attendanceSessions->isEmpty())
                <div class="attendance-session-empty-state">
                    <span class="attendance-session-empty-icon">
                        <i
                            class="bi bi-qr-code-scan"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="attendance-session-empty-title">
                        Sesi presensi tidak ditemukan
                    </h3>

                    <p class="attendance-session-empty-copy">
                        Belum terdapat sesi presensi atau data
                        tidak sesuai dengan cabang, jenis, status,
                        dan tanggal yang digunakan.
                    </p>

                    <a
                        href="{{ route(
                            'attendance-sessions.index'
                        ) }}"
                        class="btn btn-outline-primary"
                    >
                        Reset Filter
                    </a>
                </div>
            @else
                <div
                    class="attendance-session-desktop-table
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
                                    Cabang
                                </th>

                                <th scope="col">
                                    Jenis Sesi
                                </th>

                                <th scope="col">
                                    Tanggal dan Waktu
                                </th>

                                <th scope="col">
                                    Pembuat
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Presensi
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Status
                                </th>

                                <th
                                    scope="col"
                                    class="text-end"
                                    style="width: 8rem;"
                                >
                                    Tindakan
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $attendanceSessions
                                as $attendanceSession
                            )
                                @php
                                    $branch =
                                        $attendanceSession->branch;

                                    $creator =
                                        $attendanceSession->creator;

                                    $attendanceType =
                                        strtolower(
                                            (string)
                                                $attendanceSession
                                                    ->attendance_type
                                        );

                                    $sessionStatus =
                                        strtolower(
                                            (string)
                                                $attendanceSession
                                                    ->status
                                        );

                                    $attendanceTypeLabel =
                                        $attendanceTypeLabels[
                                            $attendanceType
                                        ]
                                        ?? ucfirst($attendanceType);

                                    $attendanceTypeClass =
                                        $attendanceType
                                        === 'check_in'
                                            ? 'attendance-session-type-check-in'
                                            : 'attendance-session-type-check-out';

                                    $attendanceTypeIcon =
                                        $attendanceType
                                        === 'check_in'
                                            ? 'bi-box-arrow-in-right'
                                            : 'bi-box-arrow-right';

                                    $statusLabel =
                                        $statusLabels[
                                            $sessionStatus
                                        ]
                                        ?? ucfirst($sessionStatus);

                                    $statusClass =
                                        $statusClasses[
                                            $sessionStatus
                                        ]
                                        ?? 'text-bg-secondary';

                                    $creatorRoleLabel = match (
                                        $creator?->role
                                    ) {
                                        'hrd' => 'HRD',
                                        'admin' => 'Admin Operasional',
                                        default => 'Pengguna',
                                    };
                                @endphp

                                <tr>
                                    <td
                                        class="text-center
                                            text-secondary"
                                    >
                                        {{
                                            ($attendanceSessions
                                                ->firstItem()
                                                ?? 0)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        @if ($branch !== null)
                                            <span
                                                class="attendance-session-branch-code"
                                            >
                                                {{ $branch->code }}
                                            </span>

                                            <span
                                                class="attendance-session-branch-name"
                                            >
                                                {{ $branch->name }}
                                            </span>

                                            @if (
                                                $branch->status
                                                !== 'active'
                                            )
                                                <span
                                                    class="badge
                                                        text-bg-secondary
                                                        mt-2"
                                                >
                                                    Cabang tidak aktif
                                                </span>
                                            @endif
                                        @else
                                            <span
                                                class="text-danger
                                                    small"
                                            >
                                                Data cabang tidak
                                                tersedia
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span
                                            class="attendance-session-type
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
                                            class="attendance-session-uuid"
                                            title="{{
                                                $attendanceSession
                                                    ->public_id
                                            }}"
                                        >
                                            UUID:
                                            {{
                                                \Illuminate\Support\Str::limit(
                                                    (string)
                                                        $attendanceSession
                                                            ->public_id,
                                                    18,
                                                    '…'
                                                )
                                            }}
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="attendance-session-date"
                                        >
                                            {{
                                                $formatDate(
                                                    $attendanceSession
                                                        ->session_date
                                                )
                                            }}
                                        </span>

                                        <span
                                            class="attendance-session-time"
                                        >
                                            <i
                                                class="bi bi-clock"
                                                aria-hidden="true"
                                            ></i>

                                            {{
                                                $formatTime(
                                                    $attendanceSession
                                                        ->start_time
                                                )
                                            }}
                                            –
                                            {{
                                                $formatTime(
                                                    $attendanceSession
                                                        ->end_time
                                                )
                                            }}
                                            WIB
                                        </span>

                                        @if (
                                            $attendanceSession
                                                ->closed_at
                                            !== null
                                        )
                                            <span
                                                class="attendance-session-closed"
                                            >
                                                Ditutup:
                                                {{
                                                    $formatDateTime(
                                                        $attendanceSession
                                                            ->closed_at
                                                    )
                                                }}
                                                WIB
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($creator !== null)
                                            <span
                                                class="attendance-session-creator-name"
                                            >
                                                {{ $creator->name }}
                                            </span>

                                            <span
                                                class="attendance-session-creator-role"
                                            >
                                                {{ $creatorRoleLabel }}
                                            </span>
                                        @else
                                            <span
                                                class="text-secondary
                                                    small"
                                            >
                                                Data pengguna tidak
                                                tersedia
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <span
                                            class="attendance-session-count"
                                            title="Jumlah data presensi"
                                        >
                                            {{
                                                (int)
                                                    $attendanceSession
                                                        ->attendances_count
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

                                    <td class="text-end">
                                        <div
                                            class="attendance-session-action-group"
                                        >
                                            <a
                                                href="{{ route(
                                                    'attendance-sessions.show',
                                                    $attendanceSession
                                                ) }}"
                                                class="btn btn-sm
                                                    btn-outline-primary"
                                            >
                                                <i
                                                    class="bi bi-eye me-1"
                                                    aria-hidden="true"
                                                ></i>

                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="attendance-session-mobile-list">
                    @foreach (
                        $attendanceSessions
                        as $attendanceSession
                    )
                        @php
                            $branch =
                                $attendanceSession->branch;

                            $creator =
                                $attendanceSession->creator;

                            $attendanceType =
                                strtolower(
                                    (string)
                                        $attendanceSession
                                            ->attendance_type
                                );

                            $sessionStatus =
                                strtolower(
                                    (string)
                                        $attendanceSession->status
                                );

                            $attendanceTypeLabel =
                                $attendanceTypeLabels[
                                    $attendanceType
                                ]
                                ?? ucfirst($attendanceType);

                            $attendanceTypeClass =
                                $attendanceType === 'check_in'
                                    ? 'attendance-session-type-check-in'
                                    : 'attendance-session-type-check-out';

                            $attendanceTypeIcon =
                                $attendanceType === 'check_in'
                                    ? 'bi-box-arrow-in-right'
                                    : 'bi-box-arrow-right';

                            $statusLabel =
                                $statusLabels[$sessionStatus]
                                ?? ucfirst($sessionStatus);

                            $statusClass =
                                $statusClasses[$sessionStatus]
                                ?? 'text-bg-secondary';

                            $creatorRoleLabel = match (
                                $creator?->role
                            ) {
                                'hrd' => 'HRD',
                                'admin' => 'Admin Operasional',
                                default => 'Pengguna',
                            };
                        @endphp

                        <article
                            class="attendance-session-mobile-card"
                        >
                            <div
                                class="attendance-session-mobile-top"
                            >
                                <div>
                                    <span
                                        class="attendance-session-type
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
                                        class="attendance-session-uuid"
                                        title="{{
                                            $attendanceSession
                                                ->public_id
                                        }}"
                                    >
                                        UUID:
                                        {{
                                            \Illuminate\Support\Str::limit(
                                                (string)
                                                    $attendanceSession
                                                        ->public_id,
                                                20,
                                                '…'
                                            )
                                        }}
                                    </span>
                                </div>

                                <span
                                    class="badge
                                        {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div
                                class="attendance-session-mobile-section"
                            >
                                <div
                                    class="attendance-session-mobile-label"
                                >
                                    Cabang
                                </div>

                                @if ($branch !== null)
                                    <span
                                        class="attendance-session-branch-code"
                                    >
                                        {{ $branch->code }}
                                    </span>

                                    <span
                                        class="attendance-session-branch-name"
                                    >
                                        {{ $branch->name }}
                                    </span>

                                    @if (
                                        $branch->status
                                        !== 'active'
                                    )
                                        <span
                                            class="badge
                                                text-bg-secondary
                                                mt-2"
                                        >
                                            Cabang tidak aktif
                                        </span>
                                    @endif
                                @else
                                    <span
                                        class="text-danger small"
                                    >
                                        Data cabang tidak tersedia
                                    </span>
                                @endif
                            </div>

                            <div
                                class="attendance-session-mobile-section"
                            >
                                <div
                                    class="attendance-session-mobile-grid"
                                >
                                    <div
                                        class="attendance-session-mobile-metric"
                                    >
                                        <div
                                            class="attendance-session-mobile-metric-label"
                                        >
                                            Tanggal sesi
                                        </div>

                                        <div
                                            class="attendance-session-mobile-metric-value"
                                        >
                                            {{
                                                $formatDate(
                                                    $attendanceSession
                                                        ->session_date
                                                )
                                            }}
                                        </div>
                                    </div>

                                    <div
                                        class="attendance-session-mobile-metric"
                                    >
                                        <div
                                            class="attendance-session-mobile-metric-label"
                                        >
                                            Waktu aktif
                                        </div>

                                        <div
                                            class="attendance-session-mobile-metric-value"
                                        >
                                            {{
                                                $formatTime(
                                                    $attendanceSession
                                                        ->start_time
                                                )
                                            }}
                                            –
                                            {{
                                                $formatTime(
                                                    $attendanceSession
                                                        ->end_time
                                                )
                                            }}
                                            WIB
                                        </div>
                                    </div>

                                    <div
                                        class="attendance-session-mobile-metric"
                                    >
                                        <div
                                            class="attendance-session-mobile-metric-label"
                                        >
                                            Pembuat
                                        </div>

                                        <div
                                            class="attendance-session-mobile-metric-value"
                                        >
                                            @if ($creator !== null)
                                                {{ $creator->name }}
                                                <br>
                                                <span
                                                    class="text-secondary"
                                                >
                                                    {{
                                                        $creatorRoleLabel
                                                    }}
                                                </span>
                                            @else
                                                Data tidak tersedia
                                            @endif
                                        </div>
                                    </div>

                                    <div
                                        class="attendance-session-mobile-metric"
                                    >
                                        <div
                                            class="attendance-session-mobile-metric-label"
                                        >
                                            Presensi
                                        </div>

                                        <div
                                            class="attendance-session-mobile-metric-value"
                                        >
                                            {{
                                                (int)
                                                    $attendanceSession
                                                        ->attendances_count
                                            }}
                                            data
                                        </div>
                                    </div>
                                </div>

                                @if (
                                    $attendanceSession->closed_at
                                    !== null
                                )
                                    <span
                                        class="attendance-session-closed"
                                    >
                                        Ditutup:
                                        {{
                                            $formatDateTime(
                                                $attendanceSession
                                                    ->closed_at
                                            )
                                        }}
                                        WIB
                                    </span>
                                @endif
                            </div>

                            <div
                                class="attendance-session-mobile-section"
                            >
                                <div
                                    class="attendance-session-action-group"
                                >
                                    <a
                                        href="{{ route(
                                            'attendance-sessions.show',
                                            $attendanceSession
                                        ) }}"
                                        class="btn btn-sm
                                            btn-outline-primary"
                                    >
                                        <i
                                            class="bi bi-eye me-1"
                                            aria-hidden="true"
                                        ></i>

                                        Detail
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($attendanceSessions->hasPages())
                    @php
                        $startPage = max(
                            1,
                            $attendanceSessions
                                ->currentPage() - 2
                        );

                        $endPage = min(
                            $attendanceSessions->lastPage(),
                            $attendanceSessions
                                ->currentPage() + 2
                        );
                    @endphp

                    <div class="attendance-session-pagination">
                        <div class="small text-secondary">
                            Menampilkan
                            {{ $attendanceSessions->firstItem() }}
                            sampai
                            {{ $attendanceSessions->lastItem() }}
                            dari
                            {{ $attendanceSessions->total() }}
                            sesi.
                        </div>

                        <nav
                            aria-label="Navigasi sesi presensi"
                        >
                            <ul
                                class="pagination
                                    pagination-sm mb-0"
                            >
                                <li
                                    class="page-item {{
                                        $attendanceSessions
                                            ->onFirstPage()
                                                ? 'disabled'
                                                : ''
                                    }}"
                                >
                                    <a
                                        href="{{
                                            $attendanceSessions
                                                ->previousPageUrl()
                                            ?? '#'
                                        }}"
                                        class="page-link"
                                    >
                                        Sebelumnya
                                    </a>
                                </li>

                                @foreach (
                                    $attendanceSessions
                                        ->getUrlRange(
                                            $startPage,
                                            $endPage
                                        )
                                    as $page => $url
                                )
                                    <li
                                        class="page-item {{
                                            $page
                                            === $attendanceSessions
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
                                        $attendanceSessions
                                            ->hasMorePages()
                                                ? ''
                                                : 'disabled'
                                    }}"
                                >
                                    <a
                                        href="{{
                                            $attendanceSessions
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
