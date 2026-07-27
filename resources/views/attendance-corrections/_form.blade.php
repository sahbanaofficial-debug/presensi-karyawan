@php
    $attendanceRecord = $attendance ?? null;

    $editing = isset($attendanceRecord)
        && $attendanceRecord !== null
        && $attendanceRecord->exists;

    $actionUrl = $formAction
        ?? route('attendance-corrections.store');

    $httpMethod = strtoupper(
        (string) ($formMethod ?? 'POST')
    );

    $submitText = $submitLabel
        ?? (
            $editing
                ? 'Simpan Koreksi'
                : 'Simpan Presensi Manual'
        );

    $cancelUrl = $cancelUrl
        ?? route('attendance-monitoring.index');

    $selectedEmployeeId = old(
        'employee_id',
        $attendanceRecord?->employee_id
    );

    $selectedAttendanceType = old(
        'attendance_type',
        $attendanceRecord?->attendance_type
    );

    $attendanceDateValue = old(
        'attendance_date',
        $attendanceRecord?->attendance_date
            ? $attendanceRecord
                ->attendance_date
                ->format('Y-m-d')
            : ($defaultAttendanceDate ?? '')
    );

    $attendanceTimeValue = old(
        'attendance_time',
        $attendanceRecord?->attendance_time
            ? $attendanceRecord
                ->attendance_time
                ->format('H:i')
            : ''
    );

    /*
    |--------------------------------------------------------------------------
    | Alasan Koreksi
    |--------------------------------------------------------------------------
    |
    | Alasan lama tidak dimasukkan otomatis. Setiap tindakan koreksi harus
    | memiliki alasan baru agar jejak audit dapat menjelaskan perubahan
    | yang sedang dilakukan.
    |
    */
    $reasonValue = old('reason', '');

    $recordSourceLabels = [
        'scanner' => 'Pemindai QR',
        'manual' => 'Koreksi Manual',
    ];

    $attendanceTypeLabels = [
        'check_in' => 'Presensi Masuk',
        'check_out' => 'Presensi Pulang',
    ];

    $punctualityLabels = [
        'on_time' => 'Tepat Waktu',
        'late' => 'Terlambat',
        'not_applicable' => 'Tidak Berlaku',
    ];

    $punctualityClasses = [
        'on_time' => 'text-bg-success',
        'late' => 'text-bg-danger',
        'not_applicable' => 'text-bg-secondary',
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
                ->translatedFormat('d F Y, H:i:s');
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $recordSource = (string) (
        $attendanceRecord?->record_source
        ?? ''
    );

    $recordSourceLabel =
        $recordSourceLabels[$recordSource]
        ?? ucfirst($recordSource);

    $currentAttendanceType = (string) (
        $attendanceRecord?->attendance_type
        ?? ''
    );

    $currentAttendanceTypeLabel =
        $attendanceTypeLabels[$currentAttendanceType]
        ?? ucfirst($currentAttendanceType);

    $currentPunctualityStatus = (string) (
        $attendanceRecord?->punctuality_status
        ?? ''
    );

    $currentPunctualityLabel =
        $punctualityLabels[$currentPunctualityStatus]
        ?? ucfirst($currentPunctualityStatus);

    $currentPunctualityClass =
        $punctualityClasses[$currentPunctualityStatus]
        ?? 'text-bg-secondary';
@endphp

@once
    @push('styles')
        <style>
            .attendance-correction-form {
                --correction-surface: var(--neutral-0);
                --correction-border: var(--neutral-200);
                --correction-muted: var(--neutral-600);
                --correction-orange-soft: var(--brand-50);
            }

            .correction-error-card {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                padding: var(--space-4);
                border: 1px solid #efc9c3;
                border-radius: var(--radius-lg);
                color: var(--danger-700);
                background: var(--danger-50);
            }

            .correction-error-icon {
                display: inline-flex;
                width: 2.25rem;
                height: 2.25rem;
                flex: 0 0 2.25rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                color: var(--danger-700);
                background: rgba(199, 70, 50, 0.09);
            }

            .correction-error-title {
                margin-bottom: var(--space-2);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .correction-error-list {
                margin: 0;
                padding-left: 1.125rem;
                font-size: 0.8125rem;
                line-height: 1.65;
            }

            .correction-card {
                overflow: hidden;
                margin-bottom: var(--space-5);
                border: 1px solid var(--correction-border);
                border-radius: var(--radius-lg);
                background: var(--correction-surface);
                box-shadow: var(--shadow-xs);
            }

            .correction-card-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: var(--space-4);
                padding: var(--space-4) var(--space-5);
                border-bottom: 1px solid var(--correction-border);
                background: var(--neutral-25);
            }

            .correction-card-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 1rem;
                font-weight: 800;
                letter-spacing: -0.01em;
            }

            .correction-card-copy {
                margin: var(--space-1) 0 0;
                color: var(--correction-muted);
                font-size: 0.75rem;
                line-height: 1.55;
            }

            .correction-card-body {
                padding: var(--space-4);
            }

            .correction-current-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: var(--space-3);
            }

            .correction-current-item {
                min-width: 0;
                padding: var(--space-3);
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-md);
                background: var(--neutral-25);
            }

            .correction-current-label {
                color: var(--correction-muted);
                font-size: 0.625rem;
                font-weight: 800;
                letter-spacing: 0.05em;
                text-transform: uppercase;
            }

            .correction-current-value {
                margin-top: var(--space-1);
                color: var(--neutral-900);
                font-size: 0.8125rem;
                font-weight: 800;
                line-height: 1.5;
                overflow-wrap: anywhere;
            }

            .correction-current-meta {
                display: block;
                margin-top: var(--space-1);
                color: var(--correction-muted);
                font-size: 0.6875rem;
                line-height: 1.45;
            }

            .correction-audit-panel {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: var(--space-3);
                margin-top: var(--space-4);
                padding-top: var(--space-4);
                border-top: 1px solid var(--neutral-100);
            }

            .correction-audit-item {
                min-width: 0;
                padding: var(--space-3);
                border-left: 0.1875rem solid var(--brand-300);
                border-radius: 0 var(--radius-md) var(--radius-md) 0;
                background: var(--brand-50);
            }

            .correction-field-group {
                position: relative;
            }

            .correction-field-icon {
                position: absolute;
                z-index: 2;
                top: 2.9rem;
                left: var(--space-3);
                color: var(--neutral-500);
                pointer-events: none;
            }

            .correction-field-group.has-icon .form-control,
            .correction-field-group.has-icon .form-select {
                padding-left: 2.75rem;
            }

            .correction-required {
                color: var(--danger-500);
            }

            .correction-reason-counter {
                color: var(--neutral-500);
                font-family:
                    ui-monospace,
                    SFMono-Regular,
                    Menlo,
                    Monaco,
                    Consolas,
                    monospace;
                font-size: 0.6875rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .correction-warning {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                padding: var(--space-4);
                border: 1px solid #f0ddb0;
                border-radius: var(--radius-md);
                color: var(--warning-700);
                background: var(--warning-50);
            }

            .correction-warning-icon {
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

            .correction-warning-title {
                margin: 0 0 var(--space-2);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .correction-warning-copy {
                margin: 0;
                font-size: 0.8125rem;
                line-height: 1.65;
            }

            .correction-actions {
                display: flex;
                flex-direction: column-reverse;
                justify-content: flex-end;
                gap: var(--space-2);
            }

            @media (min-width: 576px) {
                .correction-actions {
                    flex-direction: row;
                }
            }

            @media (min-width: 768px) {
                .correction-card-body {
                    padding: var(--space-5);
                }
            }

            @media (max-width: 1199.98px) {
                .correction-current-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 575.98px) {
                .correction-card-header {
                    padding: var(--space-4);
                }

                .correction-current-grid,
                .correction-audit-panel {
                    grid-template-columns: 1fr;
                }

                .correction-actions .btn {
                    width: 100%;
                }
            }
        </style>
    @endpush
@endonce

<div class="attendance-correction-form">
    @if ($errors->any())
        <div
            class="correction-error-card mb-4"
            role="alert"
        >
            <span class="correction-error-icon">
                <i
                    class="bi bi-exclamation-triangle"
                    aria-hidden="true"
                ></i>
            </span>

            <div>
                <div class="correction-error-title">
                    Data belum dapat disimpan
                </div>

                <ul class="correction-error-list">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if ($editing)
        <section
            class="correction-card"
            aria-labelledby="current-attendance-heading"
        >
            <div class="correction-card-header">
                <div>
                    <h2
                        id="current-attendance-heading"
                        class="correction-card-title"
                    >
                        Data Presensi Saat Ini
                    </h2>

                    <p class="correction-card-copy">
                        Kondisi presensi sebelum koreksi terbaru
                        disimpan.
                    </p>
                </div>

                <span class="badge text-bg-secondary">
                    {{ $recordSourceLabel }}
                </span>
            </div>

            <div class="correction-card-body">
                <div class="correction-current-grid">
                    <article class="correction-current-item">
                        <div class="correction-current-label">
                            Karyawan
                        </div>

                        <div class="correction-current-value">
                            {{
                                $attendanceRecord
                                    ->employee
                                    ?->full_name
                                ?? '-'
                            }}
                        </div>

                        <span class="correction-current-meta">
                            {{
                                $attendanceRecord
                                    ->employee
                                    ?->employee_number
                                ?? '-'
                            }}
                        </span>
                    </article>

                    <article class="correction-current-item">
                        <div class="correction-current-label">
                            Tanggal dan Waktu
                        </div>

                        <div class="correction-current-value">
                            {{
                                $formatDateTime(
                                    $attendanceRecord
                                        ->attendance_time
                                )
                            }}
                            WIB
                        </div>
                    </article>

                    <article class="correction-current-item">
                        <div class="correction-current-label">
                            Jenis Presensi
                        </div>

                        <div class="correction-current-value">
                            {{ $currentAttendanceTypeLabel }}
                        </div>
                    </article>

                    <article class="correction-current-item">
                        <div class="correction-current-label">
                            Ketepatan Waktu
                        </div>

                        <div class="correction-current-value">
                            <span
                                class="badge
                                    {{ $currentPunctualityClass }}"
                            >
                                {{ $currentPunctualityLabel }}
                            </span>
                        </div>
                    </article>
                </div>

                @if (
                    $attendanceRecord->last_corrected_at !== null
                    || $attendanceRecord->last_correction_reason
                )
                    <div class="correction-audit-panel">
                        <article class="correction-audit-item">
                            <div class="correction-current-label">
                                Koreksi Terakhir
                            </div>

                            <div class="correction-current-value">
                                {{
                                    $formatDateTime(
                                        $attendanceRecord
                                            ->last_corrected_at
                                    )
                                }}
                                WIB
                            </div>

                            <span class="correction-current-meta">
                                Oleh:
                                {{
                                    $attendanceRecord
                                        ->lastCorrectedBy
                                        ?->name
                                    ?? '-'
                                }}
                            </span>
                        </article>

                        <article class="correction-audit-item">
                            <div class="correction-current-label">
                                Alasan Koreksi Terakhir
                            </div>

                            <div class="correction-current-value">
                                {{
                                    $attendanceRecord
                                        ->last_correction_reason
                                    ?: '-'
                                }}
                            </div>
                        </article>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <form
        method="POST"
        action="{{ $actionUrl }}"
        novalidate
    >
        @csrf

        @if ($httpMethod !== 'POST')
            @method($httpMethod)
        @endif

        <section
            class="correction-card"
            aria-labelledby="attendance-data-heading"
        >
            <div class="correction-card-header">
                <div>
                    <h2
                        id="attendance-data-heading"
                        class="correction-card-title"
                    >
                        Data Presensi
                    </h2>

                    <p class="correction-card-copy">
                        Pilih karyawan dan masukkan tanggal serta
                        waktu berdasarkan hasil verifikasi HRD.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Data wajib
                </span>
            </div>

            <div class="correction-card-body">
                <div class="row g-4">
                    <div class="col-12">
                        <div
                            class="correction-field-group has-icon"
                        >
                            <label
                                for="employee_id"
                                class="form-label"
                            >
                                Karyawan
                                <span class="correction-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-person
                                    correction-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="employee_id"
                                name="employee_id"
                                class="form-select
                                    @error('employee_id')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option value="">
                                    Pilih karyawan
                                </option>

                                @foreach (
                                    $employees as $employeeOption
                                )
                                    <option
                                        value="{{
                                            $employeeOption->id
                                        }}"
                                        @selected(
                                            (string)
                                                $selectedEmployeeId
                                            === (string)
                                                $employeeOption->id
                                        )
                                    >
                                        {{
                                            $employeeOption
                                                ->employee_number
                                        }}
                                        |
                                        {{
                                            $employeeOption
                                                ->full_name
                                        }}

                                        @if (
                                            $employeeOption->branch
                                            !== null
                                        )
                                            |
                                            {{
                                                $employeeOption
                                                    ->branch
                                                    ->name
                                            }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('employee_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Hanya karyawan aktif pada cabang
                                aktif yang tersedia.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div
                            class="correction-field-group has-icon"
                        >
                            <label
                                for="attendance_date"
                                class="form-label"
                            >
                                Tanggal Presensi
                                <span class="correction-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-calendar3
                                    correction-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="date"
                                id="attendance_date"
                                name="attendance_date"
                                value="{{ $attendanceDateValue }}"
                                max="{{
                                    now(
                                        config(
                                            'app.timezone',
                                            'Asia/Jakarta'
                                        )
                                    )->format('Y-m-d')
                                }}"
                                class="form-control
                                    @error('attendance_date')
                                        is-invalid
                                    @enderror"
                                required
                            >

                            @error('attendance_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Tanggal harus memiliki jadwal kerja
                                untuk karyawan yang dipilih.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div
                            class="correction-field-group has-icon"
                        >
                            <label
                                for="attendance_type"
                                class="form-label"
                            >
                                Jenis Presensi
                                <span class="correction-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-arrow-left-right
                                    correction-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="attendance_type"
                                name="attendance_type"
                                class="form-select
                                    @error('attendance_type')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option value="">
                                    Pilih jenis presensi
                                </option>

                                <option
                                    value="check_in"
                                    @selected(
                                        $selectedAttendanceType
                                        === 'check_in'
                                    )
                                >
                                    Presensi Masuk
                                </option>

                                <option
                                    value="check_out"
                                    @selected(
                                        $selectedAttendanceType
                                        === 'check_out'
                                    )
                                >
                                    Presensi Pulang
                                </option>
                            </select>

                            @error('attendance_type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Satu jadwal hanya dapat memiliki
                                satu presensi masuk dan pulang.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div
                            class="correction-field-group has-icon"
                        >
                            <label
                                for="attendance_time"
                                class="form-label"
                            >
                                Waktu Presensi
                                <span class="correction-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-clock
                                    correction-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="time"
                                id="attendance_time"
                                name="attendance_time"
                                value="{{ $attendanceTimeValue }}"
                                step="60"
                                class="form-control
                                    @error('attendance_time')
                                        is-invalid
                                    @enderror"
                                required
                            >

                            @error('attendance_time')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Ketepatan waktu dihitung otomatis
                                berdasarkan jadwal.
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label
                            for="reason"
                            class="form-label"
                        >
                            Alasan Koreksi
                            <span class="correction-required">
                                *
                            </span>
                        </label>

                        <textarea
                            id="reason"
                            name="reason"
                            rows="5"
                            maxlength="1000"
                            class="form-control
                                @error('reason')
                                    is-invalid
                                @enderror"
                            placeholder="Jelaskan alasan pembuatan atau perubahan presensi berdasarkan hasil verifikasi."
                            required
                        >{{ $reasonValue }}</textarea>

                        @error('reason')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div
                            class="d-flex justify-content-between
                                align-items-start gap-3 form-text"
                        >
                            <span>
                                Minimal 10 karakter. Alasan disimpan
                                dalam riwayat audit.
                            </span>

                            <span
                                id="reason-counter"
                                class="correction-reason-counter"
                            >
                                0/1000
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="correction-card"
            aria-labelledby="correction-consequence-heading"
        >
            <div class="correction-card-header">
                <div>
                    <h2
                        id="correction-consequence-heading"
                        class="correction-card-title"
                    >
                        Konsekuensi Koreksi Manual
                    </h2>

                    <p class="correction-card-copy">
                        Informasi penting sebelum data disimpan.
                    </p>
                </div>
            </div>

            <div class="correction-card-body">
                <div class="correction-warning">
                    <span class="correction-warning-icon">
                        <i
                            class="bi bi-exclamation-triangle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h3 class="correction-warning-title">
                            Sumber data akan menjadi manual
                        </h3>

                        <p class="correction-warning-copy">
                            Presensi yang disimpan melalui formulir
                            ini akan memiliki sumber data
                            <strong>manual</strong>. Data sesi QR,
                            koordinat, accuracy, jarak, dan radius
                            geofence tidak digunakan pada hasil
                            koreksi aktif. Kondisi sebelum perubahan
                            tetap disimpan dalam riwayat audit.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="correction-actions">
            <a
                href="{{ $cancelUrl }}"
                class="btn btn-outline-secondary"
            >
                Batal
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                <i
                    class="bi bi-check2-circle me-2"
                    aria-hidden="true"
                ></i>

                {{ $submitText }}
            </button>
        </div>
    </form>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const reasonInput = document.getElementById(
                    'reason'
                );

                const reasonCounter = document.getElementById(
                    'reason-counter'
                );

                if (!reasonInput || !reasonCounter) {
                    return;
                }

                const updateCounter = () => {
                    reasonCounter.textContent =
                        `${reasonInput.value.length}/1000`;
                };

                reasonInput.addEventListener(
                    'input',
                    updateCounter
                );

                updateCounter();
            });
        </script>
    @endpush
@endonce
