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

@if ($errors->any())
    <div
        class="alert alert-danger"
        role="alert"
    >
        <div class="fw-semibold mb-2">
            Data belum dapat disimpan.
        </div>

        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>
                    {{ $error }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-12">
        <div class="border-bottom pb-2">
            <h2 class="h5 fw-bold mb-1">
                Informasi Karyawan
            </h2>

            <p class="small text-secondary mb-0">
                Masukkan identitas dan penempatan karyawan.
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <label
            for="employee_number"
            class="form-label"
        >
            Nomor Karyawan
            <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            id="employee_number"
            name="employee_number"
            value="{{
                old(
                    'employee_number',
                    $employeeModel?->employee_number
                )
            }}"
            class="form-control
                @error('employee_number') is-invalid @enderror"
            maxlength="50"
            autocomplete="off"
            required
        >

        <div class="form-text">
            Nomor karyawan akan disimpan dalam huruf kapital.
        </div>

        @error('employee_number')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label
            for="full_name"
            class="form-label"
        >
            Nama Lengkap
            <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            id="full_name"
            name="full_name"
            value="{{
                old(
                    'full_name',
                    $employeeModel?->full_name
                )
            }}"
            class="form-control
                @error('full_name') is-invalid @enderror"
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

    <div class="col-md-6">
        <label
            for="position"
            class="form-label"
        >
            Jabatan
            <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            id="position"
            name="position"
            value="{{
                old(
                    'position',
                    $employeeModel?->position
                )
            }}"
            class="form-control
                @error('position') is-invalid @enderror"
            maxlength="100"
            required
        >

        @error('position')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label
            for="phone_number"
            class="form-label"
        >
            Nomor Telepon
        </label>

        <input
            type="tel"
            id="phone_number"
            name="phone_number"
            value="{{
                old(
                    'phone_number',
                    $employeeModel?->phone_number
                )
            }}"
            class="form-control
                @error('phone_number') is-invalid @enderror"
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

    <div class="col-md-8">
        <label
            for="branch_id"
            class="form-label"
        >
            Cabang
            <span class="text-danger">*</span>
        </label>

        <select
            id="branch_id"
            name="branch_id"
            class="form-select
                @error('branch_id') is-invalid @enderror"
            required
        >
            <option value="">
                Pilih cabang
            </option>

            @foreach ($branches as $branch)
                <option
                    value="{{ $branch->id }}"
                    @selected(
                        (string) $selectedBranchId
                        === (string) $branch->id
                    )
                >
                    {{ $branch->code }} — {{ $branch->name }}

                    @if (
                        isset($branch->status)
                        && $branch->status === 'inactive'
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
            <div class="form-text text-danger">
                Tidak terdapat cabang aktif yang dapat dipilih.
            </div>
        @endif
    </div>

    <div class="col-md-4">
        <label
            for="employment_status"
            class="form-label"
        >
            Status Karyawan
            <span class="text-danger">*</span>
        </label>

        <select
            id="employment_status"
            name="employment_status"
            class="form-select
                @error('employment_status') is-invalid @enderror"
            required
        >
            <option
                value="active"
                @selected(
                    $selectedEmploymentStatus === 'active'
                )
            >
                Aktif
            </option>

            <option
                value="inactive"
                @selected(
                    $selectedEmploymentStatus === 'inactive'
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

    <div class="col-12 mt-5">
        <div class="border-bottom pb-2">
            <h2 class="h5 fw-bold mb-1">
                Informasi Akun
            </h2>

            <p class="small text-secondary mb-0">
                Akun digunakan karyawan untuk masuk ke sistem presensi.
            </p>
        </div>
    </div>

    <div class="col-md-8">
        <label
            for="email"
            class="form-label"
        >
            Email Akun
            <span class="text-danger">*</span>
        </label>

        <input
            type="email"
            id="email"
            name="email"
            value="{{
                old(
                    'email',
                    $userModel?->email
                )
            }}"
            class="form-control
                @error('email') is-invalid @enderror"
            maxlength="150"
            autocomplete="email"
            required
        >

        <div class="form-text">
            Email akan disimpan dalam huruf kecil.
        </div>

        @error('email')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-4">
        <label
            for="account_status"
            class="form-label"
        >
            Status Akun
            <span class="text-danger">*</span>
        </label>

        <select
            id="account_status"
            name="account_status"
            class="form-select
                @error('account_status') is-invalid @enderror"
            required
        >
            <option
                value="active"
                @selected(
                    $selectedAccountStatus === 'active'
                )
            >
                Aktif
            </option>

            <option
                value="inactive"
                @selected(
                    $selectedAccountStatus === 'inactive'
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

    <div class="col-md-6">
        <label
            for="password"
            class="form-label"
        >
            Kata Sandi

            @unless ($isEditing)
                <span class="text-danger">*</span>
            @endunless
        </label>

        <input
            type="password"
            id="password"
            name="password"
            class="form-control
                @error('password') is-invalid @enderror"
            autocomplete="new-password"
            @unless ($isEditing)
                required
            @endunless
        >

        <div class="form-text">
            @if ($isEditing)
                Kosongkan apabila kata sandi tidak diubah.
            @else
                Minimal delapan karakter serta mengandung huruf
                besar, huruf kecil, angka, dan simbol.
            @endif
        </div>

        @error('password')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label
            for="password_confirmation"
            class="form-label"
        >
            Konfirmasi Kata Sandi

            @unless ($isEditing)
                <span class="text-danger">*</span>
            @endunless
        </label>

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