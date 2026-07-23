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

<div class="row g-4">
    <div class="col-12">
        <div class="border-bottom pb-2">
            <h2 class="h5 fw-bold mb-1">
                Data Sesi Presensi
            </h2>

            <p class="small text-secondary mb-0">
                Tentukan cabang, jenis presensi, tanggal,
                dan rentang waktu berlakunya sesi.
            </p>
        </div>
    </div>

    <div class="col-lg-6">
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
                @error('branch_id')
                    is-invalid
                @enderror"
            required
        >
            <option value="">
                Pilih cabang
            </option>

            @foreach ($branches as $branchOption)
                <option
                    value="{{ $branchOption->id }}"
                    @selected(
                        (string) $selectedBranchId
                        === (string) $branchOption->id
                    )
                >
                    {{ $branchOption->code }}
                    — {{ $branchOption->name }}
                </option>
            @endforeach
        </select>

        @error('branch_id')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

        <div class="form-text">
            Hanya cabang aktif dengan konfigurasi geofence
            lengkap yang dapat dipilih.
        </div>
    </div>

    <div class="col-lg-6">
        <label
            for="attendance_type"
            class="form-label"
        >
            Jenis Presensi
            <span class="text-danger">*</span>
        </label>

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
                    $selectedAttendanceType === 'check_in'
                )
            >
                Presensi Masuk
            </option>

            <option
                value="check_out"
                @selected(
                    $selectedAttendanceType === 'check_out'
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
            Satu cabang hanya dapat mempunyai satu sesi aktif
            untuk jenis dan tanggal yang sama.
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <label
            for="session_date"
            class="form-label"
        >
            Tanggal Sesi
            <span class="text-danger">*</span>
        </label>

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

    <div class="col-md-6 col-lg-4">
        <label
            for="start_time"
            class="form-label"
        >
            Waktu Mulai
            <span class="text-danger">*</span>
        </label>

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

        <div class="form-text">
            QR Code baru dapat digunakan setelah waktu ini.
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <label
            for="end_time"
            class="form-label"
        >
            Waktu Berakhir
            <span class="text-danger">*</span>
        </label>

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

        <div class="form-text">
            Setelah waktu ini, sesi akan dianggap kedaluwarsa.
        </div>
    </div>

    <div class="col-12">
        <div
            id="time-range-warning"
            class="alert alert-danger d-none"
            role="alert"
        >
            Waktu berakhir harus setelah waktu mulai sesi.
        </div>

        <div
            class="alert alert-info mb-0"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Ketentuan sesi presensi
            </div>

            <ul class="small mb-0 ps-3">
                <li>
                    QR Code hanya aktif dalam rentang waktu sesi.
                </li>

                <li>
                    Token QR berubah setiap 30 detik.
                </li>

                <li>
                    Secret TOTP tidak ditampilkan pada QR Code
                    maupun browser.
                </li>

                <li>
                    Sesi dapat ditutup secara manual oleh HRD
                    atau admin operasional.
                </li>

                <li>
                    Sesi yang melewati waktu berakhir akan
                    berstatus kedaluwarsa.
                </li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startTimeField = document.getElementById(
                'start_time'
            );

            const endTimeField = document.getElementById(
                'end_time'
            );

            const warningElement = document.getElementById(
                'time-range-warning'
            );

            if (
                startTimeField === null
                || endTimeField === null
            ) {
                return;
            }

            const validateTimeRange = function () {
                const startTime = startTimeField.value;
                const endTime = endTimeField.value;

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
        });
    </script>
@endpush