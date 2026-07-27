@extends('layouts.app')

@section('title', 'Koreksi Data Presensi')

@push('styles')
    <style>
        .attendance-correction-edit-page {
            --edit-surface: var(--neutral-0);
            --edit-border: var(--neutral-200);
            --edit-muted: var(--neutral-600);
            --edit-orange-soft: var(--brand-50);
        }

        .correction-edit-overview,
        .correction-history-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--edit-border);
            border-radius: var(--radius-lg);
            background: var(--edit-surface);
            box-shadow: var(--shadow-xs);
        }

        .correction-edit-overview-header,
        .correction-history-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--edit-border);
            background: var(--neutral-25);
        }

        .correction-edit-title,
        .correction-history-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .correction-edit-copy,
        .correction-history-copy {
            max-width: 48rem;
            margin: var(--space-1) 0 0;
            color: var(--edit-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .correction-edit-overview-body {
            padding: var(--space-4);
        }

        .correction-edit-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .correction-edit-summary-item {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .correction-edit-summary-icon {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--edit-orange-soft);
            font-size: 0.9375rem;
        }

        .correction-edit-summary-label {
            color: var(--edit-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .correction-edit-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .correction-edit-summary-meta {
            display: block;
            margin-top: var(--space-1);
            color: var(--edit-muted);
            font-size: 0.6875rem;
            line-height: 1.45;
        }

        .correction-edit-warning {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            margin-top: var(--space-4);
            padding: var(--space-4);
            border: 1px solid #f0ddb0;
            border-radius: var(--radius-md);
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .correction-edit-warning-icon {
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

        .correction-edit-warning-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .correction-edit-warning-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .correction-history-time {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .correction-history-timezone {
            display: block;
            margin-top: var(--space-1);
            color: var(--edit-muted);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .correction-history-user {
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.45;
        }

        .correction-history-reason {
            color: var(--neutral-800);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .correction-history-mobile-list {
            display: none;
        }

        .correction-history-mobile-card {
            padding: var(--space-4);
            border-bottom: 1px solid var(--edit-border);
        }

        .correction-history-mobile-card:last-child {
            border-bottom: 0;
        }

        .correction-history-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .correction-history-mobile-number {
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-pill);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .correction-history-mobile-section {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-100);
        }

        .correction-history-mobile-label {
            margin-bottom: var(--space-2);
            color: var(--edit-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .correction-history-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .correction-history-mobile-metric {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .correction-history-mobile-metric-label {
            color: var(--edit-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .correction-history-mobile-metric-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        @media (min-width: 768px) {
            .correction-edit-overview-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .correction-edit-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .correction-history-desktop-table {
                display: none;
            }

            .correction-history-mobile-list {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .correction-edit-overview-header,
            .correction-history-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .correction-edit-summary-grid,
            .correction-history-mobile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $attendanceDateValue =
            $attendance->attendance_date
                instanceof \DateTimeInterface
                    ? $attendance->attendance_date
                        ->format('Y-m-d')
                    : substr(
                        (string) $attendance
                            ->attendance_date,
                        0,
                        10
                    );

        $monitoringUrl = route(
            'attendance-monitoring.index',
            [
                'attendance_date' =>
                    $attendanceDateValue,

                'employee_id' =>
                    $attendance->employee_id,
            ]
        );

        $correctionHistory =
            $attendance->corrections
            ?? collect();

        $actionLabels = [
            'create' => 'Pembuatan Manual',
            'update' => 'Perubahan Data',
        ];

        $actionClasses = [
            'create' => 'text-bg-primary',
            'update' => 'text-bg-warning',
        ];

        $formatDateTime = static function (
            mixed $value
        ): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat(
                        'd F Y, H:i:s'
                    );
            } catch (\Throwable) {
                return (string) $value;
            }
        };
    @endphp

    <div class="attendance-correction-edit-page">
        <header
            class="page-header d-md-flex
                align-items-start justify-content-between
                gap-3"
        >
            <div>
                <h1 class="page-title">
                    Koreksi Data Presensi
                </h1>

                <p class="page-description">
                    Perbarui catatan presensi berdasarkan hasil
                    pemeriksaan dan verifikasi HRD.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ $monitoringUrl }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Kembali ke Monitoring
                </a>
            </div>
        </header>

        <section
            class="correction-edit-overview"
            aria-labelledby="correction-information-heading"
        >
            <div class="correction-edit-overview-header">
                <div>
                    <h2
                        id="correction-information-heading"
                        class="correction-edit-title"
                    >
                        Informasi Koreksi
                    </h2>

                    <p class="correction-edit-copy">
                        Setiap perubahan dicatat sebagai riwayat
                        audit baru tanpa menghapus kondisi
                        sebelumnya.
                    </p>
                </div>

                <span class="badge text-bg-warning">
                    Khusus HRD
                </span>
            </div>

            <div class="correction-edit-overview-body">
                <div class="correction-edit-summary-grid">
                    <article class="correction-edit-summary-item">
                        <span
                            class="correction-edit-summary-icon"
                        >
                            <i
                                class="bi bi-hash"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div
                            class="correction-edit-summary-label"
                        >
                            ID Presensi
                        </div>

                        <div
                            class="correction-edit-summary-value"
                        >
                            #{{ $attendance->id }}
                        </div>
                    </article>

                    <article class="correction-edit-summary-item">
                        <span
                            class="correction-edit-summary-icon"
                        >
                            <i
                                class="bi bi-person"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div
                            class="correction-edit-summary-label"
                        >
                            Karyawan
                        </div>

                        <div
                            class="correction-edit-summary-value"
                        >
                            {{
                                $attendance
                                    ->employee
                                    ?->full_name
                                ?? '-'
                            }}
                        </div>

                        <span
                            class="correction-edit-summary-meta"
                        >
                            {{
                                $attendance
                                    ->employee
                                    ?->employee_number
                                ?? '-'
                            }}
                        </span>
                    </article>

                    <article class="correction-edit-summary-item">
                        <span
                            class="correction-edit-summary-icon"
                        >
                            <i
                                class="bi bi-building"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div
                            class="correction-edit-summary-label"
                        >
                            Cabang
                        </div>

                        <div
                            class="correction-edit-summary-value"
                        >
                            {{
                                $attendance
                                    ->employee
                                    ?->branch
                                    ?->name
                                ?? '-'
                            }}
                        </div>

                        <span
                            class="correction-edit-summary-meta"
                        >
                            {{
                                $attendance
                                    ->employee
                                    ?->branch
                                    ?->code
                                ?? '-'
                            }}
                        </span>
                    </article>

                    <article class="correction-edit-summary-item">
                        <span
                            class="correction-edit-summary-icon"
                        >
                            <i
                                class="bi bi-clock-history"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div
                            class="correction-edit-summary-label"
                        >
                            Riwayat Koreksi
                        </div>

                        <div
                            class="correction-edit-summary-value"
                        >
                            {{ $correctionHistory->count() }}
                            tindakan
                        </div>
                    </article>
                </div>

                <div class="correction-edit-warning">
                    <span
                        class="correction-edit-warning-icon"
                    >
                        <i
                            class="bi bi-exclamation-triangle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h3
                            class="correction-edit-warning-title"
                        >
                            Sumber data aktif akan menjadi manual
                        </h3>

                        <p
                            class="correction-edit-warning-copy"
                        >
                            Setelah perubahan disimpan, data
                            presensi aktif memiliki sumber
                            <strong>manual</strong>. Data sesi QR,
                            koordinat, accuracy, jarak, dan radius
                            tidak lagi digunakan pada data aktif.
                            Kondisi sebelumnya tetap tersimpan
                            dalam tabel audit.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        @if ($correctionHistory->isNotEmpty())
            <section
                class="correction-history-card"
                aria-labelledby="correction-history-heading"
            >
                <div class="correction-history-header">
                    <div>
                        <h2
                            id="correction-history-heading"
                            class="correction-history-title"
                        >
                            Riwayat Koreksi Sebelumnya
                        </h2>

                        <p class="correction-history-copy">
                            Daftar tindakan koreksi yang sudah
                            dilakukan pada transaksi ini.
                        </p>
                    </div>

                    <span class="badge text-bg-secondary">
                        {{ $correctionHistory->count() }}
                        tindakan
                    </span>
                </div>

                <div
                    class="correction-history-desktop-table
                        table-responsive"
                >
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
                                    Waktu
                                </th>

                                <th scope="col">
                                    Tindakan
                                </th>

                                <th scope="col">
                                    HRD
                                </th>

                                <th scope="col">
                                    Alasan
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $correctionHistory
                                as $correction
                            )
                                @php
                                    $currentAction =
                                        strtolower(
                                            (string)
                                                $correction
                                                    ->action
                                        );

                                    $actionLabel =
                                        $actionLabels[
                                            $currentAction
                                        ]
                                        ?? \Illuminate\Support\Str::headline(
                                            $currentAction
                                        );

                                    $actionClass =
                                        $actionClasses[
                                            $currentAction
                                        ]
                                        ?? 'text-bg-secondary';
                                @endphp

                                <tr>
                                    <td
                                        class="text-center
                                            text-secondary"
                                    >
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        <span
                                            class="correction-history-time"
                                        >
                                            {{
                                                $formatDateTime(
                                                    $correction
                                                        ->created_at
                                                )
                                            }}
                                        </span>

                                        <span
                                            class="correction-history-timezone"
                                        >
                                            WIB
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                {{ $actionClass }}"
                                        >
                                            {{ $actionLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="correction-history-user"
                                        >
                                            {{
                                                $correction
                                                    ->correctedBy
                                                    ?->name
                                                ?? 'Pengguna tidak tersedia'
                                            }}
                                        </span>
                                    </td>

                                    <td>
                                        <div
                                            class="correction-history-reason"
                                        >
                                            {{ $correction->reason }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="correction-history-mobile-list">
                    @foreach (
                        $correctionHistory
                        as $correction
                    )
                        @php
                            $currentAction =
                                strtolower(
                                    (string)
                                        $correction->action
                                );

                            $actionLabel =
                                $actionLabels[$currentAction]
                                ?? \Illuminate\Support\Str::headline(
                                    $currentAction
                                );

                            $actionClass =
                                $actionClasses[$currentAction]
                                ?? 'text-bg-secondary';
                        @endphp

                        <article
                            class="correction-history-mobile-card"
                        >
                            <div
                                class="correction-history-mobile-top"
                            >
                                <span
                                    class="correction-history-mobile-number"
                                >
                                    {{ $loop->iteration }}
                                </span>

                                <span
                                    class="badge
                                        {{ $actionClass }}"
                                >
                                    {{ $actionLabel }}
                                </span>
                            </div>

                            <div
                                class="correction-history-mobile-section"
                            >
                                <div
                                    class="correction-history-mobile-grid"
                                >
                                    <div
                                        class="correction-history-mobile-metric"
                                    >
                                        <div
                                            class="correction-history-mobile-metric-label"
                                        >
                                            Waktu
                                        </div>

                                        <div
                                            class="correction-history-mobile-metric-value"
                                        >
                                            {{
                                                $formatDateTime(
                                                    $correction
                                                        ->created_at
                                                )
                                            }}
                                            WIB
                                        </div>
                                    </div>

                                    <div
                                        class="correction-history-mobile-metric"
                                    >
                                        <div
                                            class="correction-history-mobile-metric-label"
                                        >
                                            HRD
                                        </div>

                                        <div
                                            class="correction-history-mobile-metric-value"
                                        >
                                            {{
                                                $correction
                                                    ->correctedBy
                                                    ?->name
                                                ?? 'Pengguna tidak tersedia'
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="correction-history-mobile-section"
                            >
                                <div
                                    class="correction-history-mobile-label"
                                >
                                    Alasan
                                </div>

                                <div
                                    class="correction-history-reason"
                                >
                                    {{ $correction->reason }}
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @include(
            'attendance-corrections._form',
            [
                'attendance' => $attendance,

                'formAction' => route(
                    'attendance-corrections.update',
                    [
                        'attendance' =>
                            $attendance->getKey(),
                    ]
                ),

                'formMethod' => 'PUT',

                'submitLabel' =>
                    'Simpan Koreksi',

                'cancelUrl' => $monitoringUrl,
            ]
        )
    </div>
@endsection
