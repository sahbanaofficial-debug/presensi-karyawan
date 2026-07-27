@extends('layouts.app')

@section('title', 'Catat Permohonan Pertukaran Jadwal')

@push('styles')
    <style>
        .schedule-swap-create-page {
            --swap-create-surface: var(--neutral-0);
            --swap-create-border: var(--neutral-200);
            --swap-create-muted: var(--neutral-600);
            --swap-create-soft: var(--brand-50);
        }

        .swap-create-alert,
        .swap-create-info-card,
        .swap-create-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--swap-create-border);
            border-radius: var(--radius-lg);
            background: var(--swap-create-surface);
            box-shadow: var(--shadow-xs);
        }

        .swap-create-alert {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border-color: #f0ddb0;
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .swap-create-alert-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: rgba(201, 130, 0, 0.09);
            font-size: 1rem;
        }

        .swap-create-alert-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .swap-create-alert-copy {
            margin: 0 0 var(--space-3);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .swap-create-info-header,
        .swap-create-form-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--swap-create-border);
            background: var(--neutral-25);
        }

        .swap-create-info-title,
        .swap-create-form-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .swap-create-info-copy,
        .swap-create-form-copy {
            margin: var(--space-1) 0 0;
            color: var(--swap-create-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .swap-create-info-body {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .swap-create-info-item {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .swap-create-info-icon {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--swap-create-soft);
            font-size: 0.9375rem;
        }

        .swap-create-info-label {
            color: var(--swap-create-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .swap-create-info-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .swap-create-form-body {
            padding: var(--space-4);
        }

        .swap-create-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .swap-create-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .swap-create-info-body,
            .swap-create-form-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 767.98px) {
            .swap-create-info-body {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .swap-create-info-header,
            .swap-create-form-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .swap-create-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $activeEmployeeCount = $employees->count();

        $canCreateRequest = $activeEmployeeCount >= 2;

        $authenticatedUser = auth()->user();

        $recorderRoleLabel = match (
            $authenticatedUser?->role
        ) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            default => 'Pengguna',
        };
    @endphp

    <div class="schedule-swap-create-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Catat Permohonan Pertukaran Jadwal
                </h1>

                <p class="page-description">
                    Catat permohonan pertukaran jadwal antara
                    dua karyawan aktif.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route(
                        'schedule-swap-requests.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-list-ul me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Permohonan
                </a>
            </div>
        </header>

        @if ($activeEmployeeCount === 0)
            <div
                class="swap-create-alert"
                role="alert"
            >
                <span class="swap-create-alert-icon">
                    <i
                        class="bi bi-people"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h2 class="swap-create-alert-title">
                        Karyawan aktif tidak tersedia.
                    </h2>

                    <p class="swap-create-alert-copy">
                        Permohonan pertukaran jadwal belum dapat
                        dicatat karena tidak terdapat karyawan
                        aktif.
                    </p>

                    <a
                        href="{{ route('employees.index') }}"
                        class="btn btn-sm btn-outline-dark"
                    >
                        Lihat Data Karyawan
                    </a>
                </div>
            </div>
        @elseif ($activeEmployeeCount === 1)
            <div
                class="swap-create-alert"
                role="alert"
            >
                <span class="swap-create-alert-icon">
                    <i
                        class="bi bi-person-plus"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h2 class="swap-create-alert-title">
                        Jumlah karyawan aktif belum mencukupi.
                    </h2>

                    <p class="swap-create-alert-copy">
                        Pertukaran jadwal memerlukan minimal dua
                        karyawan aktif yang berbeda.
                    </p>

                    <a
                        href="{{ route('employees.index') }}"
                        class="btn btn-sm btn-outline-dark"
                    >
                        Lihat Data Karyawan
                    </a>
                </div>
            </div>
        @endif

        <section
            class="swap-create-info-card"
            aria-labelledby="record-information-heading"
        >
            <div class="swap-create-info-header">
                <div>
                    <h2
                        id="record-information-heading"
                        class="swap-create-info-title"
                    >
                        Informasi Pencatatan
                    </h2>

                    <p class="swap-create-info-copy">
                        Ringkasan akses dan persyaratan sebelum
                        permohonan dicatat.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    {{ $activeEmployeeCount }}
                    karyawan aktif
                </span>
            </div>

            <div class="swap-create-info-body">
                <article class="swap-create-info-item">
                    <span class="swap-create-info-icon">
                        <i
                            class="bi bi-person-badge"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="swap-create-info-label">
                        Dicatat Oleh
                    </div>

                    <div class="swap-create-info-value">
                        {{ $recorderRoleLabel }}
                    </div>
                </article>

                <article class="swap-create-info-item">
                    <span class="swap-create-info-icon">
                        <i
                            class="bi bi-people"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="swap-create-info-label">
                        Karyawan Aktif
                    </div>

                    <div class="swap-create-info-value">
                        {{ $activeEmployeeCount }}
                        karyawan tersedia
                    </div>
                </article>

                <article class="swap-create-info-item">
                    <span class="swap-create-info-icon">
                        <i
                            class="bi bi-shield-check"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="swap-create-info-label">
                        Persyaratan
                    </div>

                    <div class="swap-create-info-value">
                        Minimal 2 karyawan berbeda
                    </div>
                </article>
            </div>
        </section>

        <section
            class="swap-create-form-card"
            aria-labelledby="create-schedule-swap-heading"
        >
            <div class="swap-create-form-header">
                <div>
                    <h2
                        id="create-schedule-swap-heading"
                        class="swap-create-form-title"
                    >
                        Formulir Permohonan
                    </h2>

                    <p class="swap-create-form-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Data wajib
                </span>
            </div>

            <div class="swap-create-form-body">
                <form
                    method="POST"
                    action="{{ route(
                        'schedule-swap-requests.store'
                    ) }}"
                >
                    @csrf

                    @include(
                        'schedule-swap-requests._form',
                        [
                            'employees' => $employees,
                        ]
                    )

                    <div class="swap-create-actions">
                        <a
                            href="{{ route(
                                'schedule-swap-requests.index'
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            @disabled(! $canCreateRequest)
                        >
                            <i
                                class="bi bi-check2-circle me-2"
                                aria-hidden="true"
                            ></i>

                            Simpan Permohonan
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
