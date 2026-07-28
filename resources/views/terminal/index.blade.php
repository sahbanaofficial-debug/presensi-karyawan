<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <meta
        name="robots"
        content="noindex,nofollow"
    >

    <title>Terminal Presensi</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            color-scheme: light;
            --terminal-navy: #12233f;
            --terminal-blue: #2457d6;
            --terminal-soft: #eef4ff;
            --terminal-border: #d8e1ef;
            --terminal-muted: #64748b;
            --terminal-success: #16784a;
            --terminal-warning: #9a6700;
            --terminal-danger: #b42318;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(36, 87, 214, 0.16),
                    transparent 32rem
                ),
                linear-gradient(
                    145deg,
                    #f7f9fc,
                    #edf2f9
                );
            color: var(--terminal-navy);
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .terminal-shell {
            width: min(100%, 92rem);
            min-height: 100vh;
            margin: 0 auto;
            padding: clamp(1rem, 2vw, 2rem);
        }

        .terminal-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .terminal-brand {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .terminal-brand-icon {
            display: inline-flex;
            width: 3rem;
            height: 3rem;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: var(--terminal-navy);
            color: #ffffff;
            font-size: 1.35rem;
        }

        .terminal-brand-title {
            margin: 0;
            font-size: clamp(1.05rem, 2vw, 1.35rem);
            font-weight: 800;
        }

        .terminal-brand-copy {
            margin: 0.15rem 0 0;
            color: var(--terminal-muted);
            font-size: 0.875rem;
        }

        .terminal-clock {
            min-width: 10rem;
            text-align: right;
        }

        .terminal-clock-time {
            font-variant-numeric: tabular-nums;
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            font-weight: 800;
        }

        .terminal-clock-date {
            color: var(--terminal-muted);
            font-size: 0.8rem;
        }

        .terminal-card {
            overflow: hidden;
            border: 1px solid var(--terminal-border);
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, 0.96);
            box-shadow:
                0 1.25rem 3.5rem
                rgba(18, 35, 63, 0.1);
        }

        .terminal-card-body {
            padding: clamp(1.1rem, 2.5vw, 2rem);
        }

        .terminal-state {
            display: none;
        }

        .terminal-state.is-active {
            display: block;
        }

        .activation-layout {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                minmax(18rem, 0.8fr);
            gap: clamp(1.5rem, 3vw, 3rem);
            align-items: center;
        }

        .activation-eyebrow {
            margin-bottom: 0.65rem;
            color: var(--terminal-blue);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .activation-title {
            max-width: 42rem;
            margin: 0;
            font-size: clamp(2rem, 5vw, 4.25rem);
            font-weight: 850;
            letter-spacing: -0.045em;
            line-height: 1.02;
        }

        .activation-copy {
            max-width: 40rem;
            margin: 1.15rem 0 0;
            color: var(--terminal-muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .activation-form-card {
            border: 1px solid var(--terminal-border);
            border-radius: 1rem;
            background: #ffffff;
            padding: clamp(1rem, 2vw, 1.5rem);
        }

        .form-label {
            color: var(--terminal-navy);
            font-weight: 700;
        }

        .form-control {
            min-height: 3rem;
            border-color: var(--terminal-border);
        }

        .form-control:focus {
            border-color: var(--terminal-blue);
            box-shadow:
                0 0 0 0.25rem
                rgba(36, 87, 214, 0.14);
        }

        .activation-code-input {
            letter-spacing: 0.28em;
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                monospace;
            font-size: 1.1rem;
            font-weight: 800;
            text-align: center;
        }

        .btn-terminal-primary {
            min-height: 3rem;
            border: 0;
            background: var(--terminal-blue);
            color: #ffffff;
            font-weight: 800;
        }

        .btn-terminal-primary:hover,
        .btn-terminal-primary:focus {
            background: #1948bd;
            color: #ffffff;
        }

        .display-layout {
            display: grid;
            grid-template-columns:
                minmax(20rem, 0.8fr)
                minmax(0, 1.2fr);
            gap: clamp(1rem, 2vw, 1.75rem);
        }

        .terminal-info-panel {
            display: flex;
            min-height: 34rem;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 1rem;
            background:
                linear-gradient(
                    150deg,
                    var(--terminal-navy),
                    #1d3e70
                );
            color: #ffffff;
            padding: clamp(1.25rem, 3vw, 2.25rem);
        }

        .terminal-status-pill {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid
                rgba(255, 255, 255, 0.24);
            border-radius: 999px;
            background:
                rgba(255, 255, 255, 0.1);
            padding: 0.48rem 0.75rem;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .terminal-info-name {
            margin: 1.5rem 0 0;
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 850;
            letter-spacing: -0.04em;
            line-height: 1.04;
        }

        .terminal-info-branch {
            margin: 0.75rem 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 1rem;
        }

        .terminal-info-meta {
            display: grid;
            gap: 0.8rem;
            margin-top: 2rem;
        }

        .terminal-info-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            border-top: 1px solid
                rgba(255, 255, 255, 0.13);
            padding-top: 0.8rem;
            font-size: 0.82rem;
        }

        .terminal-info-label {
            color: rgba(255, 255, 255, 0.62);
        }

        .terminal-info-value {
            max-width: 65%;
            overflow-wrap: anywhere;
            text-align: right;
            font-weight: 700;
        }

        .terminal-info-actions {
            display: flex;
            gap: 0.7rem;
            margin-top: 1.5rem;
        }

        .terminal-info-actions .btn {
            font-size: 0.8rem;
            font-weight: 700;
        }

        .qr-panel {
            display: flex;
            min-height: 34rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--terminal-border);
            border-radius: 1rem;
            background: #ffffff;
            padding: clamp(1.25rem, 3vw, 2.25rem);
            text-align: center;
        }

        .qr-panel-title {
            margin: 0;
            font-size: clamp(1.3rem, 2vw, 1.8rem);
            font-weight: 850;
        }

        .qr-panel-copy {
            margin: 0.4rem 0 1.25rem;
            color: var(--terminal-muted);
            font-size: 0.9rem;
        }

        .qr-stage {
            display: flex;
            width: min(100%, 25rem);
            aspect-ratio: 1;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--terminal-border);
            border-radius: 1.25rem;
            background: #ffffff;
            padding: clamp(1rem, 3vw, 2rem);
        }

        .qr-stage img,
        .qr-stage canvas {
            width: 100% !important;
            height: auto !important;
            image-rendering: pixelated;
        }

        .qr-placeholder {
            max-width: 20rem;
            color: var(--terminal-muted);
            line-height: 1.6;
        }

        .qr-status {
            width: min(100%, 32rem);
            margin-top: 1.25rem;
            border-radius: 0.8rem;
            padding: 0.8rem 1rem;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .qr-status.status-info {
            background: var(--terminal-soft);
            color: var(--terminal-blue);
        }

        .qr-status.status-success {
            background: #eaf8f1;
            color: var(--terminal-success);
        }

        .qr-status.status-warning {
            background: #fff7df;
            color: var(--terminal-warning);
        }

        .qr-status.status-danger {
            background: #fff0ee;
            color: var(--terminal-danger);
        }

        .qr-countdown {
            margin-top: 0.7rem;
            color: var(--terminal-muted);
            font-size: 0.78rem;
        }

        .terminal-alert {
            display: none;
            margin-bottom: 1rem;
        }

        .terminal-alert.is-visible {
            display: block;
        }

        .terminal-offline {
            display: none;
            position: fixed;
            z-index: 1050;
            right: 1rem;
            bottom: 1rem;
            max-width: 22rem;
            border-radius: 0.85rem;
            background: var(--terminal-danger);
            color: #ffffff;
            padding: 0.85rem 1rem;
            box-shadow:
                0 0.75rem 2rem
                rgba(18, 35, 63, 0.2);
            font-size: 0.85rem;
            font-weight: 700;
        }

        .terminal-offline.is-visible {
            display: block;
        }

        @media (max-width: 991.98px) {
            .activation-layout,
            .display-layout {
                grid-template-columns: 1fr;
            }

            .terminal-info-panel,
            .qr-panel {
                min-height: auto;
            }
        }

        @media (max-width: 575.98px) {
            .terminal-topbar {
                align-items: flex-start;
            }

            .terminal-clock {
                min-width: auto;
            }

            .terminal-brand-copy,
            .terminal-clock-date {
                display: none;
            }

            .terminal-shell {
                padding: 0.75rem;
            }

            .terminal-card-body {
                padding: 1rem;
            }

            .activation-form-card {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <main class="terminal-shell">
        <header class="terminal-topbar">
            <div class="terminal-brand">
                <span
                    class="terminal-brand-icon"
                    aria-hidden="true"
                >
                    <i class="bi bi-qr-code"></i>
                </span>

                <div>
                    <h1 class="terminal-brand-title">
                        Terminal Presensi
                    </h1>

                    <p class="terminal-brand-copy">
                        Perangkat QR otomatis cabang
                    </p>
                </div>
            </div>

            <div class="terminal-clock">
                <div
                    id="terminal-clock-time"
                    class="terminal-clock-time"
                >
                    --:--:--
                </div>

                <div
                    id="terminal-clock-date"
                    class="terminal-clock-date"
                >
                    Memuat waktu server lokal
                </div>
            </div>
        </header>

        <div
            id="terminal-alert"
            class="terminal-alert alert"
            role="alert"
            aria-live="polite"
        ></div>

        <section class="terminal-card">
            <div class="terminal-card-body">
                <div
                    id="activation-state"
                    class="terminal-state is-active"
                >
                    <div class="activation-layout">
                        <div>
                            <div class="activation-eyebrow">
                                Aktivasi Perangkat
                            </div>

                            <h2 class="activation-title">
                                Hubungkan layar ini ke terminal cabang.
                            </h2>

                            <p class="activation-copy">
                                Masukkan Public ID terminal dan kode
                                aktivasi delapan digit yang diberikan HRD.
                                Setelah berhasil, perangkat akan mengambil
                                QR presensi otomatis dari server.
                            </p>
                        </div>

                        <div class="activation-form-card">
                            <form
                                id="terminal-activation-form"
                                novalidate
                            >
                                <div class="mb-3">
                                    <label
                                        for="terminal-public-id"
                                        class="form-label"
                                    >
                                        Public ID Terminal
                                    </label>

                                    <input
                                        type="text"
                                        id="terminal-public-id"
                                        class="form-control font-monospace"
                                        inputmode="text"
                                        autocomplete="off"
                                        spellcheck="false"
                                        placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                        required
                                    >
                                </div>

                                <div class="mb-4">
                                    <label
                                        for="terminal-activation-code"
                                        class="form-label"
                                    >
                                        Kode Aktivasi
                                    </label>

                                    <input
                                        type="text"
                                        id="terminal-activation-code"
                                        class="form-control activation-code-input"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        pattern="[0-9]{8}"
                                        maxlength="8"
                                        placeholder="00000000"
                                        required
                                    >

                                    <div class="form-text">
                                        Kode berlaku selama 15 menit
                                        dan hanya dapat digunakan sekali.
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    id="terminal-activation-button"
                                    class="btn btn-terminal-primary w-100"
                                >
                                    <span
                                        id="terminal-activation-spinner"
                                        class="spinner-border spinner-border-sm me-2 d-none"
                                        aria-hidden="true"
                                    ></span>

                                    Aktifkan Perangkat
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    id="display-state"
                    class="terminal-state"
                >
                    <div class="display-layout">
                        <section class="terminal-info-panel">
                            <div>
                                <span class="terminal-status-pill">
                                    <i
                                        class="bi bi-broadcast-pin"
                                        aria-hidden="true"
                                    ></i>

                                    Terminal Terhubung
                                </span>

                                <h2
                                    id="terminal-name"
                                    class="terminal-info-name"
                                >
                                    Terminal
                                </h2>

                                <p
                                    id="terminal-branch"
                                    class="terminal-info-branch"
                                >
                                    Cabang
                                </p>

                                <div class="terminal-info-meta">
                                    <div class="terminal-info-row">
                                        <span class="terminal-info-label">
                                            Public ID
                                        </span>

                                        <span
                                            id="terminal-identity"
                                            class="terminal-info-value font-monospace"
                                        >
                                            -
                                        </span>
                                    </div>

                                    <div class="terminal-info-row">
                                        <span class="terminal-info-label">
                                            Status sesi
                                        </span>

                                        <span
                                            id="terminal-session-status"
                                            class="terminal-info-value"
                                        >
                                            Memeriksa
                                        </span>
                                    </div>

                                    <div class="terminal-info-row">
                                        <span class="terminal-info-label">
                                            Pembaruan terakhir
                                        </span>

                                        <span
                                            id="terminal-last-update"
                                            class="terminal-info-value"
                                        >
                                            -
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="terminal-info-actions">
                                <button
                                    type="button"
                                    id="terminal-refresh-button"
                                    class="btn btn-light"
                                >
                                    <i
                                        class="bi bi-arrow-clockwise me-1"
                                        aria-hidden="true"
                                    ></i>

                                    Perbarui
                                </button>

                                <button
                                    type="button"
                                    id="terminal-reset-button"
                                    class="btn btn-outline-light"
                                >
                                    <i
                                        class="bi bi-box-arrow-right me-1"
                                        aria-hidden="true"
                                    ></i>

                                    Lepas Perangkat
                                </button>
                            </div>
                        </section>

                        <section class="qr-panel">
                            <h2 class="qr-panel-title">
                                Pindai untuk Presensi
                            </h2>

                            <p class="qr-panel-copy">
                                QR berubah otomatis mengikuti periode
                                keamanan server.
                            </p>

                            <div
                                id="terminal-qr-stage"
                                class="qr-stage"
                                aria-live="polite"
                            >
                                <div class="qr-placeholder">
                                    Menghubungkan terminal ke server.
                                </div>
                            </div>

                            <div
                                id="terminal-qr-status"
                                class="qr-status status-info"
                            >
                                Memeriksa sesi presensi aktif.
                            </div>

                            <div class="qr-countdown">
                                Pembaruan berikutnya:
                                <strong id="terminal-countdown">
                                    -
                                </strong>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div
        id="terminal-offline"
        class="terminal-offline"
        role="status"
        aria-live="polite"
    >
        Koneksi jaringan terputus. Terminal akan mencoba
        kembali ketika jaringan tersedia.
    </div>

    <script
        src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"
    ></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            'use strict';

            const activationEndpoint = @json(
                route('terminal.activate')
            );

            const identityEndpoint = @json(
                route('terminal.identity')
            );

            const qrPayloadEndpoint = @json(
                route('terminal.qr-payload')
            );

            const storageKey =
                'presensi.branch-terminal.credentials.v1';

            const defaultRefreshSeconds = 15;

            const activationState =
                document.getElementById(
                    'activation-state'
                );

            const displayState =
                document.getElementById(
                    'display-state'
                );

            const activationForm =
                document.getElementById(
                    'terminal-activation-form'
                );

            const publicIdInput =
                document.getElementById(
                    'terminal-public-id'
                );

            const activationCodeInput =
                document.getElementById(
                    'terminal-activation-code'
                );

            const activationButton =
                document.getElementById(
                    'terminal-activation-button'
                );

            const activationSpinner =
                document.getElementById(
                    'terminal-activation-spinner'
                );

            const alertElement =
                document.getElementById(
                    'terminal-alert'
                );

            const terminalName =
                document.getElementById(
                    'terminal-name'
                );

            const terminalBranch =
                document.getElementById(
                    'terminal-branch'
                );

            const terminalIdentity =
                document.getElementById(
                    'terminal-identity'
                );

            const terminalSessionStatus =
                document.getElementById(
                    'terminal-session-status'
                );

            const terminalLastUpdate =
                document.getElementById(
                    'terminal-last-update'
                );

            const qrStage =
                document.getElementById(
                    'terminal-qr-stage'
                );

            const qrStatus =
                document.getElementById(
                    'terminal-qr-status'
                );

            const countdownElement =
                document.getElementById(
                    'terminal-countdown'
                );

            const refreshButton =
                document.getElementById(
                    'terminal-refresh-button'
                );

            const resetButton =
                document.getElementById(
                    'terminal-reset-button'
                );

            const offlineNotice =
                document.getElementById(
                    'terminal-offline'
                );

            const clockTime =
                document.getElementById(
                    'terminal-clock-time'
                );

            const clockDate =
                document.getElementById(
                    'terminal-clock-date'
                );

            let credentials = null;
            let pollingEnabled = false;
            let requestInProgress = false;
            let secondsRemaining = 0;
            let pollTimer = null;

            const uuidPattern =
                /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

            const tokenPattern =
                /^[A-Za-z0-9_-]{32,256}$/;

            const normalizeCredentials = function (
                candidate
            ) {
                if (
                    candidate === null
                    || typeof candidate !== 'object'
                ) {
                    return null;
                }

                const publicId =
                    typeof candidate.public_id === 'string'
                        ? candidate.public_id
                            .trim()
                            .toLowerCase()
                        : '';

                const deviceToken =
                    typeof candidate.device_token === 'string'
                        ? candidate.device_token.trim()
                        : '';

                if (
                    ! uuidPattern.test(publicId)
                    || ! tokenPattern.test(deviceToken)
                ) {
                    return null;
                }

                return {
                    public_id: publicId,
                    device_token: deviceToken,
                };
            };

            const loadCredentials = function () {
                try {
                    const rawValue =
                        window.localStorage.getItem(
                            storageKey
                        );

                    if (rawValue === null) {
                        return null;
                    }

                    const parsedValue = JSON.parse(
                        rawValue
                    );

                    const normalized =
                        normalizeCredentials(
                            parsedValue
                        );

                    if (normalized === null) {
                        window.localStorage.removeItem(
                            storageKey
                        );
                    }

                    return normalized;
                } catch (error) {
                    window.localStorage.removeItem(
                        storageKey
                    );

                    return null;
                }
            };

            const saveCredentials = function (
                nextCredentials
            ) {
                const normalized =
                    normalizeCredentials(
                        nextCredentials
                    );

                if (normalized === null) {
                    throw new Error(
                        'Credential terminal dari server tidak valid.'
                    );
                }

                window.localStorage.setItem(
                    storageKey,
                    JSON.stringify(
                        normalized
                    )
                );

                credentials = normalized;
            };

            const clearCredentials = function () {
                window.localStorage.removeItem(
                    storageKey
                );

                credentials = null;
            };

            const terminalHeaders = function () {
                if (credentials === null) {
                    return {
                        'Accept': 'application/json',
                    };
                }

                return {
                    'Accept': 'application/json',
                    'X-Terminal-ID':
                        credentials.public_id,

                    'X-Terminal-Token':
                        credentials.device_token,
                };
            };

            const parseJson = async function (
                response
            ) {
                return response
                    .json()
                    .catch(function () {
                        return {};
                    });
            };

            const firstValidationMessage = function (
                body
            ) {
                const errors =
                    body !== null
                    && typeof body === 'object'
                        ? body.errors
                        : null;

                if (
                    errors === null
                    || typeof errors !== 'object'
                ) {
                    return null;
                }

                for (
                    const messages
                    of Object.values(errors)
                ) {
                    if (
                        Array.isArray(messages)
                        && typeof messages[0]
                            === 'string'
                    ) {
                        return messages[0];
                    }
                }

                return null;
            };

            const responseMessage = function (
                body,
                fallback
            ) {
                return firstValidationMessage(body)
                    ?? (
                        typeof body.message === 'string'
                            ? body.message
                            : fallback
                    );
            };

            const showAlert = function (
                message,
                type
            ) {
                if (alertElement === null) {
                    return;
                }

                alertElement.className = [
                    'terminal-alert alert is-visible alert-',
                    type,
                ].join('');

                alertElement.textContent = message;
            };

            const hideAlert = function () {
                if (alertElement === null) {
                    return;
                }

                alertElement.className =
                    'terminal-alert alert';

                alertElement.textContent = '';
            };

            const setActivationBusy = function (
                busy
            ) {
                if (activationButton !== null) {
                    activationButton.disabled = busy;
                }

                if (activationSpinner !== null) {
                    activationSpinner.classList.toggle(
                        'd-none',
                        ! busy
                    );
                }
            };

            const showActivationState = function (
                message = null
            ) {
                pollingEnabled = false;
                secondsRemaining = 0;

                if (activationState !== null) {
                    activationState.classList.add(
                        'is-active'
                    );
                }

                if (displayState !== null) {
                    displayState.classList.remove(
                        'is-active'
                    );
                }

                clearQrCode(
                    'Aktifkan perangkat untuk menampilkan QR.'
                );

                if (message !== null) {
                    showAlert(
                        message,
                        'warning'
                    );
                }
            };

            const showDisplayState = function () {
                hideAlert();

                if (activationState !== null) {
                    activationState.classList.remove(
                        'is-active'
                    );
                }

                if (displayState !== null) {
                    displayState.classList.add(
                        'is-active'
                    );
                }
            };

            const updateQrStatus = function (
                message,
                type
            ) {
                if (qrStatus === null) {
                    return;
                }

                qrStatus.className =
                    'qr-status status-' + type;

                qrStatus.textContent = message;
            };

            const clearQrCode = function (
                message
            ) {
                if (qrStage === null) {
                    return;
                }

                qrStage.innerHTML = '';

                const placeholder =
                    document.createElement('div');

                placeholder.className =
                    'qr-placeholder';

                placeholder.textContent = message;

                qrStage.appendChild(
                    placeholder
                );
            };

            const renderQrCode = function (
                payload
            ) {
                if (qrStage === null) {
                    return;
                }

                if (
                    typeof window.QRCode
                    === 'undefined'
                ) {
                    clearQrCode(
                        'Pustaka QR Code tidak dapat dimuat.'
                    );

                    updateQrStatus(
                        'QR tidak dapat dibuat pada browser.',
                        'danger'
                    );

                    return;
                }

                qrStage.innerHTML = '';

                new window.QRCode(
                    qrStage,
                    {
                        text: payload,
                        width: 340,
                        height: 340,

                        correctLevel:
                            window.QRCode
                                .CorrectLevel
                                .M,
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

            const setTerminalIdentity = function (
                body
            ) {
                const data =
                    body !== null
                    && typeof body === 'object'
                        ? body.data ?? {}
                        : {};

                const terminal =
                    data.terminal
                    ?? body.terminal
                    ?? data;

                const branch =
                    data.branch
                    ?? terminal.branch
                    ?? {};

                const publicId =
                    typeof terminal.public_id
                        === 'string'
                        ? terminal.public_id
                        : credentials?.public_id
                            ?? '-';

                const name =
                    typeof terminal.name === 'string'
                        ? terminal.name
                        : 'Terminal Cabang';

                const branchCode =
                    typeof branch.code === 'string'
                        ? branch.code
                        : '';

                const branchName =
                    typeof branch.name === 'string'
                        ? branch.name
                        : 'Cabang terminal';

                if (terminalName !== null) {
                    terminalName.textContent = name;
                }

                if (terminalBranch !== null) {
                    terminalBranch.textContent =
                        branchCode !== ''
                            ? [
                                branchCode,
                                branchName,
                            ].join(' - ')
                            : branchName;
                }

                if (terminalIdentity !== null) {
                    terminalIdentity.textContent =
                        publicId;
                }
            };

            const updateLastRefresh = function () {
                if (terminalLastUpdate === null) {
                    return;
                }

                terminalLastUpdate.textContent =
                    new Intl.DateTimeFormat(
                        'id-ID',
                        {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                        }
                    ).format(
                        new Date()
                    );
            };

            const stopForInvalidCredential = function (
                message
            ) {
                clearCredentials();
                pollingEnabled = false;
                secondsRemaining = 0;

                showActivationState(
                    message
                );

                if (publicIdInput !== null) {
                    publicIdInput.value = '';
                }

                if (activationCodeInput !== null) {
                    activationCodeInput.value = '';
                }
            };

            const isCredentialFailure = function (
                response
            ) {
                return response.status === 401
                    || response.status === 403
                    || response.status === 404
                    || response.status === 410;
            };

            const verifyIdentity = async function () {
                if (credentials === null) {
                    return false;
                }

                const response = await fetch(
                    identityEndpoint,
                    {
                        method: 'GET',
                        headers: terminalHeaders(),
                        credentials: 'omit',
                        cache: 'no-store',
                    }
                );

                const body = await parseJson(
                    response
                );

                if (! response.ok) {
                    const message = responseMessage(
                        body,
                        'Credential terminal tidak lagi valid.'
                    );

                    if (isCredentialFailure(response)) {
                        stopForInvalidCredential(
                            message
                        );

                        return false;
                    }

                    throw new Error(message);
                }

                setTerminalIdentity(body);
                showDisplayState();

                return true;
            };

            const activateTerminal = async function () {
                const publicId =
                    publicIdInput?.value
                        .trim()
                        .toLowerCase()
                    ?? '';

                const activationCode =
                    activationCodeInput?.value
                        .replace(/\D/g, '')
                    ?? '';

                if (! uuidPattern.test(publicId)) {
                    throw new Error(
                        'Public ID terminal tidak valid.'
                    );
                }

                if (! /^\d{8}$/.test(activationCode)) {
                    throw new Error(
                        'Kode aktivasi harus terdiri dari delapan digit.'
                    );
                }

                const response = await fetch(
                    activationEndpoint,
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'Content-Type':
                                'application/json',
                        },

                        credentials: 'omit',
                        cache: 'no-store',

                        body: JSON.stringify({
                            public_id: publicId,

                            activation_code:
                                activationCode,
                        }),
                    }
                );

                const body = await parseJson(
                    response
                );

                if (! response.ok) {
                    throw new Error(
                        responseMessage(
                            body,
                            'Aktivasi terminal gagal.'
                        )
                    );
                }

                const data =
                    body !== null
                    && typeof body === 'object'
                        ? body.data ?? {}
                        : {};

                const terminal =
                    data.terminal
                    ?? body.terminal
                    ?? {};

                const returnedPublicId =
                    typeof terminal.public_id
                        === 'string'
                        ? terminal.public_id
                        : (
                            typeof data.public_id
                                === 'string'
                                ? data.public_id
                                : publicId
                        );

                const deviceToken =
                    typeof data.device_token
                        === 'string'
                        ? data.device_token
                        : (
                            typeof terminal.device_token
                                === 'string'
                                ? terminal.device_token
                                : ''
                        );

                saveCredentials({
                    public_id:
                        returnedPublicId,

                    device_token:
                        deviceToken,
                });

                if (activationCodeInput !== null) {
                    activationCodeInput.value = '';
                }

                setTerminalIdentity(body);
                showDisplayState();

                pollingEnabled = true;
                secondsRemaining = 0;

                await fetchQrPayload();
            };

            const fetchQrPayload = async function () {
                if (
                    ! pollingEnabled
                    || requestInProgress
                    || credentials === null
                ) {
                    return;
                }

                requestInProgress = true;

                if (refreshButton !== null) {
                    refreshButton.disabled = true;
                }

                try {
                    const response = await fetch(
                        qrPayloadEndpoint,
                        {
                            method: 'GET',
                            headers: terminalHeaders(),
                            credentials: 'omit',
                            cache: 'no-store',
                        }
                    );

                    const body = await parseJson(
                        response
                    );

                    if (! response.ok) {
                        const message = responseMessage(
                            body,
                            'QR terminal tidak dapat dimuat.'
                        );

                        if (isCredentialFailure(response)) {
                            stopForInvalidCredential(
                                message
                            );

                            return;
                        }

                        throw new Error(message);
                    }

                    const data =
                        body !== null
                        && typeof body === 'object'
                            ? body.data ?? {}
                            : {};

                    const refreshAfter =
                        Number(
                            data.refresh_after
                            ?? body.refresh_after
                            ?? defaultRefreshSeconds
                        );

                    secondsRemaining = Math.max(
                        1,
                        Number.isFinite(refreshAfter)
                            ? Math.floor(refreshAfter)
                            : defaultRefreshSeconds
                    );

                    if (data.available !== true) {
                        clearQrCode(
                            typeof body.message
                                === 'string'
                                ? body.message
                                : 'Belum ada sesi presensi aktif.'
                        );

                        updateQrStatus(
                            'Menunggu sesi presensi otomatis.',
                            'warning'
                        );

                        if (
                            terminalSessionStatus
                            !== null
                        ) {
                            terminalSessionStatus
                                .textContent =
                                'Menunggu sesi';
                        }

                        updateLastRefresh();
                        updateCountdown();

                        return;
                    }

                    if (
                        typeof data.qr_payload
                        !== 'string'
                        || data.qr_payload.trim() === ''
                    ) {
                        throw new Error(
                            'Format payload QR dari server tidak valid.'
                        );
                    }

                    renderQrCode(
                        data.qr_payload
                    );

                    const payloadSeconds =
                        Number(
                            data.seconds_remaining
                            ?? data.expires_in
                            ?? secondsRemaining
                        );

                    if (
                        Number.isFinite(
                            payloadSeconds
                        )
                    ) {
                        secondsRemaining = Math.max(
                            1,
                            Math.floor(
                                payloadSeconds
                            )
                        );
                    }

                    updateQrStatus(
                        'QR aktif dan siap dipindai.',
                        'success'
                    );

                    if (
                        terminalSessionStatus
                        !== null
                    ) {
                        terminalSessionStatus
                            .textContent =
                            'QR aktif';
                    }

                    updateLastRefresh();
                    updateCountdown();
                } catch (error) {
                    secondsRemaining =
                        defaultRefreshSeconds;

                    clearQrCode(
                        'QR sementara tidak dapat diperbarui.'
                    );

                    updateQrStatus(
                        error instanceof Error
                            ? error.message
                            : 'Terjadi kesalahan jaringan.',
                        'danger'
                    );

                    if (
                        terminalSessionStatus
                        !== null
                    ) {
                        terminalSessionStatus
                            .textContent =
                            'Gangguan koneksi';
                    }

                    updateCountdown();
                } finally {
                    requestInProgress = false;

                    if (refreshButton !== null) {
                        refreshButton.disabled = false;
                    }
                }
            };

            const updateClock = function () {
                const now = new Date();

                if (clockTime !== null) {
                    clockTime.textContent =
                        new Intl.DateTimeFormat(
                            'id-ID',
                            {
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false,
                            }
                        ).format(now);
                }

                if (clockDate !== null) {
                    clockDate.textContent =
                        new Intl.DateTimeFormat(
                            'id-ID',
                            {
                                weekday: 'long',
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric',
                            }
                        ).format(now);
                }
            };

            const updateConnectivity = function () {
                if (offlineNotice === null) {
                    return;
                }

                offlineNotice.classList.toggle(
                    'is-visible',
                    ! window.navigator.onLine
                );
            };

            if (activationCodeInput !== null) {
                activationCodeInput.addEventListener(
                    'input',
                    function () {
                        activationCodeInput.value =
                            activationCodeInput.value
                                .replace(/\D/g, '')
                                .slice(0, 8);
                    }
                );
            }

            if (activationForm !== null) {
                activationForm.addEventListener(
                    'submit',
                    async function (event) {
                        event.preventDefault();
                        hideAlert();
                        setActivationBusy(true);

                        try {
                            await activateTerminal();
                        } catch (error) {
                            showAlert(
                                error instanceof Error
                                    ? error.message
                                    : 'Aktivasi terminal gagal.',
                                'danger'
                            );
                        } finally {
                            setActivationBusy(false);
                        }
                    }
                );
            }

            if (refreshButton !== null) {
                refreshButton.addEventListener(
                    'click',
                    function () {
                        secondsRemaining = 0;
                        fetchQrPayload();
                    }
                );
            }

            if (resetButton !== null) {
                resetButton.addEventListener(
                    'click',
                    function () {
                        const confirmed =
                            window.confirm(
                                'Lepas credential terminal dari perangkat ini?'
                            );

                        if (! confirmed) {
                            return;
                        }

                        clearCredentials();

                        showActivationState(
                            'Credential terminal telah dihapus dari perangkat.'
                        );
                    }
                );
            }

            window.addEventListener(
                'online',
                function () {
                    updateConnectivity();

                    if (
                        pollingEnabled
                        && credentials !== null
                    ) {
                        secondsRemaining = 0;
                        fetchQrPayload();
                    }
                }
            );

            window.addEventListener(
                'offline',
                updateConnectivity
            );

            pollTimer = window.setInterval(
                function () {
                    updateClock();
                    updateConnectivity();

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
                        fetchQrPayload();
                    }
                },
                1000
            );

            window.addEventListener(
                'beforeunload',
                function () {
                    if (pollTimer !== null) {
                        window.clearInterval(
                            pollTimer
                        );
                    }
                }
            );

            const initialize = async function () {
                updateClock();
                updateConnectivity();

                credentials = loadCredentials();

                if (credentials === null) {
                    showActivationState();

                    return;
                }

                try {
                    const identityIsValid =
                        await verifyIdentity();

                    if (! identityIsValid) {
                        return;
                    }

                    pollingEnabled = true;
                    secondsRemaining = 0;

                    await fetchQrPayload();
                } catch (error) {
                    showDisplayState();

                    pollingEnabled = true;
                    secondsRemaining =
                        defaultRefreshSeconds;

                    updateQrStatus(
                        error instanceof Error
                            ? error.message
                            : 'Terminal belum dapat terhubung ke server.',
                        'danger'
                    );

                    clearQrCode(
                        'Menunggu koneksi ke server.'
                    );

                    updateCountdown();
                }
            };

            initialize();
        });
    </script>
</body>
</html>