<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta
            name="description"
            content="Sistem presensi karyawan berbasis geofencing dan QR Code dinamis."
        >

        <title>
            {{ config('app.name', 'Sistem Presensi Karyawan') }}
        </title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800"
            rel="stylesheet"
        >

        <style>
            :root {
                color-scheme: light;

                --brand-50: #fff6ef;
                --brand-100: #fde9d8;
                --brand-200: #f9cfad;
                --brand-300: #f3ad77;
                --brand-400: #ec8745;
                --brand-500: #e56a1f;
                --brand-600: #c95318;
                --brand-700: #a64016;
                --brand-800: #843417;
                --brand-900: #6b2d16;

                --neutral-0: #ffffff;
                --neutral-25: #fcfcfb;
                --neutral-50: #f7f7f5;
                --neutral-100: #efefec;
                --neutral-200: #dfdfda;
                --neutral-500: #74746c;
                --neutral-600: #5e5e57;
                --neutral-700: #454540;
                --neutral-800: #2d2d2a;
                --neutral-900: #20201d;

                --success-50: #eef8f3;
                --success-600: #238b5e;
                --warning-50: #fff8e7;
                --warning-600: #c98200;

                --shadow-xs:
                    0 1px 2px rgba(32, 32, 29, 0.06);
                --shadow-sm:
                    0 8px 24px rgba(32, 32, 29, 0.08);

                --radius-sm: 8px;
                --radius-md: 12px;
                --radius-lg: 16px;
                --radius-xl: 20px;
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            html {
                min-width: 320px;
                scroll-behavior: smooth;
            }

            body {
                min-height: 100vh;
                margin: 0;
                color: var(--neutral-900);
                background: var(--neutral-50);
                font-family:
                    "Plus Jakarta Sans",
                    system-ui,
                    -apple-system,
                    BlinkMacSystemFont,
                    "Segoe UI",
                    sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            a {
                color: inherit;
            }

            button,
            a {
                -webkit-tap-highlight-color: transparent;
            }

            .welcome-shell {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }

            .welcome-container {
                width: min(1180px, calc(100% - 32px));
                margin-inline: auto;
            }

            .welcome-header {
                position: relative;
                z-index: 10;
                border-bottom: 1px solid var(--neutral-200);
                background: rgba(255, 255, 255, 0.96);
            }

            .welcome-navigation {
                min-height: 72px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
            }

            .welcome-brand {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                color: var(--neutral-900);
                text-decoration: none;
            }

            .welcome-brand-mark {
                width: 42px;
                height: 42px;
                display: inline-flex;
                flex: 0 0 42px;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--brand-200);
                border-radius: var(--radius-md);
                color: var(--brand-700);
                background: var(--brand-50);
            }

            .welcome-brand-mark svg {
                width: 22px;
                height: 22px;
            }

            .welcome-brand-title {
                display: block;
                font-size: 0.875rem;
                font-weight: 800;
                letter-spacing: -0.02em;
            }

            .welcome-brand-subtitle {
                display: block;
                margin-top: 2px;
                color: var(--neutral-500);
                font-size: 0.6875rem;
                font-weight: 600;
            }

            .welcome-nav-actions {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .welcome-button {
                min-height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 10px 16px;
                border: 1px solid transparent;
                border-radius: var(--radius-sm);
                font: inherit;
                font-size: 0.8125rem;
                font-weight: 800;
                line-height: 1;
                text-decoration: none;
                transition:
                    background-color 150ms ease,
                    border-color 150ms ease,
                    color 150ms ease,
                    box-shadow 150ms ease;
            }

            .welcome-button:focus-visible,
            .welcome-brand:focus-visible {
                outline: 3px solid rgba(229, 106, 31, 0.25);
                outline-offset: 3px;
            }

            .welcome-button-primary {
                color: var(--neutral-0);
                border-color: var(--brand-500);
                background: var(--brand-500);
                box-shadow: var(--shadow-xs);
            }

            .welcome-button-primary:hover {
                border-color: var(--brand-600);
                background: var(--brand-600);
            }

            .welcome-button-secondary {
                color: var(--neutral-800);
                border-color: var(--neutral-200);
                background: var(--neutral-0);
            }

            .welcome-button-secondary:hover {
                border-color: var(--brand-300);
                color: var(--brand-700);
                background: var(--brand-50);
            }

            .welcome-main {
                flex: 1;
            }

            .welcome-hero {
                padding: 72px 0 56px;
            }

            .welcome-hero-grid {
                display: grid;
                grid-template-columns:
                    minmax(0, 1.02fr)
                    minmax(360px, 0.98fr);
                gap: 64px;
                align-items: center;
            }

            .welcome-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 20px;
                padding: 8px 12px;
                border: 1px solid var(--brand-200);
                border-radius: var(--radius-sm);
                color: var(--brand-700);
                background: var(--brand-50);
                font-size: 0.6875rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .welcome-eyebrow-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--brand-500);
            }

            .welcome-title {
                max-width: 720px;
                margin: 0;
                color: var(--neutral-900);
                font-size: clamp(2.35rem, 5vw, 4.5rem);
                font-weight: 800;
                letter-spacing: -0.055em;
                line-height: 1.03;
            }

            .welcome-title-accent {
                color: var(--brand-600);
            }

            .welcome-description {
                max-width: 650px;
                margin: 24px 0 0;
                color: var(--neutral-600);
                font-size: clamp(1rem, 1.6vw, 1.125rem);
                line-height: 1.8;
            }

            .welcome-hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 32px;
            }

            .welcome-hero-actions .welcome-button {
                min-height: 48px;
                padding-inline: 20px;
            }

            .welcome-trust-list {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
                margin: 36px 0 0;
                padding: 0;
                list-style: none;
            }

            .welcome-trust-item {
                min-width: 0;
                display: flex;
                align-items: flex-start;
                gap: 10px;
                color: var(--neutral-700);
                font-size: 0.75rem;
                font-weight: 700;
                line-height: 1.55;
            }

            .welcome-trust-icon {
                width: 24px;
                height: 24px;
                display: inline-flex;
                flex: 0 0 24px;
                align-items: center;
                justify-content: center;
                margin-top: 1px;
                border-radius: 7px;
                color: var(--success-600);
                background: var(--success-50);
            }

            .welcome-trust-icon svg {
                width: 14px;
                height: 14px;
            }

            .welcome-product-card {
                overflow: hidden;
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-xl);
                background: var(--neutral-0);
                box-shadow: var(--shadow-sm);
            }

            .welcome-product-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding: 20px 22px;
                border-bottom: 1px solid var(--neutral-200);
                background: var(--neutral-25);
            }

            .welcome-product-brand {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .welcome-product-logo {
                width: 40px;
                height: 40px;
                display: inline-flex;
                flex: 0 0 40px;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                color: var(--neutral-0);
                background: var(--brand-500);
            }

            .welcome-product-logo svg {
                width: 21px;
                height: 21px;
            }

            .welcome-product-title {
                margin: 0;
                font-size: 0.8125rem;
                font-weight: 800;
            }

            .welcome-product-copy {
                margin: 3px 0 0;
                color: var(--neutral-500);
                font-size: 0.6875rem;
            }

            .welcome-product-status {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 7px 10px;
                border: 1px solid #cce6d8;
                border-radius: var(--radius-sm);
                color: var(--success-600);
                background: var(--success-50);
                font-size: 0.625rem;
                font-weight: 800;
            }

            .welcome-product-status-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: var(--success-600);
            }

            .welcome-product-body {
                padding: 22px;
            }

            .welcome-product-date {
                color: var(--neutral-500);
                font-size: 0.6875rem;
                font-weight: 700;
            }

            .welcome-product-greeting {
                margin: 5px 0 0;
                font-size: 1.125rem;
                font-weight: 800;
                letter-spacing: -0.025em;
            }

            .welcome-attendance-card {
                margin-top: 20px;
                padding: 18px;
                border: 1px solid var(--brand-200);
                border-radius: var(--radius-lg);
                background: var(--brand-50);
            }

            .welcome-attendance-label {
                color: var(--brand-700);
                font-size: 0.625rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .welcome-attendance-time {
                margin-top: 8px;
                color: var(--neutral-900);
                font-size: 2rem;
                font-weight: 800;
                letter-spacing: -0.045em;
                line-height: 1.1;
            }

            .welcome-attendance-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 14px;
            }

            .welcome-attendance-meta-item {
                padding: 7px 9px;
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-sm);
                color: var(--neutral-700);
                background: var(--neutral-0);
                font-size: 0.625rem;
                font-weight: 800;
            }

            .welcome-location-card {
                display: grid;
                grid-template-columns: 48px minmax(0, 1fr);
                gap: 12px;
                align-items: center;
                margin-top: 14px;
                padding: 14px;
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-md);
                background: var(--neutral-25);
            }

            .welcome-location-icon {
                width: 48px;
                height: 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                color: var(--brand-700);
                background: var(--brand-100);
            }

            .welcome-location-icon svg {
                width: 22px;
                height: 22px;
            }

            .welcome-location-label {
                color: var(--neutral-500);
                font-size: 0.625rem;
                font-weight: 800;
                text-transform: uppercase;
            }

            .welcome-location-value {
                margin-top: 3px;
                font-size: 0.75rem;
                font-weight: 800;
                line-height: 1.5;
            }

            .welcome-feature-section {
                padding: 32px 0 80px;
            }

            .welcome-section-header {
                max-width: 720px;
                margin-bottom: 28px;
            }

            .welcome-section-label {
                color: var(--brand-600);
                font-size: 0.6875rem;
                font-weight: 800;
                letter-spacing: 0.07em;
                text-transform: uppercase;
            }

            .welcome-section-title {
                margin: 8px 0 0;
                color: var(--neutral-900);
                font-size: clamp(1.75rem, 3.2vw, 2.75rem);
                font-weight: 800;
                letter-spacing: -0.04em;
                line-height: 1.15;
            }

            .welcome-section-copy {
                margin: 12px 0 0;
                color: var(--neutral-600);
                font-size: 0.9375rem;
                line-height: 1.75;
            }

            .welcome-feature-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 16px;
            }

            .welcome-feature-card {
                min-width: 0;
                padding: 22px;
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-lg);
                background: var(--neutral-0);
                box-shadow: var(--shadow-xs);
            }

            .welcome-feature-icon {
                width: 44px;
                height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 18px;
                border-radius: var(--radius-md);
                color: var(--brand-700);
                background: var(--brand-50);
            }

            .welcome-feature-icon svg {
                width: 22px;
                height: 22px;
            }

            .welcome-feature-title {
                margin: 0;
                font-size: 0.9375rem;
                font-weight: 800;
                letter-spacing: -0.02em;
            }

            .welcome-feature-copy {
                margin: 8px 0 0;
                color: var(--neutral-600);
                font-size: 0.75rem;
                line-height: 1.7;
            }

            .welcome-footer {
                border-top: 1px solid var(--neutral-200);
                background: var(--neutral-0);
            }

            .welcome-footer-content {
                min-height: 78px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                color: var(--neutral-500);
                font-size: 0.6875rem;
                font-weight: 600;
            }

            .welcome-footer-status {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .welcome-footer-status-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: var(--success-600);
            }

            @media (max-width: 1023.98px) {
                .welcome-hero {
                    padding-top: 52px;
                }

                .welcome-hero-grid {
                    grid-template-columns: 1fr;
                    gap: 40px;
                }

                .welcome-product-card {
                    width: min(100%, 620px);
                }

                .welcome-feature-grid {
                    grid-template-columns:
                        repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 767.98px) {
                .welcome-container {
                    width: min(100% - 24px, 1180px);
                }

                .welcome-navigation {
                    min-height: 64px;
                }

                .welcome-brand-subtitle {
                    display: none;
                }

                .welcome-nav-actions .welcome-button-secondary {
                    display: none;
                }

                .welcome-hero {
                    padding: 40px 0 44px;
                }

                .welcome-title {
                    font-size: clamp(2.1rem, 12vw, 3.25rem);
                }

                .welcome-trust-list {
                    grid-template-columns: 1fr;
                }

                .welcome-feature-section {
                    padding-bottom: 56px;
                }

                .welcome-feature-grid {
                    grid-template-columns: 1fr;
                }

                .welcome-footer-content {
                    min-height: 92px;
                    align-items: flex-start;
                    flex-direction: column;
                    justify-content: center;
                    gap: 8px;
                }
            }

            @media (max-width: 479.98px) {
                .welcome-brand-title {
                    max-width: 150px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .welcome-button {
                    padding-inline: 13px;
                }

                .welcome-hero-actions {
                    flex-direction: column;
                }

                .welcome-hero-actions .welcome-button {
                    width: 100%;
                }

                .welcome-product-header {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .welcome-product-status {
                    align-self: flex-start;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                *,
                *::before,
                *::after {
                    scroll-behavior: auto !important;
                    transition-duration: 0.01ms !important;
                }
            }
        </style>
    </head>

    <body>
        <div class="welcome-shell">
            <header class="welcome-header">
                <div
                    class="welcome-container
                        welcome-navigation"
                >
                    <a
                        href="{{ url('/') }}"
                        class="welcome-brand"
                        aria-label="Halaman utama sistem presensi"
                    >
                        <span class="welcome-brand-mark">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M7 3.75H17C18.243 3.75 19.25 4.757 19.25 6V18C19.25 19.243 18.243 20.25 17 20.25H7C5.757 20.25 4.75 19.243 4.75 18V6C4.75 4.757 5.757 3.75 7 3.75Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                                <path
                                    d="M8 8.25H16M8 12H13M8 15.75H11"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>

                        <span>
                            <span class="welcome-brand-title">
                                {{
                                    config(
                                        'app.name',
                                        'Sistem Presensi Karyawan'
                                    )
                                }}
                            </span>

                            <span class="welcome-brand-subtitle">
                                PT Gadai Ogan Baru
                            </span>
                        </span>
                    </a>

                    @if (Route::has('login'))
                        <nav
                            class="welcome-nav-actions"
                            aria-label="Navigasi akun"
                        >
                            @auth
                                <a
                                    href="{{ url('/dashboard') }}"
                                    class="welcome-button
                                        welcome-button-primary"
                                >
                                    Dashboard
                                </a>
                            @else
                                @if (Route::has('register'))
                                    <a
                                        href="{{ route('register') }}"
                                        class="welcome-button
                                            welcome-button-secondary"
                                    >
                                        Daftar
                                    </a>
                                @endif

                                <a
                                    href="{{ route('login') }}"
                                    class="welcome-button
                                        welcome-button-primary"
                                >
                                    Masuk
                                </a>
                            @endauth
                        </nav>
                    @endif
                </div>
            </header>

            <main class="welcome-main">
                <section class="welcome-hero">
                    <div
                        class="welcome-container
                            welcome-hero-grid"
                    >
                        <div>
                            <div class="welcome-eyebrow">
                                <span
                                    class="welcome-eyebrow-dot"
                                    aria-hidden="true"
                                ></span>

                                Sistem Presensi Terintegrasi
                            </div>

                            <h1 class="welcome-title">
                                Presensi karyawan yang
                                <span class="welcome-title-accent">
                                    akurat dan terverifikasi.
                                </span>
                            </h1>

                            <p class="welcome-description">
                                Sistem presensi berbasis geofencing
                                Haversine dan QR Code dinamis untuk
                                memvalidasi lokasi, waktu, dan
                                identitas karyawan dalam satu proses.
                            </p>

                            <div class="welcome-hero-actions">
                                @auth
                                    <a
                                        href="{{ url('/dashboard') }}"
                                        class="welcome-button
                                            welcome-button-primary"
                                    >
                                        Buka Dashboard

                                        <span aria-hidden="true">
                                            &rarr;
                                        </span>
                                    </a>
                                @else
                                    @if (Route::has('login'))
                                        <a
                                            href="{{ route('login') }}"
                                            class="welcome-button
                                                welcome-button-primary"
                                        >
                                            Masuk ke Sistem

                                            <span aria-hidden="true">
                                                &rarr;
                                            </span>
                                        </a>
                                    @endif
                                @endauth

                                <a
                                    href="#fitur"
                                    class="welcome-button
                                        welcome-button-secondary"
                                >
                                    Lihat Fitur
                                </a>
                            </div>

                            <ul class="welcome-trust-list">
                                <li class="welcome-trust-item">
                                    <span
                                        class="welcome-trust-icon"
                                    >
                                        <svg
                                            viewBox="0 0 20 20"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M5.5 10.25L8.4 13.1L14.5 7"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </span>

                                    Validasi radius geofence
                                </li>

                                <li class="welcome-trust-item">
                                    <span
                                        class="welcome-trust-icon"
                                    >
                                        <svg
                                            viewBox="0 0 20 20"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M5.5 10.25L8.4 13.1L14.5 7"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </span>

                                    QR Code dinamis berbasis TOTP
                                </li>

                                <li class="welcome-trust-item">
                                    <span
                                        class="welcome-trust-icon"
                                    >
                                        <svg
                                            viewBox="0 0 20 20"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M5.5 10.25L8.4 13.1L14.5 7"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </span>

                                    Riwayat dan audit tervalidasi
                                </li>
                            </ul>
                        </div>

                        <div
                            class="welcome-product-card"
                            aria-label="Pratinjau sistem presensi"
                        >
                            <div class="welcome-product-header">
                                <div class="welcome-product-brand">
                                    <span
                                        class="welcome-product-logo"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M7 3.75H17C18.243 3.75 19.25 4.757 19.25 6V18C19.25 19.243 18.243 20.25 17 20.25H7C5.757 20.25 4.75 19.243 4.75 18V6C4.75 4.757 5.757 3.75 7 3.75Z"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                            />
                                            <path
                                                d="M8 8.25H16M8 12H13M8 15.75H11"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linecap="round"
                                            />
                                        </svg>
                                    </span>

                                    <div>
                                        <p
                                            class="welcome-product-title"
                                        >
                                            Presensi Hari Ini
                                        </p>

                                        <p
                                            class="welcome-product-copy"
                                        >
                                            Verifikasi lokasi aktif
                                        </p>
                                    </div>
                                </div>

                                <span
                                    class="welcome-product-status"
                                >
                                    <span
                                        class="welcome-product-status-dot"
                                        aria-hidden="true"
                                    ></span>

                                    Sistem aktif
                                </span>
                            </div>

                            <div class="welcome-product-body">
                                <div class="welcome-product-date">
                                    Jadwal kerja terverifikasi
                                </div>

                                <p class="welcome-product-greeting">
                                    Siap melakukan presensi
                                </p>

                                <div class="welcome-attendance-card">
                                    <div
                                        class="welcome-attendance-label"
                                    >
                                        Waktu Presensi
                                    </div>

                                    <div
                                        class="welcome-attendance-time"
                                    >
                                        08:00 WIB
                                    </div>

                                    <div
                                        class="welcome-attendance-meta"
                                    >
                                        <span
                                            class="welcome-attendance-meta-item"
                                        >
                                            Radius 30 m
                                        </span>

                                        <span
                                            class="welcome-attendance-meta-item"
                                        >
                                            Akurasi &le; 25 m
                                        </span>

                                        <span
                                            class="welcome-attendance-meta-item"
                                        >
                                            QR dinamis
                                        </span>
                                    </div>
                                </div>

                                <div class="welcome-location-card">
                                    <span
                                        class="welcome-location-icon"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M12 21C15.5 17.1 18 14.25 18 10.5C18 7.186 15.314 4.5 12 4.5C8.686 4.5 6 7.186 6 10.5C6 14.25 8.5 17.1 12 21Z"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linejoin="round"
                                            />
                                            <circle
                                                cx="12"
                                                cy="10.5"
                                                r="2"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                            />
                                        </svg>
                                    </span>

                                    <div>
                                        <div
                                            class="welcome-location-label"
                                        >
                                            Lokasi Cabang
                                        </div>

                                        <div
                                            class="welcome-location-value"
                                        >
                                            Posisi perangkat berada
                                            dalam area geofence.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="fitur"
                    class="welcome-feature-section"
                >
                    <div class="welcome-container">
                        <div class="welcome-section-header">
                            <div class="welcome-section-label">
                                Kapabilitas Utama
                            </div>

                            <h2 class="welcome-section-title">
                                Kontrol presensi dari validasi sampai
                                pelaporan.
                            </h2>

                            <p class="welcome-section-copy">
                                Setiap transaksi presensi divalidasi
                                menggunakan jadwal, lokasi, QR Code,
                                dan aturan waktu yang telah ditetapkan.
                            </p>
                        </div>

                        <div class="welcome-feature-grid">
                            <article class="welcome-feature-card">
                                <span class="welcome-feature-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M12 21C15.5 17.1 18 14.25 18 10.5C18 7.186 15.314 4.5 12 4.5C8.686 4.5 6 7.186 6 10.5C6 14.25 8.5 17.1 12 21Z"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linejoin="round"
                                        />
                                        <circle
                                            cx="12"
                                            cy="10.5"
                                            r="2"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                        />
                                    </svg>
                                </span>

                                <h3 class="welcome-feature-title">
                                    Geofencing Haversine
                                </h3>

                                <p class="welcome-feature-copy">
                                    Menghitung jarak perangkat terhadap
                                    koordinat cabang sebelum transaksi
                                    presensi disimpan.
                                </p>
                            </article>

                            <article class="welcome-feature-card">
                                <span class="welcome-feature-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M5 5H9V9H5V5ZM15 5H19V9H15V5ZM5 15H9V19H5V15Z"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                        />
                                        <path
                                            d="M14 14H16V16H14V14ZM17 14H19V19H14V17"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </span>

                                <h3 class="welcome-feature-title">
                                    QR Code Dinamis
                                </h3>

                                <p class="welcome-feature-copy">
                                    Kode berubah berdasarkan interval
                                    TOTP sehingga tidak dapat digunakan
                                    kembali di luar periode valid.
                                </p>
                            </article>

                            <article class="welcome-feature-card">
                                <span class="welcome-feature-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M6 4.75H18C19.243 4.75 20.25 5.757 20.25 7V18C20.25 19.243 19.243 20.25 18 20.25H6C4.757 20.25 3.75 19.243 3.75 18V7C3.75 5.757 4.757 4.75 6 4.75Z"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                        />
                                        <path
                                            d="M7 2.75V6.75M17 2.75V6.75M3.75 9H20.25"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </span>

                                <h3 class="welcome-feature-title">
                                    Manajemen Jadwal
                                </h3>

                                <p class="welcome-feature-copy">
                                    Mengelola pola kerja, jadwal harian,
                                    hari libur, izin, sakit, dan
                                    pertukaran jadwal karyawan.
                                </p>
                            </article>

                            <article class="welcome-feature-card">
                                <span class="welcome-feature-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M5 19.25V11.75M12 19.25V4.75M19 19.25V8.25"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </span>

                                <h3 class="welcome-feature-title">
                                    Monitoring dan Audit
                                </h3>

                                <p class="welcome-feature-copy">
                                    Menyediakan monitoring presensi,
                                    riwayat validasi, dan koreksi
                                    administratif yang terdokumentasi.
                                </p>
                            </article>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="welcome-footer">
                <div
                    class="welcome-container
                        welcome-footer-content"
                >
                    <div>
                        &copy; {{ now()->year }}
                        PT Gadai Ogan Baru.
                        Sistem Presensi Karyawan.
                    </div>

                    <div class="welcome-footer-status">
                        <span
                            class="welcome-footer-status-dot"
                            aria-hidden="true"
                        ></span>

                        Geofencing, QR TOTP, dan pencatatan audit
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
