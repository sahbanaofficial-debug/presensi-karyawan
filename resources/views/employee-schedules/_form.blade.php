@php
    $employeeScheduleModel = $employeeSchedule ?? null;

    $formatDateInput = static function ($value): string {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    };

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

    $selectedEmployeeId = old(
        'employee_id',
        $employeeScheduleModel?->employee_id
    );

    $selectedWorkScheduleId = old(
        'work_schedule_id',
        $employeeScheduleModel?->work_schedule_id
    );

    $selectedScheduleStatus = old(
        'schedule_status',
        $employeeScheduleModel?->schedule_status ?? 'work'
    );

    $selectedScheduleDate = old(
        'schedule_date',
        $formatDateInput(
            $employeeScheduleModel?->schedule_date
        )
    );

    $notesValue = old(
        'notes',
        $employeeScheduleModel?->notes
    );
@endphp

@once
    @push('styles')
        <style>
            .employee-schedule-form {
                --employee-schedule-surface: var(--neutral-0);
                --employee-schedule-border: var(--neutral-200);
                --employee-schedule-muted: var(--neutral-600);
                --employee-schedule-soft: var(--brand-50);
            }

            .employee-schedule-error-summary {
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

            .employee-schedule-error-icon {
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

            .employee-schedule-error-title {
                margin: 0 0 var(--space-2);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .employee-schedule-error-list {
                margin: 0;
                padding-left: 1.125rem;
                font-size: 0.75rem;
                line-height: 1.7;
            }

            .employee-schedule-section {
                overflow: hidden;
                border: 1px solid var(--employee-schedule-border);
                border-radius: var(--radius-lg);
                background: var(--employee-schedule-surface);
            }

            .employee-schedule-section + .employee-schedule-section {
                margin-top: var(--space-4);
            }

            .employee-schedule-section-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: var(--space-4);
                padding: var(--space-4);
                border-bottom: 1px solid var(--employee-schedule-border);
                background: var(--neutral-25);
            }

            .employee-schedule-section-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 0.9375rem;
                font-weight: 800;
                letter-spacing: -0.01em;
            }

            .employee-schedule-section-copy {
                margin: var(--space-1) 0 0;
                color: var(--employee-schedule-muted);
                font-size: 0.75rem;
                line-height: 1.6;
            }

            .employee-schedule-section-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                color: var(--brand-700);
                background: var(--employee-schedule-soft);
                font-size: 1rem;
            }

            .employee-schedule-section-body {
                padding: var(--space-4);
            }

            .employee-schedule-field-card {
                height: 100%;
                padding: var(--space-4);
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-md);
                background: var(--neutral-25);
            }

            .employee-schedule-field {
                position: relative;
            }

            .employee-schedule-field-icon {
                position: absolute;
                z-index: 2;
                top: 2.875rem;
                left: var(--space-3);
                color: var(--neutral-500);
                pointer-events: none;
            }

            .employee-schedule-field.has-icon .form-control,
            .employee-schedule-field.has-icon .form-select {
                padding-left: 2.75rem;
            }

            .employee-schedule-required {
                color: var(--danger-500);
            }

            .employee-schedule-help {
                display: flex;
                align-items: flex-start;
                gap: var(--space-2);
                margin-top: var(--space-2);
                color: var(--employee-schedule-muted);
                font-size: 0.6875rem;
                line-height: 1.55;
            }

            .employee-schedule-warning {
                display: flex;
                align-items: flex-start;
                gap: var(--space-2);
                margin-top: var(--space-2);
                color: var(--danger-600);
                font-size: 0.6875rem;
                font-weight: 700;
                line-height: 1.55;
            }

            .employee-schedule-rules {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                padding: var(--space-4);
                border: 1px solid #cde0eb;
                border-radius: var(--radius-md);
                color: var(--info-700);
                background: var(--info-50);
            }

            .employee-schedule-rules-icon {
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

            .employee-schedule-rules-title {
                margin: 0 0 var(--space-2);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .employee-schedule-rules-list {
                margin: 0;
                padding-left: 1.125rem;
                font-size: 0.75rem;
                line-height: 1.7;
            }

            @media (min-width: 768px) {
                .employee-schedule-section-header,
                .employee-schedule-section-body {
                    padding: var(--space-5);
                }
            }

            @media (max-width: 575.98px) {
                .employee-schedule-section-header {
                    flex-direction: column-reverse;
                }
            }
        </style>
    @endpush
@endonce

<div class="employee-schedule-form">
    @if ($errors->any())
        <div
            class="employee-schedule-error-summary"
            role="alert"
        >
            <span class="employee-schedule-error-icon">
                <i
                    class="bi bi-exclamation-triangle"
                    aria-hidden="true"
                ></i>
            </span>

            <div>
                <h2 class="employee-schedule-error-title">
                    Data jadwal belum dapat disimpan.
                </h2>

                <ul class="employee-schedule-error-list">
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
        class="employee-schedule-section"
        aria-labelledby="daily-schedule-information-heading"
    >
        <div class="employee-schedule-section-header">
            <div>
                <h2
                    id="daily-schedule-information-heading"
                    class="employee-schedule-section-title"
                >
                    Informasi Jadwal Harian
                </h2>

                <p class="employee-schedule-section-copy">
                    Tentukan karyawan, tanggal, dan status jadwal.
                </p>
            </div>

            <span class="employee-schedule-section-icon">
                <i
                    class="bi bi-calendar-week"
                    aria-hidden="true"
                ></i>
            </span>
        </div>

        <div class="employee-schedule-section-body">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="employee-schedule-field-card">
                        <div class="employee-schedule-field has-icon">
                            <label
                                for="employee_id"
                                class="form-label"
                            >
                                Karyawan
                                <span class="employee-schedule-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-person
                                    employee-schedule-field-icon"
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
                                        value="{{ $employeeOption->id }}"
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
                                        {{ $employeeOption->full_name }}

                                        @if (
                                            $employeeOption->branch
                                            !== null
                                        )
                                            |
                                            {{
                                                $employeeOption
                                                    ->branch
                                                    ->code
                                            }}
                                        @endif

                                        @if (
                                            isset(
                                                $employeeOption
                                                    ->employment_status
                                            )
                                            && $employeeOption
                                                ->employment_status
                                                === 'inactive'
                                        )
                                            (Karyawan tidak aktif)
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('employee_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            @if ($employees->isEmpty())
                                <div
                                    class="employee-schedule-warning"
                                >
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Tidak terdapat karyawan aktif
                                        yang dapat dijadwalkan.
                                    </span>
                                </div>
                            @else
                                <div class="employee-schedule-help">
                                    <i
                                        class="bi bi-shield-check"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Satu karyawan hanya dapat
                                        memiliki satu status jadwal
                                        pada tanggal yang sama.
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="employee-schedule-field-card">
                        <div class="employee-schedule-field has-icon">
                            <label
                                for="schedule_date"
                                class="form-label"
                            >
                                Tanggal Jadwal
                                <span class="employee-schedule-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-calendar3
                                    employee-schedule-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="date"
                                id="schedule_date"
                                name="schedule_date"
                                value="{{ $selectedScheduleDate }}"
                                class="form-control
                                    @error('schedule_date')
                                        is-invalid
                                    @enderror"
                                required
                            >

                            @error('schedule_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="employee-schedule-field-card">
                        <div class="employee-schedule-field has-icon">
                            <label
                                for="schedule_status"
                                class="form-label"
                            >
                                Status Jadwal
                                <span class="employee-schedule-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-toggle-on
                                    employee-schedule-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="schedule_status"
                                name="schedule_status"
                                class="form-select
                                    @error('schedule_status')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option
                                    value="work"
                                    @selected(
                                        $selectedScheduleStatus
                                        === 'work'
                                    )
                                >
                                    Kerja
                                </option>

                                <option
                                    value="off"
                                    @selected(
                                        $selectedScheduleStatus
                                        === 'off'
                                    )
                                >
                                    Libur
                                </option>

                                <option
                                    value="permit"
                                    @selected(
                                        $selectedScheduleStatus
                                        === 'permit'
                                    )
                                >
                                    Izin
                                </option>

                                <option
                                    value="sick"
                                    @selected(
                                        $selectedScheduleStatus
                                        === 'sick'
                                    )
                                >
                                    Sakit
                                </option>
                            </select>

                            @error('schedule_status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="employee-schedule-field-card">
                        <div class="employee-schedule-field has-icon">
                            <label
                                for="work_schedule_id"
                                class="form-label"
                            >
                                Pola Jadwal Kerja
                                <span
                                    id="work-schedule-required-mark"
                                    class="employee-schedule-required"
                                >
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-clock-history
                                    employee-schedule-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="work_schedule_id"
                                name="work_schedule_id"
                                class="form-select
                                    @error('work_schedule_id')
                                        is-invalid
                                    @enderror"
                            >
                                <option value="">
                                    Pilih pola jadwal
                                </option>

                                @foreach (
                                    $workSchedules
                                    as $workScheduleOption
                                )
                                    <option
                                        value="{{
                                            $workScheduleOption->id
                                        }}"
                                        @selected(
                                            (string)
                                                $selectedWorkScheduleId
                                            === (string)
                                                $workScheduleOption
                                                    ->id
                                        )
                                    >
                                        {{
                                            $workScheduleOption
                                                ->name
                                        }}
                                        |
                                        {{
                                            $formatTime(
                                                $workScheduleOption
                                                    ->check_in_time
                                            )
                                        }}
                                        sampai
                                        {{
                                            $formatTime(
                                                $workScheduleOption
                                                    ->check_out_time
                                            )
                                        }}
                                        WIB

                                        @if (
                                            isset(
                                                $workScheduleOption
                                                    ->status
                                            )
                                            && $workScheduleOption
                                                ->status
                                                === 'inactive'
                                        )
                                            (Tidak aktif)
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('work_schedule_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div
                                id="work-schedule-help"
                                class="employee-schedule-help"
                            >
                                <i
                                    class="bi bi-info-circle"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Pola jadwal wajib dipilih untuk
                                    status kerja.
                                </span>
                            </div>

                            @if ($workSchedules->isEmpty())
                                <div
                                    class="employee-schedule-warning"
                                >
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Tidak terdapat pola jadwal
                                        aktif yang dapat dipilih.
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="employee-schedule-field-card">
                        <div class="employee-schedule-field">
                            <label
                                for="notes"
                                class="form-label"
                            >
                                Keterangan
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="4"
                                class="form-control
                                    @error('notes')
                                        is-invalid
                                    @enderror"
                                placeholder="Contoh: Libur bergilir atau keterangan izin"
                            >{{ $notesValue }}</textarea>

                            @error('notes')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="employee-schedule-help">
                                <i
                                    class="bi bi-card-text"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Keterangan bersifat opsional.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="employee-schedule-section"
        aria-labelledby="schedule-status-rules-heading"
    >
        <div class="employee-schedule-section-header">
            <div>
                <h2
                    id="schedule-status-rules-heading"
                    class="employee-schedule-section-title"
                >
                    Ketentuan Status Jadwal
                </h2>

                <p class="employee-schedule-section-copy">
                    Aturan penggunaan pola jadwal untuk setiap
                    status harian.
                </p>
            </div>

            <span class="employee-schedule-section-icon">
                <i
                    class="bi bi-info-circle"
                    aria-hidden="true"
                ></i>
            </span>
        </div>

        <div class="employee-schedule-section-body">
            <div
                class="employee-schedule-rules"
                role="note"
            >
                <span class="employee-schedule-rules-icon">
                    <i
                        class="bi bi-shield-check"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h3 class="employee-schedule-rules-title">
                        Ketentuan status jadwal
                    </h3>

                    <ul class="employee-schedule-rules-list">
                        <li>
                            Status kerja wajib menggunakan pola
                            jadwal.
                        </li>

                        <li>
                            Status libur, izin, dan sakit tidak
                            menggunakan pola jadwal kerja.
                        </li>

                        <li>
                            Identitas HRD yang menetapkan jadwal
                            akan dicatat otomatis oleh sistem.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {
                    const statusField =
                        document.getElementById(
                            'schedule_status'
                        );

                    const workScheduleField =
                        document.getElementById(
                            'work_schedule_id'
                        );

                    const requiredMark =
                        document.getElementById(
                            'work-schedule-required-mark'
                        );

                    const helpText =
                        document.getElementById(
                            'work-schedule-help'
                        );

                    if (
                        statusField === null
                        || workScheduleField === null
                    ) {
                        return;
                    }

                    const updateWorkScheduleField =
                        function () {
                            const isWorkStatus =
                                statusField.value === 'work';

                            workScheduleField.disabled =
                                ! isWorkStatus;

                            workScheduleField.required =
                                isWorkStatus;

                            if (requiredMark !== null) {
                                requiredMark.classList.toggle(
                                    'd-none',
                                    ! isWorkStatus
                                );
                            }

                            if (helpText !== null) {
                                const helpTextValue =
                                    isWorkStatus
                                        ? 'Pola jadwal wajib dipilih untuk status kerja.'
                                        : 'Pola jadwal tidak digunakan untuk status ini.';

                                const helpTextContent =
                                    helpText.querySelector(
                                        'span'
                                    );

                                if (
                                    helpTextContent !== null
                                ) {
                                    helpTextContent.textContent =
                                        helpTextValue;
                                } else {
                                    helpText.textContent =
                                        helpTextValue;
                                }
                            }

                            if (! isWorkStatus) {
                                workScheduleField.value = '';
                            }
                        };

                    statusField.addEventListener(
                        'change',
                        updateWorkScheduleField
                    );

                    updateWorkScheduleField();
                }
            );
        </script>
    @endpush
@endonce
