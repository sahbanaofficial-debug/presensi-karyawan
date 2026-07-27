@extends('layouts.app')

@section('title', 'Edit Pola Jadwal')

@push('styles')
    <style>
        .work-schedule-edit-page {
            --work-schedule-edit-surface: var(--neutral-0);
            --work-schedule-edit-border: var(--neutral-200);
            --work-schedule-edit-muted: var(--neutral-600);
            --work-schedule-edit-soft: var(--brand-50);
        }

        .work-schedule-edit-summary-card,
        .work-schedule-edit-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--work-schedule-edit-border);
            border-radius: var(--radius-lg);
            background: var(--work-schedule-edit-surface);
            box-shadow: var(--shadow-xs);
        }

        .work-schedule-edit-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--work-schedule-edit-border);
            background: var(--neutral-25);
        }

        .work-schedule-edit-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .work-schedule-edit-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--work-schedule-edit-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .work-schedule-edit-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .work-schedule-edit-summary-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .work-schedule-edit-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--work-schedule-edit-soft);
            font-size: 1rem;
        }

        .work-schedule-edit-summary-label {
            color: var(--work-schedule-edit-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .work-schedule-edit-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .work-schedule-edit-warning {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            margin: var(--space-4);
            padding: var(--space-4);
            border: 1px solid #f0ddb0;
            border-radius: var(--radius-md);
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .work-schedule-edit-warning-icon {
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

        .work-schedule-edit-warning-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .work-schedule-edit-warning-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .work-schedule-edit-form-body {
            padding: var(--space-4);
        }

        .work-schedule-edit-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .work-schedule-edit-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .work-schedule-edit-summary-grid,
            .work-schedule-edit-form-body {
                padding: var(--space-5);
            }

            .work-schedule-edit-warning {
                margin: var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .work-schedule-edit-summary-grid {
                grid-template-columns: repeat(
                    2,
                    minmax(0, 1fr)
                );
            }
        }

        @media (max-width: 575.98px) {
            .work-schedule-edit-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .work-schedule-edit-summary-grid {
                grid-template-columns: 1fr;
            }

            .work-schedule-edit-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $formatTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }

            $time = (string) $value;

            return strlen($time) >= 5
                ? substr($time, 0, 5)
                : $time;
        };

        $isActive = $workSchedule->status === 'active';
        $employeeScheduleCount = (int) (
            $workSchedule->employee_schedules_count ?? 0
        );
    @endphp

    <div class="work-schedule-edit-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Edit Pola Jadwal
                </h1>

                <p class="page-description">
                    Perbarui jam kerja dan aturan waktu presensi
                    {{ $workSchedule->name }}.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <a
                    href="{{ route('work-schedules.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-list-ul me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Pola Jadwal
                </a>

                <a
                    href="{{ route(
                        'work-schedules.show',
                        $workSchedule
                    ) }}"
                    class="btn btn-outline-primary"
                >
                    <i
                        class="bi bi-eye me-2"
                        aria-hidden="true"
                    ></i>

                    Detail Pola Jadwal
                </a>
            </div>
        </header>

        <section
            class="work-schedule-edit-summary-card"
            aria-labelledby="work-schedule-edit-summary-heading"
        >
            <div class="work-schedule-edit-card-header">
                <div>
                    <h2
                        id="work-schedule-edit-summary-heading"
                        class="work-schedule-edit-card-title"
                    >
                        Ringkasan Pola Jadwal
                    </h2>

                    <p class="work-schedule-edit-card-copy">
                        Tinjau waktu kerja, status, dan jumlah
                        penggunaan sebelum melakukan perubahan.
                    </p>
                </div>

                <span
                    class="badge {{
                        $isActive
                            ? 'text-bg-success'
                            : 'text-bg-secondary'
                    }}"
                >
                    {{ $isActive ? 'Aktif' : 'Tidak aktif' }}
                </span>
            </div>

            <div class="work-schedule-edit-summary-grid">
                <article
                    class="work-schedule-edit-summary-item"
                >
                    <span
                        class="work-schedule-edit-summary-icon"
                    >
                        <i
                            class="bi bi-calendar-week"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-edit-summary-label"
                    >
                        Nama Pola
                    </div>

                    <div
                        class="work-schedule-edit-summary-value"
                    >
                        {{ $workSchedule->name }}
                    </div>
                </article>

                <article
                    class="work-schedule-edit-summary-item"
                >
                    <span
                        class="work-schedule-edit-summary-icon"
                    >
                        <i
                            class="bi bi-box-arrow-in-right"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-edit-summary-label"
                    >
                        Jam Masuk
                    </div>

                    <div
                        class="work-schedule-edit-summary-value"
                    >
                        {{
                            $formatTime(
                                $workSchedule->check_in_time
                            )
                        }}
                        WIB
                    </div>
                </article>

                <article
                    class="work-schedule-edit-summary-item"
                >
                    <span
                        class="work-schedule-edit-summary-icon"
                    >
                        <i
                            class="bi bi-box-arrow-right"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-edit-summary-label"
                    >
                        Jam Pulang
                    </div>

                    <div
                        class="work-schedule-edit-summary-value"
                    >
                        {{
                            $formatTime(
                                $workSchedule->check_out_time
                            )
                        }}
                        WIB
                    </div>
                </article>

                <article
                    class="work-schedule-edit-summary-item"
                >
                    <span
                        class="work-schedule-edit-summary-icon"
                    >
                        <i
                            class="bi bi-people"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-edit-summary-label"
                    >
                        Penggunaan
                    </div>

                    <div
                        class="work-schedule-edit-summary-value"
                    >
                        {{ $employeeScheduleCount }}
                        jadwal karyawan
                    </div>
                </article>
            </div>
        </section>

        <section
            class="work-schedule-edit-form-card"
            aria-labelledby="edit-work-schedule-heading"
        >
            <div class="work-schedule-edit-card-header">
                <div>
                    <h2
                        id="edit-work-schedule-heading"
                        class="work-schedule-edit-card-title"
                    >
                        Formulir Perubahan Pola Jadwal
                    </h2>

                    <p class="work-schedule-edit-card-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Mode edit
                </span>
            </div>

            @if ($employeeScheduleCount > 0)
                <div
                    class="work-schedule-edit-warning"
                    role="alert"
                >
                    <span
                        class="work-schedule-edit-warning-icon"
                    >
                        <i
                            class="bi bi-exclamation-triangle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h3
                            class="work-schedule-edit-warning-title"
                        >
                            Pola jadwal telah digunakan.
                        </h3>

                        <p
                            class="work-schedule-edit-warning-copy"
                        >
                            Perubahan pada pola ini dapat memengaruhi
                            penggunaan jadwal kerja berikutnya.
                            Pastikan konfigurasi waktu sudah sesuai.
                        </p>
                    </div>
                </div>
            @endif

            <div class="work-schedule-edit-form-body">
                <form
                    method="POST"
                    action="{{ route(
                        'work-schedules.update',
                        $workSchedule
                    ) }}"
                >
                    @csrf
                    @method('PUT')

                    @include(
                        'work-schedules._form',
                        [
                            'workSchedule' => $workSchedule,
                        ]
                    )

                    <div class="work-schedule-edit-actions">
                        <a
                            href="{{ route(
                                'work-schedules.show',
                                $workSchedule
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
