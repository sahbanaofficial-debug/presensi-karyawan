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

@if ($errors->any())
    <div
        class="alert alert-danger"
        role="alert"
    >
        <div class="fw-semibold mb-2">
            Data jadwal belum dapat disimpan.
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
                Informasi Jadwal Harian
            </h2>

            <p class="small text-secondary mb-0">
                Tentukan karyawan, tanggal, dan status jadwal.
            </p>
        </div>
    </div>

    <div class="col-lg-7">
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
                @error('employee_id') is-invalid @enderror"
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
                    — {{ $employeeOption->full_name }}

                    @if ($employeeOption->branch !== null)
                        — {{ $employeeOption->branch->code }}
                    @endif

                    @if (
                        isset($employeeOption->employment_status)
                        && $employeeOption->employment_status
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
            <div class="form-text text-danger">
                Tidak terdapat karyawan aktif yang dapat dijadwalkan.
            </div>
        @else
            <div class="form-text">
                Satu karyawan hanya dapat memiliki satu status
                jadwal pada tanggal yang sama.
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        <label
            for="schedule_date"
            class="form-label"
        >
            Tanggal Jadwal
            <span class="text-danger">*</span>
        </label>

        <input
            type="date"
            id="schedule_date"
            name="schedule_date"
            value="{{ $selectedScheduleDate }}"
            class="form-control
                @error('schedule_date') is-invalid @enderror"
            required
        >

        @error('schedule_date')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label
            for="schedule_status"
            class="form-label"
        >
            Status Jadwal
            <span class="text-danger">*</span>
        </label>

        <select
            id="schedule_status"
            name="schedule_status"
            class="form-select
                @error('schedule_status') is-invalid @enderror"
            required
        >
            <option
                value="work"
                @selected(
                    $selectedScheduleStatus === 'work'
                )
            >
                Kerja
            </option>

            <option
                value="off"
                @selected(
                    $selectedScheduleStatus === 'off'
                )
            >
                Libur
            </option>

            <option
                value="permit"
                @selected(
                    $selectedScheduleStatus === 'permit'
                )
            >
                Izin
            </option>

            <option
                value="sick"
                @selected(
                    $selectedScheduleStatus === 'sick'
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

    <div class="col-md-6">
        <label
            for="work_schedule_id"
            class="form-label"
        >
            Pola Jadwal Kerja
            <span
                id="work-schedule-required-mark"
                class="text-danger"
            >
                *
            </span>
        </label>

        <select
            id="work_schedule_id"
            name="work_schedule_id"
            class="form-select
                @error('work_schedule_id') is-invalid @enderror"
        >
            <option value="">
                Pilih pola jadwal
            </option>

            @foreach ($workSchedules as $workScheduleOption)
                <option
                    value="{{ $workScheduleOption->id }}"
                    @selected(
                        (string) $selectedWorkScheduleId
                        === (string) $workScheduleOption->id
                    )
                >
                    {{ $workScheduleOption->name }}
                    —
                    {{
                        $formatTime(
                            $workScheduleOption->check_in_time
                        )
                    }}
                    sampai
                    {{
                        $formatTime(
                            $workScheduleOption->check_out_time
                        )
                    }}
                    WIB

                    @if (
                        isset($workScheduleOption->status)
                        && $workScheduleOption->status
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
            class="form-text"
        >
            Pola jadwal wajib dipilih untuk status kerja.
        </div>

        @if ($workSchedules->isEmpty())
            <div class="form-text text-danger">
                Tidak terdapat pola jadwal aktif yang dapat dipilih.
            </div>
        @endif
    </div>

    <div class="col-12">
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
                @error('notes') is-invalid @enderror"
            placeholder="Contoh: Libur bergilir atau keterangan izin"
        >{{ $notesValue }}</textarea>

        @error('notes')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

        <div class="form-text">
            Keterangan bersifat opsional.
        </div>
    </div>

    <div class="col-12">
        <div
            class="alert alert-info mb-0"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Ketentuan status jadwal
            </div>

            <ul class="small mb-0 ps-3">
                <li>
                    Status kerja wajib menggunakan pola jadwal.
                </li>

                <li>
                    Status libur, izin, dan sakit tidak menggunakan
                    pola jadwal kerja.
                </li>

                <li>
                    Identitas HRD yang menetapkan jadwal akan
                    dicatat otomatis oleh sistem.
                </li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const statusField = document.getElementById(
                'schedule_status'
            );

            const workScheduleField = document.getElementById(
                'work_schedule_id'
            );

            const requiredMark = document.getElementById(
                'work-schedule-required-mark'
            );

            const helpText = document.getElementById(
                'work-schedule-help'
            );

            if (
                statusField === null
                || workScheduleField === null
            ) {
                return;
            }

            const updateWorkScheduleField = function () {
                const isWorkStatus =
                    statusField.value === 'work';

                workScheduleField.disabled = ! isWorkStatus;
                workScheduleField.required = isWorkStatus;

                if (requiredMark !== null) {
                    requiredMark.classList.toggle(
                        'd-none',
                        ! isWorkStatus
                    );
                }

                if (helpText !== null) {
                    helpText.textContent = isWorkStatus
                        ? 'Pola jadwal wajib dipilih untuk status kerja.'
                        : 'Pola jadwal tidak digunakan untuk status ini.';
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
        });
    </script>
@endpush