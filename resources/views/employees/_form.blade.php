@php
    $employeeModel = $employee ?? null;
    $userModel = $employeeModel?->user;

    $selectedBranchId = old(
        'branch_id',
        $employeeModel?->branch_id
    );

    $selectedAccountStatus = old(
        'account_status',
        $userModel?->status ?? 'active'
    );

    $selectedEmploymentStatus = old(
        'employment_status',
        $employeeModel?->employment_status ?? 'active'
    );

    $isEditing = $employeeModel !== null;
@endphp

@once
    @push('styles')
        <style>
            .employee-form {
                --employee-form-surface: var(--neutral-0);
                --employee-form-border: var(--neutral-200);
                --employee-form-muted: var(--neutral-600);
                --employee-form-soft: var(--brand-50);
            }

            .employee-form-error-summary {
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

            .employee-form-error-icon {
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

            .employee-form-error-title {
                margin: 0 0 var(--space-2);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .employee-form-error-list {
                margin: 0;
                padding-left: 1.125rem;
                font-size: 0.75rem;
                line-height: 1.7;
            }

            .employee-form-section {
                overflow: hidden;
                border: 1px solid var(--employee-form-border);
                border-radius: var(--radius-lg);
                background: var(--employee-form-surface);
            }

            .employee-form-section + .employee-form-section {
                margin-top: var(--space-4);
            }

            .employee-form-section-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: var(--space-4);
                padding: var(--space-4);
                border-bottom: 1px solid var(--employee-form-border);
                background: var(--neutral-25);
            }

            .employee-form-section-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 0.9375rem;
                font-weight: 800;
                letter-spacing: -0.01em;
            }

            .employee-form-section-copy {
                margin: var(--space-1) 0 0;
                color: var(--employee-form-muted);
                font-size: 0.75rem;
                line-height: 1.6;
            }

            .employee-form-section-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                color: var(--brand-700);
                background: var(--employee-form-soft);
                font-size: 1rem;
            }

            .employee-form-section-body {
                padding: var(--space-4);
            }

            .employee-form-field-card {
                height: 100%;
                padding: var(--space-4);
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-md);
                background: var(--neutral-25);
            }

            .employee-form-field {
                position: relative;
            }

            .employee-form-field-icon {
                position: absolute;
                z-index: 2;
                top: 2.875rem;
                left: var(--space-3);
                color: var(--neutral-500);
                pointer-events: none;
            }

            .employee-form-field.has-icon .form-control,
            .employee-form-field.has-icon .form-select {
                padding-left: 2.75rem;
            }

            .employee-form-required {
                color: var(--danger-500);
            }

            .employee-form-help {
                display: flex;
                align-items: flex-start;
                gap: var(--space-2);
                margin-top: var(--space-2);
                color: var(--employee-form-muted);
                font-size: 0.6875rem;
                line-height: 1.55;
            }

            .employee-form-branch-warning {
                display: flex;
                align-items: flex-start;
                gap: var(--space-2);
                margin-top: var(--space-2);
                color: var(--danger-600);
                font-size: 0.6875rem;
                font-weight: 700;
                line-height: 1.55;
            }

            .employee-form-account-note {
                display: flex;
                align-items: flex-start;
                gap: var(--space-3);
                margin-top: var(--space-4);
                padding: var(--space-4);
                border: 1px solid #cde0eb;
                border-radius: var(--radius-md);
                color: var(--info-700);
                background: var(--info-50);
            }

            .employee-form-note-icon {
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

            .employee-form-note-title {
                margin: 0 0 var(--space-1);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .employee-form-note-copy {
                margin: 0;
                font-size: 0.75rem;
                line-height: 1.65;
            }

            @media (min-width: 768px) {
                .employee-form-section-header,
                .employee-form-section-body {
                    padding: var(--space-5);
                }
            }

            @media (max-width: 575.98px) {
                .employee-form-section-header {
                    flex-direction: column-reverse;
                }
            }
        </style>
    @endpush
@endonce

<div class="employee-form">
    @if ($errors->any())
        <div
            class="employee-form-error-summary"
            role="alert"
        >
            <span class="employee-form-error-icon">
                <i
                    class="bi bi-exclamation-triangle"
                    aria-hidden="true"
                ></i>
            </span>

            <div>
                <h2 class="employee-form-error-title">
                    Data belum dapat disimpan.
                </h2>

                <ul class="employee-form-error-list">
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
        class="employee-form-section"
        aria-labelledby="employee-information-heading"
    >
        <div class="employee-form-section-header">
            <div>
                <h2
                    id="employee-information-heading"
                    class="employee-form-section-title"
                >
                    Informasi Karyawan
                </h2>

                <p class="employee-form-section-copy">
                    Masukkan identitas dan penempatan karyawan.
                </p>
            </div>

            <span class="employee-form-section-icon">
                <i
                    class="bi bi-person-vcard"
                    aria-hidden="true"
                ></i>
            </span>
        </div>

        <div class="employee-form-section-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="employee_number"
                                class="form-label"
                            >
                                Nomor Karyawan
                                <span class="employee-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-hash
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="text"
                                id="employee_number"
                                name="employee_number"
                                value="{{ old(
                                    'employee_number',
                                    $employeeModel
                                        ?->employee_number
                                ) }}"
                                class="form-control
                                    @error('employee_number')
                                        is-invalid
                                    @enderror"
                                maxlength="50"
                                autocomplete="off"
                                required
                            >

                            @error('employee_number')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="employee-form-help">
                                <i
                                    class="bi bi-info-circle"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Nomor karyawan akan disimpan
                                    dalam huruf kapital.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="full_name"
                                class="form-label"
                            >
                                Nama Lengkap
                                <span class="employee-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-person
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="{{ old(
                                    'full_name',
                                    $employeeModel?->full_name
                                ) }}"
                                class="form-control
                                    @error('full_name')
                                        is-invalid
                                    @enderror"
                                maxlength="150"
                                autocomplete="name"
                                required
                            >

                            @error('full_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="position"
                                class="form-label"
                            >
                                Jabatan
                                <span class="employee-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-briefcase
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="text"
                                id="position"
                                name="position"
                                value="{{ old(
                                    'position',
                                    $employeeModel?->position
                                ) }}"
                                class="form-control
                                    @error('position')
                                        is-invalid
                                    @enderror"
                                maxlength="100"
                                required
                            >

                            @error('position')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="phone_number"
                                class="form-label"
                            >
                                Nomor Telepon
                            </label>

                            <i
                                class="bi bi-telephone
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="tel"
                                id="phone_number"
                                name="phone_number"
                                value="{{ old(
                                    'phone_number',
                                    $employeeModel?->phone_number
                                ) }}"
                                class="form-control
                                    @error('phone_number')
                                        is-invalid
                                    @enderror"
                                maxlength="20"
                                autocomplete="tel"
                                placeholder="Contoh: 081234567890"
                            >

                            @error('phone_number')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="branch_id"
                                class="form-label"
                            >
                                Cabang
                                <span class="employee-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-building
                                    employee-form-field-icon"
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

                                @foreach ($branches as $branch)
                                    <option
                                        value="{{ $branch->id }}"
                                        @selected(
                                            (string)
                                                $selectedBranchId
                                            === (string)
                                                $branch->id
                                        )
                                    >
                                        {{ $branch->code }}
                                        |
                                        {{ $branch->name }}

                                        @if (
                                            isset($branch->status)
                                            && $branch->status
                                                === 'inactive'
                                        )
                                            (Tidak aktif)
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('branch_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            @if ($branches->isEmpty())
                                <div
                                    class="employee-form-branch-warning"
                                >
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Tidak terdapat cabang aktif
                                        yang dapat dipilih.
                                    </span>
                                </div>
                            @else
                                <div class="employee-form-help">
                                    <i
                                        class="bi bi-geo-alt"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Pilih cabang penempatan
                                        operasional karyawan.
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="employment_status"
                                class="form-label"
                            >
                                Status Karyawan
                                <span class="employee-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-person-check
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="employment_status"
                                name="employment_status"
                                class="form-select
                                    @error('employment_status')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option
                                    value="active"
                                    @selected(
                                        $selectedEmploymentStatus
                                        === 'active'
                                    )
                                >
                                    Aktif
                                </option>

                                <option
                                    value="inactive"
                                    @selected(
                                        $selectedEmploymentStatus
                                        === 'inactive'
                                    )
                                >
                                    Tidak aktif
                                </option>
                            </select>

                            @error('employment_status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="employee-form-section"
        aria-labelledby="employee-account-heading"
    >
        <div class="employee-form-section-header">
            <div>
                <h2
                    id="employee-account-heading"
                    class="employee-form-section-title"
                >
                    Informasi Akun
                </h2>

                <p class="employee-form-section-copy">
                    Akun digunakan karyawan untuk masuk ke sistem
                    presensi.
                </p>
            </div>

            <span class="employee-form-section-icon">
                <i
                    class="bi bi-shield-lock"
                    aria-hidden="true"
                ></i>
            </span>
        </div>

        <div class="employee-form-section-body">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="email"
                                class="form-label"
                            >
                                Email Akun
                                <span class="employee-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-envelope
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old(
                                    'email',
                                    $userModel?->email
                                ) }}"
                                class="form-control
                                    @error('email')
                                        is-invalid
                                    @enderror"
                                maxlength="150"
                                autocomplete="email"
                                required
                            >

                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="employee-form-help">
                                <i
                                    class="bi bi-info-circle"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Email akan disimpan dalam
                                    huruf kecil.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="account_status"
                                class="form-label"
                            >
                                Status Akun
                                <span class="employee-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-toggle-on
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="account_status"
                                name="account_status"
                                class="form-select
                                    @error('account_status')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option
                                    value="active"
                                    @selected(
                                        $selectedAccountStatus
                                        === 'active'
                                    )
                                >
                                    Aktif
                                </option>

                                <option
                                    value="inactive"
                                    @selected(
                                        $selectedAccountStatus
                                        === 'inactive'
                                    )
                                >
                                    Tidak aktif
                                </option>
                            </select>

                            @error('account_status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="password"
                                class="form-label"
                            >
                                Kata Sandi

                                @unless ($isEditing)
                                    <span
                                        class="employee-form-required"
                                    >
                                        *
                                    </span>
                                @endunless
                            </label>

                            <i
                                class="bi bi-key
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control
                                    @error('password')
                                        is-invalid
                                    @enderror"
                                autocomplete="new-password"
                                @unless ($isEditing)
                                    required
                                @endunless
                            >

                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="employee-form-help">
                                <i
                                    class="bi bi-shield-check"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    @if ($isEditing)
                                        Kosongkan apabila kata sandi
                                        tidak diubah.
                                    @else
                                        Minimal delapan karakter
                                        serta mengandung huruf besar,
                                        huruf kecil, angka, dan
                                        simbol.
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="employee-form-field-card">
                        <div class="employee-form-field has-icon">
                            <label
                                for="password_confirmation"
                                class="form-label"
                            >
                                Konfirmasi Kata Sandi

                                @unless ($isEditing)
                                    <span
                                        class="employee-form-required"
                                    >
                                        *
                                    </span>
                                @endunless
                            </label>

                            <i
                                class="bi bi-key-fill
                                    employee-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                                autocomplete="new-password"
                                @unless ($isEditing)
                                    required
                                @endunless
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="employee-form-account-note"
                role="note"
            >
                <span class="employee-form-note-icon">
                    <i
                        class="bi bi-info-circle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h3 class="employee-form-note-title">
                        Akses sistem presensi
                    </h3>

                    <p class="employee-form-note-copy">
                        Status karyawan dan status akun sama-sama
                        harus aktif agar karyawan dapat menggunakan
                        fitur presensi.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
