@extends('layouts.app')

@section('title', 'Jadwal Harian Karyawan')

@push('styles')
    <style>
        .employee-schedule-page {
            --schedule-surface: var(--neutral-0);
            --schedule-border: var(--neutral-200);
            --schedule-muted: var(--neutral-600);
            --schedule-orange-soft: var(--brand-50);
        }

        .employee-schedule-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .employee-schedule-summary-card {
            padding: var(--space-4);
            border: 1px solid var(--schedule-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-surface);
            box-shadow: var(--shadow-xs);
        }

        .employee-schedule-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--schedule-orange-soft);
            font-size: 1rem;
        }

        .employee-schedule-summary-label {
            color: var(--schedule-muted);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .employee-schedule-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .employee-schedule-summary-copy {
            margin-top: var(--space-1);
            color: var(--schedule-muted);
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .employee-schedule-toolbar {
            border: 1px solid var(--schedule-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-surface);
            box-shadow: var(--shadow-xs);
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
            border: 1px solid var(--schedule-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-surface);
            box-shadow: var(--shadow-xs);
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

        .employee-schedule-date {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .employee-schedule-day {
            display: block;
            margin-top: var(--space-1);
            color: var(--schedule-muted);
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .employee-schedule-person {
            min-width: 13rem;
        }

        .employee-schedule-person-name {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .employee-schedule-person-number {
            display: block;
            margin-top: var(--space-1);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .employee-schedule-person-position {
            display: block;
            margin-top: var(--space-1);
            color: var(--schedule-muted);
            font-size: 0.75rem;
            line-height: 1.45;
        }

        .employee-schedule-branch-code {
            display: block;
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.035em;
            text-transform: uppercase;
        }

        .employee-schedule-branch-name {
            display: block;
            margin-top: var(--space-1);
            color: var(--neutral-700);
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .employee-schedule-pattern-name {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.45;
        }

        .employee-schedule-time {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .employee-schedule-time i {
            color: var(--brand-600);
        }

        .employee-schedule-timezone {
            display: block;
            margin-top: var(--space-1);
            color: var(--schedule-muted);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .employee-schedule-attendance-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: 0.4375rem 0.6875rem;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-pill);
            color: var(--neutral-700);
            background: var(--neutral-50);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .employee-schedule-action-group {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-2);
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

        .employee-schedule-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .employee-schedule-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .employee-schedule-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--schedule-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .employee-schedule-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .employee-schedule-mobile-metric {
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .employee-schedule-mobile-metric-label {
            color: var(--schedule-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .employee-schedule-mobile-metric-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.5;
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
            background: var(--schedule-orange-soft);
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

        @media (max-width: 1199.98px) {
            .employee-schedule-summary-grid {
                grid-template-columns: 1fr;
            }

            .employee-schedule-desktop-table {
                display: none;
            }

            .employee-schedule-mobile-list {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .employee-schedule-list-header {
                padding: var(--space-4);
            }

            .employee-schedule-mobile-grid {
                grid-template-columns: 1fr;
            }

            .employee-schedule-action-group {
                width: 100%;
            }

            .employee-schedule-action-group .btn {
                flex: 1;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDay = static function ($value): string {
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
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $statusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];

        $filterActive =
            $search !== ''
            || $selectedBranchId !== null
            || $selectedStatus !== ''
            || $dateFrom !== null
            || $dateTo !== null;
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
                    Kelola penetapan hari kerja, libur, izin,
                    sakit, pola jadwal, dan cabang penempatan.
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

        <section
            class="employee-schedule-summary-grid"
            aria-label="Ringkasan jadwal harian"
        >
            <article class="employee-schedule-summary-card">
                <span class="employee-schedule-summary-icon">
                    <i
                        class="bi bi-calendar2-week"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="employee-schedule-summary-label">
                    Total jadwal
                </div>

                <div class="employee-schedule-summary-value">
                    {{ $employeeSchedules->total() }}
                </div>

                <div class="employee-schedule-summary-copy">
                    Jadwal yang sesuai dengan filter aktif.
                </div>
            </article>

            <article class="employee-schedule-summary-card">
                <span class="employee-schedule-summary-icon">
                    <i
                        class="bi bi-list-check"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="employee-schedule-summary-label">
                    Data pada halaman
                </div>

                <div class="employee-schedule-summary-value">
                    {{ $employeeSchedules->count() }}
                </div>

                <div class="employee-schedule-summary-copy">
                    Jumlah jadwal pada halaman saat ini.
                </div>
            </article>

            <article class="employee-schedule-summary-card">
                <span class="employee-schedule-summary-icon">
                    <i
                        class="bi bi-funnel"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="employee-schedule-summary-label">
                    Status filter
                </div>

                <div class="employee-schedule-summary-value">
                    {{ $filterActive ? 'Aktif' : 'Semua' }}
                </div>

                <div class="employee-schedule-summary-copy">
                    {{
                        $filterActive
                            ? 'Daftar telah disaring.'
                            : 'Menampilkan seluruh jadwal.'
                    }}
                </div>
            </article>
        </section>

        <section
            class="employee-schedule-toolbar
                p-3 p-md-4 mb-4"
        >
            <div class="mb-3">
                <h2 class="section-title">
                    Filter jadwal harian
                </h2>

                <p class="section-description">
                    Cari berdasarkan karyawan, cabang, status,
                    atau rentang tanggal.
                </p>
            </div>

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
                            Pencarian Karyawan
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
                            Status Jadwal
                        </label>

                        <select
                            id="schedule_status"
                            name="schedule_status"
                            class="form-select"
                        >
                            <option value="">
                                Semua status
                            </option>

                            <option
                                value="work"
                                @selected(
                                    $selectedStatus === 'work'
                                )
                            >
                                Kerja
                            </option>

                            <option
                                value="off"
                                @selected(
                                    $selectedStatus === 'off'
                                )
                            >
                                Libur
                            </option>

                            <option
                                value="permit"
                                @selected(
                                    $selectedStatus === 'permit'
                                )
                            >
                                Izin
                            </option>

                            <option
                                value="sick"
                                @selected(
                                    $selectedStatus === 'sick'
                                )
                            >
                                Sakit
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label
                            for="date_from"
                            class="form-label"
                        >
                            Tanggal Mulai
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
                            Tanggal Akhir
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
                                <i
                                    class="bi bi-funnel me-2"
                                    aria-hidden="true"
                                ></i>

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
        </section>

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
                        Ditemukan {{ $employeeSchedules->total() }}
                        data jadwal.
                    </p>
                </div>

                @if ($filterActive)
                    <span class="badge text-bg-warning">
                        Filter aktif
                    </span>
                @else
                    <span class="badge text-bg-secondary">
                        Halaman {{ $employeeSchedules->currentPage() }}
                    </span>
                @endif
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
                        dengan filter karyawan, cabang, status,
                        dan tanggal yang digunakan.
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
                                    Tanggal
                                </th>

                                <th scope="col">
                                    Karyawan
                                </th>

                                <th scope="col">
                                    Cabang
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Status
                                </th>

                                <th scope="col">
                                    Pola Jadwal
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Jam Kerja
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
                                    style="width: 11rem;"
                                >
                                    Tindakan
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
                                @endphp

                                <tr>
                                    <td
                                        class="text-center
                                            text-secondary"
                                    >
                                        {{
                                            ($employeeSchedules
                                                ->firstItem()
                                                ?? 0)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        <span
                                            class="employee-schedule-date"
                                        >
                                            {{
                                                $formatDate(
                                                    $employeeSchedule
                                                        ->schedule_date
                                                )
                                            }}
                                        </span>

                                        <span
                                            class="employee-schedule-day"
                                        >
                                            {{
                                                $formatDay(
                                                    $employeeSchedule
                                                        ->schedule_date
                                                )
                                            }}
                                        </span>
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
                                                    class="employee-schedule-person-number"
                                                >
                                                    {{
                                                        $employee
                                                            ->employee_number
                                                    }}
                                                </span>

                                                <span
                                                    class="employee-schedule-person-position"
                                                >
                                                    {{
                                                        $employee
                                                            ->position
                                                    }}
                                                </span>
                                            </div>
                                        @else
                                            <span
                                                class="text-danger
                                                    small"
                                            >
                                                Data karyawan tidak
                                                tersedia
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($branch !== null)
                                            <span
                                                class="employee-schedule-branch-code"
                                            >
                                                {{ $branch->code }}
                                            </span>

                                            <span
                                                class="employee-schedule-branch-name"
                                            >
                                                {{ $branch->name }}
                                            </span>

                                            @if (
                                                $branch->status
                                                === 'inactive'
                                            )
                                                <span
                                                    class="badge
                                                        text-bg-warning
                                                        mt-2"
                                                >
                                                    Tidak aktif
                                                </span>
                                            @endif
                                        @else
                                            <span
                                                class="text-secondary"
                                            >
                                                -
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
                                        @if (
                                            $scheduleStatus
                                            === 'work'
                                            && $workSchedule !== null
                                        )
                                            <span
                                                class="employee-schedule-pattern-name"
                                            >
                                                {{
                                                    $workSchedule
                                                        ->name
                                                }}
                                            </span>

                                            @if (
                                                $workSchedule
                                                    ->status
                                                === 'inactive'
                                            )
                                                <span
                                                    class="badge
                                                        text-bg-warning
                                                        mt-2"
                                                >
                                                    Pola tidak aktif
                                                </span>
                                            @endif
                                        @else
                                            <span
                                                class="text-secondary
                                                    small"
                                            >
                                                Tidak menggunakan
                                                pola jadwal
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if (
                                            $scheduleStatus
                                            === 'work'
                                            && $workSchedule !== null
                                        )
                                            <span
                                                class="employee-schedule-time"
                                            >
                                                <i
                                                    class="bi bi-clock"
                                                    aria-hidden="true"
                                                ></i>

                                                {{
                                                    $formatTime(
                                                        $workSchedule
                                                            ->check_in_time
                                                    )
                                                }}
                                                –
                                                {{
                                                    $formatTime(
                                                        $workSchedule
                                                            ->check_out_time
                                                    )
                                                }}
                                            </span>

                                            <span
                                                class="employee-schedule-timezone"
                                            >
                                                WIB
                                            </span>
                                        @else
                                            <span
                                                class="text-secondary"
                                            >
                                                -
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <span
                                            class="employee-schedule-attendance-badge"
                                        >
                                            <i
                                                class="bi
                                                    bi-check2-square"
                                                aria-hidden="true"
                                            ></i>

                                            {{
                                                $employeeSchedule
                                                    ->attendances_count
                                                ?? 0
                                            }}
                                            data
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div
                                            class="employee-schedule-action-group"
                                        >
                                            <a
                                                href="{{ route(
                                                    'employee-schedules.show',
                                                    $employeeSchedule
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

                                            <a
                                                href="{{ route(
                                                    'employee-schedules.edit',
                                                    $employeeSchedule
                                                ) }}"
                                                class="btn btn-sm
                                                    btn-outline-secondary"
                                            >
                                                <i
                                                    class="bi
                                                        bi-pencil-square
                                                        me-1"
                                                    aria-hidden="true"
                                                ></i>

                                                Edit
                                            </a>
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
                        @endphp

                        <article
                            class="employee-schedule-mobile-card"
                        >
                            <div
                                class="employee-schedule-mobile-top"
                            >
                                <div>
                                    <h3
                                        class="employee-schedule-date
                                            mb-0"
                                    >
                                        {{
                                            $formatDate(
                                                $employeeSchedule
                                                    ->schedule_date
                                            )
                                        }}
                                    </h3>

                                    <span
                                        class="employee-schedule-day"
                                    >
                                        {{
                                            $formatDay(
                                                $employeeSchedule
                                                    ->schedule_date
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
                                class="employee-schedule-mobile-section"
                            >
                                <div
                                    class="employee-schedule-mobile-label"
                                >
                                    Karyawan
                                </div>

                                @if ($employee !== null)
                                    <div
                                        class="employee-schedule-person"
                                    >
                                        <div
                                            class="employee-schedule-person-name"
                                        >
                                            {{
                                                $employee
                                                    ->full_name
                                            }}
                                        </div>

                                        <span
                                            class="employee-schedule-person-number"
                                        >
                                            {{
                                                $employee
                                                    ->employee_number
                                            }}
                                        </span>

                                        <span
                                            class="employee-schedule-person-position"
                                        >
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
                                        Data karyawan tidak tersedia
                                    </span>
                                @endif
                            </div>

                            <div
                                class="employee-schedule-mobile-section"
                            >
                                <div
                                    class="employee-schedule-mobile-grid"
                                >
                                    <div
                                        class="employee-schedule-mobile-metric"
                                    >
                                        <div
                                            class="employee-schedule-mobile-metric-label"
                                        >
                                            Cabang
                                        </div>

                                        <div
                                            class="employee-schedule-mobile-metric-value"
                                        >
                                            @if ($branch !== null)
                                                {{ $branch->code }}
                                                — {{ $branch->name }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>

                                    <div
                                        class="employee-schedule-mobile-metric"
                                    >
                                        <div
                                            class="employee-schedule-mobile-metric-label"
                                        >
                                            Pola jadwal
                                        </div>

                                        <div
                                            class="employee-schedule-mobile-metric-value"
                                        >
                                            @if (
                                                $scheduleStatus
                                                === 'work'
                                                && $workSchedule
                                                    !== null
                                            )
                                                {{
                                                    $workSchedule
                                                        ->name
                                                }}
                                            @else
                                                Tidak menggunakan
                                                pola jadwal
                                            @endif
                                        </div>
                                    </div>

                                    <div
                                        class="employee-schedule-mobile-metric"
                                    >
                                        <div
                                            class="employee-schedule-mobile-metric-label"
                                        >
                                            Jam kerja
                                        </div>

                                        <div
                                            class="employee-schedule-mobile-metric-value"
                                        >
                                            @if (
                                                $scheduleStatus
                                                === 'work'
                                                && $workSchedule
                                                    !== null
                                            )
                                                {{
                                                    $formatTime(
                                                        $workSchedule
                                                            ->check_in_time
                                                    )
                                                }}
                                                –
                                                {{
                                                    $formatTime(
                                                        $workSchedule
                                                            ->check_out_time
                                                    )
                                                }}
                                                WIB
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>

                                    <div
                                        class="employee-schedule-mobile-metric"
                                    >
                                        <div
                                            class="employee-schedule-mobile-metric-label"
                                        >
                                            Presensi
                                        </div>

                                        <div
                                            class="employee-schedule-mobile-metric-value"
                                        >
                                            {{
                                                $employeeSchedule
                                                    ->attendances_count
                                                ?? 0
                                            }}
                                            data
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="employee-schedule-mobile-section"
                            >
                                <div
                                    class="employee-schedule-action-group"
                                >
                                    <a
                                        href="{{ route(
                                            'employee-schedules.show',
                                            $employeeSchedule
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

                                    <a
                                        href="{{ route(
                                            'employee-schedules.edit',
                                            $employeeSchedule
                                        ) }}"
                                        class="btn btn-sm
                                            btn-outline-secondary"
                                    >
                                        <i
                                            class="bi
                                                bi-pencil-square
                                                me-1"
                                            aria-hidden="true"
                                        ></i>

                                        Edit
                                    </a>
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
