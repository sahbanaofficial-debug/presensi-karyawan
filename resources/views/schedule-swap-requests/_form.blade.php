@php
    $selectedRequesterEmployeeId = old(
        'requester_employee_id'
    );

    $selectedPartnerEmployeeId = old(
        'partner_employee_id'
    );

    $selectedRequesterDate = old(
        'requester_date',
        ''
    );

    $selectedPartnerDate = old(
        'partner_date',
        ''
    );

    $reasonValue = old(
        'reason',
        ''
    );
@endphp

@once
    @push('styles')
        <style>
            .schedule-swap-form {
                --swap-form-surface: var(--neutral-0);
                --swap-form-border: var(--neutral-200);
                --swap-form-muted: var(--neutral-600);
                --swap-form-soft: var(--brand-50);
            }

            .swap-form-section-heading {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: var(--space-4);
                padding-bottom: var(--space-4);
                border-bottom: 1px solid var(--swap-form-border);
            }

            .swap-form-section-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 1rem;
                font-weight: 800;
                letter-spacing: -0.01em;
            }

            .swap-form-section-copy {
                margin: var(--space-1) 0 0;
                color: var(--swap-form-muted);
                font-size: 0.75rem;
                line-height: 1.6;
            }

            .swap-form-employee-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: var(--space-4);
            }

            .swap-form-employee-card {
                overflow: hidden;
                min-width: 0;
                height: 100%;
                border: 1px solid var(--swap-form-border);
                border-radius: var(--radius-lg);
                background: var(--swap-form-surface);
            }

            .swap-form-employee-header {
                display: flex;
                align-items: center;
                gap: var(--space-3);
                padding: var(--space-4);
                border-bottom: 1px solid var(--swap-form-border);
                background: var(--neutral-25);
            }

            .swap-form-employee-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                color: var(--brand-700);
                background: var(--swap-form-soft);
                font-size: 1rem;
            }

            .swap-form-employee-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 0.875rem;
                font-weight: 800;
                line-height: 1.45;
            }

            .swap-form-employee-copy {
                margin: 0.125rem 0 0;
                color: var(--swap-form-muted);
                font-size: 0.6875rem;
                line-height: 1.5;
            }

            .swap-form-employee-body {
                display: grid;
                gap: var(--space-4);
                padding: var(--space-4);
            }

            .swap-form-field {
                position: relative;
            }

            .swap-form-field-icon {
                position: absolute;
                z-index: 2;
                top: 2.9rem;
                left: var(--space-3);
                color: var(--neutral-500);
                pointer-events: none;
            }

            .swap-form-field.has-icon .form-control,
            .swap-form-field.has-icon .form-select {
                padding-left: 2.75rem;
            }

            .swap-form-required {
                color: var(--danger-500);
            }

            .swap-form-reason-card {
                padding: var(--space-4);
                border: 1px solid var(--swap-form-border);
                border-radius: var(--radius-lg);
                background: var(--neutral-25);
            }

            .swap-form-reason-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: var(--space-3);
                margin-bottom: var(--space-3);
            }

            .swap-form-reason-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .swap-form-warning {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                padding: var(--space-4);
                border: 1px solid #efc9c3;
                border-radius: var(--radius-md);
                color: var(--danger-700);
                background: var(--danger-50);
            }

            .swap-form-warning-icon,
            .swap-form-rule-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                font-size: 1rem;
            }

            .swap-form-warning-icon {
                background: rgba(199, 70, 50, 0.09);
            }

            .swap-form-warning-title {
                margin: 0 0 var(--space-1);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .swap-form-warning-copy {
                margin: 0;
                font-size: 0.8125rem;
                line-height: 1.65;
            }

            .swap-form-rules {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                padding: var(--space-4);
                border: 1px solid #cde0eb;
                border-radius: var(--radius-md);
                color: var(--info-700);
                background: var(--info-50);
            }

            .swap-form-rule-icon {
                background: rgba(59, 126, 161, 0.09);
            }

            .swap-form-rules-title {
                margin: 0 0 var(--space-2);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .swap-form-rules-list {
                margin: 0;
                padding-left: 1.125rem;
                font-size: 0.75rem;
                line-height: 1.7;
            }

            @media (min-width: 768px) {
                .swap-form-employee-body,
                .swap-form-reason-card {
                    padding: var(--space-5);
                }
            }

            @media (max-width: 991.98px) {
                .swap-form-employee-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 575.98px) {
                .swap-form-section-heading,
                .swap-form-reason-header {
                    flex-direction: column;
                }
            }
        </style>
    @endpush
@endonce

<div class="schedule-swap-form">
    <div class="row g-4">
        <div class="col-12">
            <div class="swap-form-section-heading">
                <div>
                    <h2 class="swap-form-section-title">
                        Data Pertukaran Jadwal
                    </h2>

                    <p class="swap-form-section-copy">
                        Pilih dua karyawan beserta tanggal jadwal
                        yang akan dipertukarkan.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Dua karyawan
                </span>
            </div>
        </div>

        <div class="col-12">
            <div class="swap-form-employee-grid">
                <section
                    class="swap-form-employee-card"
                    aria-labelledby="requester-section-title"
                >
                    <div class="swap-form-employee-header">
                        <span class="swap-form-employee-icon">
                            <i
                                class="bi bi-person"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3
                                id="requester-section-title"
                                class="swap-form-employee-title"
                            >
                                Karyawan Pengaju
                            </h3>

                            <p class="swap-form-employee-copy">
                                Karyawan yang mengajukan
                                pertukaran jadwal.
                            </p>
                        </div>
                    </div>

                    <div class="swap-form-employee-body">
                        <div class="swap-form-field has-icon">
                            <label
                                for="requester_employee_id"
                                class="form-label"
                            >
                                Karyawan Pengaju
                                <span class="swap-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-person-vcard
                                    swap-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="requester_employee_id"
                                name="requester_employee_id"
                                class="form-select
                                    @error('requester_employee_id')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option value="">
                                    Pilih karyawan pengaju
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
                                                $selectedRequesterEmployeeId
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
                                                    ->code
                                            }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('requester_employee_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="swap-form-field has-icon">
                            <label
                                for="requester_date"
                                class="form-label"
                            >
                                Tanggal Jadwal Pengaju
                                <span class="swap-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-calendar3
                                    swap-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="date"
                                id="requester_date"
                                name="requester_date"
                                value="{{ $selectedRequesterDate }}"
                                class="form-control
                                    @error('requester_date')
                                        is-invalid
                                    @enderror"
                                required
                            >

                            @error('requester_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Karyawan pengaju harus sudah
                                memiliki jadwal pada tanggal ini.
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="swap-form-employee-card"
                    aria-labelledby="partner-section-title"
                >
                    <div class="swap-form-employee-header">
                        <span class="swap-form-employee-icon">
                            <i
                                class="bi bi-person-check"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div>
                            <h3
                                id="partner-section-title"
                                class="swap-form-employee-title"
                            >
                                Karyawan Pasangan
                            </h3>

                            <p class="swap-form-employee-copy">
                                Karyawan yang jadwalnya akan
                                dipertukarkan dengan pengaju.
                            </p>
                        </div>
                    </div>

                    <div class="swap-form-employee-body">
                        <div class="swap-form-field has-icon">
                            <label
                                for="partner_employee_id"
                                class="form-label"
                            >
                                Karyawan Pasangan
                                <span class="swap-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-person-vcard
                                    swap-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="partner_employee_id"
                                name="partner_employee_id"
                                class="form-select
                                    @error('partner_employee_id')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option value="">
                                    Pilih karyawan pasangan
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
                                                $selectedPartnerEmployeeId
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
                                                    ->code
                                            }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('partner_employee_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Karyawan pasangan harus berbeda
                                dari karyawan pengaju.
                            </div>
                        </div>

                        <div class="swap-form-field has-icon">
                            <label
                                for="partner_date"
                                class="form-label"
                            >
                                Tanggal Jadwal Pasangan
                                <span class="swap-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-calendar3
                                    swap-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="date"
                                id="partner_date"
                                name="partner_date"
                                value="{{ $selectedPartnerDate }}"
                                class="form-control
                                    @error('partner_date')
                                        is-invalid
                                    @enderror"
                                required
                            >

                            @error('partner_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Karyawan pasangan harus sudah
                                memiliki jadwal pada tanggal ini.
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="col-12">
            <section class="swap-form-reason-card">
                <div class="swap-form-reason-header">
                    <div>
                        <h3 class="swap-form-reason-title">
                            Alasan Pertukaran
                        </h3>

                        <p class="swap-form-section-copy">
                            Jelaskan kebutuhan pertukaran jadwal
                            secara spesifik.
                        </p>
                    </div>

                    <span class="badge text-bg-light border">
                        Wajib diisi
                    </span>
                </div>

                <label
                    for="reason"
                    class="form-label"
                >
                    Alasan Pertukaran
                    <span class="swap-form-required">
                        *
                    </span>
                </label>

                <textarea
                    id="reason"
                    name="reason"
                    rows="5"
                    class="form-control
                        @error('reason')
                            is-invalid
                        @enderror"
                    placeholder="Tuliskan alasan pengajuan pertukaran jadwal"
                    required
                >{{ $reasonValue }}</textarea>

                @error('reason')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

                <div class="form-text">
                    Alasan harus menjelaskan kebutuhan pertukaran
                    jadwal secara jelas.
                </div>
            </section>
        </div>

        <div class="col-12">
            <div
                id="same-employee-warning"
                class="swap-form-warning d-none mb-4"
                role="alert"
            >
                <span class="swap-form-warning-icon">
                    <i
                        class="bi bi-exclamation-triangle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h3 class="swap-form-warning-title">
                        Karyawan tidak boleh sama
                    </h3>

                    <p class="swap-form-warning-copy">
                        Karyawan pengaju dan karyawan pasangan
                        tidak boleh sama.
                    </p>
                </div>
            </div>

            <div
                class="swap-form-rules"
                role="note"
            >
                <span class="swap-form-rule-icon">
                    <i
                        class="bi bi-info-circle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h3 class="swap-form-rules-title">
                        Ketentuan pengajuan
                    </h3>

                    <ul class="swap-form-rules-list">
                        <li>
                            Kedua karyawan harus masih berstatus
                            aktif.
                        </li>

                        <li>
                            Kedua karyawan harus memiliki jadwal
                            pada tanggal yang dipilih.
                        </li>

                        <li>
                            Permohonan baru disimpan dengan status
                            menunggu keputusan.
                        </li>

                        <li>
                            Persetujuan atau penolakan hanya dapat
                            dilakukan oleh HRD.
                        </li>

                        <li>
                            Jadwal yang sudah memiliki data
                            presensi tidak dapat dipertukarkan.
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
                    const requesterField =
                        document.getElementById(
                            'requester_employee_id'
                        );

                    const partnerField =
                        document.getElementById(
                            'partner_employee_id'
                        );

                    const warningElement =
                        document.getElementById(
                            'same-employee-warning'
                        );

                    if (
                        requesterField === null
                        || partnerField === null
                    ) {
                        return;
                    }

                    const synchronizeEmployeeOptions =
                        function () {
                            const requesterValue =
                                requesterField.value;

                            const partnerValue =
                                partnerField.value;

                            Array.from(
                                requesterField.options
                            ).forEach(
                                function (option) {
                                    if (
                                        option.value === ''
                                    ) {
                                        option.disabled = false;

                                        return;
                                    }

                                    option.disabled =
                                        partnerValue !== ''
                                        && option.value
                                            === partnerValue;
                                }
                            );

                            Array.from(
                                partnerField.options
                            ).forEach(
                                function (option) {
                                    if (
                                        option.value === ''
                                    ) {
                                        option.disabled = false;

                                        return;
                                    }

                                    option.disabled =
                                        requesterValue !== ''
                                        && option.value
                                            === requesterValue;
                                }
                            );

                            const hasSameEmployee =
                                requesterValue !== ''
                                && partnerValue !== ''
                                && requesterValue
                                    === partnerValue;

                            if (warningElement !== null) {
                                warningElement.classList.toggle(
                                    'd-none',
                                    ! hasSameEmployee
                                );
                            }

                            partnerField.setCustomValidity(
                                hasSameEmployee
                                    ? 'Karyawan pasangan harus berbeda dari karyawan pengaju.'
                                    : ''
                            );
                        };

                    requesterField.addEventListener(
                        'change',
                        synchronizeEmployeeOptions
                    );

                    partnerField.addEventListener(
                        'change',
                        synchronizeEmployeeOptions
                    );

                    synchronizeEmployeeOptions();
                }
            );
        </script>
    @endpush
@endonce
