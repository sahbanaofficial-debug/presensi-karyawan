@extends('layouts.app')

@section('title', 'Jadwal Harian Karyawan')

@push('styles')
    <style>
        .employee-schedule-page {
            --schedule-surface: var(--neutral-0);
            --schedule-border: var(--neutral-200);
            --schedule-muted: var(--neutral-600);
            --schedule-soft: var(--brand-50);
        }

        .employee-schedule-filter,
        .employee-schedule-list-card {
            border: 1px solid var(--schedule-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-surface);
            box-shadow: var(--shadow-xs);
        }

        .employee-schedule-filter {
            margin-bottom: var(--space-4);
            overflow: hidden;
        }

        .employee-schedule-filter-summary {
            display: flex;
            min-height: 4rem;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            color: var(--neutral-900);
            cursor: pointer;
            list-style: none;
        }

        .employee-schedule-filter-summary::-webkit-details-marker {
            display: none;
        }

        .employee-schedule-filter-summary:hover {
            background: var(--neutral-25);
        }

        .employee-schedule-filter-title {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .employee-schedule-filter-icon {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--schedule-soft);
        }

        .employee-schedule-filter-hint {
            color: var(--schedule-muted);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .employee-schedule-filter[open]
            .employee-schedule-filter-chevron {
            transform: rotate(180deg);
        }

        .employee-schedule-filter-chevron {
            transition: transform 150ms ease;
        }

        .employee-schedule-filter-body {
            padding: var(--space-4);
            border-top: 1px solid var(--schedule-border);
        }

        .employee-schedule-search-control {
            position: relative;
        }

        .employee-schedule-search-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: var(--space-3);
            color: var(--neutral-500);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .employee-schedule-search-control .form-control {
            padding-left: 2.75rem;
        }

        .employee-schedule-list-card {
            overflow: hidden;
        }

        .employee-schedule-list-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--schedule-border);
            background: var(--neutral-25);
        }

        .employee-schedule-list-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .employee-schedule-list-copy {
            margin: var(--space-1) 0 0;
            color: var(--schedule-muted);
            font-size: 0.75rem;
        }

        .employee-schedule-list-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-2);
        }

        .employee-schedule-meta-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: 0.4rem 0.7rem;
            border-radius: var(--radius-pill);
            color: var(--neutral-700);
            background: var(--neutral-100);
            font-size: 0.6875rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .employee-schedule-meta-badge.primary {
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .employee-schedule-table-wrap {
            overflow-x: auto;
        }

        .employee-schedule-table {
            min-width: 64rem;
        }

        .employee-schedule-table > :not(caption) > * > * {
            padding: 0.8rem 0.9rem;
        }

        .employee-schedule-date {
            min-width: 10.5rem;
        }

        .employee-schedule-date-day {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
        }

        .employee-schedule-date-value {
            display: block;
            margin-top: 0.125rem;
            color: var(--schedule-muted);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .employee-schedule-person {
            min-width: 13rem;
        }

        .employee-schedule-person-name {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .employee-schedule-person-meta {
            display: block;
            margin-top: 0.2rem;
            color: var(--schedule-muted);
            font-size: 0.7rem;
            line-height: 1.45;
        }

        .employee-schedule-person-number {
            color: var(--brand-700);
            font-weight: 800;
        }

        .employee-schedule-work {
            min-width: 14rem;
        }

        .employee-schedule-work-name {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .employee-schedule-work-time {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            margin-top: 0.25rem;
            color: var(--schedule-muted);
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .employee-schedule-work-time i {
            color: var(--brand-600);
        }

        .employee-schedule-attendance-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: 0.4rem 0.65rem;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-pill);
            color: var(--neutral-700);
            background: var(--neutral-50);
            font-size: 0.6875rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .employee-schedule-action-button {
            min-width: 6rem;
        }

        .employee-schedule-mobile-list {
            display: none;
        }

        .employee-schedule-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--schedule-border);
        }

        .employee-schedule-mobile-card:last-child {
            border-bottom: 0;
        }

        .employee-schedule-mobile-top,
        .employee-schedule-mobile-footer {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .employee-schedule-mobile-person {
            margin-top: var(--space-3);
        }

        .employee-schedule-mobile-work {
            margin-top: var(--space-3);
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .employee-schedule-mobile-branch {
            margin-top: var(--space-3);
            color: var(--schedule-muted);
            font-size: 0.7rem;
            line-height: 1.5;
        }

        .employee-schedule-mobile-footer {
            align-items: center;
            margin-top: var(--space-3);
            padding-top: var(--space-3);
            border-top: 1px solid var(--neutral-100);
        }

        .employee-schedule-empty-state {
            padding: var(--space-8) var(--space-5);
            text-align: center;
        }

        .employee-schedule-empty-icon {
            display: inline-flex;
            width: 3.75rem;
            height: 3.75rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--schedule-soft);
            font-size: 1.5rem;
        }

        .employee-schedule-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .employee-schedule-empty-copy {
            max-width: 32rem;
            margin: var(--space-2) auto var(--space-4);
            color: var(--schedule-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .employee-schedule-pagination {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border-top: 1px solid var(--schedule-border);
        }

        @media (min-width: 768px) {
            .employee-schedule-pagination {
                flex-direction: row;
                align-items: center;
                padding: var(--space-4) var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .employee-schedule-desktop-table {
                display: none;
            }

            .employee-schedule-mobile-list {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .employee-schedule-list-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .employee-schedule-list-meta {
                justify-content: flex-start;
            }

            .employee-schedule-filter-summary {
                align-items: flex-start;
            }

            .employee-schedule-filter-hint {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $formatDateDay = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l');
            } catch (\Throwable) {
                return '-';
            }
        };

        $formatDateValue = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d M Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDateFull = static function ($value) use (
            $formatDateDay,
            $formatDateValue
        ): string {
            return $formatDateDay($value)
                . ', '
                . $formatDateValue($value);
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

        $statusLabels = [
            'work' => 'Kerja',
            'off' => 'Libur',
            'leave' => 'Cuti',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $statusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'leave' => 'text-bg-primary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];

        $filterActive =
            $search !== ''
            || $selectedBranchId !== null
            || $selectedStatus !== ''
            || $dateFrom !== null
            || $dateTo !== null;

        $selectedBranch = $selectedBranchId !== null
            ? $branches->firstWhere('id', $selectedBranchId)
            : null;

        $scopeLabel = $selectedBranch !== null
            ? $selectedBranch->code . ' — ' . $selectedBranch->name
            : 'Semua cabang';
    @endphp

    <div class="employee-schedule-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Jadwal Harian Karyawan
                </h1>

                <p class="page-description">
                    Tinjau jadwal secara kronologis, status kerja,
                    pola jam, dan jumlah transaksi presensi.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route('employee-schedules.create') }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-calendar-plus me-2"
                        aria-hidden="true"
                    ></i>

                    Tambah Jadwal Harian
                </a>
            </div>
        </header>

        <details
            class="employee-schedule-filter"
            @if ($filterActive) open @endif
        >
            <summary class="employee-schedule-filter-summary">
                <span class="employee-schedule-filter-title">
                    <span class="employee-schedule-filter-icon">
                        <i
                            class="bi bi-funnel"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <span>
                        Filter jadwal
                    </span>
                </span>

                <span class="d-flex align-items-center gap-3">
                    <span class="employee-schedule-filter-hint">
                        {{
                            $filterActive
                                ? 'Filter sedang aktif'
                                : 'Buka untuk menyaring data'
                        }}
                    </span>

                    <i
                        class="bi bi-chevron-down
                            employee-schedule-filter-chevron"
                        aria-hidden="true"
                    ></i>
                </span>
            </summary>

            <div class="employee-schedule-filter-body">
                <form
                    method="GET"
                    action="{{ route('employee-schedules.index') }}"
                >
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6 col-xl-3">
                            <label
                                for="search"
                                class="form-label"
                            >
                                Karyawan
                            </label>

                            <div
                                class="employee-schedule-search-control"
                            >
                                <i
                                    class="bi bi-search
                                        employee-schedule-search-icon"
                                    aria-hidden="true"
                                ></i>

                                <input
                                    type="search"
                                    id="search"
                                    name="search"
                                    value="{{ $search }}"
                                    class="form-control"
                                    placeholder="Nama atau nomor karyawan"
                                >
                            </div>
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

                                @foreach ($branches as $branch)
                                    <option
                                        value="{{ $branch->id }}"
                                        @selected(
                                            (string) $selectedBranchId
                                            === (string) $branch->id
                                        )
                                    >
                                        {{ $branch->code }}
                                        — {{ $branch->name }}

                                        @if (
                                            $branch->status
                                            === 'inactive'
                                        )
                                            (Tidak aktif)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 col-xl-2">
                            <label
                                for="schedule_status"
                                class="form-label"
                            >
                                Status
                            </label>

                            <select
                                id="schedule_status"
                                name="schedule_status"
                                class="form-select"
                            >
                                <option value="">
                                    Semua status
                                </option>

                                @foreach (
                                    $statusLabels
                                    as $statusValue => $statusLabel
                                )
                                    <option
                                        value="{{ $statusValue }}"
                                        @selected(
                                            $selectedStatus
                                            === $statusValue
                                        )
                                    >
                                        {{ $statusLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 col-xl-2">
                            <label
                                for="date_from"
                                class="form-label"
                            >
                                Mulai
                            </label>

                            <input
                                type="date"
                                id="date_from"
                                name="date_from"
                                value="{{ $dateFrom }}"
                                class="form-control"
                            >
                        </div>

                        <div class="col-md-6 col-xl-2">
                            <label
                                for="date_to"
                                class="form-label"
                            >
                                Sampai
                            </label>

                            <input
                                type="date"
                                id="date_to"
                                name="date_to"
                                value="{{ $dateTo }}"
                                class="form-control"
                            >
                        </div>

                        <div class="col-12">
                            <div class="d-grid d-sm-flex gap-2">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Terapkan Filter
                                </button>

                                <a
                                    href="{{ route(
                                        'employee-schedules.index'
                                    ) }}"
                                    class="btn btn-outline-secondary"
                                >
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </details>

        <section
            class="employee-schedule-list-card"
            aria-labelledby="employee-schedule-list-heading"
        >
            <div class="employee-schedule-list-header">
                <div>
                    <h2
                        id="employee-schedule-list-heading"
                        class="employee-schedule-list-title"
                    >
                        Daftar Jadwal Harian
                    </h2>

                    <p class="employee-schedule-list-copy">
                        {{ $employeeSchedules->total() }} data
                        ditampilkan dari tanggal paling awal
                        ke tanggal paling akhir.
                    </p>
                </div>

                <div class="employee-schedule-list-meta">
                    <span
                        class="employee-schedule-meta-badge primary"
                    >
                        <i
                            class="bi bi-sort-down-alt"
                            aria-hidden="true"
                        ></i>

                        Urutan kronologis
                    </span>

                    <span class="employee-schedule-meta-badge">
                        <i
                            class="bi bi-building"
                            aria-hidden="true"
                        ></i>

                        {{ $scopeLabel }}
                    </span>

                    @if ($filterActive)
                        <span class="badge text-bg-warning">
                            Filter aktif
                        </span>
                    @endif
                </div>
            </div>

            @if ($employeeSchedules->isEmpty())
                <div class="employee-schedule-empty-state">
                    <span class="employee-schedule-empty-icon">
                        <i
                            class="bi bi-calendar2-x"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="employee-schedule-empty-title">
                        Jadwal harian tidak ditemukan
                    </h3>

                    <p class="employee-schedule-empty-copy">
                        Belum ada jadwal atau data tidak sesuai
                        dengan filter yang digunakan.
                    </p>

                    <a
                        href="{{ route(
                            'employee-schedules.index'
                        ) }}"
                        class="btn btn-outline-primary"
                    >
                        Reset Filter
                    </a>
                </div>
            @else
                <div
                    class="employee-schedule-desktop-table
                        employee-schedule-table-wrap"
                >
                    <table
                        class="table table-hover align-middle
                            employee-schedule-table"
                    >
                        <thead>
                            <tr>
                                <th scope="col">
                                    Tanggal
                                </th>

                                <th scope="col">
                                    Karyawan
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Status
                                </th>

                                <th scope="col">
                                    Jadwal Kerja
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Presensi
                                </th>

                                <th
                                    scope="col"
                                    class="text-end"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $employeeSchedules
                                as $employeeSchedule
                            )
                                @php
                                    $employee =
                                        $employeeSchedule->employee;

                                    $branch = $employee?->branch;

                                    $workSchedule =
                                        $employeeSchedule
                                            ->workSchedule;

                                    $scheduleStatus =
                                        $employeeSchedule
                                            ->schedule_status;

                                    $statusLabel =
                                        $statusLabels[
                                            $scheduleStatus
                                        ]
                                        ?? ucfirst(
                                            $scheduleStatus
                                        );

                                    $statusClass =
                                        $statusClasses[
                                            $scheduleStatus
                                        ]
                                        ?? 'text-bg-secondary';

                                    $workScheduleName =
                                        $employeeSchedule
                                            ->work_schedule_name_snapshot
                                        ?? $workSchedule?->name;

                                    $checkInTime = $formatTime(
                                        $employeeSchedule
                                            ->check_in_time_snapshot
                                        ?? $workSchedule
                                            ?->check_in_time
                                    );

                                    $checkOutTime = $formatTime(
                                        $employeeSchedule
                                            ->check_out_time_snapshot
                                        ?? $workSchedule
                                            ?->check_out_time
                                    );

                                    $hasWorkTime =
                                        $scheduleStatus === 'work'
                                        && $workScheduleName !== null
                                        && $checkInTime !== '-'
                                        && $checkOutTime !== '-';

                                    $attendanceCount =
                                        $employeeSchedule
                                            ->attendances_count
                                        ?? 0;
                                @endphp

                                <tr
                                    data-schedule-date="{{
                                        $employeeSchedule
                                            ->schedule_date
                                            ?->toDateString()
                                    }}"
                                >
                                    <td>
                                        <div
                                            class="employee-schedule-date"
                                            aria-label="{{
                                                $formatDateFull(
                                                    $employeeSchedule
                                                        ->schedule_date
                                                )
                                            }}"
                                        >
                                            <span
                                                class="employee-schedule-date-day"
                                            >
                                                {{
                                                    $formatDateDay(
                                                        $employeeSchedule
                                                            ->schedule_date
                                                    )
                                                }}
                                            </span>

                                            <span
                                                class="employee-schedule-date-value"
                                            >
                                                {{
                                                    $formatDateValue(
                                                        $employeeSchedule
                                                            ->schedule_date
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        @if ($employee !== null)
                                            <div
                                                class="employee-schedule-person"
                                            >
                                                <span
                                                    class="employee-schedule-person-name"
                                                >
                                                    {{
                                                        $employee
                                                            ->full_name
                                                    }}
                                                </span>

                                                <span
                                                    class="employee-schedule-person-meta"
                                                >
                                                    <span
                                                        class="employee-schedule-person-number"
                                                    >
                                                        {{
                                                            $employee
                                                                ->employee_number
                                                        }}
                                                    </span>

                                                    ·
                                                    {{
                                                        $employee
                                                            ->position
                                                    }}
                                                </span>
                                            </div>
                                        @else
                                            <span
                                                class="text-danger small"
                                            >
                                                Data karyawan tidak
                                                tersedia
                                            </span>
                                        @endif
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
                                            class="employee-schedule-work"
                                        >
                                            @if ($hasWorkTime)
                                                <span
                                                    class="employee-schedule-work-name"
                                                >
                                                    {{
                                                        $workScheduleName
                                                    }}
                                                </span>

                                                <span
                                                    class="employee-schedule-work-time"
                                                >
                                                    <i
                                                        class="bi bi-clock"
                                                        aria-hidden="true"
                                                    ></i>

                                                    {{ $checkInTime }}–{{ $checkOutTime }} WIB
                                                </span>
                                            @else
                                                <span
                                                    class="employee-schedule-work-name"
                                                >
                                                    Tidak ada jam kerja
                                                </span>

                                                <span
                                                    class="employee-schedule-work-time"
                                                >
                                                    Status
                                                    {{ strtolower(
                                                        $statusLabel
                                                    ) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span
                                            class="employee-schedule-attendance-badge"
                                        >
                                            <i
                                                class="bi bi-check2-square"
                                                aria-hidden="true"
                                            ></i>

                                            {{ $attendanceCount }}
                                            data
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button
                                                type="button"
                                                class="btn btn-sm
                                                    btn-outline-secondary
                                                    dropdown-toggle
                                                    employee-schedule-action-button"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                Aksi
                                            </button>

                                            <ul
                                                class="dropdown-menu
                                                    dropdown-menu-end"
                                            >
                                                <li>
                                                    <a
                                                        href="{{ route(
                                                            'employee-schedules.show',
                                                            $employeeSchedule
                                                        ) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i
                                                            class="bi bi-eye me-2"
                                                            aria-hidden="true"
                                                        ></i>

                                                        Detail
                                                    </a>
                                                </li>

                                                <li>
                                                    <a
                                                        href="{{ route(
                                                            'employee-schedules.edit',
                                                            $employeeSchedule
                                                        ) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i
                                                            class="bi bi-pencil-square me-2"
                                                            aria-hidden="true"
                                                        ></i>

                                                        Edit
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="employee-schedule-mobile-list">
                    @foreach (
                        $employeeSchedules
                        as $employeeSchedule
                    )
                        @php
                            $employee =
                                $employeeSchedule->employee;

                            $branch = $employee?->branch;

                            $workSchedule =
                                $employeeSchedule->workSchedule;

                            $scheduleStatus =
                                $employeeSchedule
                                    ->schedule_status;

                            $statusLabel =
                                $statusLabels[$scheduleStatus]
                                ?? ucfirst($scheduleStatus);

                            $statusClass =
                                $statusClasses[$scheduleStatus]
                                ?? 'text-bg-secondary';

                            $workScheduleName =
                                $employeeSchedule
                                    ->work_schedule_name_snapshot
                                ?? $workSchedule?->name;

                            $checkInTime = $formatTime(
                                $employeeSchedule
                                    ->check_in_time_snapshot
                                ?? $workSchedule?->check_in_time
                            );

                            $checkOutTime = $formatTime(
                                $employeeSchedule
                                    ->check_out_time_snapshot
                                ?? $workSchedule?->check_out_time
                            );

                            $hasWorkTime =
                                $scheduleStatus === 'work'
                                && $workScheduleName !== null
                                && $checkInTime !== '-'
                                && $checkOutTime !== '-';

                            $attendanceCount =
                                $employeeSchedule
                                    ->attendances_count
                                ?? 0;
                        @endphp

                        <article
                            class="employee-schedule-mobile-card"
                            data-schedule-date="{{
                                $employeeSchedule
                                    ->schedule_date
                                    ?->toDateString()
                            }}"
                        >
                            <div
                                class="employee-schedule-mobile-top"
                            >
                                <div>
                                    <span
                                        class="employee-schedule-date-day"
                                    >
                                        {{
                                            $formatDateDay(
                                                $employeeSchedule
                                                    ->schedule_date
                                            )
                                        }}
                                    </span>

                                    <span
                                        class="employee-schedule-date-value"
                                    >
                                        {{
                                            $formatDateValue(
                                                $employeeSchedule
                                                    ->schedule_date
                                            )
                                        }}
                                    </span>
                                </div>

                                <span
                                    class="badge {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div
                                class="employee-schedule-mobile-person"
                            >
                                @if ($employee !== null)
                                    <span
                                        class="employee-schedule-person-name"
                                    >
                                        {{ $employee->full_name }}
                                    </span>

                                    <span
                                        class="employee-schedule-person-meta"
                                    >
                                        <span
                                            class="employee-schedule-person-number"
                                        >
                                            {{
                                                $employee
                                                    ->employee_number
                                            }}
                                        </span>

                                        · {{ $employee->position }}
                                    </span>
                                @else
                                    <span class="text-danger small">
                                        Data karyawan tidak tersedia
                                    </span>
                                @endif
                            </div>

                            <div
                                class="employee-schedule-mobile-work"
                            >
                                @if ($hasWorkTime)
                                    <span
                                        class="employee-schedule-work-name"
                                    >
                                        {{ $workScheduleName }}
                                    </span>

                                    <span
                                        class="employee-schedule-work-time"
                                    >
                                        <i
                                            class="bi bi-clock"
                                            aria-hidden="true"
                                        ></i>

                                        {{ $checkInTime }}–{{ $checkOutTime }} WIB
                                    </span>
                                @else
                                    <span
                                        class="employee-schedule-work-name"
                                    >
                                        Tidak ada jam kerja
                                    </span>

                                    <span
                                        class="employee-schedule-work-time"
                                    >
                                        Status
                                        {{ strtolower($statusLabel) }}
                                    </span>
                                @endif
                            </div>

                            @if ($branch !== null)
                                <div
                                    class="employee-schedule-mobile-branch"
                                >
                                    <i
                                        class="bi bi-building me-1"
                                        aria-hidden="true"
                                    ></i>

                                    {{ $branch->code }}
                                    — {{ $branch->name }}
                                </div>
                            @endif

                            <div
                                class="employee-schedule-mobile-footer"
                            >
                                <span
                                    class="employee-schedule-attendance-badge"
                                >
                                    <i
                                        class="bi bi-check2-square"
                                        aria-hidden="true"
                                    ></i>

                                    {{ $attendanceCount }} data
                                </span>

                                <div class="dropdown">
                                    <button
                                        type="button"
                                        class="btn btn-sm
                                            btn-outline-secondary
                                            dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Aksi
                                    </button>

                                    <ul
                                        class="dropdown-menu
                                            dropdown-menu-end"
                                    >
                                        <li>
                                            <a
                                                href="{{ route(
                                                    'employee-schedules.show',
                                                    $employeeSchedule
                                                ) }}"
                                                class="dropdown-item"
                                            >
                                                Detail
                                            </a>
                                        </li>

                                        <li>
                                            <a
                                                href="{{ route(
                                                    'employee-schedules.edit',
                                                    $employeeSchedule
                                                ) }}"
                                                class="dropdown-item"
                                            >
                                                Edit
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($employeeSchedules->hasPages())
                    @php
                        $startPage = max(
                            1,
                            $employeeSchedules
                                ->currentPage() - 2
                        );

                        $endPage = min(
                            $employeeSchedules->lastPage(),
                            $employeeSchedules
                                ->currentPage() + 2
                        );
                    @endphp

                    <div class="employee-schedule-pagination">
                        <div class="small text-secondary">
                            Menampilkan
                            {{ $employeeSchedules->firstItem() }}
                            sampai
                            {{ $employeeSchedules->lastItem() }}
                            dari
                            {{ $employeeSchedules->total() }}
                            data.
                        </div>

                        <nav
                            aria-label="Navigasi jadwal harian"
                        >
                            <ul
                                class="pagination
                                    pagination-sm mb-0"
                            >
                                <li
                                    class="page-item {{
                                        $employeeSchedules
                                            ->onFirstPage()
                                                ? 'disabled'
                                                : ''
                                    }}"
                                >
                                    <a
                                        class="page-link"
                                        href="{{
                                            $employeeSchedules
                                                ->previousPageUrl()
                                            ?? '#'
                                        }}"
                                    >
                                        Sebelumnya
                                    </a>
                                </li>

                                @foreach (
                                    $employeeSchedules
                                        ->getUrlRange(
                                            $startPage,
                                            $endPage
                                        )
                                    as $page => $url
                                )
                                    <li
                                        class="page-item {{
                                            $page
                                            === $employeeSchedules
                                                ->currentPage()
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <a
                                            class="page-link"
                                            href="{{ $url }}"
                                        >
                                            {{ $page }}
                                        </a>
                                    </li>
                                @endforeach

                                <li
                                    class="page-item {{
                                        $employeeSchedules
                                            ->hasMorePages()
                                                ? ''
                                                : 'disabled'
                                    }}"
                                >
                                    <a
                                        class="page-link"
                                        href="{{
                                            $employeeSchedules
                                                ->nextPageUrl()
                                            ?? '#'
                                        }}"
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
