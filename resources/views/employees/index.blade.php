@extends('layouts.app')

@section('title', 'Data Karyawan')

@push('styles')
    <style>
        .employee-page {
            --employee-surface: var(--neutral-0);
            --employee-border: var(--neutral-200);
            --employee-muted: var(--neutral-600);
            --employee-orange-soft: var(--brand-50);
        }

        .employee-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .employee-summary-card {
            padding: var(--space-4);
            border: 1px solid var(--employee-border);
            border-radius: var(--radius-lg);
            background: var(--employee-surface);
            box-shadow: var(--shadow-xs);
        }

        .employee-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--employee-orange-soft);
            font-size: 1rem;
        }

        .employee-summary-label {
            color: var(--employee-muted);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .employee-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .employee-summary-copy {
            margin-top: var(--space-1);
            color: var(--employee-muted);
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .employee-toolbar {
            border: 1px solid var(--employee-border);
            border-radius: var(--radius-lg);
            background: var(--employee-surface);
            box-shadow: var(--shadow-xs);
        }

        .employee-search-control {
            position: relative;
        }

        .employee-search-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: var(--space-3);
            color: var(--neutral-500);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .employee-search-control .form-control {
            padding-left: 2.75rem;
        }

        .employee-list-card {
            overflow: hidden;
            border: 1px solid var(--employee-border);
            border-radius: var(--radius-lg);
            background: var(--employee-surface);
            box-shadow: var(--shadow-xs);
        }

        .employee-list-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--employee-border);
            background: var(--neutral-25);
        }

        .employee-list-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .employee-list-copy {
            margin: var(--space-1) 0 0;
            color: var(--employee-muted);
            font-size: 0.75rem;
        }

        .employee-identity {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            min-width: 15rem;
        }

        .employee-avatar {
            display: inline-flex;
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 2.75rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--employee-orange-soft);
            font-size: 0.8125rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .employee-name {
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .employee-number {
            display: block;
            margin-top: var(--space-1);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.035em;
        }

        .employee-email {
            display: block;
            margin-top: var(--space-1);
            color: var(--employee-muted);
            font-size: 0.75rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .employee-branch-name {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .employee-branch-code {
            display: block;
            margin-bottom: var(--space-1);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.035em;
            text-transform: uppercase;
        }

        .employee-position {
            color: var(--neutral-700);
            font-size: 0.8125rem;
            line-height: 1.55;
        }

        .employee-action-group {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-2);
        }

        .employee-mobile-list {
            display: none;
        }

        .employee-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--employee-border);
        }

        .employee-mobile-card:last-child {
            border-bottom: 0;
        }

        .employee-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .employee-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .employee-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--employee-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .employee-status-stack {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
        }

        .employee-empty-state {
            padding: var(--space-8) var(--space-5);
            text-align: center;
        }

        .employee-empty-icon {
            display: inline-flex;
            width: 3.75rem;
            height: 3.75rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--employee-orange-soft);
            font-size: 1.5rem;
        }

        .employee-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .employee-empty-copy {
            max-width: 30rem;
            margin: var(--space-2) auto var(--space-4);
            color: var(--employee-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .employee-pagination {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border-top: 1px solid var(--employee-border);
        }

        @media (min-width: 768px) {
            .employee-pagination {
                flex-direction: row;
                align-items: center;
                padding: var(--space-4) var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .employee-summary-grid {
                grid-template-columns: 1fr;
            }

            .employee-desktop-table {
                display: none;
            }

            .employee-mobile-list {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .employee-list-header {
                padding: var(--space-4);
            }

            .employee-action-group {
                width: 100%;
            }

            .employee-action-group .btn {
                flex: 1;
            }

            .employee-mobile-top {
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $filterActive =
            $search !== ''
            || $selectedBranchId !== null
            || $selectedStatus !== '';

        $employeeInitials = static function (
            string $name
        ): string {
            return collect(
                preg_split('/\s+/', trim($name))
            )
                ->filter()
                ->take(2)
                ->map(
                    static fn (string $part): string =>
                        mb_strtoupper(
                            mb_substr($part, 0, 1)
                        )
                )
                ->implode('');
        };
    @endphp

    <div class="employee-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Data Karyawan
                </h1>

                <p class="page-description">
                    Kelola profil, cabang penempatan, jabatan,
                    status kerja, dan akun karyawan.
                </p>
            </div>

            @if (auth()->user()->hasRole('hrd'))
                <div class="mt-3 mt-md-0">
                    <a
                        href="{{ route('employees.create') }}"
                        class="btn btn-primary"
                    >
                        <i
                            class="bi bi-person-plus me-2"
                            aria-hidden="true"
                        ></i>

                        Tambah Karyawan
                    </a>
                </div>
            @endif
        </header>

        <section
            class="employee-summary-grid"
            aria-label="Ringkasan data karyawan"
        >
            <article class="employee-summary-card">
                <span class="employee-summary-icon">
                    <i
                        class="bi bi-people"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="employee-summary-label">
                    Total karyawan
                </div>

                <div class="employee-summary-value">
                    {{ $employees->total() }}
                </div>

                <div class="employee-summary-copy">
                    Seluruh data yang sesuai dengan filter aktif.
                </div>
            </article>

            <article class="employee-summary-card">
                <span class="employee-summary-icon">
                    <i
                        class="bi bi-list-check"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="employee-summary-label">
                    Data pada halaman
                </div>

                <div class="employee-summary-value">
                    {{ $employees->count() }}
                </div>

                <div class="employee-summary-copy">
                    Jumlah karyawan pada halaman saat ini.
                </div>
            </article>

            <article class="employee-summary-card">
                <span class="employee-summary-icon">
                    <i
                        class="bi bi-funnel"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="employee-summary-label">
                    Status filter
                </div>

                <div class="employee-summary-value">
                    {{ $filterActive ? 'Aktif' : 'Semua' }}
                </div>

                <div class="employee-summary-copy">
                    {{
                        $filterActive
                            ? 'Daftar telah disaring.'
                            : 'Menampilkan seluruh karyawan.'
                    }}
                </div>
            </article>
        </section>

        <section class="employee-toolbar p-3 p-md-4 mb-4">
            <div class="mb-3">
                <h2 class="section-title">
                    Filter data karyawan
                </h2>

                <p class="section-description">
                    Gunakan nama, nomor karyawan, cabang,
                    atau status untuk mempersempit daftar.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('employees.index') }}"
            >
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label
                            for="search"
                            class="form-label"
                        >
                            Pencarian
                        </label>

                        <div class="employee-search-control">
                            <i
                                class="bi bi-search
                                    employee-search-icon"
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

                    <div class="col-md-6 col-lg-3">
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
                                        $selectedBranchId
                                        === $branch->id
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

                    <div class="col-md-6 col-lg-2">
                        <label
                            for="status"
                            class="form-label"
                        >
                            Status karyawan
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

                    <div class="col-lg-3">
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
                                    'employees.index'
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
            class="employee-list-card"
            aria-labelledby="employee-list-heading"
        >
            <div class="employee-list-header">
                <div>
                    <h2
                        id="employee-list-heading"
                        class="employee-list-title"
                    >
                        Daftar Karyawan
                    </h2>

                    <p class="employee-list-copy">
                        Ditemukan {{ $employees->total() }}
                        data karyawan.
                    </p>
                </div>

                @if ($filterActive)
                    <span class="badge text-bg-warning">
                        Filter aktif
                    </span>
                @else
                    <span class="badge text-bg-secondary">
                        Halaman {{ $employees->currentPage() }}
                    </span>
                @endif
            </div>

            @if ($employees->isEmpty())
                <div class="employee-empty-state">
                    <span class="employee-empty-icon">
                        <i
                            class="bi bi-person-x"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="employee-empty-title">
                        Data karyawan tidak ditemukan
                    </h3>

                    <p class="employee-empty-copy">
                        Periksa kata pencarian, cabang, atau status
                        yang digunakan pada filter.
                    </p>

                    <a
                        href="{{ route('employees.index') }}"
                        class="btn btn-outline-primary"
                    >
                        Reset pencarian
                    </a>
                </div>
            @else
                <div class="employee-desktop-table table-responsive">
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
                                    Karyawan
                                </th>

                                <th scope="col">
                                    Cabang
                                </th>

                                <th scope="col">
                                    Jabatan
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Status Karyawan
                                </th>

                                <th
                                    scope="col"
                                    class="text-center"
                                >
                                    Status Akun
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
                            @foreach ($employees as $employee)
                                <tr>
                                    <td
                                        class="text-center
                                            text-secondary"
                                    >
                                        {{
                                            ($employees->firstItem()
                                            ?? 0)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        <div class="employee-identity">
                                            <span class="employee-avatar">
                                                {{
                                                    $employeeInitials(
                                                        $employee
                                                            ->full_name
                                                    )
                                                    ?: 'KR'
                                                }}
                                            </span>

                                            <span>
                                                <span
                                                    class="employee-name"
                                                >
                                                    {{
                                                        $employee
                                                            ->full_name
                                                    }}
                                                </span>

                                                <span
                                                    class="employee-number"
                                                >
                                                    {{
                                                        $employee
                                                            ->employee_number
                                                    }}
                                                </span>

                                                <span
                                                    class="employee-email"
                                                >
                                                    {{
                                                        $employee
                                                            ->user
                                                            ?->email
                                                        ?? '-'
                                                    }}
                                                </span>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        @if (
                                            $employee->branch
                                            !== null
                                        )
                                            <span
                                                class="employee-branch-code"
                                            >
                                                {{
                                                    $employee
                                                        ->branch
                                                        ->code
                                                }}
                                            </span>

                                            <span
                                                class="employee-branch-name"
                                            >
                                                {{
                                                    $employee
                                                        ->branch
                                                        ->name
                                                }}
                                            </span>

                                            @if (
                                                $employee
                                                    ->branch
                                                    ->status
                                                === 'inactive'
                                            )
                                                <span
                                                    class="badge
                                                        text-bg-warning
                                                        mt-2"
                                                >
                                                    Cabang tidak aktif
                                                </span>
                                            @endif
                                        @else
                                            <span
                                                class="text-secondary
                                                    small"
                                            >
                                                Cabang tidak tersedia
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span
                                            class="employee-position"
                                        >
                                            {{ $employee->position }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        @if (
                                            $employee
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
                                                Tidak aktif
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if (
                                            $employee
                                                ->user
                                                ?->status
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
                                            class="employee-action-group"
                                        >
                                            <a
                                                href="{{ route(
                                                    'employees.show',
                                                    $employee
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

                                            @if (
                                                auth()
                                                    ->user()
                                                    ->hasRole('hrd')
                                            )
                                                <a
                                                    href="{{ route(
                                                        'employees.edit',
                                                        $employee
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
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="employee-mobile-list">
                    @foreach ($employees as $employee)
                        <article class="employee-mobile-card">
                            <div class="employee-mobile-top">
                                <div class="employee-identity">
                                    <span class="employee-avatar">
                                        {{
                                            $employeeInitials(
                                                $employee->full_name
                                            )
                                            ?: 'KR'
                                        }}
                                    </span>

                                    <span>
                                        <h3 class="employee-name">
                                            {{
                                                $employee
                                                    ->full_name
                                            }}
                                        </h3>

                                        <span class="employee-number">
                                            {{
                                                $employee
                                                    ->employee_number
                                            }}
                                        </span>

                                        <span class="employee-email">
                                            {{
                                                $employee
                                                    ->user
                                                    ?->email
                                                ?? '-'
                                            }}
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <div class="employee-mobile-section">
                                <div class="employee-mobile-label">
                                    Cabang dan jabatan
                                </div>

                                @if ($employee->branch !== null)
                                    <span
                                        class="employee-branch-code"
                                    >
                                        {{ $employee->branch->code }}
                                    </span>

                                    <div class="employee-branch-name">
                                        {{ $employee->branch->name }}
                                    </div>
                                @else
                                    <div class="text-secondary small">
                                        Cabang tidak tersedia
                                    </div>
                                @endif

                                <div
                                    class="employee-position mt-2"
                                >
                                    {{ $employee->position }}
                                </div>
                            </div>

                            <div class="employee-mobile-section">
                                <div class="employee-mobile-label">
                                    Status
                                </div>

                                <div class="employee-status-stack">
                                    <span
                                        class="badge {{
                                            $employee
                                                ->employment_status
                                            === 'active'
                                                ? 'text-bg-success'
                                                : 'text-bg-secondary'
                                        }}"
                                    >
                                        Karyawan:
                                        {{
                                            $employee
                                                ->employment_status
                                            === 'active'
                                                ? 'Aktif'
                                                : 'Tidak aktif'
                                        }}
                                    </span>

                                    <span
                                        class="badge {{
                                            $employee
                                                ->user
                                                ?->status
                                            === 'active'
                                                ? 'text-bg-success'
                                                : 'text-bg-secondary'
                                        }}"
                                    >
                                        Akun:
                                        {{
                                            $employee
                                                ->user
                                                ?->status
                                            === 'active'
                                                ? 'Aktif'
                                                : 'Tidak aktif'
                                        }}
                                    </span>

                                    @if (
                                        $employee->branch !== null
                                        && $employee
                                            ->branch
                                            ->status
                                        === 'inactive'
                                    )
                                        <span
                                            class="badge
                                                text-bg-warning"
                                        >
                                            Cabang tidak aktif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="employee-mobile-section">
                                <div class="employee-action-group">
                                    <a
                                        href="{{ route(
                                            'employees.show',
                                            $employee
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

                                    @if (
                                        auth()
                                            ->user()
                                            ->hasRole('hrd')
                                    )
                                        <a
                                            href="{{ route(
                                                'employees.edit',
                                                $employee
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
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($employees->hasPages())
                    @php
                        $startPage = max(
                            1,
                            $employees->currentPage() - 2
                        );

                        $endPage = min(
                            $employees->lastPage(),
                            $employees->currentPage() + 2
                        );
                    @endphp

                    <div class="employee-pagination">
                        <div class="small text-secondary">
                            Menampilkan
                            {{ $employees->firstItem() }}
                            sampai
                            {{ $employees->lastItem() }}
                            dari
                            {{ $employees->total() }}
                            data.
                        </div>

                        <nav
                            aria-label="Navigasi halaman karyawan"
                        >
                            <ul
                                class="pagination
                                    pagination-sm mb-0"
                            >
                                <li
                                    class="page-item {{
                                        $employees->onFirstPage()
                                            ? 'disabled'
                                            : ''
                                    }}"
                                >
                                    <a
                                        class="page-link"
                                        href="{{
                                            $employees
                                                ->previousPageUrl()
                                            ?? '#'
                                        }}"
                                        aria-label="Halaman sebelumnya"
                                    >
                                        Sebelumnya
                                    </a>
                                </li>

                                @foreach (
                                    $employees->getUrlRange(
                                        $startPage,
                                        $endPage
                                    ) as $page => $url
                                )
                                    <li
                                        class="page-item {{
                                            $page
                                            === $employees
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
                                        $employees
                                            ->hasMorePages()
                                                ? ''
                                                : 'disabled'
                                    }}"
                                >
                                    <a
                                        class="page-link"
                                        href="{{
                                            $employees
                                                ->nextPageUrl()
                                            ?? '#'
                                        }}"
                                        aria-label="Halaman berikutnya"
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
