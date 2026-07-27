@extends('layouts.app')

@section('title', 'Data Cabang')

@push('styles')
    <style>
        .branch-page {
            --branch-surface: var(--neutral-0);
            --branch-border: var(--neutral-200);
            --branch-muted: var(--neutral-600);
            --branch-orange: var(--brand-500);
            --branch-orange-soft: var(--brand-50);
        }

        .branch-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .branch-summary-card {
            padding: var(--space-4);
            border: 1px solid var(--branch-border);
            border-radius: var(--radius-lg);
            background: var(--branch-surface);
            box-shadow: var(--shadow-xs);
        }

        .branch-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--branch-orange-soft);
            font-size: 1rem;
        }

        .branch-summary-label {
            color: var(--branch-muted);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .branch-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .branch-summary-copy {
            margin-top: var(--space-1);
            color: var(--branch-muted);
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .branch-toolbar {
            border: 1px solid var(--branch-border);
            border-radius: var(--radius-lg);
            background: var(--branch-surface);
            box-shadow: var(--shadow-xs);
        }

        .branch-search-control {
            position: relative;
        }

        .branch-search-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: var(--space-3);
            color: var(--neutral-500);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .branch-search-control .form-control {
            padding-left: 2.75rem;
        }

        .branch-table-card {
            overflow: hidden;
            border: 1px solid var(--branch-border);
            border-radius: var(--radius-lg);
            background: var(--branch-surface);
            box-shadow: var(--shadow-xs);
        }

        .branch-table-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--branch-border);
            background: var(--neutral-25);
        }

        .branch-table-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .branch-table-copy {
            margin: var(--space-1) 0 0;
            color: var(--branch-muted);
            font-size: 0.75rem;
        }

        .branch-name {
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .branch-code {
            display: inline-flex;
            margin-top: var(--space-1);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.035em;
            text-transform: uppercase;
        }

        .branch-address {
            max-width: 20rem;
            color: var(--neutral-700);
            font-size: 0.8125rem;
            line-height: 1.6;
        }

        .branch-location-stack {
            display: grid;
            gap: var(--space-1);
            margin-top: var(--space-2);
            color: var(--branch-muted);
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .branch-location-row {
            display: flex;
            align-items: flex-start;
            gap: var(--space-2);
        }

        .branch-location-row i {
            width: 1rem;
            flex: 0 0 1rem;
            margin-top: 0.125rem;
            color: var(--neutral-500);
            text-align: center;
        }

        .branch-action-group {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-2);
        }

        .branch-mobile-list {
            display: none;
        }

        .branch-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--branch-border);
        }

        .branch-mobile-card:last-child {
            border-bottom: 0;
        }

        .branch-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .branch-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .branch-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--branch-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .branch-empty-state {
            padding: var(--space-8) var(--space-5);
            text-align: center;
        }

        .branch-empty-icon {
            display: inline-flex;
            width: 3.75rem;
            height: 3.75rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--branch-orange-soft);
            font-size: 1.5rem;
        }

        .branch-empty-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .branch-empty-copy {
            max-width: 30rem;
            margin: var(--space-2) auto var(--space-4);
            color: var(--branch-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        @media (max-width: 991.98px) {
            .branch-summary-grid {
                grid-template-columns: 1fr;
            }

            .branch-desktop-table {
                display: none;
            }

            .branch-mobile-list {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .branch-table-header {
                padding: var(--space-4);
            }

            .branch-mobile-card {
                padding: var(--space-4);
            }

            .branch-action-group {
                width: 100%;
            }

            .branch-action-group .btn {
                flex: 1;
            }
        }
    </style>
@endpush

@section('content')
    <div class="branch-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Data Cabang
                </h1>

                <p class="page-description">
                    Kelola identitas cabang, koordinat lokasi,
                    radius geofence, dan batas akurasi GPS.
                </p>
            </div>

            <a
                href="{{ route('branches.create') }}"
                class="btn btn-primary mt-3 mt-md-0"
            >
                <i
                    class="bi bi-plus-lg me-2"
                    aria-hidden="true"
                ></i>

                Tambah Cabang
            </a>
        </header>

        <section
            class="branch-summary-grid"
            aria-label="Ringkasan data cabang"
        >
            <article class="branch-summary-card">
                <span class="branch-summary-icon">
                    <i
                        class="bi bi-building"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="branch-summary-label">
                    Total cabang
                </div>

                <div class="branch-summary-value">
                    {{ $branches->total() }}
                </div>

                <div class="branch-summary-copy">
                    Seluruh cabang yang tercatat pada sistem.
                </div>
            </article>

            <article class="branch-summary-card">
                <span class="branch-summary-icon">
                    <i
                        class="bi bi-list-check"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="branch-summary-label">
                    Data pada halaman
                </div>

                <div class="branch-summary-value">
                    {{ $branches->count() }}
                </div>

                <div class="branch-summary-copy">
                    Jumlah data yang sedang ditampilkan.
                </div>
            </article>

            <article class="branch-summary-card">
                <span class="branch-summary-icon">
                    <i
                        class="bi bi-search"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="branch-summary-label">
                    Status pencarian
                </div>

                <div class="branch-summary-value">
                    {{ $search !== '' ? 'Aktif' : 'Semua' }}
                </div>

                <div class="branch-summary-copy">
                    @if ($search !== '')
                        Filter: “{{ $search }}”
                    @else
                        Tidak ada filter pencarian.
                    @endif
                </div>
            </article>
        </section>

        <section
            class="branch-toolbar p-3 p-md-4 mb-4"
            aria-labelledby="branch-search-heading"
        >
            <div class="d-md-flex align-items-end gap-4">
                <div class="flex-grow-1">
                    <h2
                        id="branch-search-heading"
                        class="section-title"
                    >
                        Pencarian cabang
                    </h2>

                    <p class="section-description mb-3">
                        Cari berdasarkan kode, nama, atau alamat cabang.
                    </p>

                    <form
                        method="GET"
                        action="{{ route('branches.index') }}"
                        class="row g-3 align-items-end"
                    >
                        <div class="col-lg-8">
                            <label
                                for="search"
                                class="form-label"
                            >
                                Kata kunci
                            </label>

                            <div class="branch-search-control">
                                <i
                                    class="bi bi-search
                                        branch-search-icon"
                                    aria-hidden="true"
                                ></i>

                                <input
                                    type="search"
                                    id="search"
                                    name="search"
                                    value="{{ $search }}"
                                    class="form-control"
                                    placeholder="Contoh: C02 atau Ogan Baru"
                                    maxlength="100"
                                >
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="d-grid d-sm-flex gap-2">
                                <button
                                    type="submit"
                                    class="btn btn-primary flex-fill"
                                >
                                    <i
                                        class="bi bi-search me-2"
                                        aria-hidden="true"
                                    ></i>

                                    Cari
                                </button>

                                @if ($search !== '')
                                    <a
                                        href="{{ route(
                                            'branches.index'
                                        ) }}"
                                        class="btn
                                            btn-outline-secondary
                                            flex-fill"
                                    >
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section
            class="branch-table-card"
            aria-labelledby="branch-list-heading"
        >
            <div class="branch-table-header">
                <div>
                    <h2
                        id="branch-list-heading"
                        class="branch-table-title"
                    >
                        Daftar Cabang
                    </h2>

                    <p class="branch-table-copy">
                        Total {{ $branches->total() }} data cabang.
                    </p>
                </div>

                <span class="badge text-bg-secondary">
                    Halaman {{ $branches->currentPage() }}
                </span>
            </div>

            @if ($branches->isEmpty())
                <div class="branch-empty-state">
                    <span class="branch-empty-icon">
                        <i
                            class="bi bi-building-x"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h3 class="branch-empty-title">
                        Data cabang tidak ditemukan
                    </h3>

                    <p class="branch-empty-copy">
                        @if ($search !== '')
                            Tidak ada cabang yang sesuai dengan
                            pencarian “{{ $search }}”.
                        @else
                            Belum ada data cabang yang tersimpan
                            pada sistem.
                        @endif
                    </p>

                    @if ($search !== '')
                        <a
                            href="{{ route('branches.index') }}"
                            class="btn btn-outline-primary"
                        >
                            Tampilkan Semua Cabang
                        </a>
                    @else
                        <a
                            href="{{ route('branches.create') }}"
                            class="btn btn-primary"
                        >
                            <i
                                class="bi bi-plus-lg me-2"
                                aria-hidden="true"
                            ></i>

                            Tambah Cabang
                        </a>
                    @endif
                </div>
            @else
                <div class="branch-desktop-table table-responsive">
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
                                    Alamat
                                </th>

                                <th scope="col">
                                    Konfigurasi Lokasi
                                </th>

                                <th scope="col">
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
                            @foreach ($branches as $branch)
                                <tr>
                                    <td
                                        class="text-center
                                            text-secondary"
                                    >
                                        {{
                                            $branches->firstItem()
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        <p class="branch-name">
                                            {{ $branch->name }}
                                        </p>

                                        <span class="branch-code">
                                            {{ $branch->code }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="branch-address">
                                            {{ $branch->address }}
                                        </div>
                                    </td>

                                    <td>
                                        @if (
                                            $branch
                                                ->hasGeofenceConfiguration()
                                        )
                                            <span
                                                class="badge
                                                    text-bg-success"
                                            >
                                                Sudah dikonfigurasi
                                            </span>

                                            <div
                                                class="branch-location-stack"
                                            >
                                                <div
                                                    class="branch-location-row"
                                                >
                                                    <i
                                                        class="bi
                                                            bi-geo-alt"
                                                        aria-hidden="true"
                                                    ></i>

                                                    <span>
                                                        {{
                                                            number_format(
                                                                (float)
                                                                    $branch
                                                                        ->latitude,
                                                                8,
                                                                '.',
                                                                ''
                                                            )
                                                        }},
                                                        {{
                                                            number_format(
                                                                (float)
                                                                    $branch
                                                                        ->longitude,
                                                                8,
                                                                '.',
                                                                ''
                                                            )
                                                        }}
                                                    </span>
                                                </div>

                                                <div
                                                    class="branch-location-row"
                                                >
                                                    <i
                                                        class="bi
                                                            bi-bullseye"
                                                        aria-hidden="true"
                                                    ></i>

                                                    <span>
                                                        Radius
                                                        {{
                                                            number_format(
                                                                (float)
                                                                    $branch
                                                                        ->geofence_radius,
                                                                2,
                                                                ',',
                                                                '.'
                                                            )
                                                        }}
                                                        meter
                                                    </span>
                                                </div>

                                                <div
                                                    class="branch-location-row"
                                                >
                                                    <i
                                                        class="bi
                                                            bi-crosshair"
                                                        aria-hidden="true"
                                                    ></i>

                                                    <span>
                                                        Akurasi maksimum
                                                        {{
                                                            number_format(
                                                                (float)
                                                                    $branch
                                                                        ->maximum_accuracy,
                                                                2,
                                                                ',',
                                                                '.'
                                                            )
                                                        }}
                                                        meter
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <span
                                                class="badge
                                                    text-bg-warning"
                                            >
                                                Belum dikonfigurasi
                                            </span>

                                            <p
                                                class="small
                                                    text-secondary
                                                    mt-2 mb-0"
                                            >
                                                Latitude dan longitude
                                                belum diisi.
                                            </p>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($branch->isActive())
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
                                        <div class="branch-action-group">
                                            <a
                                                href="{{ route(
                                                    'branches.show',
                                                    $branch
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
                                                    'branches.edit',
                                                    $branch
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

                <div class="branch-mobile-list">
                    @foreach ($branches as $branch)
                        <article class="branch-mobile-card">
                            <div class="branch-mobile-top">
                                <div>
                                    <h3 class="branch-name">
                                        {{ $branch->name }}
                                    </h3>

                                    <span class="branch-code">
                                        {{ $branch->code }}
                                    </span>
                                </div>

                                @if ($branch->isActive())
                                    <span
                                        class="badge text-bg-success"
                                    >
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="badge text-bg-secondary"
                                    >
                                        Tidak aktif
                                    </span>
                                @endif
                            </div>

                            <div class="branch-mobile-section">
                                <div class="branch-mobile-label">
                                    Alamat
                                </div>

                                <div class="branch-address">
                                    {{ $branch->address }}
                                </div>
                            </div>

                            <div class="branch-mobile-section">
                                <div class="branch-mobile-label">
                                    Konfigurasi lokasi
                                </div>

                                @if (
                                    $branch->hasGeofenceConfiguration()
                                )
                                    <span
                                        class="badge text-bg-success"
                                    >
                                        Sudah dikonfigurasi
                                    </span>

                                    <div class="branch-location-stack">
                                        <div
                                            class="branch-location-row"
                                        >
                                            <i
                                                class="bi bi-geo-alt"
                                                aria-hidden="true"
                                            ></i>

                                            <span>
                                                {{
                                                    number_format(
                                                        (float)
                                                            $branch
                                                                ->latitude,
                                                        8,
                                                        '.',
                                                        ''
                                                    )
                                                }},
                                                {{
                                                    number_format(
                                                        (float)
                                                            $branch
                                                                ->longitude,
                                                        8,
                                                        '.',
                                                        ''
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="branch-location-row"
                                        >
                                            <i
                                                class="bi bi-bullseye"
                                                aria-hidden="true"
                                            ></i>

                                            <span>
                                                Radius
                                                {{
                                                    number_format(
                                                        (float)
                                                            $branch
                                                                ->geofence_radius,
                                                        2,
                                                        ',',
                                                        '.'
                                                    )
                                                }}
                                                meter
                                            </span>
                                        </div>

                                        <div
                                            class="branch-location-row"
                                        >
                                            <i
                                                class="bi bi-crosshair"
                                                aria-hidden="true"
                                            ></i>

                                            <span>
                                                Akurasi maksimum
                                                {{
                                                    number_format(
                                                        (float)
                                                            $branch
                                                                ->maximum_accuracy,
                                                        2,
                                                        ',',
                                                        '.'
                                                    )
                                                }}
                                                meter
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <span
                                        class="badge text-bg-warning"
                                    >
                                        Belum dikonfigurasi
                                    </span>

                                    <p
                                        class="small text-secondary
                                            mt-2 mb-0"
                                    >
                                        Latitude dan longitude belum
                                        diisi.
                                    </p>
                                @endif
                            </div>

                            <div class="branch-mobile-section">
                                <div class="branch-action-group">
                                    <a
                                        href="{{ route(
                                            'branches.show',
                                            $branch
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
                                            'branches.edit',
                                            $branch
                                        ) }}"
                                        class="btn btn-sm
                                            btn-outline-secondary"
                                    >
                                        <i
                                            class="bi bi-pencil-square
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

                @if ($branches->hasPages())
                    <div class="border-top p-3 p-md-4">
                        {{
                            $branches
                                ->onEachSide(1)
                                ->links('pagination::bootstrap-5')
                        }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
