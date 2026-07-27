@php
    $workScheduleModel = $workSchedule ?? null;

    $formatTime = static function ($value): string {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        $time = (string) $value;

        return strlen($time) >= 5
            ? substr($time, 0, 5)
            : $time;
    };

    $checkInTime = old(
        'check_in_time',
        $formatTime(
            $workScheduleModel?->check_in_time
        )
    );

    $checkOutTime = old(
        'check_out_time',
        $formatTime(
            $workScheduleModel?->check_out_time
        )
    );

    $checkInOpenMinutes = old(
        'check_in_open_minutes',
        $workScheduleModel?->check_in_open_minutes
            ?? 30
    );

    $lateToleranceMinutes = old(
        'late_tolerance_minutes',
        $workScheduleModel?->late_tolerance_minutes
            ?? 5
    );

    $checkOutLimitMinutes = old(
        'check_out_limit_minutes',
        $workScheduleModel?->check_out_limit_minutes
            ?? 60
    );

    $selectedStatus = old(
        'status',
        $workScheduleModel?->status
            ?? 'active'
    );
@endphp

@once
    @push('styles')
        <style>
            .work-schedule-form {
                --work-schedule-surface: var(--neutral-0);
                --work-schedule-border: var(--neutral-200);
                --work-schedule-muted: var(--neutral-600);
                --work-schedule-soft: var(--brand-50);
            }

            .work-schedule-error-summary {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                margin-bottom: var(--space-4);
                padding: var(--space-4);
                border: 1px solid #efc9c3;
                border-radius: var(--radius-md);
                color: var(--danger-700);
                background: var(--danger-50);
            }

            .work-schedule-error-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                background: rgba(199, 70, 50, 0.09);
                font-size: 1rem;
            }

            .work-schedule-error-title {
                margin: 0 0 var(--space-2);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .work-schedule-error-list {
                margin: 0;
                padding-left: 1.125rem;
                font-size: 0.75rem;
                line-height: 1.7;
            }

            .work-schedule-section {
                overflow: hidden;
                border: 1px solid var(--work-schedule-border);
                border-radius: var(--radius-lg);
                background: var(--work-schedule-surface);
            }

            .work-schedule-section
            + .work-schedule-section {
                margin-top: var(--space-4);
            }

            .work-schedule-section-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: var(--space-4);
                padding: var(--space-4);
                border-bottom: 1px solid var(--work-schedule-border);
                background: var(--neutral-25);
            }

            .work-schedule-section-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 0.9375rem;
                font-weight: 800;
                letter-spacing: -0.01em;
            }

            .work-schedule-section-copy {
                margin: var(--space-1) 0 0;
                color: var(--work-schedule-muted);
                font-size: 0.75rem;
                line-height: 1.6;
            }

            .work-schedule-section-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                color: var(--brand-700);
                background: var(--work-schedule-soft);
                font-size: 1rem;
            }

            .work-schedule-section-body {
                padding: var(--space-4);
            }

            .work-schedule-field-card {
                height: 100%;
                padding: var(--space-4);
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-md);
                background: var(--neutral-25);
            }

            .work-schedule-field {
                position: relative;
            }

            .work-schedule-field-icon {
                position: absolute;
                z-index: 2;
                top: 2.875rem;
                left: var(--space-3);
                color: var(--neutral-500);
                pointer-events: none;
            }

            .work-schedule-field.has-icon .form-control,
            .work-schedule-field.has-icon .form-select {
                padding-left: 2.75rem;
            }

            .work-schedule-required {
                color: var(--danger-500);
            }

            .work-schedule-help {
                display: flex;
                align-items: flex-start;
                gap: var(--space-2);
                margin-top: var(--space-2);
                color: var(--work-schedule-muted);
                font-size: 0.6875rem;
                line-height: 1.55;
            }

            .work-schedule-rule-card {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                padding: var(--space-4);
                border: 1px solid #cde0eb;
                border-radius: var(--radius-md);
                color: var(--info-700);
                background: var(--info-50);
            }

            .work-schedule-rule-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                background: rgba(59, 126, 161, 0.09);
                font-size: 1rem;
            }

            .work-schedule-rule-title {
                margin: 0 0 var(--space-1);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .work-schedule-rule-copy {
                margin: 0;
                font-size: 0.75rem;
                line-height: 1.7;
            }

            @media (min-width: 768px) {
                .work-schedule-section-header,
                .work-schedule-section-body {
                    padding: var(--space-5);
                }
            }

            @media (max-width: 575.98px) {
                .work-schedule-section-header {
                    flex-direction: column-reverse;
                }
            }
        </style>
    @endpush
@endonce

<div class="work-schedule-form">
    @if ($errors->any())
        <div
            class="work-schedule-error-summary"
            role="alert"
        >
            <span class="work-schedule-error-icon">
                <i
                    class="bi bi-exclamation-triangle"
                    aria-hidden="true"
                ></i>
            </span>

            <div>
                <h2 class="work-schedule-error-title">
                    Data belum dapat disimpan.
                </h2>

                <ul class="work-schedule-error-list">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section
        class="work-schedule-section"
        aria-labelledby="work-schedule-information-heading"
    >
        <div class="work-schedule-section-header">
            <div>
                <h2
                    id="work-schedule-information-heading"
                    class="work-schedule-section-title"
                >
                    Informasi Pola Jadwal
                </h2>

                <p class="work-schedule-section-copy">
                    Tentukan nama, jam masuk, jam pulang,
                    dan status pola jadwal kerja.
                </p>
            </div>

            <span class="work-schedule-section-icon">
                <i
                    class="bi bi-calendar-week"
                    aria-hidden="true"
                ></i>
            </span>
        </div>

        <div class="work-schedule-section-body">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="work-schedule-field-card">
                        <div class="work-schedule-field has-icon">
                            <label
                                for="name"
                                class="form-label"
                            >
                                Nama Jadwal
                                <span class="work-schedule-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-card-heading
                                    work-schedule-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old(
                                    'name',
                                    $workScheduleModel?->name
                                ) }}"
                                class="form-control
                                    @error('name')
                                        is-invalid
                                    @enderror"
                                maxlength="100"
                                autocomplete="off"
                                placeholder="Contoh: Jadwal penuh"
                                required
                            >

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="work-schedule-field-card">
                        <div class="work-schedule-field has-icon">
                            <label
                                for="status"
                                class="form-label"
                            >
                                Status
                                <span class="work-schedule-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-toggle-on
                                    work-schedule-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="status"
                                name="status"
                                class="form-select
                                    @error('status')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option
                                    value="active"
                                    @selected(
                                        $selectedStatus
                                        === 'active'
                                    )
                                >
                                    Aktif
                                </option>

                                <option
                                    value="inactive"
                                    @selected(
                                        $selectedStatus
                                        === 'inactive'
                                    )
                                >
                                    Tidak aktif
                                </option>
                            </select>

                            @error('status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="work-schedule-field-card">
                        <div class="work-schedule-field has-icon">
                            <label
                                for="check_in_time"
                                class="form-label"
                            >
                                Jam Masuk
                                <span class="work-schedule-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-box-arrow-in-right
                                    work-schedule-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="time"
                                id="check_in_time"
                                name="check_in_time"
                                value="{{ $checkInTime }}"
                                class="form-control
                                    @error('check_in_time')
                                        is-invalid
                                    @enderror"
                                step="60"
                                required
                            >

                            @error('check_in_time')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="work-schedule-help">
                                <i
                                    class="bi bi-clock"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Waktu mulai jadwal kerja
                                    dalam WIB.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="work-schedule-field-card">
                        <div class="work-schedule-field has-icon">
                            <label
                                for="check_out_time"
                                class="form-label"
                            >
                                Jam Pulang
                                <span class="work-schedule-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-box-arrow-right
                                    work-schedule-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="time"
                                id="check_out_time"
                                name="check_out_time"
                                value="{{ $checkOutTime }}"
                                class="form-control
                                    @error('check_out_time')
                                        is-invalid
                                    @enderror"
                                step="60"
                                required
                            >

                            @error('check_out_time')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="work-schedule-help">
                                <i
                                    class="bi bi-info-circle"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Jam pulang harus setelah
                                    jam masuk.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="work-schedule-section"
        aria-labelledby="attendance-time-rules-heading"
    >
        <div class="work-schedule-section-header">
            <div>
                <h2
                    id="attendance-time-rules-heading"
                    class="work-schedule-section-title"
                >
                    Aturan Waktu Presensi
                </h2>

                <p class="work-schedule-section-copy">
                    Nilai waktu pada bagian ini menggunakan
                    satuan menit.
                </p>
            </div>

            <span class="work-schedule-section-icon">
                <i
                    class="bi bi-stopwatch"
                    aria-hidden="true"
                ></i>
            </span>
        </div>

        <div class="work-schedule-section-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="work-schedule-field-card">
                        <div class="work-schedule-field">
                            <label
                                for="check_in_open_minutes"
                                class="form-label"
                            >
                                Pembukaan Presensi Masuk
                                <span class="work-schedule-required">
                                    *
                                </span>
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="check_in_open_minutes"
                                    name="check_in_open_minutes"
                                    value="{{ $checkInOpenMinutes }}"
                                    class="form-control
                                        @error(
                                            'check_in_open_minutes'
                                        )
                                            is-invalid
                                        @enderror"
                                    min="0"
                                    max="1440"
                                    step="1"
                                    required
                                >

                                <span class="input-group-text">
                                    menit
                                </span>

                                @error('check_in_open_minutes')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="work-schedule-help">
                                <i
                                    class="bi bi-door-open"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Presensi masuk dibuka sejumlah
                                    menit sebelum jam masuk.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="work-schedule-field-card">
                        <div class="work-schedule-field">
                            <label
                                for="late_tolerance_minutes"
                                class="form-label"
                            >
                                Toleransi Keterlambatan
                                <span class="work-schedule-required">
                                    *
                                </span>
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="late_tolerance_minutes"
                                    name="late_tolerance_minutes"
                                    value="{{
                                        $lateToleranceMinutes
                                    }}"
                                    class="form-control
                                        @error(
                                            'late_tolerance_minutes'
                                        )
                                            is-invalid
                                        @enderror"
                                    min="0"
                                    max="1440"
                                    step="1"
                                    required
                                >

                                <span class="input-group-text">
                                    menit
                                </span>

                                @error('late_tolerance_minutes')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="work-schedule-help">
                                <i
                                    class="bi bi-alarm"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Batas toleransi setelah
                                    jam masuk.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="work-schedule-field-card">
                        <div class="work-schedule-field">
                            <label
                                for="check_out_limit_minutes"
                                class="form-label"
                            >
                                Batas Akhir Presensi Pulang
                                <span class="work-schedule-required">
                                    *
                                </span>
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="check_out_limit_minutes"
                                    name="check_out_limit_minutes"
                                    value="{{
                                        $checkOutLimitMinutes
                                    }}"
                                    class="form-control
                                        @error(
                                            'check_out_limit_minutes'
                                        )
                                            is-invalid
                                        @enderror"
                                    min="0"
                                    max="1440"
                                    step="1"
                                    required
                                >

                                <span class="input-group-text">
                                    menit
                                </span>

                                @error('check_out_limit_minutes')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="work-schedule-help">
                                <i
                                    class="bi bi-hourglass-bottom"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Batas waktu setelah jam pulang
                                    untuk melakukan presensi pulang.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div
                        class="work-schedule-rule-card"
                        role="note"
                    >
                        <span class="work-schedule-rule-icon">
                            <i
                                class="bi bi-info-circle"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3 class="work-schedule-rule-title">
                                Ketentuan waktu presensi
                            </h3>

                            <p class="work-schedule-rule-copy">
                                Presensi masuk dibuka sebelum jam
                                kerja. Presensi lebih dari batas
                                toleransi tetap diterima dengan
                                status terlambat. Presensi pulang
                                hanya dapat dilakukan mulai dari
                                jam pulang sampai batas akhir yang
                                ditentukan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
