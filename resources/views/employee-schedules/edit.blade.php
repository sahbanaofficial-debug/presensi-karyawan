@extends('layouts.app')

@section('title', 'Edit Jadwal Harian')

@push('styles')
    <style>
        .employee-schedule-edit-page {
            --schedule-edit-surface: var(--neutral-0);
            --schedule-edit-border: var(--neutral-200);
            --schedule-edit-muted: var(--neutral-600);
            --schedule-edit-soft: var(--brand-50);
        }

        .schedule-edit-summary-card,
        .schedule-edit-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--schedule-edit-border);
            border-radius: var(--radius-lg);
            background: var(--schedule-edit-surface);
            box-shadow: var(--shadow-xs);
        }

        .schedule-edit-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--schedule-edit-border);
            background: var(--neutral-25);
        }

        .schedule-edit-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .schedule-edit-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--schedule-edit-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .schedule-edit-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .schedule-edit-summary-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .schedule-edit-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--schedule-edit-soft);
            font-size: 1rem;
        }

        .schedule-edit-summary-label {
            color: var(--schedule-edit-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .schedule-edit-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .schedule-edit-warning-stack {
            display: grid;
            gap: var(--space-3);
            margin-bottom: var(--space-5);
        }

        .schedule-edit-warning,
        .schedule-edit-danger {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border-radius: var(--radius-md);
        }

        .schedule-edit-warning {
            border: 1px solid #f0ddb0;
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .schedule-edit-danger {
            border: 1px solid #efc9c3;
            color: var(--danger-700);
            background: var(--danger-50);
        }

        .schedule-edit-alert-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.45);
            font-size: 1rem;
        }

        .schedule-edit-alert-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .schedule-edit-alert-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .schedule-edit-form-body {
            padding: var(--space-4);
        }

        .schedule-edit-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .schedule-edit-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .schedule-edit-summary-grid,
            .schedule-edit-form-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .schedule-edit-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .schedule-edit-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .schedule-edit-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $employee = $employeeSchedule->employee;
        $workSchedule = $employeeSchedule->workSchedule;

        $currentStatus = old(
            'schedule_status',
            $employeeSchedule->schedule_status
        );

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

        $currentStatusLabel = $statusLabels[$currentStatus]
            ?? ucfirst((string) $currentStatus);

        $currentStatusClass = $statusClasses[$currentStatus]
            ?? 'text-bg-secondary';

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l, d F Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $hasAvailableEmployee = $employees->isNotEmpty();

        $hasAvailableWorkSchedule =
            $workSchedules->isNotEmpty();
    @endphp

    <div class="employee-schedule-edit-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Edit Jadwal Harian
                </h1>

                <p class="page-description">
                    Perbarui karyawan, tanggal, status,
                    atau pola jadwal kerja.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <a
                    href="{{ route(
                        'employee-schedules.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-list-ul me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Jadwal
                </a>

                <a
                    href="{{ route(
                        'employee-schedules.show',
                        $employeeSchedule
                    ) }}"
                    class="btn btn-outline-primary"
                >
                    <i
                        class="bi bi-eye me-2"
                        aria-hidden="true"
                    ></i>

                    Detail Jadwal
                </a>
            </div>
        </header>

        <section
            class="schedule-edit-summary-card"
            aria-labelledby="schedule-edit-summary-heading"
        >
            <div class="schedule-edit-card-header">
                <div>
                    <h2
                        id="schedule-edit-summary-heading"
                        class="schedule-edit-card-title"
                    >
                        Jadwal yang sedang diubah
                    </h2>

                    <p class="schedule-edit-card-copy">
                        Tinjau data saat ini sebelum menyimpan
                        perubahan jadwal harian.
                    </p>
                </div>

                <span class="badge {{ $currentStatusClass }}">
                    Status: {{ $currentStatusLabel }}
                </span>
            </div>

            <div class="schedule-edit-summary-grid">
                <article class="schedule-edit-summary-item">
                    <span class="schedule-edit-summary-icon">
                        <i
                            class="bi bi-person"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-edit-summary-label">
                        Karyawan
                    </div>

                    <div class="schedule-edit-summary-value">
                        @if ($employee !== null)
                            {{ $employee->employee_number }}
                            |
                            {{ $employee->full_name }}
                        @else
                            Data karyawan tidak tersedia
                        @endif
                    </div>
                </article>

                <article class="schedule-edit-summary-item">
                    <span class="schedule-edit-summary-icon">
                        <i
                            class="bi bi-calendar3"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-edit-summary-label">
                        Tanggal Jadwal
                    </div>

                    <div class="schedule-edit-summary-value">
                        {{
                            $formatDate(
                                $employeeSchedule->schedule_date
                            )
                        }}
                    </div>
                </article>

                <article class="schedule-edit-summary-item">
                    <span class="schedule-edit-summary-icon">
                        <i
                            class="bi bi-clock-history"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="schedule-edit-summary-label">
                        Pola Jadwal
                    </div>

                    <div class="schedule-edit-summary-value">
                        {{ $workSchedule?->name ?? '-' }}
                    </div>
                </article>
            </div>
        </section>

        @if (
            (
                $employee !== null
                && $employee->employment_status === 'inactive'
            )
            || (
                $workSchedule !== null
                && $workSchedule->status === 'inactive'
            )
            || ! $hasAvailableEmployee
            || ! $hasAvailableWorkSchedule
        )
            <div class="schedule-edit-warning-stack">
                @if (
                    $employee !== null
                    && $employee->employment_status === 'inactive'
                )
                    <div
                        class="schedule-edit-warning"
                        role="alert"
                    >
                        <span class="schedule-edit-alert-icon">
                            <i
                                class="bi bi-exclamation-triangle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="schedule-edit-alert-title">
                                Karyawan sudah tidak aktif.
                            </h3>

                            <p class="schedule-edit-alert-copy">
                                Karyawan lama tetap ditampilkan agar
                                jadwal ini dapat dipertahankan.
                                Apabila karyawan diganti, pilih
                                karyawan yang masih aktif.
                            </p>
                        </div>
                    </div>
                @endif

                @if (
                    $workSchedule !== null
                    && $workSchedule->status === 'inactive'
                )
                    <div
                        class="schedule-edit-warning"
                        role="alert"
                    >
                        <span class="schedule-edit-alert-icon">
                            <i
                                class="bi bi-exclamation-triangle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="schedule-edit-alert-title">
                                Pola jadwal sudah tidak aktif.
                            </h3>

                            <p class="schedule-edit-alert-copy">
                                Pola jadwal lama tetap dapat
                                dipertahankan. Apabila pola jadwal
                                diganti, pilih pola yang masih aktif.
                            </p>
                        </div>
                    </div>
                @endif

                @if (! $hasAvailableEmployee)
                    <div
                        class="schedule-edit-danger"
                        role="alert"
                    >
                        <span class="schedule-edit-alert-icon">
                            <i
                                class="bi bi-x-octagon"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="schedule-edit-alert-title">
                                Data karyawan tidak tersedia.
                            </h3>

                            <p class="schedule-edit-alert-copy">
                                Perubahan belum dapat dilakukan
                                karena tidak terdapat data karyawan
                                yang dapat dipilih.
                            </p>
                        </div>
                    </div>
                @endif

                @if (! $hasAvailableWorkSchedule)
                    <div
                        class="schedule-edit-warning"
                        role="alert"
                    >
                        <span class="schedule-edit-alert-icon">
                            <i
                                class="bi bi-exclamation-triangle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="schedule-edit-alert-title">
                                Pola jadwal kerja tidak tersedia.
                            </h3>

                            <p class="schedule-edit-alert-copy">
                                Status kerja tidak dapat dipilih
                                sebelum terdapat pola jadwal kerja
                                yang aktif.
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
            class="schedule-edit-form-card"
            aria-labelledby="edit-employee-schedule-heading"
        >
            <div class="schedule-edit-card-header">
                <div>
                    <h2
                        id="edit-employee-schedule-heading"
                        class="schedule-edit-card-title"
                    >
                        Formulir Perubahan Jadwal
                    </h2>

                    <p class="schedule-edit-card-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Mode edit
                </span>
            </div>

            <div class="schedule-edit-form-body">
                <form
                    method="POST"
                    action="{{ route(
                        'employee-schedules.update',
                        $employeeSchedule
                    ) }}"
                >
                    @csrf
                    @method('PUT')

                    @include(
                        'employee-schedules._form',
                        [
                            'employeeSchedule' =>
                                $employeeSchedule,
                            'employees' => $employees,
                            'workSchedules' => $workSchedules,
                        ]
                    )

                    <div class="schedule-edit-actions">
                        <a
                            href="{{ route(
                                'employee-schedules.show',
                                $employeeSchedule
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            @disabled(! $hasAvailableEmployee)
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
