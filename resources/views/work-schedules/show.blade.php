@extends('layouts.app')

@section('title', 'Detail Pola Jadwal')

@push('styles')
    <style>
        .work-schedule-detail-page {
            --work-schedule-detail-surface: var(--neutral-0);
            --work-schedule-detail-border: var(--neutral-200);
            --work-schedule-detail-muted: var(--neutral-600);
            --work-schedule-detail-soft: var(--brand-50);
        }

        .work-schedule-detail-summary-card,
        .work-schedule-detail-card,
        .work-schedule-detail-danger-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--work-schedule-detail-border);
            border-radius: var(--radius-lg);
            background: var(--work-schedule-detail-surface);
            box-shadow: var(--shadow-xs);
        }

        .work-schedule-detail-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--work-schedule-detail-border);
            background: var(--neutral-25);
        }

        .work-schedule-detail-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .work-schedule-detail-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--work-schedule-detail-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .work-schedule-detail-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .work-schedule-detail-summary-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .work-schedule-detail-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--work-schedule-detail-soft);
            font-size: 1rem;
        }

        .work-schedule-detail-summary-label {
            color: var(--work-schedule-detail-muted);
            font-size: 0.6875rem;
            font-weight: 700;
        }

        .work-schedule-detail-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.2;
        }

        .work-schedule-detail-summary-meta {
            margin-top: var(--space-1);
            color: var(--work-schedule-detail-muted);
            font-size: 0.6875rem;
            line-height: 1.5;
        }

        .work-schedule-detail-main-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.15fr)
                minmax(20rem, 0.85fr);
            gap: var(--space-4);
        }

        .work-schedule-detail-side-stack {
            display: grid;
            gap: var(--space-4);
        }

        .work-schedule-detail-card-body {
            padding: var(--space-4);
        }

        .work-schedule-detail-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
        }

        .work-schedule-detail-row {
            display: grid;
            grid-template-columns:
                minmax(10rem, 0.9fr)
                minmax(0, 1.1fr);
            gap: var(--space-3);
            align-items: start;
            margin: 0;
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--neutral-100);
        }

        .work-schedule-detail-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .work-schedule-detail-row dt,
        .work-schedule-detail-row dd {
            margin: 0;
        }

        .work-schedule-detail-row dt {
            color: var(--work-schedule-detail-muted);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .work-schedule-detail-row dd {
            min-width: 0;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .work-schedule-detail-window {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .work-schedule-detail-window.is-check-out {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .work-schedule-detail-window-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .work-schedule-detail-window-label {
            color: var(--work-schedule-detail-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .work-schedule-detail-window-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.9375rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .work-schedule-detail-notice,
        .work-schedule-detail-warning {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            margin-top: var(--space-4);
            padding: var(--space-4);
            border-radius: var(--radius-md);
        }

        .work-schedule-detail-notice {
            border: 1px solid #cde0eb;
            color: var(--info-700);
            background: var(--info-50);
        }

        .work-schedule-detail-warning {
            border: 1px solid #f0ddb0;
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .work-schedule-detail-notice-icon {
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

        .work-schedule-detail-notice-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .work-schedule-detail-notice-copy {
            margin: 0;
            font-size: 0.75rem;
            line-height: 1.7;
        }

        .work-schedule-detail-danger-card {
            border-color: #efc9c3;
        }

        .work-schedule-detail-danger-header {
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid #efc9c3;
            background: var(--danger-50);
        }

        .work-schedule-detail-danger-title {
            margin: 0;
            color: var(--danger-700);
            font-size: 1rem;
            font-weight: 800;
        }

        .work-schedule-detail-danger-body {
            padding: var(--space-4);
        }

        @media (min-width: 768px) {
            .work-schedule-detail-summary-grid,
            .work-schedule-detail-card-body,
            .work-schedule-detail-danger-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .work-schedule-detail-summary-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .work-schedule-detail-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .work-schedule-detail-window,
            .work-schedule-detail-window.is-check-out {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .work-schedule-detail-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .work-schedule-detail-summary-grid {
                grid-template-columns: 1fr;
            }

            .work-schedule-detail-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $toTime = static function ($value): ?\DateTimeImmutable {
            if ($value === null || $value === '') {
                return null;
            }

            if ($value instanceof \DateTimeInterface) {
                return \DateTimeImmutable::createFromInterface(
                    $value
                );
            }

            $time = trim((string) $value);

            foreach (['!H:i:s', '!H:i'] as $format) {
                $parsedTime =
                    \DateTimeImmutable::createFromFormat(
                        $format,
                        $time
                    );

                if ($parsedTime !== false) {
                    return $parsedTime;
                }
            }

            return null;
        };

        $formatTime = static function (
            ?\DateTimeInterface $time
        ): string {
            return $time?->format('H:i') ?? '-';
        };

        $checkInTime = $toTime(
            $workSchedule->check_in_time
        );

        $checkOutTime = $toTime(
            $workSchedule->check_out_time
        );

        $checkInOpenMinutes = (int) (
            $workSchedule->check_in_open_minutes ?? 0
        );

        $lateToleranceMinutes = (int) (
            $workSchedule->late_tolerance_minutes ?? 0
        );

        $checkOutLimitMinutes = (int) (
            $workSchedule->check_out_limit_minutes ?? 0
        );

        $checkInOpenTime = $checkInTime?->modify(
            "-{$checkInOpenMinutes} minutes"
        );

        $lateToleranceLimit = $checkInTime?->modify(
            "+{$lateToleranceMinutes} minutes"
        );

        $checkOutLimitTime = $checkOutTime?->modify(
            "+{$checkOutLimitMinutes} minutes"
        );

        $usageCount = (int) (
            $workSchedule->employee_schedules_count ?? 0
        );

        $isActive = $workSchedule->status === 'active';
    @endphp

    <div class="work-schedule-detail-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Detail Pola Jadwal
                </h1>

                <p class="page-description">
                    Informasi jam kerja dan aturan waktu presensi
                    {{ $workSchedule->name }}.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <a
                    href="{{ route('work-schedules.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Pola Jadwal
                </a>

                <a
                    href="{{ route(
                        'work-schedules.edit',
                        $workSchedule
                    ) }}"
                    class="btn btn-primary"
                >
                    <i
                        class="bi bi-pencil-square me-2"
                        aria-hidden="true"
                    ></i>

                    Edit Pola Jadwal
                </a>
            </div>
        </header>

        <section
            class="work-schedule-detail-summary-card"
            aria-labelledby="work-schedule-summary-heading"
        >
            <div class="work-schedule-detail-card-header">
                <div>
                    <h2
                        id="work-schedule-summary-heading"
                        class="work-schedule-detail-card-title"
                    >
                        Ringkasan Pola Jadwal
                    </h2>

                    <p class="work-schedule-detail-card-copy">
                        Waktu kerja, status penggunaan, dan jumlah
                        jadwal harian yang menggunakan pola ini.
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

            <div class="work-schedule-detail-summary-grid">
                <article
                    class="work-schedule-detail-summary-item"
                >
                    <span
                        class="work-schedule-detail-summary-icon"
                    >
                        <i
                            class="bi bi-box-arrow-in-right"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-detail-summary-label"
                    >
                        Jam Masuk
                    </div>

                    <div
                        class="work-schedule-detail-summary-value"
                    >
                        {{ $formatTime($checkInTime) }}
                    </div>

                    <div
                        class="work-schedule-detail-summary-meta"
                    >
                        WIB
                    </div>
                </article>

                <article
                    class="work-schedule-detail-summary-item"
                >
                    <span
                        class="work-schedule-detail-summary-icon"
                    >
                        <i
                            class="bi bi-box-arrow-right"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-detail-summary-label"
                    >
                        Jam Pulang
                    </div>

                    <div
                        class="work-schedule-detail-summary-value"
                    >
                        {{ $formatTime($checkOutTime) }}
                    </div>

                    <div
                        class="work-schedule-detail-summary-meta"
                    >
                        WIB
                    </div>
                </article>

                <article
                    class="work-schedule-detail-summary-item"
                >
                    <span
                        class="work-schedule-detail-summary-icon"
                    >
                        <i
                            class="bi bi-people"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-detail-summary-label"
                    >
                        Digunakan
                    </div>

                    <div
                        class="work-schedule-detail-summary-value"
                    >
                        {{ $usageCount }}
                    </div>

                    <div
                        class="work-schedule-detail-summary-meta"
                    >
                        Jadwal harian karyawan
                    </div>
                </article>

                <article
                    class="work-schedule-detail-summary-item"
                >
                    <span
                        class="work-schedule-detail-summary-icon"
                    >
                        <i
                            class="bi bi-toggle-on"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-detail-summary-label"
                    >
                        Status
                    </div>

                    <div class="mt-2">
                        <span
                            class="badge {{
                                $isActive
                                    ? 'text-bg-success'
                                    : 'text-bg-secondary'
                            }}"
                        >
                            {{
                                $isActive
                                    ? 'Aktif'
                                    : 'Tidak aktif'
                            }}
                        </span>
                    </div>

                    <div
                        class="work-schedule-detail-summary-meta"
                    >
                        Status pola jadwal
                    </div>
                </article>
            </div>
        </section>

        <div class="work-schedule-detail-main-grid">
            <section
                class="work-schedule-detail-card"
                aria-labelledby="work-schedule-information-heading"
            >
                <div class="work-schedule-detail-card-header">
                    <div>
                        <h2
                            id="work-schedule-information-heading"
                            class="work-schedule-detail-card-title"
                        >
                            Informasi Pola Jadwal
                        </h2>

                        <p class="work-schedule-detail-card-copy">
                            Identitas dan konfigurasi utama
                            jadwal kerja.
                        </p>
                    </div>
                </div>

                <div class="work-schedule-detail-card-body">
                    <dl class="work-schedule-detail-list">
                        <div class="work-schedule-detail-row">
                            <dt>
                                Nama Jadwal
                            </dt>

                            <dd>
                                {{ $workSchedule->name }}
                            </dd>
                        </div>

                        <div class="work-schedule-detail-row">
                            <dt>
                                Jam Masuk
                            </dt>

                            <dd>
                                {{ $formatTime($checkInTime) }} WIB
                            </dd>
                        </div>

                        <div class="work-schedule-detail-row">
                            <dt>
                                Jam Pulang
                            </dt>

                            <dd>
                                {{ $formatTime($checkOutTime) }} WIB
                            </dd>
                        </div>

                        <div class="work-schedule-detail-row">
                            <dt>
                                Pembukaan Presensi Masuk
                            </dt>

                            <dd>
                                {{ $checkInOpenMinutes }} menit
                                sebelum jam masuk
                            </dd>
                        </div>

                        <div class="work-schedule-detail-row">
                            <dt>
                                Toleransi Keterlambatan
                            </dt>

                            <dd>
                                {{ $lateToleranceMinutes }} menit
                                setelah jam masuk
                            </dd>
                        </div>

                        <div class="work-schedule-detail-row">
                            <dt>
                                Batas Presensi Pulang
                            </dt>

                            <dd>
                                {{ $checkOutLimitMinutes }} menit
                                setelah jam pulang
                            </dd>
                        </div>

                        <div class="work-schedule-detail-row">
                            <dt>
                                Status
                            </dt>

                            <dd>
                                @if ($isActive)
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
                            </dd>
                        </div>

                        <div class="work-schedule-detail-row">
                            <dt>
                                Dibuat
                            </dt>

                            <dd>
                                @if (
                                    $workSchedule->created_at
                                    !== null
                                )
                                    {{
                                        $workSchedule->created_at
                                            ->timezone(
                                                'Asia/Jakarta'
                                            )
                                            ->format(
                                                'd-m-Y H:i'
                                            )
                                    }}
                                    WIB
                                @else
                                    -
                                @endif
                            </dd>
                        </div>

                        <div class="work-schedule-detail-row">
                            <dt>
                                Terakhir Diperbarui
                            </dt>

                            <dd>
                                @if (
                                    $workSchedule->updated_at
                                    !== null
                                )
                                    {{
                                        $workSchedule->updated_at
                                            ->timezone(
                                                'Asia/Jakarta'
                                            )
                                            ->format(
                                                'd-m-Y H:i'
                                            )
                                    }}
                                    WIB
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <div class="work-schedule-detail-side-stack">
                <section
                    class="work-schedule-detail-card"
                    aria-labelledby="check-in-window-heading"
                >
                    <div class="work-schedule-detail-card-header">
                        <div>
                            <h2
                                id="check-in-window-heading"
                                class="work-schedule-detail-card-title"
                            >
                                Rentang Presensi Masuk
                            </h2>

                            <p
                                class="work-schedule-detail-card-copy"
                            >
                                Perhitungan berdasarkan konfigurasi
                                jadwal.
                            </p>
                        </div>
                    </div>

                    <div class="work-schedule-detail-card-body">
                        <div
                            class="work-schedule-detail-window"
                        >
                            <article
                                class="work-schedule-detail-window-item"
                            >
                                <div
                                    class="work-schedule-detail-window-label"
                                >
                                    Presensi Dibuka
                                </div>

                                <div
                                    class="work-schedule-detail-window-value"
                                >
                                    {{
                                        $formatTime(
                                            $checkInOpenTime
                                        )
                                    }}
                                    WIB
                                </div>
                            </article>

                            <article
                                class="work-schedule-detail-window-item"
                            >
                                <div
                                    class="work-schedule-detail-window-label"
                                >
                                    Jam Masuk
                                </div>

                                <div
                                    class="work-schedule-detail-window-value"
                                >
                                    {{
                                        $formatTime(
                                            $checkInTime
                                        )
                                    }}
                                    WIB
                                </div>
                            </article>

                            <article
                                class="work-schedule-detail-window-item"
                            >
                                <div
                                    class="work-schedule-detail-window-label"
                                >
                                    Batas Toleransi
                                </div>

                                <div
                                    class="work-schedule-detail-window-value"
                                >
                                    {{
                                        $formatTime(
                                            $lateToleranceLimit
                                        )
                                    }}
                                    WIB
                                </div>
                            </article>
                        </div>

                        <div
                            class="work-schedule-detail-notice"
                            role="note"
                        >
                            <span
                                class="work-schedule-detail-notice-icon"
                            >
                                <i
                                    class="bi bi-info-circle"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3
                                    class="work-schedule-detail-notice-title"
                                >
                                    Status keterlambatan
                                </h3>

                                <p
                                    class="work-schedule-detail-notice-copy"
                                >
                                    Presensi setelah batas toleransi
                                    tetap dapat dicatat, tetapi
                                    diberikan status terlambat.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="work-schedule-detail-card"
                    aria-labelledby="check-out-window-heading"
                >
                    <div class="work-schedule-detail-card-header">
                        <div>
                            <h2
                                id="check-out-window-heading"
                                class="work-schedule-detail-card-title"
                            >
                                Rentang Presensi Pulang
                            </h2>

                            <p
                                class="work-schedule-detail-card-copy"
                            >
                                Presensi pulang hanya sah dalam
                                rentang waktu yang ditentukan.
                            </p>
                        </div>
                    </div>

                    <div class="work-schedule-detail-card-body">
                        <div
                            class="work-schedule-detail-window
                                is-check-out"
                        >
                            <article
                                class="work-schedule-detail-window-item"
                            >
                                <div
                                    class="work-schedule-detail-window-label"
                                >
                                    Presensi Pulang Dibuka
                                </div>

                                <div
                                    class="work-schedule-detail-window-value"
                                >
                                    {{
                                        $formatTime(
                                            $checkOutTime
                                        )
                                    }}
                                    WIB
                                </div>
                            </article>

                            <article
                                class="work-schedule-detail-window-item"
                            >
                                <div
                                    class="work-schedule-detail-window-label"
                                >
                                    Batas Akhir
                                </div>

                                <div
                                    class="work-schedule-detail-window-value"
                                >
                                    {{
                                        $formatTime(
                                            $checkOutLimitTime
                                        )
                                    }}
                                    WIB
                                </div>
                            </article>
                        </div>

                        <div
                            class="work-schedule-detail-warning"
                            role="note"
                        >
                            <span
                                class="work-schedule-detail-notice-icon"
                            >
                                <i
                                    class="bi
                                        bi-exclamation-triangle"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <h3
                                    class="work-schedule-detail-notice-title"
                                >
                                    Validasi presensi pulang
                                </h3>

                                <p
                                    class="work-schedule-detail-notice-copy"
                                >
                                    Percobaan presensi pulang
                                    sebelum jam pulang tidak dianggap
                                    sebagai presensi pulang yang sah.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="work-schedule-detail-danger-card"
                    aria-labelledby="delete-work-schedule-heading"
                >
                    <div
                        class="work-schedule-detail-danger-header"
                    >
                        <h2
                            id="delete-work-schedule-heading"
                            class="work-schedule-detail-danger-title"
                        >
                            Penghapusan Pola Jadwal
                        </h2>
                    </div>

                    <div
                        class="work-schedule-detail-danger-body"
                    >
                        @if ($usageCount > 0)
                            <div
                                class="work-schedule-detail-warning
                                    mt-0"
                                role="alert"
                            >
                                <span
                                    class="work-schedule-detail-notice-icon"
                                >
                                    <i
                                        class="bi bi-lock"
                                        aria-hidden="true"
                                    ></i>
                                </span>

                                <div>
                                    <h3
                                        class="work-schedule-detail-notice-title"
                                    >
                                        Penghapusan tidak tersedia
                                    </h3>

                                    <p
                                        class="work-schedule-detail-notice-copy"
                                    >
                                        Pola jadwal ini telah
                                        digunakan pada
                                        {{ $usageCount }}
                                        jadwal karyawan dan tidak
                                        dapat dihapus.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div
                                class="work-schedule-detail-warning
                                    mt-0 mb-4"
                                role="alert"
                            >
                                <span
                                    class="work-schedule-detail-notice-icon"
                                >
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>
                                </span>

                                <div>
                                    <h3
                                        class="work-schedule-detail-notice-title"
                                    >
                                        Tindakan permanen
                                    </h3>

                                    <p
                                        class="work-schedule-detail-notice-copy"
                                    >
                                        Pola jadwal belum digunakan
                                        pada jadwal karyawan.
                                        Penghapusan akan
                                        menghilangkan data secara
                                        permanen.
                                    </p>
                                </div>
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'work-schedules.destroy',
                                    $workSchedule
                                ) }}"
                                onsubmit="
                                    return confirm(
                                        'Hapus pola jadwal ini secara permanen?'
                                    );
                                "
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn
                                        btn-outline-danger"
                                >
                                    <i
                                        class="bi bi-trash me-2"
                                        aria-hidden="true"
                                    ></i>

                                    Hapus Pola Jadwal
                                </button>
                            </form>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
