@extends('layouts.app')

@section('title', 'Detail Cabang')

@push('styles')
    <style>
        .branch-detail-page {
            --branch-detail-surface: var(--neutral-0);
            --branch-detail-border: var(--neutral-200);
            --branch-detail-muted: var(--neutral-600);
            --branch-detail-soft: var(--brand-50);
        }

        .branch-detail-overview-card,
        .branch-detail-info-card,
        .branch-detail-geofence-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--branch-detail-border);
            border-radius: var(--radius-lg);
            background: var(--branch-detail-surface);
            box-shadow: var(--shadow-xs);
        }

        .branch-detail-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--branch-detail-border);
            background: var(--neutral-25);
        }

        .branch-detail-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .branch-detail-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--branch-detail-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .branch-detail-overview-body {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .branch-detail-overview-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .branch-detail-overview-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--branch-detail-soft);
            font-size: 1rem;
        }

        .branch-detail-overview-label {
            color: var(--branch-detail-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .branch-detail-overview-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .branch-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-4);
            padding: var(--space-4);
        }

        .branch-detail-panel {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .branch-detail-panel-heading {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-4);
        }

        .branch-detail-panel-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--branch-detail-soft);
            font-size: 1rem;
        }

        .branch-detail-panel-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .branch-detail-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
        }

        .branch-detail-row {
            display: grid;
            grid-template-columns: minmax(8rem, 0.85fr) minmax(0, 1.15fr);
            gap: var(--space-3);
            align-items: start;
            margin: 0;
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--neutral-100);
        }

        .branch-detail-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .branch-detail-row dt,
        .branch-detail-row dd {
            margin: 0;
        }

        .branch-detail-row dt {
            color: var(--branch-detail-muted);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .branch-detail-row dd {
            min-width: 0;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .branch-detail-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            padding: var(--space-4);
            border-top: 1px solid var(--branch-detail-border);
        }

        @media (min-width: 576px) {
            .branch-detail-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .branch-detail-overview-body,
            .branch-detail-grid,
            .branch-detail-actions {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .branch-detail-overview-body,
            .branch-detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .branch-detail-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .branch-detail-row {
                grid-template-columns: 1fr;
            }

            .branch-detail-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $statusLabel =
            $branch->status === 'active'
                ? 'Aktif'
                : 'Tidak aktif';

        $statusClass =
            $branch->status === 'active'
                ? 'text-bg-success'
                : 'text-bg-secondary';
    @endphp

    <div class="branch-detail-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Detail Cabang
                </h1>

                <p class="page-description">
                    Informasi cabang yang telah disimpan.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <a
                    href="{{ route('branches.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Kembali
                </a>

                <a
                    href="{{ route(
                        'branches.edit',
                        $branch
                    ) }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-pencil-square me-2"
                        aria-hidden="true"
                    ></i>

                    Edit Cabang
                </a>
            </div>
        </header>

        <section
            class="branch-detail-overview-card"
            aria-labelledby="branch-overview-heading"
        >
            <div class="branch-detail-card-header">
                <div>
                    <h2
                        id="branch-overview-heading"
                        class="branch-detail-card-title"
                    >
                        Ringkasan Cabang
                    </h2>

                    <p class="branch-detail-card-copy">
                        Identitas utama dan status operasional
                        cabang.
                    </p>
                </div>

                <span class="badge {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="branch-detail-overview-body">
                <article class="branch-detail-overview-item">
                    <span class="branch-detail-overview-icon">
                        <i
                            class="bi bi-hash"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-detail-overview-label">
                        Kode Cabang
                    </div>

                    <div class="branch-detail-overview-value">
                        {{ $branch->code }}
                    </div>
                </article>

                <article class="branch-detail-overview-item">
                    <span class="branch-detail-overview-icon">
                        <i
                            class="bi bi-building"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-detail-overview-label">
                        Nama Cabang
                    </div>

                    <div class="branch-detail-overview-value">
                        {{ $branch->name }}
                    </div>
                </article>

                <article class="branch-detail-overview-item">
                    <span class="branch-detail-overview-icon">
                        <i
                            class="bi bi-toggle-on"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-detail-overview-label">
                        Status
                    </div>

                    <div class="branch-detail-overview-value">
                        {{ $statusLabel }}
                    </div>
                </article>
            </div>
        </section>

        <section
            class="branch-detail-info-card"
            aria-labelledby="branch-information-heading"
        >
            <div class="branch-detail-card-header">
                <div>
                    <h2
                        id="branch-information-heading"
                        class="branch-detail-card-title"
                    >
                        Informasi Cabang
                    </h2>

                    <p class="branch-detail-card-copy">
                        Data alamat dan konfigurasi geofence yang
                        digunakan pada proses presensi.
                    </p>
                </div>
            </div>

            <div class="branch-detail-grid">
                <article class="branch-detail-panel">
                    <div class="branch-detail-panel-heading">
                        <span class="branch-detail-panel-icon">
                            <i
                                class="bi bi-building"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <h3 class="branch-detail-panel-title">
                            Identitas dan Alamat
                        </h3>
                    </div>

                    <dl class="branch-detail-list">
                        <div class="branch-detail-row">
                            <dt>
                                Kode cabang
                            </dt>

                            <dd>
                                {{ $branch->code }}
                            </dd>
                        </div>

                        <div class="branch-detail-row">
                            <dt>
                                Nama cabang
                            </dt>

                            <dd>
                                {{ $branch->name }}
                            </dd>
                        </div>

                        <div class="branch-detail-row">
                            <dt>
                                Alamat
                            </dt>

                            <dd>
                                {{ $branch->address }}
                            </dd>
                        </div>

                        <div class="branch-detail-row">
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
                    </dl>
                </article>

                <article class="branch-detail-panel">
                    <div class="branch-detail-panel-heading">
                        <span class="branch-detail-panel-icon">
                            <i
                                class="bi bi-geo-alt"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <h3 class="branch-detail-panel-title">
                            Konfigurasi Geofence
                        </h3>
                    </div>

                    <dl class="branch-detail-list">
                        <div class="branch-detail-row">
                            <dt>
                                Latitude
                            </dt>

                            <dd>
                                {{ $branch->latitude ?? '-' }}
                            </dd>
                        </div>

                        <div class="branch-detail-row">
                            <dt>
                                Longitude
                            </dt>

                            <dd>
                                {{ $branch->longitude ?? '-' }}
                            </dd>
                        </div>

                        <div class="branch-detail-row">
                            <dt>
                                Radius geofence
                            </dt>

                            <dd>
                                {{ $branch->geofence_radius }}
                                meter
                            </dd>
                        </div>

                        <div class="branch-detail-row">
                            <dt>
                                Batas akurasi lokasi
                            </dt>

                            <dd>
                                {{ $branch->maximum_accuracy }}
                                meter
                            </dd>
                        </div>
                    </dl>
                </article>
            </div>

            <div class="branch-detail-actions">
                <a
                    href="{{ route('branches.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Kembali
                </a>

                <a
                    href="{{ route(
                        'branches.edit',
                        $branch
                    ) }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-pencil-square me-2"
                        aria-hidden="true"
                    ></i>

                    Edit Cabang
                </a>
            </div>
        </section>
    </div>
@endsection
