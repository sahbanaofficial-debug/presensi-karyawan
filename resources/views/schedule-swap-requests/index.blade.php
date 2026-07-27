@extends('layouts.app')

@section('title', 'Pertukaran Jadwal')

@push('styles')
    <style>
        .swap-page {
            --swap-surface: var(--neutral-0);
            --swap-border: var(--neutral-200);
            --swap-muted: var(--neutral-600);
            --swap-orange-soft: var(--brand-50);
        }

        .swap-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .swap-summary-card {
            padding: var(--space-4);
            border: 1px solid var(--swap-border);
            border-radius: var(--radius-lg);
            background: var(--swap-surface);
            box-shadow: var(--shadow-xs);
        }

        .swap-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--swap-orange-soft);
            font-size: 1rem;
        }

        .swap-summary-label {
            color: var(--swap-muted);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .swap-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .swap-summary-copy {
            margin-top: var(--space-1);
            color: var(--swap-muted);
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .swap-toolbar {
            border: 1px solid var(--swap-border);
            border-radius: var(--radius-lg);
            background: var(--swap-surface);
            box-shadow: var(--shadow-xs);
        }

        .swap-search-control {
            position: relative;
        }

        .swap-search-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: var(--space-3);
            color: var(--neutral-500);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .swap-search-control .form-control {
            padding-left: 2.75rem;
        }

        .swap-list-card {
            overflow: hidden;
            border: 1px solid var(--swap-border);
            border-radius: var(--radius-lg);
            background: var(--swap-surface);
            box-shadow: var(--shadow-xs);
        }

        .swap-list-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--swap-border);
            background: var(--neutral-25);
        }

        .swap-list-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .swap-list-copy {
            margin: var(--space-1) 0 0;
            color: var(--swap-muted);
            font-size: 0.75rem;
        }

        .swap-person {
            min-width: 13rem;
        }

        .swap-person-name {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .swap-person-number {
            display: block;
            margin-top: var(--space-1);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .swap-person-branch {
            display: block;
            margin-top: var(--space-1);
            color: var(--swap-muted);
            font-size: 0.75rem;
            line-height: 1.45;
        }

        .swap-date-card {
            display: inline-flex;
            min-width: 10rem;
            align-items: flex-start;
            gap: var(--space-2);
        }

        .swap-date-icon {
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            flex: 0 0 2rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            color: var(--brand-700);
            background: var(--swap-orange-soft);
            font-size: 0.875rem;
        }

        .swap-date-value {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .swap-date-copy {
            display: block;
            margin-top: var(--space-1);
            color: var(--swap-muted);
            font-size: 0.6875rem;
            line-height: 1.4;
        }

        .swap-decision-name {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .swap-decision-time {
            display: block;
            margin-top: var(--space-1);
            color: var(--swap-muted);
            font-size: 0.6875rem;
            line-height: 1.45;
        }

        .swap-action-group {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-2);
        }

        .swap-mobile-list {
            display: none;
        }

        .swap-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--swap-border);
        }

        .swap-mobile-card:last-child {
            border-bottom: 0;
        }

        .swap-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .swap-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .swap-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--swap-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .swap-mobile-flow {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            gap: var(--space-3);
            align-items: center;
        }

        .swap-mobile-person-card {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .swap-mobile-arrow {
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-pill);
            color: var(--brand-700);
            background: var(--swap-orange-soft);
            font-size: 0.875rem;
        }

        .swap-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .swap-mobile-metric {
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .swap-mobile-metric-label {
            color: var(--swap-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .swap-mobile-metric-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.5;
        }

        .swap-empty-state {
            padding: var(--space-8) var(--space-5);
            text-align: center;
        }

        .swap-empty-icon {
            display: inline-flex;
            width: 3.75rem;
            height: 3.75rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--swap-orange-soft);
            font-size: 1.5rem;
        }

        .swap-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .swap-empty-copy {
            max-width: 32rem;
            margin: var(--space-2) auto var(--space-4);
            color: var(--swap-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .swap-pagination {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border-top: 1px solid var(--swap-border);
        }

        @media (min-width: 768px) {
            .swap-pagination {
                flex-direction: row;
                align-items: center;
                padding: var(--space-4) var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .swap-summary-grid {
                grid-template-columns: 1fr;
            }

            .swap-desktop-table {
                display: none;
            }

            .swap-mobile-list {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .swap-list-header {
                padding: var(--space-4);
            }

            .swap-mobile-flow {
                grid-template-columns: 1fr;
            }

            .swap-mobile-arrow {
                margin: 0 auto;
                transform: rotate(90deg);
            }

            .swap-mobile-grid {
                grid-template-columns: 1fr;
            }

            .swap-action-group {
                width: 100%;
            }

            .swap-action-group .btn {
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

        $statusLabels = [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        $statusClasses = [
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ];

        $filterActive =
            $search !== ''
            || $selectedStatus !== ''
            || $dateFrom !== null
            || $dateTo !== null;
    @endphp

    <div class="swap-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Pertukaran Jadwal
                </h1>

                <p class="page-description">
                    Catat, pantau, dan kelola permohonan pertukaran
                    jadwal antarkaryawan.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route(
                        'schedule-swap-requests.create'
                    ) }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-arrow-left-right me-2"
                        aria-hidden="true"
                    ></i>

                    Catat Permohonan
                </a>
            </div>
        </header>

        <section
            class="swap-summary-grid"
            aria-label="Ringkasan pertukaran jadwal"
        >
            <article class="swap-summary-card">
                <span class="swap-summary-icon">
                    <i
                        class="bi bi-arrow-left-right"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="swap-summary-label">
                    Total permohonan
                </div>

                <div class="swap-summary-value">
                    {{ $scheduleSwapRequests->total() }}
                </div>

                <div class="swap-summary-copy">
                    Permohonan yang sesuai dengan filter aktif.
                </div>
            </article>

            <article class="swap-summary-card">
                <span class="swap-summary-icon">
                    <i
                        class="bi bi-list-check"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="swap-summary-label">
                    Data pada halaman
                </div>

                <div class="swap-summary-value">
                    {{ $scheduleSwapRequests->count() }}
                </div>

                <div class="swap-summary-copy">
                    Jumlah permohonan pada halaman saat ini.
                </div>
            </article>

            <article class="swap-summary-card">
                <span class="swap-summary-icon">
                    <i
                        class="bi bi-funnel"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="swap-summary-label">
                    Status filter
                </div>

                <div class="swap-summary-value">
                    {{ $filterActive ? 'Aktif' : 'Semua' }}
                </div>

                <div class="swap-summary-copy">
                    {{
                        $filterActive
                            ? 'Daftar telah disaring.'
                            : 'Menampilkan seluruh permohonan.'
                    }}
                </div>
            </article>
        </section>

        <section class="swap-toolbar p-3 p-md-4 mb-4">
            <div class="mb-3">
                <h2 class="section-title">
                    Filter permohonan
                </h2>

                <p class="section-description">
                    Cari berdasarkan karyawan, alasan, status,
                    atau rentang tanggal.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route(
                    'schedule-swap-requests.index'
                ) }}"
            >
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-xl-4">
                        <label
                            for="search"
                            class="form-label"
                        >
                            Pencarian
                        </label>

                        <div class="swap-search-control">
                            <i
                                class="bi bi-search
                                    swap-search-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="search"
                                id="search"
                                name="search"
                                value="{{ $search }}"
                                class="form-control"
                                placeholder="Nama, nomor karyawan, atau alasan"
                            >
                        </div>
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
                                value="pending"
                                @selected(
                                    $selectedStatus === 'pending'
                                )
                            >
                                Menunggu
                            </option>

                            <option
                                value="approved"
                                @selected(
                                    $selectedStatus === 'approved'
                                )
                            >
                                Disetujui
                            </option>

                            <option
                                value="rejected"
                                @selected(
                                    $selectedStatus === 'rejected'
                                )
                            >
                                Ditolak
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
                                    'schedule-swap-requests.index'
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
            class="swap-list-card"
            aria-labelledby="swap-list-heading"
        >
            <div class="swap-list-header">
                <div>
                    <h2
                        id="swap-list-heading"
                        class="swap-list-title"
                    >
                        Daftar Permohonan
                    </h2>

                    <p class="swap-list-copy">
                        Ditemukan
                        {{ $scheduleSwapRequests->total() }}
                        permohonan pertukaran jadwal.
                    </p>
                </div>

                @if ($filterActive)
                    <span class="badge text-bg-warning">
                        Filter aktif
                    </span>
                @else
                    <span class="badge text-bg-secondary">
                        Halaman
                        {{ $scheduleSwapRequests->currentPage() }}
                    </span>
                @endif
            </div>

            @if ($scheduleSwapRequests->isEmpty())
                <div class="swap-empty-state">
                    <span class="swap-empty-icon">
                        <i
                            class="bi bi-calendar2-x"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="swap-empty-title">
                        Permohonan tidak ditemukan
                    </h3>

                    <p class="swap-empty-copy">
                        Belum terdapat permohonan atau data tidak
                        sesuai dengan kata pencarian, status,
                        dan rentang tanggal yang digunakan.
                    </p>

                    <a
                        href="{{ route(
                            'schedule-swap-requests.index'
                        ) }}"
                        class="btn btn-outline-primary"
                    >
                        Reset Filter
                    </a>
                </div>
            @else
                <div class="swap-desktop-table table-responsive">
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
                                    Karyawan Pengaju
                                </th>

                                <th scope="col">
                                    Jadwal Pengaju
                                </th>

                                <th scope="col">
                                    Karyawan Pasangan
                                </th>

                                <th scope="col">
                                    Jadwal Pasangan
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Status
                                </th>

                                <th scope="col">
                                    Keputusan
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
                                $scheduleSwapRequests
                                as $scheduleSwapRequest
                            )
                                @php
                                    $requester =
                                        $scheduleSwapRequest
                                            ->requesterEmployee;

                                    $requesterBranch =
                                        $requester?->branch;

                                    $partner =
                                        $scheduleSwapRequest
                                            ->partnerEmployee;

                                    $partnerBranch =
                                        $partner?->branch;

                                    $requestStatus =
                                        strtolower(
                                            (string)
                                                $scheduleSwapRequest
                                                    ->status
                                        );

                                    $statusLabel =
                                        $statusLabels[
                                            $requestStatus
                                        ]
                                        ?? ucfirst($requestStatus);

                                    $statusClass =
                                        $statusClasses[
                                            $requestStatus
                                        ]
                                        ?? 'text-bg-secondary';
                                @endphp

                                <tr>
                                    <td
                                        class="text-center
                                            text-secondary"
                                    >
                                        {{
                                            ($scheduleSwapRequests
                                                ->firstItem()
                                                ?? 0)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        @if ($requester !== null)
                                            <div class="swap-person">
                                                <span
                                                    class="swap-person-name"
                                                >
                                                    {{
                                                        $requester
                                                            ->full_name
                                                    }}
                                                </span>

                                                <span
                                                    class="swap-person-number"
                                                >
                                                    {{
                                                        $requester
                                                            ->employee_number
                                                    }}
                                                </span>

                                                <span
                                                    class="swap-person-branch"
                                                >
                                                    {{
                                                        $requesterBranch
                                                            ?->code
                                                        ?? '-'
                                                    }}

                                                    @if (
                                                        $requesterBranch
                                                        !== null
                                                    )
                                                        —
                                                        {{
                                                            $requesterBranch
                                                                ->name
                                                        }}
                                                    @endif
                                                </span>

                                                @if (
                                                    $requester
                                                        ->employment_status
                                                    === 'inactive'
                                                )
                                                    <span
                                                        class="badge
                                                            text-bg-secondary
                                                            mt-2"
                                                    >
                                                        Tidak aktif
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span
                                                class="text-danger
                                                    small"
                                            >
                                                Data tidak tersedia
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="swap-date-card">
                                            <span class="swap-date-icon">
                                                <i
                                                    class="bi
                                                        bi-calendar-event"
                                                    aria-hidden="true"
                                                ></i>
                                            </span>

                                            <span>
                                                <span
                                                    class="swap-date-value"
                                                >
                                                    {{
                                                        $formatDate(
                                                            $scheduleSwapRequest
                                                                ->requester_date
                                                        )
                                                    }}
                                                </span>

                                                <span
                                                    class="swap-date-copy"
                                                >
                                                    Jadwal yang akan
                                                    ditukar
                                                </span>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        @if ($partner !== null)
                                            <div class="swap-person">
                                                <span
                                                    class="swap-person-name"
                                                >
                                                    {{
                                                        $partner
                                                            ->full_name
                                                    }}
                                                </span>

                                                <span
                                                    class="swap-person-number"
                                                >
                                                    {{
                                                        $partner
                                                            ->employee_number
                                                    }}
                                                </span>

                                                <span
                                                    class="swap-person-branch"
                                                >
                                                    {{
                                                        $partnerBranch
                                                            ?->code
                                                        ?? '-'
                                                    }}

                                                    @if (
                                                        $partnerBranch
                                                        !== null
                                                    )
                                                        —
                                                        {{
                                                            $partnerBranch
                                                                ->name
                                                        }}
                                                    @endif
                                                </span>

                                                @if (
                                                    $partner
                                                        ->employment_status
                                                    === 'inactive'
                                                )
                                                    <span
                                                        class="badge
                                                            text-bg-secondary
                                                            mt-2"
                                                    >
                                                        Tidak aktif
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span
                                                class="text-danger
                                                    small"
                                            >
                                                Data tidak tersedia
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="swap-date-card">
                                            <span class="swap-date-icon">
                                                <i
                                                    class="bi
                                                        bi-calendar-event"
                                                    aria-hidden="true"
                                                ></i>
                                            </span>

                                            <span>
                                                <span
                                                    class="swap-date-value"
                                                >
                                                    {{
                                                        $formatDate(
                                                            $scheduleSwapRequest
                                                                ->partner_date
                                                        )
                                                    }}
                                                </span>

                                                <span
                                                    class="swap-date-copy"
                                                >
                                                    Jadwal yang akan
                                                    ditukar
                                                </span>
                                            </span>
                                        </div>
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
                                            $requestStatus
                                            === 'pending'
                                        )
                                            <span
                                                class="text-secondary
                                                    small"
                                            >
                                                Belum diputuskan
                                            </span>
                                        @elseif (
                                            $scheduleSwapRequest
                                                ->approver !== null
                                        )
                                            <span
                                                class="swap-decision-name"
                                            >
                                                {{
                                                    $scheduleSwapRequest
                                                        ->approver
                                                        ->name
                                                }}
                                            </span>

                                            <span
                                                class="swap-decision-time"
                                            >
                                                {{
                                                    $formatDateTime(
                                                        $scheduleSwapRequest
                                                            ->approved_at
                                                    )
                                                }}
                                                WIB
                                            </span>
                                        @else
                                            <span
                                                class="text-secondary
                                                    small"
                                            >
                                                Data pemberi keputusan
                                                tidak tersedia
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div
                                            class="swap-action-group"
                                        >
                                            <a
                                                href="{{ route(
                                                    'schedule-swap-requests.show',
                                                    $scheduleSwapRequest
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

                <div class="swap-mobile-list">
                    @foreach (
                        $scheduleSwapRequests
                        as $scheduleSwapRequest
                    )
                        @php
                            $requester =
                                $scheduleSwapRequest
                                    ->requesterEmployee;

                            $requesterBranch =
                                $requester?->branch;

                            $partner =
                                $scheduleSwapRequest
                                    ->partnerEmployee;

                            $partnerBranch =
                                $partner?->branch;

                            $requestStatus =
                                strtolower(
                                    (string)
                                        $scheduleSwapRequest->status
                                );

                            $statusLabel =
                                $statusLabels[$requestStatus]
                                ?? ucfirst($requestStatus);

                            $statusClass =
                                $statusClasses[$requestStatus]
                                ?? 'text-bg-secondary';
                        @endphp

                        <article class="swap-mobile-card">
                            <div class="swap-mobile-top">
                                <div>
                                    <h3
                                        class="swap-list-title mb-1"
                                    >
                                        Pertukaran Jadwal
                                    </h3>

                                    <span class="swap-list-copy">
                                        Permohonan
                                        #{{ $scheduleSwapRequest->id }}
                                    </span>
                                </div>

                                <span
                                    class="badge
                                        {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="swap-mobile-section">
                                <div class="swap-mobile-label">
                                    Karyawan
                                </div>

                                <div class="swap-mobile-flow">
                                    <div
                                        class="swap-mobile-person-card"
                                    >
                                        <div
                                            class="swap-mobile-metric-label"
                                        >
                                            Pengaju
                                        </div>

                                        @if ($requester !== null)
                                            <div
                                                class="swap-person-name
                                                    mt-1"
                                            >
                                                {{
                                                    $requester
                                                        ->full_name
                                                }}
                                            </div>

                                            <span
                                                class="swap-person-number"
                                            >
                                                {{
                                                    $requester
                                                        ->employee_number
                                                }}
                                            </span>

                                            <span
                                                class="swap-person-branch"
                                            >
                                                {{
                                                    $requesterBranch
                                                        ?->code
                                                    ?? '-'
                                                }}
                                                @if (
                                                    $requesterBranch
                                                    !== null
                                                )
                                                    —
                                                    {{
                                                        $requesterBranch
                                                            ->name
                                                    }}
                                                @endif
                                            </span>
                                        @else
                                            <span
                                                class="text-danger
                                                    small"
                                            >
                                                Data tidak tersedia
                                            </span>
                                        @endif
                                    </div>

                                    <span
                                        class="swap-mobile-arrow"
                                        aria-hidden="true"
                                    >
                                        <i
                                            class="bi
                                                bi-arrow-left-right"
                                        ></i>
                                    </span>

                                    <div
                                        class="swap-mobile-person-card"
                                    >
                                        <div
                                            class="swap-mobile-metric-label"
                                        >
                                            Pasangan
                                        </div>

                                        @if ($partner !== null)
                                            <div
                                                class="swap-person-name
                                                    mt-1"
                                            >
                                                {{
                                                    $partner
                                                        ->full_name
                                                }}
                                            </div>

                                            <span
                                                class="swap-person-number"
                                            >
                                                {{
                                                    $partner
                                                        ->employee_number
                                                }}
                                            </span>

                                            <span
                                                class="swap-person-branch"
                                            >
                                                {{
                                                    $partnerBranch
                                                        ?->code
                                                    ?? '-'
                                                }}
                                                @if (
                                                    $partnerBranch
                                                    !== null
                                                )
                                                    —
                                                    {{
                                                        $partnerBranch
                                                            ->name
                                                    }}
                                                @endif
                                            </span>
                                        @else
                                            <span
                                                class="text-danger
                                                    small"
                                            >
                                                Data tidak tersedia
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="swap-mobile-section">
                                <div class="swap-mobile-grid">
                                    <div class="swap-mobile-metric">
                                        <div
                                            class="swap-mobile-metric-label"
                                        >
                                            Jadwal pengaju
                                        </div>

                                        <div
                                            class="swap-mobile-metric-value"
                                        >
                                            {{
                                                $formatDate(
                                                    $scheduleSwapRequest
                                                        ->requester_date
                                                )
                                            }}
                                        </div>
                                    </div>

                                    <div class="swap-mobile-metric">
                                        <div
                                            class="swap-mobile-metric-label"
                                        >
                                            Jadwal pasangan
                                        </div>

                                        <div
                                            class="swap-mobile-metric-value"
                                        >
                                            {{
                                                $formatDate(
                                                    $scheduleSwapRequest
                                                        ->partner_date
                                                )
                                            }}
                                        </div>
                                    </div>

                                    <div class="swap-mobile-metric">
                                        <div
                                            class="swap-mobile-metric-label"
                                        >
                                            Keputusan
                                        </div>

                                        <div
                                            class="swap-mobile-metric-value"
                                        >
                                            @if (
                                                $requestStatus
                                                === 'pending'
                                            )
                                                Belum diputuskan
                                            @elseif (
                                                $scheduleSwapRequest
                                                    ->approver !== null
                                            )
                                                {{
                                                    $scheduleSwapRequest
                                                        ->approver
                                                        ->name
                                                }}
                                            @else
                                                Data tidak tersedia
                                            @endif
                                        </div>
                                    </div>

                                    <div class="swap-mobile-metric">
                                        <div
                                            class="swap-mobile-metric-label"
                                        >
                                            Waktu keputusan
                                        </div>

                                        <div
                                            class="swap-mobile-metric-value"
                                        >
                                            @if (
                                                $requestStatus
                                                === 'pending'
                                            )
                                                -
                                            @else
                                                {{
                                                    $formatDateTime(
                                                        $scheduleSwapRequest
                                                            ->approved_at
                                                    )
                                                }}
                                                WIB
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="swap-mobile-section">
                                <div class="swap-action-group">
                                    <a
                                        href="{{ route(
                                            'schedule-swap-requests.show',
                                            $scheduleSwapRequest
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

                @if ($scheduleSwapRequests->hasPages())
                    @php
                        $startPage = max(
                            1,
                            $scheduleSwapRequests
                                ->currentPage() - 2
                        );

                        $endPage = min(
                            $scheduleSwapRequests->lastPage(),
                            $scheduleSwapRequests
                                ->currentPage() + 2
                        );
                    @endphp

                    <div class="swap-pagination">
                        <div class="small text-secondary">
                            Menampilkan
                            {{ $scheduleSwapRequests->firstItem() }}
                            sampai
                            {{ $scheduleSwapRequests->lastItem() }}
                            dari
                            {{ $scheduleSwapRequests->total() }}
                            permohonan.
                        </div>

                        <nav
                            aria-label="Navigasi permohonan"
                        >
                            <ul
                                class="pagination
                                    pagination-sm mb-0"
                            >
                                <li
                                    class="page-item {{
                                        $scheduleSwapRequests
                                            ->onFirstPage()
                                                ? 'disabled'
                                                : ''
                                    }}"
                                >
                                    <a
                                        href="{{
                                            $scheduleSwapRequests
                                                ->previousPageUrl()
                                            ?? '#'
                                        }}"
                                        class="page-link"
                                    >
                                        Sebelumnya
                                    </a>
                                </li>

                                @foreach (
                                    $scheduleSwapRequests
                                        ->getUrlRange(
                                            $startPage,
                                            $endPage
                                        )
                                    as $page => $url
                                )
                                    <li
                                        class="page-item {{
                                            $page
                                            === $scheduleSwapRequests
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
                                        $scheduleSwapRequests
                                            ->hasMorePages()
                                                ? ''
                                                : 'disabled'
                                    }}"
                                >
                                    <a
                                        href="{{
                                            $scheduleSwapRequests
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
