@extends('layouts.app')

@section('title', 'Pola Jadwal Kerja')

@push('styles')
    <style>
        .schedule-page {
            --schedule-surface: var(--neutral-0);
            --schedule-border: var(--neutral-200);
            --schedule-muted: var(--neutral-600);
            --schedule-orange-soft: var(--brand-50);
        }

        .schedule-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .schedule-summary-card {
            padding: var(--space-4);
            border: 1px solid var(--schedule-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-surface);
            box-shadow: var(--shadow-xs);
        }

        .schedule-summary-icon {
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

        .schedule-summary-label {
            color: var(--schedule-muted);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .schedule-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .schedule-summary-copy {
            margin-top: var(--space-1);
            color: var(--schedule-muted);
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .schedule-toolbar {
            border: 1px solid var(--schedule-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-surface);
            box-shadow: var(--shadow-xs);
        }

        .schedule-search-control {
            position: relative;
        }

        .schedule-search-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: var(--space-3);
            color: var(--neutral-500);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .schedule-search-control .form-control {
            padding-left: 2.75rem;
        }

        .schedule-list-card {
            overflow: hidden;
            border: 1px solid var(--schedule-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-surface);
            box-shadow: var(--shadow-xs);
        }

        .schedule-list-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--schedule-border);
            background: var(--neutral-25);
        }

        .schedule-list-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .schedule-list-copy {
            margin: var(--space-1) 0 0;
            color: var(--schedule-muted);
            font-size: 0.75rem;
        }

        .schedule-name {
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .schedule-id {
            display: inline-flex;
            margin-top: var(--space-1);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.035em;
        }

        .schedule-time {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .schedule-time i {
            color: var(--brand-600);
        }

        .schedule-timezone {
            display: block;
            margin-top: var(--space-1);
            color: var(--schedule-muted);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .schedule-rule {
            display: grid;
            gap: var(--space-1);
            color: var(--neutral-700);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .schedule-rule strong {
            color: var(--neutral-900);
            font-weight: 800;
        }

        .schedule-usage-badge {
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

        .schedule-action-group {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-2);
        }

        .schedule-mobile-list {
            display: none;
        }

        .schedule-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--schedule-border);
        }

        .schedule-mobile-card:last-child {
            border-bottom: 0;
        }

        .schedule-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .schedule-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .schedule-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--schedule-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .schedule-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .schedule-mobile-metric {
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .schedule-mobile-metric-label {
            color: var(--schedule-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .schedule-mobile-metric-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .schedule-empty-state {
            padding: var(--space-8) var(--space-5);
            text-align: center;
        }

        .schedule-empty-icon {
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

        .schedule-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .schedule-empty-copy {
            max-width: 30rem;
            margin: var(--space-2) auto var(--space-4);
            color: var(--schedule-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .schedule-pagination {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border-top: 1px solid var(--schedule-border);
        }

        @media (min-width: 768px) {
            .schedule-pagination {
                flex-direction: row;
                align-items: center;
                padding: var(--space-4) var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .schedule-summary-grid {
                grid-template-columns: 1fr;
            }

            .schedule-desktop-table {
                display: none;
            }

            .schedule-mobile-list {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .schedule-list-header {
                padding: var(--space-4);
            }

            .schedule-mobile-grid {
                grid-template-columns: 1fr;
            }

            .schedule-action-group {
                width: 100%;
            }

            .schedule-action-group .btn {
                flex: 1;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $formatTime = static function ($value): string {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }

            $time = (string) $value;

            return strlen($time) >= 5
                ? substr($time, 0, 5)
                : $time;
        };

        $filterActive =
            $search !== ''
            || $selectedStatus !== '';
    @endphp

    <div class="schedule-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Pola Jadwal Kerja
                </h1>

                <p class="page-description">
                    Kelola jam kerja, waktu pembukaan presensi,
                    toleransi keterlambatan, dan batas presensi pulang.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route('work-schedules.create') }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-plus-lg me-2"
                        aria-hidden="true"
                    ></i>

                    Tambah Pola Jadwal
                </a>
            </div>
        </header>

        <section
            class="schedule-summary-grid"
            aria-label="Ringkasan pola jadwal kerja"
        >
            <article class="schedule-summary-card">
                <span class="schedule-summary-icon">
                    <i
                        class="bi bi-calendar2-week"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="schedule-summary-label">
                    Total pola jadwal
                </div>

                <div class="schedule-summary-value">
                    {{ $workSchedules->total() }}
                </div>

                <div class="schedule-summary-copy">
                    Pola jadwal yang sesuai dengan filter aktif.
                </div>
            </article>

            <article class="schedule-summary-card">
                <span class="schedule-summary-icon">
                    <i
                        class="bi bi-list-check"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="schedule-summary-label">
                    Data pada halaman
                </div>

                <div class="schedule-summary-value">
                    {{ $workSchedules->count() }}
                </div>

                <div class="schedule-summary-copy">
                    Jumlah pola jadwal pada halaman saat ini.
                </div>
            </article>

            <article class="schedule-summary-card">
                <span class="schedule-summary-icon">
                    <i
                        class="bi bi-funnel"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="schedule-summary-label">
                    Status filter
                </div>

                <div class="schedule-summary-value">
                    {{ $filterActive ? 'Aktif' : 'Semua' }}
                </div>

                <div class="schedule-summary-copy">
                    {{
                        $filterActive
                            ? 'Daftar telah disaring.'
                            : 'Menampilkan seluruh pola jadwal.'
                    }}
                </div>
            </article>
        </section>

        <section class="schedule-toolbar p-3 p-md-4 mb-4">
            <div class="mb-3">
                <h2 class="section-title">
                    Filter pola jadwal
                </h2>

                <p class="section-description">
                    Cari berdasarkan nama pola dan status penggunaan.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('work-schedules.index') }}"
            >
                <div class="row g-3 align-items-end">
                    <div class="col-md-7 col-lg-5">
                        <label
                            for="search"
                            class="form-label"
                        >
                            Pencarian
                        </label>

                        <div class="schedule-search-control">
                            <i
                                class="bi bi-search
                                    schedule-search-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="search"
                                id="search"
                                name="search"
                                value="{{ $search }}"
                                class="form-control"
                                placeholder="Masukkan nama pola jadwal"
                            >
                        </div>
                    </div>

                    <div class="col-md-5 col-lg-3">
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
                                value="inactive"
                                @selected(
                                    $selectedStatus === 'inactive'
                                )
                            >
                                Tidak aktif
                            </option>
                        </select>
                    </div>

                    <div class="col-lg-4">
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
                                    'work-schedules.index'
                                ) }}"
                                class="btn btn-outline-secondary
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
            class="schedule-list-card"
            aria-labelledby="schedule-list-heading"
        >
            <div class="schedule-list-header">
                <div>
                    <h2
                        id="schedule-list-heading"
                        class="schedule-list-title"
                    >
                        Daftar Pola Jadwal
                    </h2>

                    <p class="schedule-list-copy">
                        Ditemukan {{ $workSchedules->total() }}
                        pola jadwal kerja.
                    </p>
                </div>

                @if ($filterActive)
                    <span class="badge text-bg-warning">
                        Filter aktif
                    </span>
                @else
                    <span class="badge text-bg-secondary">
                        Halaman {{ $workSchedules->currentPage() }}
                    </span>
                @endif
            </div>

            @if ($workSchedules->isEmpty())
                <div class="schedule-empty-state">
                    <span class="schedule-empty-icon">
                        <i
                            class="bi bi-calendar2-x"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="schedule-empty-title">
                        Pola jadwal tidak ditemukan
                    </h3>

                    <p class="schedule-empty-copy">
                        Periksa nama pola atau status yang digunakan
                        pada filter.
                    </p>

                    <a
                        href="{{ route(
                            'work-schedules.index'
                        ) }}"
                        class="btn btn-outline-primary"
                    >
                        Reset Pencarian
                    </a>
                </div>
            @else
                <div class="schedule-desktop-table table-responsive">
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
                                    Nama Jadwal
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
                                    Presensi Masuk
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Presensi Pulang
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Penggunaan
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
                                    style="width: 11rem;"
                                >
                                    Tindakan
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $workSchedules as $workSchedule
                            )
                                <tr>
                                    <td
                                        class="text-center
                                            text-secondary"
                                    >
                                        {{
                                            ($workSchedules
                                                ->firstItem()
                                                ?? 0)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        <h3 class="schedule-name">
                                            {{ $workSchedule->name }}
                                        </h3>

                                        <span class="schedule-id">
                                            ID {{ $workSchedule->id }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span class="schedule-time">
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

                                        <span class="schedule-timezone">
                                            WIB
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="schedule-rule">
                                            <span>
                                                Dibuka
                                                <strong>
                                                    {{
                                                        $workSchedule
                                                            ->check_in_open_minutes
                                                    }}
                                                    menit
                                                </strong>
                                                sebelumnya
                                            </span>

                                            <span>
                                                Toleransi
                                                <strong>
                                                    {{
                                                        $workSchedule
                                                            ->late_tolerance_minutes
                                                    }}
                                                    menit
                                                </strong>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <div class="schedule-rule">
                                            <span>
                                                Batas akhir
                                                <strong>
                                                    {{
                                                        $workSchedule
                                                            ->check_out_limit_minutes
                                                    }}
                                                    menit
                                                </strong>
                                            </span>

                                            <span>
                                                Setelah jam pulang
                                            </span>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span
                                            class="schedule-usage-badge"
                                        >
                                            <i
                                                class="bi
                                                    bi-calendar-check"
                                                aria-hidden="true"
                                            ></i>

                                            {{
                                                $workSchedule
                                                    ->employee_schedules_count
                                                ?? 0
                                            }}
                                            jadwal
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        @if (
                                            $workSchedule->status
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
                                                Tidak aktif
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div
                                            class="schedule-action-group"
                                        >
                                            <a
                                                href="{{ route(
                                                    'work-schedules.show',
                                                    $workSchedule
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
                                                    'work-schedules.edit',
                                                    $workSchedule
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

                <div class="schedule-mobile-list">
                    @foreach ($workSchedules as $workSchedule)
                        <article class="schedule-mobile-card">
                            <div class="schedule-mobile-top">
                                <div>
                                    <h3 class="schedule-name">
                                        {{ $workSchedule->name }}
                                    </h3>

                                    <span class="schedule-id">
                                        ID {{ $workSchedule->id }}
                                    </span>
                                </div>

                                <span
                                    class="badge {{
                                        $workSchedule->status
                                        === 'active'
                                            ? 'text-bg-success'
                                            : 'text-bg-secondary'
                                    }}"
                                >
                                    {{
                                        $workSchedule->status
                                        === 'active'
                                            ? 'Aktif'
                                            : 'Tidak aktif'
                                    }}
                                </span>
                            </div>

                            <div class="schedule-mobile-section">
                                <div class="schedule-mobile-label">
                                    Jam kerja
                                </div>

                                <span class="schedule-time">
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
                                    WIB
                                </span>
                            </div>

                            <div class="schedule-mobile-section">
                                <div class="schedule-mobile-grid">
                                    <div class="schedule-mobile-metric">
                                        <div
                                            class="schedule-mobile-metric-label"
                                        >
                                            Presensi masuk
                                        </div>

                                        <div
                                            class="schedule-mobile-metric-value"
                                        >
                                            Dibuka
                                            {{
                                                $workSchedule
                                                    ->check_in_open_minutes
                                            }}
                                            menit sebelumnya
                                        </div>
                                    </div>

                                    <div class="schedule-mobile-metric">
                                        <div
                                            class="schedule-mobile-metric-label"
                                        >
                                            Toleransi
                                        </div>

                                        <div
                                            class="schedule-mobile-metric-value"
                                        >
                                            {{
                                                $workSchedule
                                                    ->late_tolerance_minutes
                                            }}
                                            menit
                                        </div>
                                    </div>

                                    <div class="schedule-mobile-metric">
                                        <div
                                            class="schedule-mobile-metric-label"
                                        >
                                            Presensi pulang
                                        </div>

                                        <div
                                            class="schedule-mobile-metric-value"
                                        >
                                            Batas
                                            {{
                                                $workSchedule
                                                    ->check_out_limit_minutes
                                            }}
                                            menit setelah pulang
                                        </div>
                                    </div>

                                    <div class="schedule-mobile-metric">
                                        <div
                                            class="schedule-mobile-metric-label"
                                        >
                                            Penggunaan
                                        </div>

                                        <div
                                            class="schedule-mobile-metric-value"
                                        >
                                            {{
                                                $workSchedule
                                                    ->employee_schedules_count
                                                ?? 0
                                            }}
                                            jadwal
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="schedule-mobile-section">
                                <div class="schedule-action-group">
                                    <a
                                        href="{{ route(
                                            'work-schedules.show',
                                            $workSchedule
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
                                            'work-schedules.edit',
                                            $workSchedule
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

                @if ($workSchedules->hasPages())
                    @php
                        $startPage = max(
                            1,
                            $workSchedules->currentPage() - 2
                        );

                        $endPage = min(
                            $workSchedules->lastPage(),
                            $workSchedules->currentPage() + 2
                        );
                    @endphp

                    <div class="schedule-pagination">
                        <div class="small text-secondary">
                            Menampilkan
                            {{ $workSchedules->firstItem() }}
                            sampai
                            {{ $workSchedules->lastItem() }}
                            dari
                            {{ $workSchedules->total() }}
                            data.
                        </div>

                        <nav
                            aria-label="Navigasi pola jadwal"
                        >
                            <ul
                                class="pagination
                                    pagination-sm mb-0"
                            >
                                <li
                                    class="page-item {{
                                        $workSchedules
                                            ->onFirstPage()
                                                ? 'disabled'
                                                : ''
                                    }}"
                                >
                                    <a
                                        class="page-link"
                                        href="{{
                                            $workSchedules
                                                ->previousPageUrl()
                                            ?? '#'
                                        }}"
                                    >
                                        Sebelumnya
                                    </a>
                                </li>

                                @foreach (
                                    $workSchedules->getUrlRange(
                                        $startPage,
                                        $endPage
                                    ) as $page => $url
                                )
                                    <li
                                        class="page-item {{
                                            $page
                                            === $workSchedules
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
                                        $workSchedules
                                            ->hasMorePages()
                                                ? ''
                                                : 'disabled'
                                    }}"
                                >
                                    <a
                                        class="page-link"
                                        href="{{
                                            $workSchedules
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
