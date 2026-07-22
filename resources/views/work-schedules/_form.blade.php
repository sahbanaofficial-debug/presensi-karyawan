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
                Informasi Pola Jadwal
            </h2>

            <p class="small text-secondary mb-0">
                Tentukan nama, jam masuk, jam pulang,
                dan status pola jadwal kerja.
            </p>
        </div>
    </div>

    <div class="col-md-8">
        <label
            for="name"
            class="form-label"
        >
            Nama Jadwal
            <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            value="{{
                old(
                    'name',
                    $workScheduleModel?->name
                )
            }}"
            class="form-control
                @error('name') is-invalid @enderror"
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

    <div class="col-md-4">
        <label
            for="status"
            class="form-label"
        >
            Status
            <span class="text-danger">*</span>
        </label>

        <select
            id="status"
            name="status"
            class="form-select
                @error('status') is-invalid @enderror"
            required
        >
            <option
                value="active"
                @selected($selectedStatus === 'active')
            >
                Aktif
            </option>

            <option
                value="inactive"
                @selected($selectedStatus === 'inactive')
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

    <div class="col-md-6">
        <label
            for="check_in_time"
            class="form-label"
        >
            Jam Masuk
            <span class="text-danger">*</span>
        </label>

        <input
            type="time"
            id="check_in_time"
            name="check_in_time"
            value="{{ $checkInTime }}"
            class="form-control
                @error('check_in_time') is-invalid @enderror"
            step="60"
            required
        >

        <div class="form-text">
            Waktu mulai jadwal kerja dalam WIB.
        </div>

        @error('check_in_time')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label
            for="check_out_time"
            class="form-label"
        >
            Jam Pulang
            <span class="text-danger">*</span>
        </label>

        <input
            type="time"
            id="check_out_time"
            name="check_out_time"
            value="{{ $checkOutTime }}"
            class="form-control
                @error('check_out_time') is-invalid @enderror"
            step="60"
            required
        >

        <div class="form-text">
            Jam pulang harus setelah jam masuk.
        </div>

        @error('check_out_time')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-12 mt-5">
        <div class="border-bottom pb-2">
            <h2 class="h5 fw-bold mb-1">
                Aturan Waktu Presensi
            </h2>

            <p class="small text-secondary mb-0">
                Nilai waktu pada bagian ini menggunakan satuan menit.
            </p>
        </div>
    </div>

    <div class="col-md-4">
        <label
            for="check_in_open_minutes"
            class="form-label"
        >
            Pembukaan Presensi Masuk
            <span class="text-danger">*</span>
        </label>

        <div class="input-group">
            <input
                type="number"
                id="check_in_open_minutes"
                name="check_in_open_minutes"
                value="{{ $checkInOpenMinutes }}"
                class="form-control
                    @error('check_in_open_minutes')
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

        <div class="form-text">
            Presensi masuk dibuka sejumlah menit
            sebelum jam masuk.
        </div>
    </div>

    <div class="col-md-4">
        <label
            for="late_tolerance_minutes"
            class="form-label"
        >
            Toleransi Keterlambatan
            <span class="text-danger">*</span>
        </label>

        <div class="input-group">
            <input
                type="number"
                id="late_tolerance_minutes"
                name="late_tolerance_minutes"
                value="{{ $lateToleranceMinutes }}"
                class="form-control
                    @error('late_tolerance_minutes')
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

        <div class="form-text">
            Batas toleransi setelah jam masuk.
        </div>
    </div>

    <div class="col-md-4">
        <label
            for="check_out_limit_minutes"
            class="form-label"
        >
            Batas Akhir Presensi Pulang
            <span class="text-danger">*</span>
        </label>

        <div class="input-group">
            <input
                type="number"
                id="check_out_limit_minutes"
                name="check_out_limit_minutes"
                value="{{ $checkOutLimitMinutes }}"
                class="form-control
                    @error('check_out_limit_minutes')
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

        <div class="form-text">
            Batas waktu setelah jam pulang untuk
            melakukan presensi pulang.
        </div>
    </div>

    <div class="col-12">
        <div
            class="alert alert-info mb-0"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Ketentuan waktu presensi
            </div>

            <div class="small">
                Presensi masuk dibuka sebelum jam kerja.
                Presensi lebih dari batas toleransi tetap
                diterima dengan status terlambat.
                Presensi pulang hanya dapat dilakukan
                mulai dari jam pulang sampai batas akhir
                yang ditentukan.
            </div>
        </div>
    </div>
</div>