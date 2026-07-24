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
@endphp

@if ($errors->any())
    <div
        class="alert alert-danger"
        role="alert"
    >
        <div class="fw-semibold mb-2">
            Data belum dapat disimpan
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

@if ($editing)
    <section class="content-card p-3 p-md-4 mb-4">
        <div
            class="d-flex flex-column flex-lg-row
                justify-content-between gap-3"
        >
            <div>
                <h2 class="h5 fw-bold mb-2">
                    Data Presensi Saat Ini
                </h2>

                <p class="small text-secondary mb-0">
                    Data berikut merupakan kondisi presensi
                    sebelum koreksi terbaru disimpan.
                </p>
            </div>

            <div>
                <span class="badge text-bg-secondary">
                    {{
                        $recordSourceLabels[
                            (string) $attendanceRecord
                                ->record_source
                        ]
                        ?? ucfirst(
                            (string) $attendanceRecord
                                ->record_source
                        )
                    }}
                </span>
            </div>
        </div>

        <hr>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary">
                    Karyawan
                </div>

                <div class="fw-semibold">
                    {{
                        $attendanceRecord
                            ->employee
                            ?->full_name
                        ?? '-'
                    }}
                </div>

                <div class="small text-secondary">
                    {{
                        $attendanceRecord
                            ->employee
                            ?->employee_number
                        ?? '-'
                    }}
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary">
                    Tanggal dan Waktu
                </div>

                <div class="fw-semibold">
                    {{
                        $formatDateTime(
                            $attendanceRecord
                                ->attendance_time
                        )
                    }}
                    WIB
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary">
                    Jenis Presensi
                </div>

                <div class="fw-semibold">
                    {{
                        $attendanceTypeLabels[
                            (string) $attendanceRecord
                                ->attendance_type
                        ]
                        ?? ucfirst(
                            (string) $attendanceRecord
                                ->attendance_type
                        )
                    }}
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary">
                    Ketepatan Waktu
                </div>

                <div class="fw-semibold">
                    {{
                        $punctualityLabels[
                            (string) $attendanceRecord
                                ->punctuality_status
                        ]
                        ?? ucfirst(
                            (string) $attendanceRecord
                                ->punctuality_status
                        )
                    }}
                </div>
            </div>
        </div>

        @if (
            $attendanceRecord->last_corrected_at !== null
            || $attendanceRecord->last_correction_reason
        )
            <hr>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-secondary">
                        Koreksi Terakhir
                    </div>

                    <div class="fw-semibold">
                        {{
                            $formatDateTime(
                                $attendanceRecord
                                    ->last_corrected_at
                            )
                        }}
                        WIB
                    </div>

                    <div class="small text-secondary">
                        Oleh:
                        {{
                            $attendanceRecord
                                ->lastCorrectedBy
                                ?->name
                            ?? '-'
                        }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="small text-secondary">
                        Alasan Koreksi Terakhir
                    </div>

                    <div>
                        {{
                            $attendanceRecord
                                ->last_correction_reason
                            ?: '-'
                        }}
                    </div>
                </div>
            </div>
        @endif
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

    <section class="content-card p-3 p-md-4 mb-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">
                Data Presensi
            </h2>

            <p class="small text-secondary mb-0">
                Pilih karyawan dan masukkan tanggal serta
                waktu presensi berdasarkan hasil verifikasi HRD.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <label
                    for="employee_id"
                    class="form-label"
                >
                    Karyawan
                    <span class="text-danger">*</span>
                </label>

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

                    @foreach ($employees as $employeeOption)
                        <option
                            value="{{ $employeeOption->id }}"
                            @selected(
                                (string) $selectedEmployeeId
                                === (string) $employeeOption->id
                            )
                        >
                            {{ $employeeOption->employee_number }}
                            |
                            {{ $employeeOption->full_name }}

                            @if ($employeeOption->branch !== null)
                                |
                                {{ $employeeOption->branch->name }}
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
                    Hanya karyawan aktif pada cabang aktif
                    yang tersedia dalam pilihan.
                </div>
            </div>

            <div class="col-md-6">
                <label
                    for="attendance_date"
                    class="form-label"
                >
                    Tanggal Presensi
                    <span class="text-danger">*</span>
                </label>

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

            <div class="col-md-6">
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
                    Satu jadwal hanya dapat memiliki satu
                    presensi masuk dan satu presensi pulang.
                </div>
            </div>

            <div class="col-md-6">
                <label
                    for="attendance_time"
                    class="form-label"
                >
                    Waktu Presensi
                    <span class="text-danger">*</span>
                </label>

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
                    Status tepat waktu atau terlambat akan
                    dihitung oleh sistem berdasarkan jadwal.
                </div>
            </div>

            <div class="col-12">
                <label
                    for="reason"
                    class="form-label"
                >
                    Alasan Koreksi
                    <span class="text-danger">*</span>
                </label>

                <textarea
                    id="reason"
                    name="reason"
                    rows="4"
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
                        gap-3 form-text"
                >
                    <span>
                        Minimal 10 karakter. Alasan akan
                        disimpan dalam riwayat audit.
                    </span>

                    <span id="reason-counter">
                        0/1000
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="content-card p-3 p-md-4 mb-4">
        <h2 class="h5 fw-bold mb-3">
            Konsekuensi Koreksi Manual
        </h2>

        <div class="alert alert-warning mb-0">
            <p class="mb-2">
                Presensi yang disimpan melalui formulir ini
                akan memiliki sumber data
                <strong>manual</strong>.
            </p>

            <p class="mb-0">
                Data sesi QR, koordinat, accuracy, jarak,
                dan radius geofence tidak digunakan pada
                hasil koreksi aktif. Kondisi sebelum perubahan
                tetap disimpan dalam riwayat audit.
            </p>
        </div>
    </section>

    <div
        class="d-flex flex-column-reverse
            flex-sm-row justify-content-end gap-2"
    >
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
            {{ $submitText }}
        </button>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const reasonInput = document.getElementById('reason');
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

            reason
              </script>
@endpush