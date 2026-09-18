@extends('layouts.app')

@section('title', 'Laporan Presensi')

@push('styles')
    <style>
        .report-page {
            --report-surface: var(--neutral-0);
            --report-border: var(--neutral-200);
            --report-muted: var(--neutral-600);
        }

        .report-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .report-summary-card,
        .report-filter-card,
        .report-list-card {
            border: 1px solid var(--report-border);
            border-radius: var(--radius-lg);
            background: var(--report-surface);
            box-shadow: var(--shadow-xs);
        }

        .report-summary-card {
            min-width: 0;
            padding: var(--space-4);
        }

        .report-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .report-summary-label {
            color: var(--report-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .report-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .report-summary-copy,
        .report-section-copy {
            margin: var(--space-1) 0 0;
            color: var(--report-muted);
            font-size: 0.72rem;
            line-height: 1.55;
        }

        .report-filter-card {
            padding: var(--space-4);
            margin-bottom: var(--space-5);
        }

        .report-section-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .report-filter-heading {
            margin-bottom: var(--space-4);
        }

        .report-list-card {
            overflow: hidden;
        }

        .report-list-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--report-border);
            background: var(--neutral-25);
        }

        .report-table {
            margin-bottom: 0;
            min-width: 1080px;
        }

        .report-table thead th {
            padding: 0.9rem 0.8rem;
            border-bottom-width: 1px;
            color: var(--neutral-600);
            background: var(--neutral-25);
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .report-table tbody td {
            padding: 0.95rem 0.8rem;
            border-color: var(--report-border);
            color: var(--neutral-700);
            font-size: 0.76rem;
            vertical-align: middle;
        }

        .report-primary {
            display: block;
            color: var(--neutral-900);
            font-weight: 800;
            line-height: 1.45;
        }

        .report-secondary {
            display: block;
            margin-top: 0.2rem;
            color: var(--report-muted);
            font-size: 0.68rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .report-code {
            color: var(--brand-700);
            font-weight: 800;
        }

        .report-time-box {
            min-width: 8rem;
        }

        .report-location {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem 0.6rem;
            margin-top: 0.35rem;
            color: var(--report-muted);
            font-size: 0.64rem;
            font-weight: 600;
        }

        .report-empty-state {
            padding: var(--space-7) var(--space-4);
            text-align: center;
        }

        .report-empty-icon {
            display: inline-flex;
            width: 3rem;
            height: 3rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 1.25rem;
        }

        .report-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .report-mobile-list {
            display: none;
            padding: var(--space-3);
        }

        .report-mobile-card {
            padding: var(--space-4);
            border: 1px solid var(--report-border);
            border-radius: var(--radius-lg);
            background: var(--report-surface);
        }

        .report-mobile-card + .report-mobile-card {
            margin-top: var(--space-3);
        }

        .report-mobile-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--report-border);
        }

        .report-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
            padding-top: var(--space-3);
        }

        .report-mobile-label {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--report-muted);
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .report-pagination {
            padding: var(--space-4) var(--space-5);
            border-top: 1px solid var(--report-border);
        }

        @media (max-width: 1199.98px) {
            .report-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .report-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .report-desktop-table {
                display: none;
            }

            .report-mobile-list {
                display: block;
            }

            .report-list-header {
                padding: var(--space-4);
            }
        }

        @media (max-width: 420px) {
            .report-summary-grid {
                grid-template-columns: 1fr;
            }

            .report-mobile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $formatNumber = static function (
            mixed $value,
            int $precision = 2
        ): string {
            if ($value === null || ! is_numeric($value)) {
                return '-';
            }

            return number_format(
                (float) $value,
                $precision,
                ',',
                '.'
            );
        };

        $statusLabels = [
            '' => 'Semua status',
            'present' => 'Sudah presensi masuk',
            'on_time' => 'Tepat waktu',
            'late' => 'Terlambat',
            'complete' => 'Masuk dan pulang lengkap',
            'incomplete' => 'Belum presensi pulang',
            'not_recorded' => 'Belum presensi masuk',
        ];

        $filterIsActive = $selectedBranchId !== null
            || $selectedEmployeeId !== null
            || $selectedReportStatus !== '';
    @endphp

    <div class="report-page">
        <header class="page-header">
            <h1 class="page-title">Laporan Presensi</h1>

            <p class="page-description">
                Rekap jadwal dan hasil presensi karyawan dalam satu
                tampilan untuk membantu pemeriksaan data oleh HRD.
            </p>
        </header>

        <section
            class="report-summary-grid"
            aria-label="Ringkasan laporan presensi"
        >
            @foreach ([
                [
                    'key' => 'scheduled',
                    'label' => 'Jadwal kerja',
                    'copy' => 'Jadwal pada periode terpilih.',
                    'icon' => 'bi-calendar-check',
                ],
                [
                    'key' => 'present',
                    'label' => 'Sudah masuk',
                    'copy' => 'Karyawan telah presensi masuk.',
                    'icon' => 'bi-box-arrow-in-right',
                ],
                [
                    'key' => 'on_time',
                    'label' => 'Tepat waktu',
                    'copy' => 'Presensi masuk tepat waktu.',
                    'icon' => 'bi-check-circle',
                ],
                [
                    'key' => 'late',
                    'label' => 'Terlambat',
                    'copy' => 'Presensi masuk terlambat.',
                    'icon' => 'bi-clock-history',
                ],
                [
                    'key' => 'complete',
                    'label' => 'Lengkap',
                    'copy' => 'Presensi masuk dan pulang tersedia.',
                    'icon' => 'bi-clipboard2-check',
                ],
                [
                    'key' => 'not_recorded',
                    'label' => 'Belum masuk',
                    'copy' => 'Belum ada presensi masuk.',
                    'icon' => 'bi-exclamation-circle',
                ],
            ] as $card)
                <article class="report-summary-card">
                    <span class="report-summary-icon">
                        <i
                            class="bi {{ $card['icon'] }}"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="report-summary-label">
                        {{ $card['label'] }}
                    </div>

                    <div class="report-summary-value">
                        {{ (int) $summary[$card['key']] }}
                    </div>

                    <div class="report-summary-copy">
                        {{ $card['copy'] }}
                    </div>
                </article>
            @endforeach
        </section>

        <section
            class="report-filter-card"
            aria-labelledby="report-filter-heading"
        >
            <div class="report-filter-heading">
                <h2
                    id="report-filter-heading"
                    class="report-section-title"
                >
                    Filter Laporan
                </h2>

                <p class="report-section-copy">
                    Pilih periode, cabang, karyawan, dan status
                    presensi yang ingin ditampilkan.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('attendance-reports.index') }}"
            >
                <div class="row g-3">
                    <div class="col-md-6 col-xl-2">
                        <label for="date_from" class="form-label">
                            Tanggal Awal
                        </label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="{{ $selectedDateFrom }}"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label for="date_to" class="form-label">
                            Tanggal Akhir
                        </label>

                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="{{ $selectedDateTo }}"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label for="branch_id" class="form-label">
                            Cabang
                        </label>

                        <select
                            id="branch_id"
                            name="branch_id"
                            class="form-select"
                            @disabled($isAdmin)
                        >
                            @if (! $isAdmin)
                                <option value="">Semua cabang</option>
                            @endif

                            @foreach ($branches as $branchOption)
                                <option
                                    value="{{ $branchOption->id }}"
                                    @selected(
                                        $selectedBranchId !== null
                                        && (int) $selectedBranchId
                                            === (int) $branchOption->id
                                    )
                                >
                                    {{ $branchOption->code }} —
                                    {{ $branchOption->name }}
                                </option>
                            @endforeach
                        </select>

                        @if ($isAdmin && $selectedBranchId !== null)
                            <input
                                type="hidden"
                                name="branch_id"
                                value="{{ $selectedBranchId }}"
                            >
                        @endif
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label for="employee_id" class="form-label">
                            Karyawan
                        </label>

                        <select
                            id="employee_id"
                            name="employee_id"
                            class="form-select"
                        >
                            <option value="">Semua karyawan</option>

                            @foreach ($employees as $employeeOption)
                                <option
                                    value="{{ $employeeOption->id }}"
                                    @selected(
                                        $selectedEmployeeId !== null
                                        && (int) $selectedEmployeeId
                                            === (int) $employeeOption->id
                                    )
                                >
                                    {{ $employeeOption->employee_number }}
                                    — {{ $employeeOption->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label for="report_status" class="form-label">
                            Status Laporan
                        </label>

                        <select
                            id="report_status"
                            name="report_status"
                            class="form-select"
                        >
                            @foreach ($statusLabels as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        $selectedReportStatus === $value
                                    )
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div
                        class="col-md-6 col-xl-2
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
                                    'attendance-reports.index'
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
            class="report-list-card"
            aria-labelledby="report-list-heading"
        >
            <div class="report-list-header">
                <div>
                    <h2
                        id="report-list-heading"
                        class="report-section-title"
                    >
                        Rekap Presensi Karyawan
                    </h2>

                    <p class="report-section-copy">
                        Periode
                        {{ \Carbon\CarbonImmutable::parse(
                            $selectedDateFrom
                        )->translatedFormat('d F Y') }}
                        sampai
                        {{ \Carbon\CarbonImmutable::parse(
                            $selectedDateTo
                        )->translatedFormat('d F Y') }}.
                        Ditemukan {{ $reports->total() }} data.
                    </p>
                </div>

                @if ($filterIsActive)
                    <span class="badge text-bg-warning">Filter aktif</span>
                @else
                    <span class="badge text-bg-secondary">
                        Halaman {{ $reports->currentPage() }}
                    </span>
                @endif
            </div>

            @if ($reports->isEmpty())
                <div class="report-empty-state">
                    <span class="report-empty-icon">
                        <i
                            class="bi bi-clipboard2-x"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="report-empty-title">
                        Data laporan belum tersedia
                    </h3>

                    <p class="report-section-copy">
                        Tidak ditemukan jadwal kerja yang sesuai dengan
                        periode dan filter yang dipilih.
                    </p>
                </div>
            @else
                <div class="report-desktop-table table-responsive">
                    <table class="table table-hover report-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">No.</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Karyawan</th>
                                <th scope="col">Cabang</th>
                                <th scope="col">Jadwal</th>
                                <th scope="col">Presensi Masuk</th>
                                <th scope="col">Presensi Pulang</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($reports as $report)
                                @php
                                    $checkIn = $report->attendances
                                        ->firstWhere(
                                            'attendance_type',
                                            'check_in'
                                        );
                                    $checkOut = $report->attendances
                                        ->firstWhere(
                                            'attendance_type',
                                            'check_out'
                                        );
                                    $workScheduleName =
                                        $report
                                            ->work_schedule_name_snapshot
                                        ?? $report->workSchedule?->name
                                        ?? '-';
                                    $checkInSchedule =
                                        $report->check_in_time_snapshot
                                        ?? $report
                                            ->workSchedule?->check_in_time;
                                    $checkOutSchedule =
                                        $report->check_out_time_snapshot
                                        ?? $report
                                            ->workSchedule?->check_out_time;
                                    $rowStatus = $checkIn === null
                                        ? 'Belum Masuk'
                                        : ($checkOut === null
                                            ? 'Belum Pulang'
                                            : 'Lengkap');
                                    $rowBadge = $checkIn === null
                                        ? 'text-bg-secondary'
                                        : ($checkOut === null
                                            ? 'text-bg-warning'
                                            : 'text-bg-success');
                                @endphp

                                <tr>
                                    <td class="text-center">
                                        {{ $reports->firstItem()
                                            + $loop->index }}
                                    </td>

                                    <td>
                                        <span class="report-primary">
                                            {{ $report->schedule_date
                                                ->translatedFormat(
                                                    'd F Y'
                                                ) }}
                                        </span>
                                        <span class="report-secondary">
                                            {{ $report->schedule_date
                                                ->translatedFormat(
                                                    'l'
                                                ) }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="report-primary">
                                            {{ $report->employee
                                                ->full_name }}
                                        </span>
                                        <span class="report-secondary
                                            report-code">
                                            {{ $report->employee
                                                ->employee_number }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="report-primary
                                            report-code">
                                            {{ $report->employee
                                                ->branch?->code ?? '-' }}
                                        </span>
                                        <span class="report-secondary">
                                            {{ $report->employee
                                                ->branch?->name ?? '-' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="report-primary">
                                            {{ $workScheduleName }}
                                        </span>
                                        <span class="report-secondary">
                                            {{ $checkInSchedule
                                                ? \Carbon\CarbonImmutable::parse(
                                                    $checkInSchedule
                                                )->format('H:i')
                                                : '-' }}
                                            —
                                            {{ $checkOutSchedule
                                                ? \Carbon\CarbonImmutable::parse(
                                                    $checkOutSchedule
                                                )->format('H:i')
                                                : '-' }} WIB
                                        </span>
                                    </td>

                                    <td>
                                        <div class="report-time-box">
                                            <span class="report-primary">
                                                {{ $checkIn?->attendance_time
                                                    ?->format('H:i:s')
                                                    ?? '-' }}
                                            </span>

                                            @if ($checkIn !== null)
                                                <span class="badge {{
                                                    $checkIn
                                                        ->punctuality_status
                                                        === 'late'
                                                        ? 'text-bg-warning'
                                                        : 'text-bg-success'
                                                }}">
                                                    {{ $checkIn
                                                        ->punctuality_status
                                                        === 'late'
                                                        ? 'Terlambat'
                                                        : 'Tepat Waktu' }}
                                                </span>

                                                <div class="report-location">
                                                    <span>
                                                        Jarak
                                                        {{ $formatNumber(
                                                            $checkIn->distance
                                                        ) }} m
                                                    </span>
                                                    <span>
                                                        Accuracy
                                                        {{ $formatNumber(
                                                            $checkIn->accuracy
                                                        ) }} m
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <div class="report-time-box">
                                            <span class="report-primary">
                                                {{ $checkOut?->attendance_time
                                                    ?->format('H:i:s')
                                                    ?? '-' }}
                                            </span>

                                            @if ($checkOut !== null)
                                                <span class="report-secondary">
                                                    Presensi pulang tercatat
                                                </span>
                                                <div class="report-location">
                                                    <span>
                                                        Jarak
                                                        {{ $formatNumber(
                                                            $checkOut->distance
                                                        ) }} m
                                                    </span>
                                                    <span>
                                                        Accuracy
                                                        {{ $formatNumber(
                                                            $checkOut->accuracy
                                                        ) }} m
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge {{ $rowBadge }}">
                                            {{ $rowStatus }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="report-mobile-list">
                    @foreach ($reports as $report)
                        @php
                            $checkIn = $report->attendances
                                ->firstWhere(
                                    'attendance_type',
                                    'check_in'
                                );
                            $checkOut = $report->attendances
                                ->firstWhere(
                                    'attendance_type',
                                    'check_out'
                                );
                            $rowStatus = $checkIn === null
                                ? 'Belum Masuk'
                                : ($checkOut === null
                                    ? 'Belum Pulang'
                                    : 'Lengkap');
                            $rowBadge = $checkIn === null
                                ? 'text-bg-secondary'
                                : ($checkOut === null
                                    ? 'text-bg-warning'
                                    : 'text-bg-success');
                        @endphp

                        <article class="report-mobile-card">
                            <div class="report-mobile-header">
                                <div>
                                    <span class="report-primary">
                                        {{ $report->employee->full_name }}
                                    </span>
                                    <span class="report-secondary">
                                        {{ $report->employee
                                            ->employee_number }}
                                        ·
                                        {{ $report->employee
                                            ->branch?->code ?? '-' }}
                                    </span>
                                </div>

                                <span class="badge {{ $rowBadge }}">
                                    {{ $rowStatus }}
                                </span>
                            </div>

                            <div class="report-mobile-grid">
                                <div>
                                    <span class="report-mobile-label">
                                        Tanggal
                                    </span>
                                    <span class="report-primary">
                                        {{ $report->schedule_date
                                            ->translatedFormat(
                                                'd F Y'
                                            ) }}
                                    </span>
                                </div>

                                <div>
                                    <span class="report-mobile-label">
                                        Jadwal
                                    </span>
                                    <span class="report-primary">
                                        {{ $report
                                            ->work_schedule_name_snapshot
                                            ?? $report
                                                ->workSchedule?->name
                                            ?? '-' }}
                                    </span>
                                </div>

                                <div>
                                    <span class="report-mobile-label">
                                        Masuk
                                    </span>
                                    <span class="report-primary">
                                        {{ $checkIn?->attendance_time
                                            ?->format('H:i:s') ?? '-' }}
                                    </span>
                                    @if ($checkIn !== null)
                                        <span class="report-secondary">
                                            {{ $checkIn
                                                ->punctuality_status
                                                === 'late'
                                                ? 'Terlambat'
                                                : 'Tepat waktu' }}
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <span class="report-mobile-label">
                                        Pulang
                                    </span>
                                    <span class="report-primary">
                                        {{ $checkOut?->attendance_time
                                            ?->format('H:i:s') ?? '-' }}
                                    </span>
                                </div>

                                @if ($checkIn !== null)
                                    <div>
                                        <span class="report-mobile-label">
                                            Jarak Masuk
                                        </span>
                                        <span class="report-primary">
                                            {{ $formatNumber(
                                                $checkIn->distance
                                            ) }} meter
                                        </span>
                                    </div>

                                    <div>
                                        <span class="report-mobile-label">
                                            Accuracy Masuk
                                        </span>
                                        <span class="report-primary">
                                            {{ $formatNumber(
                                                $checkIn->accuracy
                                            ) }} meter
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($reports->hasPages())
                    <div class="report-pagination">
                        {{ $reports->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
