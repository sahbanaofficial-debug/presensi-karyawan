@extends('layouts.app')

@section('title', 'Presensi Karyawan')

@push('styles')
    <style>
        .scanner-wrapper {
            max-width: 520px;
            margin-right: auto;
            margin-left: auto;
        }

        .scanner-stage {
            min-height: 340px;
            overflow: hidden;
            border: 2px dashed #ced4da;
            border-radius: 1rem;
            background: #f8f9fa;
        }

        #qr-reader {
            width: 100%;
            min-height: 340px;
        }

        #qr-reader video {
            max-width: 100%;
            border-radius: 0.75rem;
        }

        .attendance-status-card {
            height: 100%;
            border: 1px solid #dee2e6;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .location-value {
            overflow-wrap: anywhere;
            font-family: ui-monospace, SFMono-Regular, Menlo,
                Monaco, Consolas, monospace;
        }

        .processing-spinner {
            width: 1rem;
            height: 1rem;
            border-width: 0.15rem;
        }
    </style>
@endpush

@section('content')
    @php
        $scheduleStatusLabels = [
            'work' => 'Hari Kerja',
            'off' => 'Libur',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $scheduleStatusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];

        $scheduleStatus = strtolower(
            (string) ($employeeSchedule?->schedule_status ?? '')
        );

        $scheduleStatusLabel =
            $scheduleStatusLabels[$scheduleStatus]
            ?? 'Jadwal Tidak Tersedia';

        $scheduleStatusClass =
            $scheduleStatusClasses[$scheduleStatus]
            ?? 'text-bg-secondary';

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('d-m-Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDateTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('d-m-Y H:i:s');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('H:i');
            } catch (\Throwable) {
                return substr((string) $value, 0, 5);
            }
        };

        $nextAttendanceLabel = match (true) {
            $checkInAttendance === null =>
                'Presensi Masuk',

            $checkOutAttendance === null =>
                'Presensi Pulang',

            default =>
                'Presensi Hari Ini Selesai',
        };

        $scannerStatusClass =
            $canScan
                ? 'text-bg-success'
                : 'text-bg-secondary';
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Presensi Karyawan
            </h1>

            <p class="page-description">
                Pindai QR Code dinamis dan kirim lokasi
                perangkat untuk mencatat presensi.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <span
                class="badge {{ $scannerStatusClass }}
                    px-3 py-2"
            >
                {{ $nextAttendanceLabel }}
            </span>
        </div>
    </header>

    @if (! $canScan)
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Pemindai presensi tidak tersedia
            </div>

            <div>
                {{
                    $scanBlockReason
                    ?? 'Presensi tidak dapat dilakukan saat ini.'
                }}
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Data Karyawan
                    </h2>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 mb-2">
                            Nomor Karyawan
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->employee_number }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Nama
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->full_name }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Jabatan
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->position }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Cabang
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            @if ($branch !== null)
                                <div class="fw-semibold">
                                    {{ $branch->code }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $branch->name }}
                                </div>
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Radius Geofence
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            @if ($branch !== null)
                                {{
                                    number_format(
                                        (float) $branch
                                            ->geofence_radius,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}
                                meter
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Batas Akurasi
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            @if ($branch !== null)
                                {{
                                    number_format(
                                        (float) $branch
                                            ->maximum_accuracy,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}
                                meter
                            @else
                                -
                            @endif
                        </dd>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-xl-6">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Jadwal Hari Ini
                    </h2>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 mb-2">
                            Tanggal
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatDate(
                                    $employeeSchedule
                                        ?->schedule_date
                                    ?? $currentMoment
                                )
                            }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Status Jadwal
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            <span
                                class="badge {{
                                    $scheduleStatusClass
                                }}"
                            >
                                {{ $scheduleStatusLabel }}
                            </span>
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Pola Jadwal
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $employeeSchedule
                                    ?->workSchedule
                                    ?->name
                                ?? '-'
                            }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Presensi Masuk Dibuka
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'check_in_opens_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Jadwal Masuk
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'scheduled_check_in_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Batas Tepat Waktu
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'late_limit_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Jadwal Pulang
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'scheduled_check_out_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Batas Presensi Pulang
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            {{
                                $formatTime(
                                    $scheduleTimeWindow[
                                        'check_out_limit_at'
                                    ] ?? null
                                )
                            }}
                            @if ($scheduleTimeWindow !== null)
                                WIB
                            @endif
                        </dd>
                    </dl>
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Status Presensi Hari Ini
            </h2>

            <p class="small text-secondary mb-0">
                Waktu yang ditampilkan berasal dari waktu server.
            </p>
        </div>

        <div class="p-3 p-md-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="attendance-status-card">
                        <div
                            class="d-flex justify-content-between
                                align-items-start gap-2 mb-3"
                        >
                            <div class="fw-semibold">
                                Presensi Masuk
                            </div>

                            @if ($checkInAttendance !== null)
                                <span class="badge text-bg-success">
                                    Tercatat
                                </span>
                            @else
                                <span
                                    class="badge text-bg-secondary"
                                >
                                    Belum
                                </span>
                            @endif
                        </div>

                        @if ($checkInAttendance !== null)
                            <div class="mb-2">
                                {{
                                    $formatDateTime(
                                        $checkInAttendance
                                            ->attendance_time
                                    )
                                }}
                                WIB
                            </div>

                            <div class="small text-secondary">
                                Status:
                                {{
                                    $checkInAttendance
                                        ->punctuality_status
                                    === 'late'
                                        ? 'Terlambat'
                                        : 'Tepat Waktu'
                                }}
                            </div>

                            <div class="small text-secondary">
                                Jarak:
                                {{
                                    number_format(
                                        (float) $checkInAttendance
                                            ->distance,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}
                                meter
                            </div>
                        @else
                            <div class="small text-secondary">
                                Presensi masuk belum tercatat.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="attendance-status-card">
                        <div
                            class="d-flex justify-content-between
                                align-items-start gap-2 mb-3"
                        >
                            <div class="fw-semibold">
                                Presensi Pulang
                            </div>

                            @if ($checkOutAttendance !== null)
                                <span class="badge text-bg-success">
                                    Tercatat
                                </span>
                            @else
                                <span
                                    class="badge text-bg-secondary"
                                >
                                    Belum
                                </span>
                            @endif
                        </div>

                        @if ($checkOutAttendance !== null)
                            <div class="mb-2">
                                {{
                                    $formatDateTime(
                                        $checkOutAttendance
                                            ->attendance_time
                                    )
                                }}
                                WIB
                            </div>

                            <div class="small text-secondary">
                                Status: Presensi pulang diterima
                            </div>

                            <div class="small text-secondary">
                                Jarak:
                                {{
                                    number_format(
                                        (float) $checkOutAttendance
                                            ->distance,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}
                                meter
                            </div>
                        @else
                            <div class="small text-secondary">
                                Presensi pulang belum tercatat.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content-card">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Pemindai QR Code
            </h2>

            <p class="small text-secondary mb-0">
                Arahkan kamera ke QR Code yang ditampilkan
                pada perangkat cabang.
            </p>
        </div>

        <div class="p-3 p-md-4">
            <div class="scanner-wrapper">
                <div
                    id="scanner-alert"
                    class="alert {{
                        $canScan
                            ? 'alert-info'
                            : 'alert-warning'
                    }}"
                    role="status"
                    aria-live="polite"
                >
                    @if ($canScan)
                        Tekan tombol Mulai Kamera untuk
                        memindai QR Code.
                    @else
                        {{
                            $scanBlockReason
                            ?? 'Pemindai tidak tersedia.'
                        }}
                    @endif
                </div>

                <div
                    id="qr-reader"
                    class="scanner-stage mb-3"
                >
                    <div
                        id="scanner-placeholder"
                        class="d-flex align-items-center
                            justify-content-center
                            text-center text-secondary
                            h-100 p-4"
                        style="min-height: 340px;"
                    >
                        Kamera belum dinyalakan.
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row
                        justify-content-center gap-2 mb-4"
                >
                    <button
                        type="button"
                        id="start-scanner-button"
                        class="btn btn-primary"
                        @disabled(! $canScan)
                    >
                        Mulai Kamera
                    </button>

                    <button
                        type="button"
                        id="stop-scanner-button"
                        class="btn btn-outline-secondary"
                        disabled
                    >
                        Matikan Kamera
                    </button>

                    <button
                        type="button"
                        id="reset-scanner-button"
                        class="btn btn-outline-primary"
                        disabled
                    >
                        Pindai Ulang
                    </button>
                </div>

                <div
                    id="processing-indicator"
                    class="alert alert-light border d-none"
                    role="status"
                >
                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="spinner-border
                                processing-spinner"
                            aria-hidden="true"
                        ></span>

                        <span id="processing-message">
                            Memproses presensi...
                        </span>
                    </div>
                </div>

                <section
                    id="location-information"
                    class="border rounded-3 p-3 mb-4"
                >
                    <h3 class="h6 fw-bold mb-3">
                        Informasi Lokasi Perangkat
                    </h3>

                    <dl class="row small mb-0">
                        <dt class="col-sm-4 mb-2">
                            Latitude
                        </dt>

                        <dd
                            id="location-latitude"
                            class="col-sm-8 mb-2
                                location-value"
                        >
                            -
                        </dd>

                        <dt class="col-sm-4 mb-2">
                            Longitude
                        </dt>

                        <dd
                            id="location-longitude"
                            class="col-sm-8 mb-2
                                location-value"
                        >
                            -
                        </dd>

                        <dt class="col-sm-4 mb-2">
                            Accuracy
                        </dt>

                        <dd
                            id="location-accuracy"
                            class="col-sm-8 mb-0
                                location-value"
                        >
                            -
                        </dd>
                    </dl>
                </section>

                <div
                    id="attendance-result"
                    class="d-none"
                    aria-live="polite"
                ></div>
            </div>
        </div>
    </section>

    <section class="content-card p-3 p-md-4 mt-4">
        <h2 class="h5 fw-bold mb-3">
            Ketentuan Penggunaan
        </h2>

        <ol class="small text-secondary mb-0 ps-3">
            <li class="mb-2">
                Izinkan browser mengakses kamera dan lokasi
                perangkat.
            </li>

            <li class="mb-2">
                Pindai QR Code yang sedang aktif pada cabang
                penempatan.
            </li>

            <li class="mb-2">
                Tetap berada di dalam area toko atau halaman
                depan ruko yang termasuk radius geofence.
            </li>

            <li class="mb-2">
                Tunggu sampai informasi lokasi diperoleh dan
                server menyelesaikan validasi.
            </li>

            <li>
                Jangan menutup halaman selama transaksi sedang
                diproses.
            </li>
        </ol>
    </section>
@endsection

@push('scripts')
    <script
        src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"
    ></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canScan = @json($canScan);

            const attendanceEndpoint = @json(
                route('attendance.store')
            );

            const csrfToken = @json(csrf_token());

            const startButton = document.getElementById(
                'start-scanner-button'
            );

            const stopButton = document.getElementById(
                'stop-scanner-button'
            );

            const resetButton = document.getElementById(
                'reset-scanner-button'
            );

            const scannerAlert = document.getElementById(
                'scanner-alert'
            );

            const processingIndicator =
                document.getElementById(
                    'processing-indicator'
                );

            const processingMessage =
                document.getElementById(
                    'processing-message'
                );

            const attendanceResult =
                document.getElementById(
                    'attendance-result'
                );

            const latitudeElement =
                document.getElementById(
                    'location-latitude'
                );

            const longitudeElement =
                document.getElementById(
                    'location-longitude'
                );

            const accuracyElement =
                document.getElementById(
                    'location-accuracy'
                );

            let qrScanner = null;
            let scannerRunning = false;
            let requestInProgress = false;
            let attendanceAccepted = false;

            const setScannerAlert = function (
                message,
                alertClass
            ) {
                if (scannerAlert === null) {
                    return;
                }

                scannerAlert.className =
                    'alert ' + alertClass;

                scannerAlert.textContent = message;
            };

            const setProcessing = function (
                isProcessing,
                message
            ) {
                if (processingIndicator !== null) {
                    processingIndicator.classList.toggle(
                        'd-none',
                        ! isProcessing
                    );
                }

                if (
                    processingMessage !== null
                    && typeof message === 'string'
                ) {
                    processingMessage.textContent = message;
                }
            };

            const updateButtons = function () {
                if (startButton !== null) {
                    startButton.disabled =
                        ! canScan
                        || scannerRunning
                        || requestInProgress
                        || attendanceAccepted;
                }

                if (stopButton !== null) {
                    stopButton.disabled =
                        ! scannerRunning
                        || requestInProgress;
                }

                if (resetButton !== null) {
                    resetButton.disabled =
                        ! canScan
                        || scannerRunning
                        || requestInProgress
                        || attendanceAccepted;
                }
            };

            const displayResult = function (
                success,
                message,
                data
            ) {
                if (attendanceResult === null) {
                    return;
                }

                attendanceResult.className =
                    success
                        ? 'alert alert-success'
                        : 'alert alert-danger';

                attendanceResult.innerHTML = '';

                const title =
                    document.createElement('div');

                title.className = 'fw-semibold mb-1';

                title.textContent =
                    success
                        ? 'Presensi Berhasil'
                        : 'Presensi Ditolak';

                const messageElement =
                    document.createElement('div');

                messageElement.textContent = message;

                attendanceResult.appendChild(title);
                attendanceResult.appendChild(
                    messageElement
                );

                if (
                    success
                    && data !== null
                    && typeof data === 'object'
                ) {
                    const details =
                        document.createElement('div');

                    details.className =
                        'small mt-2';

                    const attendanceType =
                        data.attendance_type === 'check_out'
                            ? 'Presensi Pulang'
                            : 'Presensi Masuk';

                    const distance =
                        Number(data.distance ?? 0)
                            .toFixed(2);

                    const accuracy =
                        Number(data.accuracy ?? 0)
                            .toFixed(2);

                    details.textContent =
                        attendanceType
                        + ' | Jarak '
                        + distance
                        + ' meter | Accuracy '
                        + accuracy
                        + ' meter';

                    attendanceResult.appendChild(details);
                }
            };

            const clearResult = function () {
                if (attendanceResult === null) {
                    return;
                }

                attendanceResult.className = 'd-none';
                attendanceResult.innerHTML = '';
            };

            const updateLocationDisplay = function (
                coordinates
            ) {
                if (latitudeElement !== null) {
                    latitudeElement.textContent =
                        coordinates.latitude.toFixed(8);
                }

                if (longitudeElement !== null) {
                    longitudeElement.textContent =
                        coordinates.longitude.toFixed(8);
                }

                if (accuracyElement !== null) {
                    accuracyElement.textContent =
                        coordinates.accuracy.toFixed(2)
                        + ' meter';
                }
            };

            const clearLocationDisplay = function () {
                if (latitudeElement !== null) {
                    latitudeElement.textContent = '-';
                }

                if (longitudeElement !== null) {
                    longitudeElement.textContent = '-';
                }

                if (accuracyElement !== null) {
                    accuracyElement.textContent = '-';
                }
            };

            const firstValidationMessage = function (
                responseBody
            ) {
                if (
                    responseBody === null
                    || typeof responseBody !== 'object'
                ) {
                    return null;
                }

                const errors = responseBody.errors;

                if (
                    errors === null
                    || typeof errors !== 'object'
                ) {
                    return null;
                }

                for (const messages of Object.values(errors)) {
                    if (
                        Array.isArray(messages)
                        && typeof messages[0] === 'string'
                    ) {
                        return messages[0];
                    }

                    if (typeof messages === 'string') {
                        return messages;
                    }
                }

                return null;
            };

            const locationErrorMessage = function (
                error
            ) {
                if (error === null || error === undefined) {
                    return 'Lokasi perangkat tidak dapat diperoleh.';
                }

                switch (error.code) {
                    case 1:
                        return 'Izin lokasi ditolak. Aktifkan izin lokasi pada browser.';

                    case 2:
                        return 'Posisi perangkat tidak tersedia. Aktifkan GPS dan coba kembali.';

                    case 3:
                        return 'Pengambilan lokasi melewati batas waktu. Coba kembali.';

                    default:
                        return 'Lokasi perangkat tidak dapat diperoleh.';
                }
            };

            const getCurrentLocation = function () {
                return new Promise(function (
                    resolve,
                    reject
                ) {
                    if (
                        typeof navigator.geolocation
                        === 'undefined'
                    ) {
                        reject(
                            new Error(
                                'Browser tidak mendukung pengambilan lokasi.'
                            )
                        );

                        return;
                    }

                    navigator.geolocation
                        .getCurrentPosition(
                            function (position) {
                                resolve({
                                    latitude:
                                        position.coords.latitude,

                                    longitude:
                                        position.coords.longitude,

                                    accuracy:
                                        position.coords.accuracy,
                                });
                            },

                            function (error) {
                                reject(
                                    new Error(
                                        locationErrorMessage(error)
                                    )
                                );
                            },

                            {
                                enableHighAccuracy: true,
                                timeout: 20000,
                                maximumAge: 0,
                            }
                        );
                });
            };

            const stopScanner = async function () {
                if (
                    qrScanner === null
                    || ! scannerRunning
                ) {
                    scannerRunning = false;
                    updateButtons();

                    return;
                }

                try {
                    await qrScanner.stop();
                } catch (error) {
                    console.error(
                        'Kamera gagal dihentikan.',
                        error
                    );
                } finally {
                    scannerRunning = false;
                    updateButtons();
                }
            };

            const submitAttendance = async function (
                qrPayload,
                coordinates
            ) {
                const response = await fetch(
                    attendanceEndpoint,
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrfToken,

                            'X-Requested-With':
                                'XMLHttpRequest',
                        },

                        credentials: 'same-origin',

                        cache: 'no-store',

                        body: JSON.stringify({
                            qr_payload: qrPayload,

                            latitude:
                                coordinates.latitude,

                            longitude:
                                coordinates.longitude,

                            accuracy:
                                coordinates.accuracy,
                        }),
                    }
                );

                const responseBody =
                    await response
                        .json()
                        .catch(function () {
                            return {};
                        });

                if (! response.ok) {
                    const validationMessage =
                        firstValidationMessage(
                            responseBody
                        );

                    throw new Error(
                        validationMessage
                        ?? responseBody.message
                        ?? 'Presensi tidak dapat diproses.'
                    );
                }

                return responseBody;
            };

            const processScannedPayload = async function (
                decodedText
            ) {
                if (
                    requestInProgress
                    || attendanceAccepted
                ) {
                    return;
                }

                const qrPayload =
                    String(decodedText ?? '').trim();

                if (qrPayload === '') {
                    return;
                }

                requestInProgress = true;
                updateButtons();
                clearResult();

                await stopScanner();

                setScannerAlert(
                    'QR Code terbaca. Mengambil lokasi perangkat...',
                    'alert-info'
                );

                setProcessing(
                    true,
                    'Mengambil lokasi perangkat...'
                );

                try {
                    const coordinates =
                        await getCurrentLocation();

                    updateLocationDisplay(
                        coordinates
                    );

                    setProcessing(
                        true,
                        'Memvalidasi presensi pada server...'
                    );

                    const responseBody =
                        await submitAttendance(
                            qrPayload,
                            coordinates
                        );

                    attendanceAccepted = true;

                    displayResult(
                        true,
                        responseBody.message
                        ?? 'Presensi berhasil dicatat.',
                        responseBody.data ?? null
                    );

                    setScannerAlert(
                        'Presensi berhasil dicatat.',
                        'alert-success'
                    );

                    setProcessing(false);

                    window.setTimeout(
                        function () {
                            window.location.reload();
                        },
                        2500
                    );
                } catch (error) {
                    const message =
                        error instanceof Error
                            ? error.message
                            : 'Presensi tidak dapat diproses.';

                    displayResult(
                        false,
                        message,
                        null
                    );

                    setScannerAlert(
                        message,
                        'alert-danger'
                    );

                    setProcessing(false);
                } finally {
                    requestInProgress = false;
                    updateButtons();
                }
            };

            const startScanner = async function () {
                if (
                    ! canScan
                    || scannerRunning
                    || requestInProgress
                    || attendanceAccepted
                ) {
                    return;
                }

                clearResult();

                setScannerAlert(
                    'Meminta izin kamera...',
                    'alert-info'
                );

                if (
                    typeof Html5Qrcode === 'undefined'
                    || typeof Html5QrcodeSupportedFormats
                        === 'undefined'
                ) {
                    setScannerAlert(
                        'Pustaka pemindai QR tidak dapat dimuat.',
                        'alert-danger'
                    );

                    return;
                }

                if (qrScanner === null) {
                    qrScanner = new Html5Qrcode(
                        'qr-reader',
                        {
                            formatsToSupport: [
                                Html5QrcodeSupportedFormats
                                    .QR_CODE,
                            ],
                        }
                    );
                }

                try {
                    await qrScanner.start(
                        {
                            facingMode: 'environment',
                        },

                        {
                            fps: 10,

                            qrbox: {
                                width: 250,
                                height: 250,
                            },

                            aspectRatio: 1.0,

                            disableFlip: false,
                        },

                        function (
                            decodedText
                        ) {
                            processScannedPayload(
                                decodedText
                            );
                        },

                        function () {
                            /*
                             * Kesalahan per frame diabaikan
                             * karena normal ketika QR belum
                             * berada di depan kamera.
                             */
                        }
                    );

                    scannerRunning = true;

                    setScannerAlert(
                        'Kamera aktif. Arahkan kamera ke QR Code.',
                        'alert-success'
                    );
                } catch (error) {
                    scannerRunning = false;

                    setScannerAlert(
                        error instanceof Error
                            ? error.message
                            : 'Kamera tidak dapat dinyalakan.',
                        'alert-danger'
                    );
                } finally {
                    updateButtons();
                }
            };

            const resetScanner = async function () {
                await stopScanner();

                requestInProgress = false;
                attendanceAccepted = false;

                clearResult();
                clearLocationDisplay();
                setProcessing(false);

                setScannerAlert(
                    'Tekan tombol Mulai Kamera untuk memindai QR Code.',
                    'alert-info'
                );

                updateButtons();
            };

            if (startButton !== null) {
                startButton.addEventListener(
                    'click',
                    startScanner
                );
            }

            if (stopButton !== null) {
                stopButton.addEventListener(
                    'click',
                    async function () {
                        await stopScanner();

                        setScannerAlert(
                            'Kamera telah dimatikan.',
                            'alert-secondary'
                        );
                    }
                );
            }

            if (resetButton !== null) {
                resetButton.addEventListener(
                    'click',
                    resetScanner
                );
            }

            window.addEventListener(
                'beforeunload',
                function () {
                    if (
                        qrScanner !== null
                        && scannerRunning
                    ) {
                        qrScanner.stop().catch(
                            function () {
                                return null;
                            }
                        );
                    }
                }
            );

            updateButtons();
        });
    </script>
@endpush