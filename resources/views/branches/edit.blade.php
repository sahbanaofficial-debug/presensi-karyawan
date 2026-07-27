@extends('layouts.app')

@section('title', 'Edit Cabang')

@push('styles')
    <style>
        .branch-edit-page {
            --branch-edit-surface: var(--neutral-0);
            --branch-edit-border: var(--neutral-200);
            --branch-edit-muted: var(--neutral-600);
            --branch-edit-soft: var(--brand-50);
        }

        .branch-edit-summary-card,
        .branch-edit-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--branch-edit-border);
            border-radius: var(--radius-lg);
            background: var(--branch-edit-surface);
            box-shadow: var(--shadow-xs);
        }

        .branch-edit-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--branch-edit-border);
            background: var(--neutral-25);
        }

        .branch-edit-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .branch-edit-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--branch-edit-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .branch-edit-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .branch-edit-summary-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .branch-edit-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--branch-edit-soft);
            font-size: 1rem;
        }

        .branch-edit-summary-label {
            color: var(--branch-edit-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .branch-edit-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .branch-edit-form-body {
            padding: var(--space-4);
        }

        .branch-edit-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .branch-edit-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .branch-edit-summary-grid,
            .branch-edit-form-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .branch-edit-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .branch-edit-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .branch-edit-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="branch-edit-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Edit Cabang
                </h1>

                <p class="page-description">
                    Perbarui identitas dan konfigurasi lokasi cabang
                    {{ $branch->code }}.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <a
                    href="{{ route('branches.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-list-ul me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Cabang
                </a>

                <a
                    href="{{ route(
                        'branches.show',
                        $branch
                    ) }}"
                    class="btn btn-outline-primary"
                >
                    <i
                        class="bi bi-eye me-2"
                        aria-hidden="true"
                    ></i>

                    Detail Cabang
                </a>
            </div>
        </header>

        <section
            class="branch-edit-summary-card"
            aria-labelledby="branch-edit-summary-heading"
        >
            <div class="branch-edit-card-header">
                <div>
                    <h2
                        id="branch-edit-summary-heading"
                        class="branch-edit-card-title"
                    >
                        Ringkasan Cabang
                    </h2>

                    <p class="branch-edit-card-copy">
                        Pastikan data cabang dan konfigurasi
                        geofence tetap sesuai kondisi operasional.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    {{ $branch->code }}
                </span>
            </div>

            <div class="branch-edit-summary-grid">
                <article class="branch-edit-summary-item">
                    <span class="branch-edit-summary-icon">
                        <i
                            class="bi bi-building"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-edit-summary-label">
                        Nama Cabang
                    </div>

                    <div class="branch-edit-summary-value">
                        {{ $branch->name }}
                    </div>
                </article>

                <article class="branch-edit-summary-item">
                    <span class="branch-edit-summary-icon">
                        <i
                            class="bi bi-geo-alt"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-edit-summary-label">
                        Koordinat
                    </div>

                    <div class="branch-edit-summary-value">
                        {{ $branch->latitude ?? '-' }},
                        {{ $branch->longitude ?? '-' }}
                    </div>
                </article>

                <article class="branch-edit-summary-item">
                    <span class="branch-edit-summary-icon">
                        <i
                            class="bi bi-bullseye"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-edit-summary-label">
                        Radius Geofence
                    </div>

                    <div class="branch-edit-summary-value">
                        {{ $branch->geofence_radius }}
                        meter
                    </div>
                </article>
            </div>
        </section>

        <section
            class="branch-edit-form-card"
            aria-labelledby="edit-branch-heading"
        >
            <div class="branch-edit-card-header">
                <div>
                    <h2
                        id="edit-branch-heading"
                        class="branch-edit-card-title"
                    >
                        Formulir Perubahan Cabang
                    </h2>

                    <p class="branch-edit-card-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Mode edit
                </span>
            </div>

            <div class="branch-edit-form-body">
                <form
                    method="POST"
                    action="{{ route(
                        'branches.update',
                        $branch
                    ) }}"
                >
                    @csrf
                    @method('PUT')

                    @include(
                        'branches._form',
                        [
                            'branch' => $branch,
                        ]
                    )

                    <div class="branch-edit-actions">
                        <a
                            href="{{ route(
                                'branches.show',
                                $branch
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i
                                class="bi bi-save me-2"
                                aria-hidden="true"
                            ></i>

                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
