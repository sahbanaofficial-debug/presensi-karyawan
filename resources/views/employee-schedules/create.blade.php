@extends('layouts.app')

@section('title', 'Tambah Jadwal Harian')

@push('styles')
    <style>
        .employee-schedule-create-page {
            --schedule-create-surface: var(--neutral-0);
            --schedule-create-border: var(--neutral-200);
            --schedule-create-muted: var(--neutral-600);
            --schedule-create-soft: var(--brand-50);
        }

        .schedule-create-overview-card,
        .schedule-create-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--schedule-create-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-create-surface);
            box-shadow: var(--shadow-xs);
        }

        .schedule-create-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--schedule-create-border);
            background: var(--neutral-25);
        }

        .schedule-create-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .schedule-create-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--schedule-create-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .schedule-create-overview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .schedule-create-overview-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .schedule-create-overview-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--schedule-create-soft);
            font-size: 1rem;
        }

        .schedule-create-overview-label {
            color: var(--schedule-create-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .schedule-create-overview-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
        }

        .schedule-create-warning-stack {
            display: grid;
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .schedule-create-warning {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border: 1px solid #f0ddb0;
            border-radius: var(--radius-md);
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .schedule-create-warning-icon {
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

        .schedule-create-warning-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .schedule-create-warning-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .schedule-create-form-body {
            padding: var(--space-4);
        }

        .schedule-create-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .schedule-create-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .schedule-create-overview-grid,
            .schedule-create-form-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .schedule-create-overview-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .schedule-create-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .schedule-create-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $canCreateSchedule =
            $employees->isNotEmpty()
            && $workSchedules->isNotEmpty();
    @endphp

    <div class="employee-schedule-create-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Tambah Jadwal Harian
                </h1>

                <p class="page-description">
                    Tetapkan jadwal kerja, hari libur, izin,
                    atau sakit untuk karyawan.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route(
                        'employee-schedules.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Jadwal Harian
                </a>
            </div>
        </header>

        <section
            class="schedule-create-overview-card"
            aria-labelledby="schedule-create-overview-heading"
        >
            <div class="schedule-create-card-header">
                <div>
                    <h2
                        id="schedule-create-overview-heading"
                        class="schedule-create-card-title"
                    >
                        Informasi Penetapan Jadwal
                    </h2>

                    <p class="schedule-create-card-copy">
                        Pilih karyawan, tanggal, status harian,
                        dan pola kerja yang sesuai.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Data baru
                </span>
            </div>

            <div class="schedule-create-overview-grid">
                <article class="schedule-create-overview-item">
                    <span class="schedule-create-overview-icon">
                        <i
                            class="bi bi-person"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-create-overview-label">
                        Karyawan
                    </div>

                    <div class="schedule-create-overview-value">
                        {{ $employees->count() }}
                        karyawan tersedia
                    </div>
                </article>

                <article class="schedule-create-overview-item">
                    <span class="schedule-create-overview-icon">
                        <i
                            class="bi bi-calendar-week"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-create-overview-label">
                        Pola Jadwal
                    </div>

                    <div class="schedule-create-overview-value">
                        {{ $workSchedules->count() }}
                        pola aktif tersedia
                    </div>
                </article>

                <article class="schedule-create-overview-item">
                    <span class="schedule-create-overview-icon">
                        <i
                            class="bi bi-toggle-on"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-create-overview-label">
                        Status Harian
                    </div>

                    <div class="schedule-create-overview-value">
                        Kerja, libur, izin, atau sakit.
                    </div>
                </article>
            </div>
        </section>

        @if (
            $employees->isEmpty()
            || $workSchedules->isEmpty()
        )
            <div class="schedule-create-warning-stack">
                @if ($employees->isEmpty())
                    <div
                        class="schedule-create-warning"
                        role="alert"
                    >
                        <span class="schedule-create-warning-icon">
                            <i
                                class="bi bi-exclamation-triangle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h2 class="schedule-create-warning-title">
                                Karyawan aktif tidak tersedia.
                            </h2>

                            <p class="schedule-create-warning-copy">
                                Jadwal harian belum dapat ditambahkan
                                karena tidak terdapat karyawan aktif.
                            </p>

                            <a
                                href="{{ route(
                                    'employees.index'
                                ) }}"
                                class="btn btn-sm
                                    btn-outline-dark mt-3"
                            >
                                Lihat Data Karyawan
                            </a>
                        </div>
                    </div>
                @endif

                @if ($workSchedules->isEmpty())
                    <div
                        class="schedule-create-warning"
                        role="alert"
                    >
                        <span class="schedule-create-warning-icon">
                            <i
                                class="bi bi-exclamation-triangle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h2 class="schedule-create-warning-title">
                                Pola jadwal aktif tidak tersedia.
                            </h2>

                            <p class="schedule-create-warning-copy">
                                Status kerja belum dapat ditetapkan
                                karena tidak terdapat pola jadwal
                                kerja yang aktif.
                            </p>

                            <a
                                href="{{ route(
                                    'work-schedules.index'
                                ) }}"
                                class="btn btn-sm
                                    btn-outline-dark mt-3"
                            >
                                Kelola Pola Jadwal
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <section
            class="schedule-create-form-card"
            aria-labelledby="create-employee-schedule-heading"
        >
            <div class="schedule-create-card-header">
                <div>
                    <h2
                        id="create-employee-schedule-heading"
                        class="schedule-create-card-title"
                    >
                        Formulir Jadwal Harian Baru
                    </h2>

                    <p class="schedule-create-card-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    Wajib diisi
                </span>
            </div>

            <div class="schedule-create-form-body">
                <form
                    method="POST"
                    action="{{ route(
                        'employee-schedules.store'
                    ) }}"
                >
                    @csrf

                    @include(
                        'employee-schedules._form',
                        [
                            'employees' => $employees,
                            'workSchedules' => $workSchedules,
                        ]
                    )

                    <div class="schedule-create-actions">
                        <a
                            href="{{ route(
                                'employee-schedules.index'
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            @disabled(! $canCreateSchedule)
                        >
                            <i
                                class="bi bi-save me-2"
                                aria-hidden="true"
                            ></i>

                            Simpan Jadwal Harian
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
