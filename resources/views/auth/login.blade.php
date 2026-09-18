<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>Login | Sistem Presensi Karyawan</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            /*
             * PT Gadai Ogan Baru — Login Design Tokens
             */
            --brand-50: #fff6ef;
            --brand-100: #fde8d7;
            --brand-200: #fac8a5;
            --brand-300: #f4a46d;
            --brand-500: #e56a1f;
            --brand-600: #c95314;
            --brand-700: #9f3f12;

            --neutral-0: #ffffff;
            --neutral-25: #fbfbfc;
            --neutral-50: #f6f7f8;
            --neutral-100: #eef0f2;
            --neutral-200: #e2e5e8;
            --neutral-300: #cfd4d9;
            --neutral-500: #7a828b;
            --neutral-600: #68717a;
            --neutral-700: #4e565e;
            --neutral-800: #353b41;
            --neutral-900: #252a2f;

            --success-50: #edf8f2;
            --success-500: #238b5e;
            --success-700: #176b47;

            --danger-50: #fff1ef;
            --danger-500: #c74632;
            --danger-700: #913223;

            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.5rem;
            --space-6: 2rem;
            --space-8: 3rem;
            --space-10: 4rem;

            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.25rem;
            --radius-pill: 999px;

            --shadow-xs:
                0 1px 2px rgba(37, 42, 47, 0.05);
            --shadow-sm:
                0 8px 24px rgba(37, 42, 47, 0.07);
            --shadow-md:
                0 18px 48px rgba(37, 42, 47, 0.10);

            --bs-primary: var(--brand-500);
            --bs-primary-rgb: 229, 106, 31;
            --bs-danger: var(--danger-500);
            --bs-body-color: var(--neutral-900);
            --bs-body-bg: var(--neutral-50);
            --bs-border-color: var(--neutral-200);
            --bs-font-sans-serif:
                "Plus Jakarta Sans",
                ui-sans-serif,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            background: var(--neutral-50);
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--neutral-900);
            background: var(--neutral-50);
            font-family: var(--bs-font-sans-serif);
            font-size: 0.9375rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        :focus-visible {
            outline: 0.1875rem solid rgba(229, 106, 31, 0.28);
            outline-offset: 0.125rem;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .login-topbar {
            min-height: 4.5rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--neutral-200);
            background: var(--neutral-0);
        }

        .login-topbar-inner {
            width: 100%;
            max-width: 82rem;
            margin: 0 auto;
            padding: var(--space-3) var(--space-4);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-4);
        }

        .brand {
            display: inline-flex;
            min-width: 0;
            align-items: center;
            gap: var(--space-3);
            color: var(--neutral-900);
            text-decoration: none;
        }

        .brand:hover,
        .brand:focus {
            color: var(--neutral-900);
        }

        .brand-mark {
            display: inline-flex;
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 2.75rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--neutral-0);
            background: var(--brand-500);
            box-shadow: var(--shadow-xs);
            font-size: 0.875rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .brand-mark {
            background: transparent;
            box-shadow: none;
        }

        .brand-mark img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-copy {
            min-width: 0;
        }

        .brand-title {
            display: block;
            color: var(--neutral-900);
            font-size: 0.9375rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .brand-subtitle {
            display: block;
            margin-top: 0.125rem;
            color: var(--neutral-600);
            font-size: 0.6875rem;
            font-weight: 500;
            line-height: 1.35;
        }

        .security-label {
            display: none;
            align-items: center;
            gap: var(--space-2);
            color: var(--neutral-600);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .security-label i {
            color: var(--success-500);
        }

        .login-main {
            width: 100%;
            max-width: 82rem;
            flex: 1;
            margin: 0 auto;
            padding: var(--space-5) var(--space-4);
            display: flex;
            align-items: center;
        }

        .login-shell {
            width: 100%;
            overflow: hidden;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-xl);
            background: var(--neutral-0);
            box-shadow: var(--shadow-md);
        }

        .login-information {
            position: relative;
            min-height: 100%;
            padding: var(--space-8);
            border-bottom: 1px solid var(--neutral-200);
            background: var(--neutral-25);
        }

        .login-information::before {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 0.25rem;
            background: var(--brand-500);
            content: "";
        }

        .information-kicker {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-4);
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .information-kicker::before {
            width: 1.5rem;
            height: 0.125rem;
            border-radius: var(--radius-pill);
            background: var(--brand-500);
            content: "";
        }

        .information-title {
            max-width: 32rem;
            margin: 0;
            color: var(--neutral-900);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            letter-spacing: -0.045em;
            line-height: 1.12;
        }

        .information-copy {
            max-width: 33rem;
            margin: var(--space-4) 0 0;
            color: var(--neutral-600);
            font-size: 0.9375rem;
            line-height: 1.75;
        }

        .capability-list {
            display: grid;
            gap: var(--space-3);
            margin: var(--space-6) 0 0;
            padding: 0;
            list-style: none;
        }

        .capability-item {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-0);
        }

        .capability-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 1rem;
        }

        .capability-title {
            display: block;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .capability-copy {
            display: block;
            margin-top: var(--space-1);
            color: var(--neutral-600);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .login-form-panel {
            padding: var(--space-6);
            background: var(--neutral-0);
        }

        .login-form-wrapper {
            width: 100%;
            max-width: 28rem;
            margin-right: auto;
            margin-left: auto;
        }

        .login-form-icon {
            display: inline-flex;
            width: 3rem;
            height: 3rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 1.25rem;
        }

        .login-form-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .login-form-copy {
            margin: var(--space-2) 0 0;
            color: var(--neutral-600);
            font-size: 0.875rem;
        }

        .login-form {
            margin-top: var(--space-6);
        }

        .form-label {
            margin-bottom: var(--space-2);
            color: var(--neutral-800);
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .input-group {
            position: relative;
        }

        .input-group-text {
            width: 3rem;
            min-height: 3rem;
            justify-content: center;
            border-color: var(--neutral-300);
            border-right: 0;
            border-radius: var(--radius-md) 0 0 var(--radius-md);
            color: var(--neutral-500);
            background: var(--neutral-25);
        }

        .form-control {
            min-height: 3rem;
            border-color: var(--neutral-300);
            border-radius: var(--radius-md);
            color: var(--neutral-900);
            background: var(--neutral-0);
            font-size: 0.875rem;
        }

        .input-group .form-control {
            border-left: 0;
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
        }

        .input-group .form-control:focus {
            border-left: 0;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--brand-400, #ee8240);
            color: var(--brand-600);
            background: var(--brand-50);
        }

        .form-control::placeholder {
            color: #9aa1a8;
        }

        .form-control:focus {
            border-color: var(--brand-400, #ee8240);
            box-shadow: 0 0 0 0.1875rem rgba(229, 106, 31, 0.13);
        }

        .password-toggle {
            position: absolute;
            z-index: 5;
            top: 50%;
            right: var(--space-2);
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: var(--radius-sm);
            color: var(--neutral-500);
            background: transparent;
        }

        .password-toggle:hover,
        .password-toggle:focus {
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .password-input {
            padding-right: 3rem;
        }

        .form-check-input {
            border-color: var(--neutral-300);
        }

        .form-check-input:checked {
            border-color: var(--brand-500);
            background-color: var(--brand-500);
        }

        .form-check-input:focus {
            border-color: var(--brand-400, #ee8240);
            box-shadow: 0 0 0 0.1875rem rgba(229, 106, 31, 0.13);
        }

        .form-check-label {
            color: var(--neutral-700);
            font-size: 0.8125rem;
        }

        .btn-login {
            min-height: 3rem;
            border-color: var(--brand-500);
            border-radius: var(--radius-md);
            color: var(--neutral-0);
            background: var(--brand-500);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .btn-login:hover,
        .btn-login:focus {
            border-color: var(--brand-600);
            color: var(--neutral-0);
            background: var(--brand-600);
        }

        .btn-login:active {
            border-color: var(--brand-700) !important;
            background: var(--brand-700) !important;
        }

        .alert {
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            box-shadow: none;
        }

        .alert-success {
            border-color: #c9ead8;
            color: var(--success-700);
            background: var(--success-50);
        }

        .alert-danger {
            border-color: #efc9c3;
            color: var(--danger-700);
            background: var(--danger-50);
        }

        .invalid-feedback {
            color: var(--danger-700);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .login-support {
            display: flex;
            align-items: flex-start;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            color: var(--neutral-600);
            background: var(--neutral-25);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .login-support i {
            margin-top: 0.125rem;
            color: var(--brand-600);
        }

        .login-footer {
            padding: 0 var(--space-4) var(--space-5);
            color: var(--neutral-600);
            font-size: 0.75rem;
            text-align: center;
        }

        @media (min-width: 768px) {
            .security-label {
                display: inline-flex;
            }
        }

        @media (min-width: 992px) {
            .login-main {
                padding: var(--space-8) var(--space-6);
            }

            .login-shell {
                display: grid;
                grid-template-columns:
                    minmax(0, 1.05fr)
                    minmax(24rem, 0.95fr);
            }

            .login-information {
                padding: var(--space-10);
                border-right: 1px solid var(--neutral-200);
                border-bottom: 0;
            }

            .login-information::before {
                top: 0;
                right: auto;
                bottom: 0;
                left: 0;
                width: 0.25rem;
                height: auto;
            }

            .login-form-panel {
                display: flex;
                align-items: center;
                padding: var(--space-10) var(--space-8);
            }
        }

        @media (max-width: 575.98px) {
            .login-topbar-inner,
            .login-main {
                padding-right: var(--space-3);
                padding-left: var(--space-3);
            }

            .login-main {
                align-items: flex-start;
                padding-top: var(--space-4);
                padding-bottom: var(--space-4);
            }

            .login-shell {
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-sm);
            }

            .login-information {
                display: none;
            }

            .login-form-panel {
                padding: var(--space-5);
            }

            .login-form-title {
                font-size: 1.5rem;
            }

            .login-footer {
                padding-right: var(--space-3);
                padding-left: var(--space-3);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>

    <link
        href="{{ asset('css/ui-modern.css') }}"
        rel="stylesheet"
    >
</head>

<body class="login-body">
    <div class="login-page">
        <header class="login-topbar">
            <div class="login-topbar-inner">
                <a
                    href="{{ route('login') }}"
                    class="brand"
                    aria-label="Sistem Presensi PT Gadai Ogan Baru"
                >
                    <span class="brand-mark">
                        <img
                            src="{{ asset('images/logo-pt-gadai-ogan-baru.png') }}"
                            alt="Logo PT Gadai Ogan Baru"
                        >
                    </span>

                    <span class="brand-copy">
                        <span class="brand-title">
                            Sistem Presensi
                        </span>

                        <span class="brand-subtitle">
                            PT Gadai Ogan Baru
                        </span>
                    </span>
                </a>

                <span class="security-label">
                    <i
                        class="bi bi-shield-check"
                        aria-hidden="true"
                    ></i>

                    Akses internal perusahaan
                </span>
            </div>
        </header>

        <main class="login-main">
            <div class="login-shell">
                <section
                    class="login-information"
                    aria-labelledby="system-information-title"
                >
                    <div class="login-panel-brand">
                        <img
                            src="{{ asset('images/logo-pt-gadai-ogan-baru.png') }}"
                            alt="Logo PT Gadai Ogan Baru"
                        >

                        <span>
                            <strong>PT Gadai Ogan Baru</strong>
                            <span>Sistem internal perusahaan</span>
                        </span>
                    </div>

                    <p class="information-kicker">
                        Selamat datang
                    </p>

                    <h1
                        id="system-information-title"
                        class="information-title"
                    >
                        Sistem Presensi
                    </h1>

                    <p class="information-copy">
                        Presensi karyawan berbasis lokasi dan QR dinamis
                        dengan data yang tersimpan secara terpusat.
                    </p>

                    <ul class="capability-list">
                        <li class="capability-item">
                            <span class="capability-icon">
                                <i
                                    class="bi bi-geo-alt-fill"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <span>
                                <span class="capability-title">
                                    Lokasi
                                </span>

                                <span class="capability-copy">
                                    Posisi perangkat divalidasi sesuai
                                    lokasi cabang.
                                </span>
                            </span>
                        </li>

                        <li class="capability-item">
                            <span class="capability-icon">
                                <i
                                    class="bi bi-qr-code-scan"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <span>
                                <span class="capability-title">
                                    QR Dinamis
                                </span>

                                <span class="capability-copy">
                                    Token presensi berubah berkala dan
                                    divalidasi oleh server.
                                </span>
                            </span>
                        </li>

                        <li class="capability-item">
                            <span class="capability-icon">
                                <i
                                    class="bi bi-shield-check"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <span>
                                <span class="capability-title">
                                    Data terpusat
                                </span>

                                <span class="capability-copy">
                                    Hasil presensi tersimpan dan dapat
                                    dipantau oleh HRD.
                                </span>
                            </span>
                        </li>
                    </ul>
                </section>

                <section
                    class="login-form-panel"
                    aria-labelledby="login-title"
                >
                    <div class="login-form-wrapper">
                        <span class="login-form-icon">
                            <i
                                class="bi bi-person-lock"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <h2
                            id="login-title"
                            class="login-form-title"
                        >
                            Masuk ke Akun
                        </h2>

                        <p class="login-form-copy">
                            Gunakan akun yang telah diberikan oleh
                            administrator.
                        </p>

                        @if (session('success'))
                            <div
                                class="alert alert-success mt-4 mb-0"
                                role="alert"
                            >
                                <i
                                    class="bi bi-check-circle-fill me-2"
                                    aria-hidden="true"
                                ></i>

                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div
                                class="alert alert-danger mt-4 mb-0"
                                role="alert"
                            >
                                <div class="d-flex align-items-start gap-2">
                                    <i
                                        class="bi bi-exclamation-octagon-fill"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Data login belum valid. Periksa
                                        kembali email dan kata sandi.
                                    </span>
                                </div>
                            </div>
                        @endif

                        <form
                            method="POST"
                            action="{{ route('login.store') }}"
                            class="login-form"
                        >
                            @csrf

                            <div class="mb-4">
                                <label
                                    for="email"
                                    class="form-label"
                                >
                                    Alamat email
                                </label>

                                <div class="input-group">
                                    <span
                                        class="input-group-text"
                                        aria-hidden="true"
                                    >
                                        <i class="bi bi-envelope"></i>
                                    </span>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        class="form-control
                                            @error('email')
                                                is-invalid
                                            @enderror"
                                        placeholder="nama@perusahaan.com"
                                        autocomplete="username"
                                        maxlength="255"
                                        required
                                        autofocus
                                    >
                                </div>

                                @error('email')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label
                                    for="password"
                                    class="form-label"
                                >
                                    Kata sandi
                                </label>

                                <div class="input-group">
                                    <span
                                        class="input-group-text"
                                        aria-hidden="true"
                                    >
                                        <i class="bi bi-lock"></i>
                                    </span>

                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control password-input
                                            @error('password')
                                                is-invalid
                                            @enderror"
                                        placeholder="Masukkan kata sandi"
                                        autocomplete="current-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        id="password-toggle"
                                        class="password-toggle"
                                        aria-label="Tampilkan kata sandi"
                                        aria-pressed="false"
                                    >
                                        <i
                                            id="password-toggle-icon"
                                            class="bi bi-eye"
                                            aria-hidden="true"
                                        ></i>
                                    </button>
                                </div>

                                @error('password')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div
                                class="d-flex align-items-center
                                    justify-content-between gap-3 mb-4"
                            >
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        id="remember"
                                        name="remember"
                                        value="1"
                                        class="form-check-input"
                                        @checked(old('remember'))
                                    >

                                    <label
                                        for="remember"
                                        class="form-check-label fw-semibold"
                                    >
                                        Tetap masuk di perangkat ini
                                    </label>
                                </div>

                                <span
                                    class="small text-secondary
                                        login-remember-hint"
                                >
                                    Tidak perlu masuk ulang di perangkat ini
                                </span>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary btn-login w-100"
                            >
                                <i
                                    class="bi bi-box-arrow-in-right me-2"
                                    aria-hidden="true"
                                ></i>

                                Masuk
                            </button>
                        </form>

                        <div class="login-support">
                            <i
                                class="bi bi-info-circle"
                                aria-hidden="true"
                            ></i>

                            <span>
                                Mengalami kendala?
                                <strong>Hubungi HRD.</strong>
                            </span>
                        </div>

                        <p class="login-form-copyright">
                            © {{ now()->year }} PT Gadai Ogan Baru
                        </p>
                    </div>
                </section>
            </div>
        </main>

        <footer class="login-footer">
            © {{ now()->year }} PT Gadai Ogan Baru
            · Sistem Presensi QR TOTP dan Geofencing
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput =
                document.getElementById('password');

            const passwordToggle =
                document.getElementById('password-toggle');

            const passwordToggleIcon =
                document.getElementById('password-toggle-icon');

            if (
                passwordInput === null
                || passwordToggle === null
                || passwordToggleIcon === null
            ) {
                return;
            }

            passwordToggle.addEventListener(
                'click',
                function () {
                    const passwordIsVisible =
                        passwordInput.type === 'text';

                    passwordInput.type =
                        passwordIsVisible
                            ? 'password'
                            : 'text';

                    passwordToggle.setAttribute(
                        'aria-pressed',
                        passwordIsVisible
                            ? 'false'
                            : 'true'
                    );

                    passwordToggle.setAttribute(
                        'aria-label',
                        passwordIsVisible
                            ? 'Tampilkan kata sandi'
                            : 'Sembunyikan kata sandi'
                    );

                    passwordToggleIcon.className =
                        passwordIsVisible
                            ? 'bi bi-eye'
                            : 'bi bi-eye-slash';
                }
            );
        });
    </script>
</body>
</html>
