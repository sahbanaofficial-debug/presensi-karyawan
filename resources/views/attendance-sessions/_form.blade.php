@php
    $selectedBranchId = old(
        'branch_id'
    );

    $selectedAttendanceType = old(
        'attendance_type',
        ''
    );

    $selectedSessionDate = old(
        'session_date',
        now()->toDateString()
    );

    $selectedStartTime = old(
        'start_time',
        ''
    );

    $selectedEndTime = old(
        'end_time',
        ''
    );
@endphp

@once
    @push('styles')
        <style>
            .attendance-session-form {
                --session-form-surface: var(--neutral-0);
                --session-form-border: var(--neutral-200);
                --session-form-muted: var(--neutral-600);
                --session-form-soft: var(--brand-50);
            }

            .session-form-heading {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: var(--space-4);
                padding-bottom: var(--space-4);
                border-bottom: 1px solid var(--session-form-border);
            }

            .session-form-heading-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 1rem;
                font-weight: 800;
                letter-spacing: -0.01em;
            }

            .session-form-heading-copy {
                margin: var(--space-1) 0 0;
                color: var(--session-form-muted);
                font-size: 0.75rem;
                line-height: 1.6;
            }

            .session-form-primary-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: var(--space-4);
            }

            .session-form-time-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: var(--space-4);
            }

            .session-form-field-card {
                min-width: 0;
                padding: var(--space-4);
                border: 1px solid var(--session-form-border);
                border-radius: var(--radius-lg);
                background: var(--neutral-25);
            }

            .session-form-field {
                position: relative;
            }

            .session-form-field-icon {
                position: absolute;
                z-index: 2;
                top: 2.9rem;
                left: var(--space-3);
                color: var(--neutral-500);
                pointer-events: none;
            }

            .session-form-field.has-icon .form-control,
            .session-form-field.has-icon .form-select {
                padding-left: 2.75rem;
            }

            .session-form-required {
                color: var(--danger-500);
            }

            .session-form-field-note {
                display: flex;
                align-items: flex-start;
                gap: var(--space-2);
                margin-top: var(--space-2);
                color: var(--session-form-muted);
                font-size: 0.6875rem;
                line-height: 1.55;
            }

            .session-form-warning {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                padding: var(--space-4);
                border: 1px solid #efc9c3;
                border-radius: var(--radius-md);
                color: var(--danger-700);
                background: var(--danger-50);
            }

            .session-form-warning-icon,
            .session-form-rules-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                font-size: 1rem;
            }

            .session-form-warning-icon {
                background: rgba(199, 70, 50, 0.09);
            }

            .session-form-warning-title {
                margin: 0 0 var(--space-1);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .session-form-warning-copy {
                margin: 0;
                font-size: 0.8125rem;
                line-height: 1.65;
            }

            .session-form-rules {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                padding: var(--space-4);
                border: 1px solid #cde0eb;
                border-radius: var(--radius-md);
                color: var(--info-700);
                background: var(--info-50);
            }

            .session-form-rules-icon {
                background: rgba(59, 126, 161, 0.09);
            }

            .session-form-rules-title {
                margin: 0 0 var(--space-2);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .session-form-rules-list {
                margin: 0;
                padding-left: 1.125rem;
                font-size: 0.75rem;
                line-height: 1.7;
            }

            @media (min-width: 768px) {
                .session-form-field-card {
                    padding: var(--space-5);
                }
            }

            @media (max-width: 991.98px) {
                .session-form-primary-grid {
                    grid-template-columns: 1fr;
                }

                .session-form-time-grid {
                    grid-template-columns: repeat(
                        2,
                        minmax(0, 1fr)
                    );
                }

                .session-form-date-card {
                    grid-column: 1 / -1;
                }
            }

            @media (max-width: 575.98px) {
                .session-form-heading {
                    flex-direction: column;
                }

                .session-form-time-grid {
                    grid-template-columns: 1fr;
                }

                .session-form-date-card {
                    grid-column: auto;
                }
            }
        </style>
    @endpush
@endonce

<div class="attendance-session-form">
    <div class="row g-4">
        <div class="col-12">
            <div class="session-form-heading">
                <div>
                    <h2 class="session-form-heading-title">
                        Data Sesi Presensi
                    </h2>

                    <p class="session-form-heading-copy">
                        Tentukan cabang, jenis presensi, tanggal,
                        dan rentang waktu berlakunya sesi.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Konfigurasi sesi
                </span>
            </div>
        </div>

        <div class="col-12">
            <div class="session-form-primary-grid">
                <section class="session-form-field-card">
                    <div class="session-form-field has-icon">
                        <label
                            for="branch_id"
                            class="form-label"
                        >
                            Cabang
                            <span class="session-form-required">
                                *
                            </span>
                        </label>

                        <i
                            class="bi bi-building
                                session-form-field-icon"
                            aria-hidden="true"
                        ></i>

                        <select
                            id="branch_id"
                            name="branch_id"
                            class="form-select
                                @error('branch_id')
                                    is-invalid
                                @enderror"
                            required
                        >
                            <option value="">
                                Pilih cabang
                            </option>

                            @foreach (
                                $branches as $branchOption
                            )
                                <option
                                    value="{{ $branchOption->id }}"
                                    @selected(
                                        (string) $selectedBranchId
                                        === (string)
                                            $branchOption->id
                                    )
                                >
                                    {{ $branchOption->code }}
                                    |
                                    {{ $branchOption->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('branch_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="session-form-field-note">
                            <i
                                class="bi bi-geo-alt"
                                aria-hidden="true"
                            ></i>

                            <span>
                                Hanya cabang aktif dengan
                                konfigurasi geofence lengkap yang
                                dapat dipilih.
                            </span>
                        </div>
                    </div>
                </section>

                <section class="session-form-field-card">
                    <div class="session-form-field has-icon">
                        <label
                            for="attendance_type"
                            class="form-label"
                        >
                            Jenis Presensi
                            <span class="session-form-required">
                                *
                            </span>
                        </label>

                        <i
                            class="bi bi-arrow-left-right
                                session-form-field-icon"
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

                        <div class="session-form-field-note">
                            <i
                                class="bi bi-shield-check"
                                aria-hidden="true"
                            ></i>

                            <span>
                                Satu cabang hanya dapat mempunyai
                                satu sesi aktif untuk jenis dan
                                tanggal yang sama.
                            </span>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="col-12">
            <div class="session-form-time-grid">
                <section
                    class="session-form-field-card
                        session-form-date-card"
                >
                    <div class="session-form-field has-icon">
                        <label
                            for="session_date"
                            class="form-label"
                        >
                            Tanggal Sesi
                            <span class="session-form-required">
                                *
                            </span>
                        </label>

                        <i
                            class="bi bi-calendar3
                                session-form-field-icon"
                            aria-hidden="true"
                        ></i>

                        <input
                            type="date"
                            id="session_date"
                            name="session_date"
                            value="{{ $selectedSessionDate }}"
                            class="form-control
                                @error('session_date')
                                    is-invalid
                                @enderror"
                            required
                        >

                        @error('session_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </section>

                <section class="session-form-field-card">
                    <div class="session-form-field has-icon">
                        <label
                            for="start_time"
                            class="form-label"
                        >
                            Waktu Mulai
                            <span class="session-form-required">
                                *
                            </span>
                        </label>

                        <i
                            class="bi bi-clock
                                session-form-field-icon"
                            aria-hidden="true"
                        ></i>

                        <input
                            type="time"
                            id="start_time"
                            name="start_time"
                            value="{{ $selectedStartTime }}"
                            class="form-control
                                @error('start_time')
                                    is-invalid
                                @enderror"
                            step="60"
                            required
                        >

                        @error('start_time')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="session-form-field-note">
                            <i
                                class="bi bi-qr-code"
                                aria-hidden="true"
                            ></i>

                            <span>
                                QR Code baru dapat digunakan
                                setelah waktu ini.
                            </span>
                        </div>
                    </div>
                </section>

                <section class="session-form-field-card">
                    <div class="session-form-field has-icon">
                        <label
                            for="end_time"
                            class="form-label"
                        >
                            Waktu Berakhir
                            <span class="session-form-required">
                                *
                            </span>
                        </label>

                        <i
                            class="bi bi-clock-history
                                session-form-field-icon"
                            aria-hidden="true"
                        ></i>

                        <input
                            type="time"
                            id="end_time"
                            name="end_time"
                            value="{{ $selectedEndTime }}"
                            class="form-control
                                @error('end_time')
                                    is-invalid
                                @enderror"
                            step="60"
                            required
                        >

                        @error('end_time')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="session-form-field-note">
                            <i
                                class="bi bi-hourglass-bottom"
                                aria-hidden="true"
                            ></i>

                            <span>
                                Setelah waktu ini, sesi akan
                                dianggap kedaluwarsa.
                            </span>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="col-12">
            <div
                id="time-range-warning"
                class="session-form-warning d-none mb-4"
                role="alert"
            >
                <span class="session-form-warning-icon">
                    <i
                        class="bi bi-exclamation-triangle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h3 class="session-form-warning-title">
                        Rentang waktu tidak valid
                    </h3>

                    <p class="session-form-warning-copy">
                        Waktu berakhir harus setelah waktu mulai sesi.
                    </p>
                </div>
            </div>

            <div
                class="session-form-rules"
                role="note"
            >
                <span class="session-form-rules-icon">
                    <i
                        class="bi bi-info-circle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h3 class="session-form-rules-title">
                        Ketentuan sesi presensi
                    </h3>

                    <ul class="session-form-rules-list">
                        <li>
                            QR Code hanya aktif dalam rentang
                            waktu sesi.
                        </li>

                        <li>
                            Token QR berubah setiap 30 detik.
                        </li>

                        <li>
                            Secret TOTP tidak ditampilkan pada
                            QR Code maupun browser.
                        </li>

                        <li>
                            Sesi dapat ditutup secara manual oleh
                            HRD atau admin operasional.
                        </li>

                        <li>
                            Sesi yang melewati waktu berakhir
                            akan berstatus kedaluwarsa.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {
                    const startTimeField =
                        document.getElementById(
                            'start_time'
                        );

                    const endTimeField =
                        document.getElementById(
                            'end_time'
                        );

                    const warningElement =
                        document.getElementById(
                            'time-range-warning'
                        );

                    if (
                        startTimeField === null
                        || endTimeField === null
                    ) {
                        return;
                    }

                    const validateTimeRange = function () {
                        const startTime =
                            startTimeField.value;

                        const endTime =
                            endTimeField.value;

                        const isInvalid =
                            startTime !== ''
                            && endTime !== ''
                            && endTime <= startTime;

                        endTimeField.setCustomValidity(
                            isInvalid
                                ? 'Waktu berakhir harus setelah waktu mulai sesi.'
                                : ''
                        );

                        if (warningElement !== null) {
                            warningElement.classList.toggle(
                                'd-none',
                                ! isInvalid
                            );
                        }
                    };

                    startTimeField.addEventListener(
                        'change',
                        validateTimeRange
                    );

                    endTimeField.addEventListener(
                        'change',
                        validateTimeRange
                    );

                    validateTimeRange();
                }
            );
        </script>
    @endpush
@endonce
