@extends('layouts.app')

@section('title', 'Detail Sesi Presensi')

@push('styles')
    <style>
        .qr-stage {
            display: flex;
            min-height: 21rem;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            background: #ffffff;
        }

        .qr-code-container {
            display: flex;
            min-height: 17.5rem;
            align-items: center;
            justify-content: center;
        }

        .qr-code-container img,
        .qr-code-container canvas {
            max-width: 100%;
            height: auto !important;
        }

        .token-value {
            font-family: ui-monospace, SFMono-Regular, Menlo,
                Monaco, Consolas, monospace;
            font-size: clamp(1.75rem, 6vw, 3rem);
            font-weight: 700;
            letter-spacing: 0.35rem;
        }

        .session-identifier {
            overflow-wrap: anywhere;
            font-family: ui-monospace, SFMono-Regular, Menlo,
                Monaco, Consolas, monospace;
        }
    </style>
@endpush

@section('content')
    @php
        $branch = $attendanceSession->branch;
        $creator = $attendanceSession->creator;

        $attendanceType = strtolower(
            (string) $attendanceSession->attendance_type
        );

        $sessionStatus = strtolower(
            (string) $attendanceSession->status
        );

        $attendanceTypeLabels = [
            'check_in' => 'Presensi Masuk',
            'check_out' => 'Presensi Pulang',
        ];

        $attendanceTypeClasses = [
            'check_in' => 'text-bg-primary',
            'check_out' => 'text-bg-info',
        ];

        $statusLabels = [
            'active' => 'Aktif',
            'closed' => 'Ditutup',
            'expired' => 'Kedaluwarsa',
        ];

        $statusClasses = [
            'active' => 'text-bg-success',
            'closed' => 'text-bg-secondary',
            'expired' => 'text-bg-danger',
        ];

        $attendanceTypeLabel =
            $attendanceTypeLabels[$attendanceType]
            ?? ucfirst($attendanceType);

        $attendanceTypeClass =
            $attendanceTypeClasses[$attendanceType]
            ?? 'text-bg-secondary';

        $statusLabel =
            $statusLabels[$sessionStatus]
            ?? ucfirst($sessionStatus);

        $statusClass =
            $statusClasses[$sessionStatus]
            ?? 'text-bg-secondary';

        $creatorRoleLabel = match ($creator?->role) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            default => 'Pengguna',
        };

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l, d F Y');
            } catch (\Throwable) {
                return substr((string) $value, 0, 10);
            }
        };

        $formatDateTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y H:i');
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

        $initialQrPayload = null;

        if ($currentToken !== null) {
            $initialQrPayload = json_encode(
                [
                    'session' => (string) $attendanceSession->public_id,
                    'token' => $currentToken,
                ],
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
            );
        }

        $currentMoment = now(
            config('app.timezone')
        );

        $hasNotStarted =
            $attendanceSession->isActive()
            && $currentMoment->lessThan(
                $attendanceSession->start_time
            );

        $sessionStateLabel = match (true) {
            $attendanceSession->isClosed() =>
                'Sesi telah ditutup secara manual.',

            $attendanceSession->isExpired() =>
                'Sesi telah melewati waktu berakhir.',

            $hasNotStarted =>
                'Sesi aktif, tetapi belum memasuki waktu mulai.',

            $isUsable =>
                'Sesi sedang berlangsung dan dapat digunakan.',

            default =>
                'Sesi tidak dapat digunakan saat ini.',
        };
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Detail Sesi Presensi
            </h1>

            <p class="page-description">
                Informasi sesi dan QR Code dinamis
                berbasis TOTP.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a
                href="{{ route('attendance-sessions.index') }}"
                class="btn btn-outline-secondary"
            >
                Kembali ke Daftar
            </a>

            <a
                href="{{ route('attendance-sessions.create') }}"
                class="btn btn-primary"
            >
                Buka Sesi Baru
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-start gap-3"
        >
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge {{ $attendanceTypeClass }}">
                        {{ $attendanceTypeLabel }}
                    </span>

                    <span class="badge {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <h2 class="h4 fw-bold mb-2">
                    {{ $branch?->code ?? 'Cabang tidak tersedia' }}
                    @if ($branch !== null)
                        — {{ $branch->name }}
                    @endif
                </h2>

                <p class="text-secondary mb-0">
                    {{ $sessionStateLabel }}
                </p>
            </div>

            <div class="text-md-end">
                <div class="small text-secondary">
                    Jumlah presensi diterima
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $attendanceSession->attendances_count }}
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        QR Code Dinamis
                    </h2>

                    <p class="small text-secondary mb-0">
                        QR Code diperbarui mengikuti periode
                        token TOTP.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    <div
                        id="qr-status-alert"
                        class="alert {{
                            $isUsable
                                ? 'alert-success'
                                : 'alert-warning'
                        }}"
                        role="status"
                    >
                        @if ($isUsable)
                            QR Code aktif dan dapat dipindai.
                        @elseif ($hasNotStarted)
                            Sesi belum memasuki waktu mulai.
                        @else
                            QR Code tidak aktif.
                        @endif
                    </div>

                    <div class="qr-stage mb-4">
                        <div
                            id="qr-code-container"
                            class="qr-code-container"
                        >
                            <div
                                id="qr-placeholder"
                                class="text-center text-secondary px-3"
                            >
                                @if ($isUsable)
                                    Memuat QR Code...
                                @else
                                    QR Code tidak tersedia.
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <div class="small text-secondary mb-1">
                            Token saat ini
                        </div>

                        <div
                            id="token-value"
                            class="token-value mb-2"
                        >
                            {{ $currentToken ?? '------' }}
                        </div>

                        <div class="small text-secondary">
                            Token diperbarui dalam
                            <span
                                id="token-countdown"
                                class="fw-semibold"
                            >
                                {{
                                    $tokenSecondsRemaining !== null
                                        ? $tokenSecondsRemaining.' detik'
                                        : '-'
                                }}
                            </span>
                        </div>

                        <button
                            type="button"
                            id="refresh-qr-button"
                            class="btn btn-sm
                                btn-outline-primary mt-3"
                            @disabled(! $attendanceSession->isActive())
                        >
                            Perbarui QR Sekarang
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Informasi Sesi
                    </h2>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 mb-2">
                            UUID Publik
                        </dt>

                        <dd
                            class="col-sm-7 mb-3
                                session-identifier"
                        >
                            {{ $attendanceSession->public_id }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Jenis Presensi
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $attendanceTypeLabel }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Tanggal Sesi
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatDate(
                                    $attendanceSession->session_date
                                )
                            }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Waktu Mulai
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $attendanceSession->start_time
                                )
                            }}
                            WIB
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Waktu Berakhir
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatTime(
                                    $attendanceSession->end_time
                                )
                            }}
                            WIB
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Status
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            <span class="badge {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Pembuat Sesi
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            @if ($creator !== null)
                                <div class="fw-semibold">
                                    {{ $creator->name }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $creatorRoleLabel }}
                                </div>
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Dibuat Pada
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatDateTime(
                                    $attendanceSession->created_at
                                )
                            }}
                            WIB
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Ditutup Pada
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            @if (
                                $attendanceSession->closed_at !== null
                            )
                                {{
                                    $formatDateTime(
                                        $attendanceSession->closed_at
                                    )
                                }}
                                WIB
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Log Validasi
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            {{
                                (int) $attendanceSession
                                    ->validation_logs_count
                            }}
                            log
                        </dd>
                    </dl>
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Informasi Cabang dan Geofence
            </h2>
        </div>

        <div class="p-3 p-md-4">
            @if ($branch !== null)
                <div class="row g-4">
                    <div class="col-lg-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 mb-2">
                                Kode Cabang
                            </dt>

                            <dd class="col-sm-8 mb-3">
                                {{ $branch->code }}
                            </dd>

                            <dt class="col-sm-4 mb-2">
                                Nama Cabang
                            </dt>

                            <dd class="col-sm-8 mb-3">
                                {{ $branch->name }}
                            </dd>

                            <dt class="col-sm-4 mb-2">
                                Alamat
                            </dt>

                            <dd class="col-sm-8 mb-0">
                                {{ $branch->address }}
                            </dd>
                        </dl>
                    </div>

                    <div class="col-lg-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 mb-2">
                                Latitude
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $branch->latitude }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Longitude
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $branch->longitude }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Radius Geofence
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $branch->geofence_radius }}
                                meter
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Batas Akurasi
                            </dt>

                            <dd class="col-sm-7 mb-0">
                                {{ $branch->maximum_accuracy }}
                                meter
                            </dd>
                        </dl>
                    </div>
                </div>
            @else
                <div class="alert alert-danger mb-0">
                    Data cabang tidak tersedia.
                </div>
            @endif
        </div>
    </section>

    <section class="content-card">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Tindakan Sesi
            </h2>
        </div>

        <div class="p-3 p-md-4">
            @if ($attendanceSession->isActive())
                <div class="alert alert-warning">
                    Menutup sesi akan menghentikan penggunaan
                    QR Code secara langsung.
                </div>

                <form
                    method="POST"
                    action="{{
                        route(
                            'attendance-sessions.close',
                            $attendanceSession
                        )
                    }}"
                    onsubmit="
                        return confirm(
                            'Tutup sesi presensi ini?'
                        );
                    "
                >
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Tutup Sesi Presensi
                    </button>
                </form>
            @else
                <div class="alert alert-light border mb-0">
                    Sesi sudah tidak aktif dan tidak dapat
                    ditutup kembali.
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script
        src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"
    ></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const payloadEndpoint = @json(
                route(
                    'attendance-sessions.payload',
                    $attendanceSession
                )
            );

            const initialPayload = @json($initialQrPayload);
            const initialToken = @json($currentToken);

            let secondsRemaining = Number(
                @json($tokenSecondsRemaining ?? 0)
            );

            let pollingEnabled = @json(
                $attendanceSession->isActive()
            );

            let requestInProgress = false;

            const qrContainer = document.getElementById(
                'qr-code-container'
            );

            const tokenElement = document.getElementById(
                'token-value'
            );

            const countdownElement = document.getElementById(
                'token-countdown'
            );

            const statusAlert = document.getElementById(
                'qr-status-alert'
            );

            const refreshButton = document.getElementById(
                'refresh-qr-button'
            );

            const setStatus = function (
                message,
                alertClass
            ) {
                if (statusAlert === null) {
                    return;
                }

                statusAlert.className =
                    'alert ' + alertClass;

                statusAlert.textContent = message;
            };

            const clearQrCode = function (message) {
                if (qrContainer === null) {
                    return;
                }

                qrContainer.innerHTML = '';

                const placeholder =
                    document.createElement('div');

                placeholder.className =
                    'text-center text-secondary px-3';

                placeholder.textContent =
                    message ?? 'QR Code tidak tersedia.';

                qrContainer.appendChild(placeholder);
            };

            const renderQrCode = function (payload) {
                if (qrContainer === null) {
                    return;
                }

                if (typeof QRCode === 'undefined') {
                    clearQrCode(
                        'Pustaka QR Code tidak dapat dimuat.'
                    );

                    setStatus(
                        'QR Code tidak dapat dibuat pada browser.',
                        'alert-danger'
                    );

                    return;
                }

                qrContainer.innerHTML = '';

                new QRCode(
                    qrContainer,
                    {
                        text: payload,
                        width: 280,
                        height: 280,
                        correctLevel:
                            QRCode.CorrectLevel.M,
                    }
                );
            };

            const updateCountdown = function () {
                if (countdownElement === null) {
                    return;
                }

                if (! pollingEnabled) {
                    countdownElement.textContent = '-';

                    return;
                }

                if (secondsRemaining > 0) {
                    countdownElement.textContent =
                        secondsRemaining + ' detik';

                    return;
                }

                countdownElement.textContent =
                    'memperbarui...';
            };

            const stopPolling = function (
                message,
                alertClass
            ) {
                pollingEnabled = false;
                secondsRemaining = 0;

                clearQrCode(message);

                if (tokenElement !== null) {
                    tokenElement.textContent = '------';
                }

                updateCountdown();

                setStatus(
                    message,
                    alertClass
                );

                if (refreshButton !== null) {
                    refreshButton.disabled = true;
                }
            };

            const fetchPayload = async function () {
                if (
                    ! pollingEnabled
                    || requestInProgress
                ) {
                    return;
                }

                requestInProgress = true;

                try {
                    const response = await fetch(
                        payloadEndpoint,
                        {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            cache: 'no-store',
                        }
                    );

                    const body = await response
                        .json()
                        .catch(function () {
                            return {};
                        });

                    if (! response.ok) {
                        const message =
                            body.message
                            ?? 'Payload QR tidak dapat dimuat.';

                        if (
                            response.status === 409
                            || response.status === 410
                        ) {
                            stopPolling(
                                message,
                                'alert-danger'
                            );

                            return;
                        }

                        if (response.status === 422) {
                            clearQrCode(message);

                            if (tokenElement !== null) {
                                tokenElement.textContent =
                                    '------';
                            }

                            secondsRemaining = 5;

                            setStatus(
                                message,
                                'alert-warning'
                            );

                            updateCountdown();

                            return;
                        }

                        throw new Error(message);
                    }

                    const data = body.data ?? {};

                    if (
                        typeof data.qr_payload !== 'string'
                        || typeof data.token !== 'string'
                    ) {
                        throw new Error(
                            'Format payload QR tidak valid.'
                        );
                    }

                    renderQrCode(data.qr_payload);

                    if (tokenElement !== null) {
                        tokenElement.textContent =
                            data.token;
                    }

                    secondsRemaining = Math.max(
                        1,
                        Number(data.expires_in ?? 1)
                    );

                    setStatus(
                        'QR Code aktif dan dapat dipindai.',
                        'alert-success'
                    );

                    updateCountdown();
                } catch (error) {
                    secondsRemaining = 5;

                    setStatus(
                        error instanceof Error
                            ? error.message
                            : 'Terjadi kesalahan saat memperbarui QR Code.',
                        'alert-danger'
                    );

                    updateCountdown();
                } finally {
                    requestInProgress = false;
                }
            };

            if (
                initialPayload !== null
                && initialToken !== null
            ) {
                renderQrCode(initialPayload);

                if (tokenElement !== null) {
                    tokenElement.textContent =
                        initialToken;
                }
            }

            if (refreshButton !== null) {
                refreshButton.addEventListener(
                    'click',
                    function () {
                        secondsRemaining = 0;
                        fetchPayload();
                    }
                );
            }

            updateCountdown();

            if (pollingEnabled) {
                fetchPayload();
            }

            window.setInterval(
                function () {
                    if (! pollingEnabled) {
                        return;
                    }

                    if (secondsRemaining > 0) {
                        secondsRemaining--;
                        updateCountdown();
                    }

                    if (
                        secondsRemaining <= 0
                        && ! requestInProgress
                    ) {
                        fetchPayload();
                    }
                },
                1000
            );
        });
    </script>
@endpush