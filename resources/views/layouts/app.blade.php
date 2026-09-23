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

    <title>
        @yield('title', 'Sistem Presensi Karyawan')
        · PT Gadai Ogan Baru
    </title>

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
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            /*
             * Brand palette — PT Gadai Ogan Baru
             */
            --brand-50: #fff6ef;
            --brand-100: #fde8d7;
            --brand-200: #fac8a5;
            --brand-300: #f4a46d;
            --brand-400: #ee8240;
            --brand-500: #e56a1f;
            --brand-600: #c95314;
            --brand-700: #9f3f12;
            --brand-800: #7a3114;
            --brand-900: #5b2713;

            /*
             * Neutral palette
             */
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

            /*
             * Semantic colors
             */
            --success-50: #edf8f2;
            --success-500: #238b5e;
            --success-700: #176b47;

            --warning-50: #fff8e8;
            --warning-500: #c98200;
            --warning-700: #8c5d00;

            --danger-50: #fff1ef;
            --danger-500: #c74632;
            --danger-700: #913223;

            --info-50: #eef6fb;
            --info-500: #3b7ea1;
            --info-700: #2b5d79;

            /*
             * 8-point spacing system
             */
            --space-1: 0.25rem;   /* 4px: micro spacing */
            --space-2: 0.5rem;    /* 8px */
            --space-3: 0.75rem;   /* 12px */
            --space-4: 1rem;      /* 16px */
            --space-5: 1.5rem;    /* 24px */
            --space-6: 2rem;      /* 32px */
            --space-7: 2.5rem;    /* 40px */
            --space-8: 3rem;      /* 48px */
            --space-10: 4rem;     /* 64px */

            /*
             * Radius system
             */
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.25rem;
            --radius-pill: 999px;

            /*
             * Shadow system
             */
            --shadow-xs:
                0 1px 2px rgba(37, 42, 47, 0.05);
            --shadow-sm:
                0 4px 14px rgba(37, 42, 47, 0.06);
            --shadow-md:
                0 12px 32px rgba(37, 42, 47, 0.09);

            /*
             * Layout tokens
             */
            --sidebar-width: 17.5rem;
            --topbar-height: 4.5rem;
            --content-max-width: 92rem;

            /*
             * Bootstrap semantic mapping
             */
            --bs-primary: var(--brand-500);
            --bs-primary-rgb: 229, 106, 31;
            --bs-success: var(--success-500);
            --bs-warning: var(--warning-500);
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

        a {
            color: var(--brand-600);
            text-underline-offset: 0.18em;
        }

        a:hover {
            color: var(--brand-700);
        }

        :focus-visible {
            outline: 0.1875rem solid rgba(229, 106, 31, 0.28);
            outline-offset: 0.125rem;
        }

        .min-w-0 {
            min-width: 0;
        }

        .app-shell {
            min-height: 100vh;
        }

        /*
         * Sidebar
         */
        .app-sidebar {
            position: fixed;
            z-index: 1030;
            top: 0;
            bottom: 0;
            left: 0;
            width: var(--sidebar-width);
            display: none;
            flex-direction: column;
            overflow-y: auto;
            border-right: 1px solid var(--neutral-200);
            background: var(--neutral-0);
        }

        .app-brand {
            display: flex;
            min-height: var(--topbar-height);
            align-items: center;
            gap: var(--space-3);
            padding: 0 var(--space-5);
            border-bottom: 1px solid var(--neutral-200);
            color: var(--neutral-900);
            text-decoration: none;
        }

        .app-brand:hover,
        .app-brand:focus {
            color: var(--neutral-900);
        }

        .app-brand-mark {
            display: inline-flex;
            width: 2.625rem;
            height: 2.625rem;
            flex: 0 0 2.625rem;
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

        .app-brand-mark {
            background: transparent;
            box-shadow: none;
        }

        .app-brand-mark img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .app-brand-copy {
            min-width: 0;
        }

        .app-brand-title {
            display: block;
            color: var(--neutral-900);
            font-size: 0.9375rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .app-brand-subtitle {
            display: block;
            margin-top: 0.125rem;
            color: var(--neutral-600);
            font-size: 0.6875rem;
            font-weight: 500;
            line-height: 1.35;
        }

        .app-nav {
            padding: var(--space-4) var(--space-3) var(--space-5);
        }

        .app-nav-section + .app-nav-section {
            margin-top: var(--space-5);
        }

        .app-nav-heading {
            margin: 0 0 var(--space-2);
            padding: 0 var(--space-3);
            color: var(--neutral-500);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .app-nav-link {
            position: relative;
            display: flex;
            min-height: 2.75rem;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-1);
            padding: 0.6875rem var(--space-3);
            border-radius: var(--radius-md);
            color: var(--neutral-700);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition:
                color 150ms ease,
                background-color 150ms ease;
        }

        .app-nav-link:hover,
        .app-nav-link:focus {
            color: var(--neutral-900);
            background: var(--neutral-50);
        }

        .app-nav-link.active {
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .app-nav-link.active::before {
            position: absolute;
            top: 0.75rem;
            bottom: 0.75rem;
            left: 0;
            width: 0.1875rem;
            border-radius: var(--radius-pill);
            background: var(--brand-500);
            content: "";
        }

        .app-nav-icon {
            width: 1.25rem;
            flex: 0 0 1.25rem;
            color: var(--neutral-500);
            text-align: center;
            font-size: 1rem;
        }

        .app-nav-link.active .app-nav-icon {
            color: var(--brand-500);
        }

        .app-sidebar-user {
            margin-top: auto;
            padding: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        .app-user-card {
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-25);
        }

        .app-user-avatar {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--brand-100);
            font-size: 0.8125rem;
            font-weight: 800;
        }

        .app-user-name {
            overflow: hidden;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .app-user-role {
            color: var(--neutral-600);
            font-size: 0.6875rem;
        }

        /*
         * Main layout
         */
        .app-main {
            min-width: 0;
            min-height: 100vh;
        }

        .app-topbar {
            position: sticky;
            z-index: 1020;
            top: 0;
            min-height: var(--topbar-height);
            border-bottom: 1px solid var(--neutral-200);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(0.625rem);
        }

        .app-topbar-inner {
            display: flex;
            min-height: var(--topbar-height);
            align-items: center;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-2) var(--space-4);
        }

        .app-mobile-brand {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: var(--space-2);
            color: var(--neutral-900);
            text-decoration: none;
        }

        .app-mobile-brand:hover {
            color: var(--neutral-900);
        }

        .app-mobile-brand .app-brand-mark {
            width: 2.25rem;
            height: 2.25rem;
            flex-basis: 2.25rem;
        }

        .app-topbar-eyebrow {
            color: var(--neutral-600);
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .app-topbar-title {
            overflow: hidden;
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.9375rem;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .app-icon-button {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            color: var(--neutral-700);
            background: var(--neutral-0);
        }

        .app-icon-button:hover,
        .app-icon-button:focus {
            border-color: var(--neutral-300);
            color: var(--brand-600);
            background: var(--brand-50);
        }

        .app-content {
            width: 100%;
            max-width: var(--content-max-width);
            margin: 0 auto;
            padding: var(--space-5) var(--space-4) var(--space-7);
        }

        .app-footer {
            padding: 0 var(--space-4) var(--space-5);
            color: var(--neutral-600);
            font-size: 0.75rem;
            text-align: center;
        }

        /*
         * Typography
         */
        .page-header {
            margin-bottom: var(--space-5);
        }

        .page-title {
            margin: 0 0 var(--space-2);
            color: var(--neutral-900);
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.2;
        }

        .page-description {
            max-width: 54rem;
            margin-bottom: 0;
            color: var(--neutral-600);
            font-size: 0.9375rem;
            line-height: 1.7;
        }

        .section-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .section-description {
            margin: var(--space-1) 0 0;
            color: var(--neutral-600);
            font-size: 0.8125rem;
        }

        /*
         * Surfaces and cards
         */
        .content-card,
        .card {
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .content-card {
            overflow: hidden;
        }

        .content-card > .border-bottom,
        .card-header {
            border-bottom-color: var(--neutral-200) !important;
            background: var(--neutral-0);
        }

        .dropdown-menu,
        .modal-content,
        .offcanvas {
            border-color: var(--neutral-200);
        }

        .dropdown-menu {
            padding: var(--space-2);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
        }

        .dropdown-item {
            min-height: 2.5rem;
            display: flex;
            align-items: center;
            border-radius: var(--radius-sm);
            color: var(--neutral-800);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: var(--neutral-900);
            background: var(--neutral-50);
        }

        /*
         * Forms
         */
        .form-label {
            margin-bottom: var(--space-2);
            color: var(--neutral-800);
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .form-control,
        .form-select,
        .input-group-text {
            min-height: 2.75rem;
            border-color: var(--neutral-300);
            border-radius: var(--radius-md);
            color: var(--neutral-900);
            background-color: var(--neutral-0);
            font-size: 0.875rem;
        }

        textarea.form-control {
            min-height: 7rem;
        }

        .form-control::placeholder {
            color: #9aa1a8;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--brand-400);
            box-shadow: 0 0 0 0.1875rem rgba(229, 106, 31, 0.13);
        }

        .form-text {
            color: var(--neutral-600);
            font-size: 0.75rem;
        }

        .invalid-feedback {
            color: var(--danger-700);
            font-size: 0.75rem;
            font-weight: 600;
        }

        /*
         * Buttons
         */
        .btn {
            min-height: 2.5rem;
            padding: 0.5625rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 700;
            box-shadow: none;
        }

        .btn-sm {
            min-height: 2rem;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
        }

        .btn-primary {
            border-color: var(--brand-500);
            color: var(--neutral-0);
            background: var(--brand-500);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            border-color: var(--brand-600);
            color: var(--neutral-0);
            background: var(--brand-600);
        }

        .btn-primary:active {
            border-color: var(--brand-700) !important;
            background: var(--brand-700) !important;
        }

        .btn-primary:disabled,
        .btn-primary.disabled {
            border-color: var(--brand-200);
            color: var(--brand-700);
            background: var(--brand-100);
            opacity: 1;
            cursor: not-allowed;
        }

        .btn-outline-primary {
            border-color: var(--brand-300);
            color: var(--brand-700);
            background: var(--neutral-0);
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            border-color: var(--brand-500);
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .btn-outline-primary:disabled,
        .btn-outline-primary.disabled,
        .btn-outline-secondary:disabled,
        .btn-outline-secondary.disabled {
            border-color: var(--neutral-200);
            color: var(--neutral-500);
            background: var(--neutral-50);
            opacity: 1;
            cursor: not-allowed;
        }

        .btn-success {
            border-color: var(--success-500);
            background: var(--success-500);
        }

        .btn-success:hover,
        .btn-success:focus {
            border-color: var(--success-700);
            background: var(--success-700);
        }

        .btn-light {
            border-color: var(--neutral-200);
            color: var(--neutral-800);
            background: var(--neutral-0);
        }

        .btn-light:hover,
        .btn-light:focus {
            border-color: var(--neutral-300);
            background: var(--neutral-50);
        }

        /*
         * Badges and status
         */
        .badge {
            padding: 0.4375rem 0.6875rem;
            border-radius: var(--radius-pill);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.015em;
        }

        .text-bg-primary {
            color: var(--brand-700) !important;
            background: var(--brand-100) !important;
        }

        .text-bg-success {
            color: var(--success-700) !important;
            background: var(--success-50) !important;
        }

        .text-bg-warning {
            color: var(--warning-700) !important;
            background: var(--warning-50) !important;
        }

        .text-bg-danger {
            color: var(--danger-700) !important;
            background: var(--danger-50) !important;
        }

        .text-bg-secondary {
            color: var(--neutral-700) !important;
            background: var(--neutral-100) !important;
        }

        /*
         * Alerts
         */
        .alert {
            border-radius: var(--radius-md);
            box-shadow: none;
        }

        .alert-success {
            border-color: #c9ead8;
            color: var(--success-700);
            background: var(--success-50);
        }

        .alert-warning {
            border-color: #f0ddb0;
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .alert-danger {
            border-color: #efc9c3;
            color: var(--danger-700);
            background: var(--danger-50);
        }

        .alert-info {
            border-color: #cde0eb;
            color: var(--info-700);
            background: var(--info-50);
        }

        .app-flash {
            position: relative;
            overflow: hidden;
            padding-left: 3.25rem;
        }

        .app-flash-icon {
            position: absolute;
            top: 1rem;
            left: 1rem;
            font-size: 1.125rem;
        }

        /*
         * Tables
         */
        .table-responsive {
            border-radius: var(--radius-md);
        }

        .table {
            --bs-table-bg: transparent;
            margin-bottom: 0;
            color: var(--neutral-800);
            vertical-align: middle;
        }

        .table > :not(caption) > * > * {
            padding: 0.875rem 1rem;
            border-bottom-color: var(--neutral-100);
        }

        .table thead th {
            color: var(--neutral-600);
            background: var(--neutral-50);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .table-hover > tbody > tr:hover > * {
            --bs-table-bg-state: var(--neutral-25);
        }

        /*
         * Pagination
         */
        .pagination {
            --bs-pagination-border-color: var(--neutral-200);
            --bs-pagination-color: var(--neutral-700);
            --bs-pagination-hover-color: var(--brand-700);
            --bs-pagination-hover-bg: var(--brand-50);
            --bs-pagination-active-bg: var(--brand-500);
            --bs-pagination-active-border-color: var(--brand-500);
        }

        .page-link {
            min-width: 2.25rem;
            border-radius: var(--radius-sm);
            text-align: center;
        }

        /*
         * Utility components for future dashboard refresh
         */
        .stat-card {
            height: 100%;
            padding: var(--space-5);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .stat-card-icon {
            display: inline-flex;
            width: 2.75rem;
            height: 2.75rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 1.125rem;
        }

        .stat-card-label {
            margin-top: var(--space-4);
            color: var(--neutral-600);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .stat-card-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.2;
        }

        .empty-state {
            padding: var(--space-8) var(--space-5);
            color: var(--neutral-600);
            text-align: center;
        }

        .empty-state-icon {
            display: inline-flex;
            width: 3.5rem;
            height: 3.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-4);
            border-radius: var(--radius-lg);
            color: var(--brand-600);
            background: var(--brand-50);
            font-size: 1.375rem;
        }

        /*
         * Mobile navigation
         */
        .offcanvas.app-mobile-navigation {
            color: var(--neutral-900);
            background: var(--neutral-0);
        }

        .app-mobile-navigation .offcanvas-header {
            min-height: var(--topbar-height);
            border-bottom: 1px solid var(--neutral-200);
        }

        .app-mobile-navigation .app-nav {
            padding-top: var(--space-4);
        }

        /*
         * Responsive behavior
         */
        @media (min-width: 992px) {
            .app-sidebar {
                display: flex;
            }

            .app-main {
                margin-left: var(--sidebar-width);
            }

            .app-topbar-inner {
                padding-right: var(--space-6);
                padding-left: var(--space-6);
            }

            .app-content {
                padding: var(--space-6) var(--space-6) var(--space-8);
            }

            .app-footer {
                padding-right: var(--space-6);
                padding-left: var(--space-6);
                text-align: left;
            }
        }

        @media (max-width: 575.98px) {
            .app-content {
                padding-right: var(--space-3);
                padding-left: var(--space-3);
            }

            .page-header {
                margin-bottom: var(--space-4);
            }

            .page-title {
                font-size: 1.375rem;
            }

            .content-card,
            .card {
                border-radius: var(--radius-md);
            }

            .btn {
                white-space: normal;
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

        /* ====================================================
         * EMPLOYEE GLOBAL APP NAV V1
         * Tampilan aplikasi mobile pada seluruh halaman karyawan
         * ==================================================== */

        .employee-app .app-sidebar {
            display: none !important;
        }

        .employee-app .app-main {
            width: 100% !important;
            margin-left: 0 !important;
        }

        .employee-app .app-topbar .app-icon-button {
            display: none !important;
        }

        .employee-app .app-topbar .dropdown {
            display: none !important;
        }

        .employee-app .app-footer {
            display: none !important;
        }

        .employee-app .app-content {
            max-width: 48rem;
            margin-right: auto;
            margin-left: auto;
            padding-bottom: 7rem;
        }

        /*
         * Menyembunyikan bottom navigation lama
         * yang sebelumnya hanya berada di dashboard.
         */
        .employee-app .employee-bottom-nav {
            display: none !important;
        }

        .employee-global-nav {
            position: fixed;
            z-index: 1050;
            left: 50%;
            bottom: max(
                .7rem,
                env(safe-area-inset-bottom)
            );
            transform: translateX(-50%);
            width: calc(100% - 1.1rem);
            max-width: 40rem;
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: .15rem;
            padding: .45rem;
            border:
                1px solid rgba(226, 229, 232, .95);
            border-radius: 1.3rem;
            background: rgba(255, 255, 255, .97);
            box-shadow:
                0 1rem 2.5rem rgba(37, 42, 47, .15);
            backdrop-filter: blur(1rem);
            -webkit-backdrop-filter: blur(1rem);
        }

        .employee-global-nav-link {
            min-width: 0;
            min-height: 3.7rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .15rem;
            padding: .45rem .2rem;
            border-radius: .85rem;
            color: var(--neutral-500);
            font-size: .65rem;
            font-weight: 700;
            text-decoration: none;
            transition:
                color 150ms ease,
                background-color 150ms ease;
        }

        .employee-global-nav-link i {
            font-size: 1.05rem;
        }

        .employee-global-nav-link:hover,
        .employee-global-nav-link:focus {
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .employee-global-nav-link.active {
            color: var(--brand-700);
            background: var(--brand-50);
        }

        @media (max-width: 575.98px) {
            .employee-app .app-content {
                padding-right: .8rem;
                padding-left: .8rem;
            }
        }
</style>

    @stack('styles')

    <link
        href="{{ asset('css/ui-modern.css') }}"
        rel="stylesheet"
    >
    <link
        href="{{ asset('css/ui-operational.css') }}"
        rel="stylesheet"
    >
    <link
        href="{{ asset('css/ui-luxe.css') }}"
        rel="stylesheet"
    >
</head>

<body
    class="{{
        auth()->user()?->role === 'employee'
            ? 'employee-app'
            : 'management-app role-'
                . (auth()->user()?->role ?? 'guest')
    }}"
>
    <a href="#main-content" class="app-skip-link">Langsung ke konten</a>
    @php
        $authenticatedUser = auth()->user();

        $roleLabel = match ($authenticatedUser?->role) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            'employee' => 'Karyawan',
            default => 'Pengguna',
        };

        $userInitials = collect(
            preg_split(
                '/\s+/',
                trim((string) ($authenticatedUser?->name ?? 'Pengguna'))
            )
        )
            ->filter()
            ->take(2)
            ->map(
                static fn (string $part): string =>
                    mb_strtoupper(mb_substr($part, 0, 1))
            )
            ->implode('');

        $navigationSections = [
            [
                'label' => 'Utama',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'route' => 'dashboard',
                        'patterns' => ['dashboard'],
                        'icon' => 'bi-grid-1x2-fill',
                        'roles' => ['hrd', 'admin', 'employee'],
                    ],
                ],
            ],
            [
                'label' => 'Manajemen',
                'items' => [
                    [
                        'label' => 'Data Karyawan',
                        'route' => 'employees.index',
                        'patterns' => ['employees.*'],
                        'icon' => 'bi-people',
                        'roles' => ['hrd', 'admin'],
                    ],
                    [
                        'label' => 'Data Cabang',
                        'route' => 'branches.index',
                        'patterns' => ['branches.*'],
                        'icon' => 'bi-building',
                        'roles' => ['hrd'],
                    ],                    [
                        'label' => 'Terminal Cabang',
                        'route' => 'branch-terminals.index',
                        'patterns' => ['branch-terminals.*'],
                        'icon' => 'bi-display',
                        'roles' => ['hrd', 'admin'],
                    ],
                    [
                        'label' => 'Pola Jadwal Kerja',
                        'route' => 'work-schedules.index',
                        'patterns' => ['work-schedules.*'],
                        'icon' => 'bi-calendar2-week',
                        'roles' => ['hrd'],
                    ],
                    [
                        'label' => 'Jadwal Karyawan',
                        'route' => 'employee-schedules.index',
                        'patterns' => ['employee-schedules.*'],
                        'icon' => 'bi-calendar-check',
                        'roles' => ['hrd'],
                    ],
                    [
                        'label' => 'Roster Mingguan',
                        'route' => 'weekly-rosters.index',
                        'patterns' => ['weekly-rosters.*'],
                        'icon' => 'bi-calendar3-week',
                        'roles' => ['hrd', 'admin'],
                        'requires_branch_assignment' => true,
                    ],

                    [
                        'label' => 'Pertukaran Jadwal',
                        'route' => 'schedule-swap-requests.index',
                        'patterns' => ['schedule-swap-requests.*'],
                        'icon' => 'bi-arrow-left-right',
                        'roles' => ['hrd', 'admin'],
                    ],
                ],
            ],
            [
                'label' => 'Presensi',
                'items' => [
                    [
                        'label' => 'Sesi Presensi',
                        'route' => 'attendance-sessions.index',
                        'patterns' => ['attendance-sessions.*'],
                        'icon' => 'bi-qr-code-scan',
                        'roles' => ['hrd', 'admin'],
                    ],
                    [
                        'label' => 'Monitoring Presensi',
                        'route' => 'attendance-monitoring.index',
                        'patterns' => ['attendance-monitoring.*'],
                        'icon' => 'bi-activity',
                        'roles' => ['hrd', 'admin'],
                    ],
                    [
                        'label' => 'Laporan Presensi',
                        'route' => 'attendance-reports.index',
                        'patterns' => ['attendance-reports.*'],
                        'icon' => 'bi-clipboard-data',
                        'roles' => ['hrd', 'admin'],
                    ],
                    [
                        'label' => 'Log Validasi',
                        'route' => 'attendance-validation-logs.index',
                        'patterns' => ['attendance-validation-logs.*'],
                        'icon' => 'bi-shield-check',
                        'roles' => ['hrd'],
                    ],
                    [
                        'label' => 'Presensi Saya',
                        'route' => 'attendance.create',
                        'patterns' => [
                            'attendance.create',
                            'attendance.store',
                        ],
                        'icon' => 'bi-fingerprint',
                        'roles' => ['employee'],
                    ],
                    [
                        'label' => 'Riwayat Presensi',
                        'route' => 'attendance.history',
                        'patterns' => ['attendance.history'],
                        'icon' => 'bi-clock-history',
                        'roles' => ['employee'],
                    ],
                ],
            ],
            [
                'label' => 'Akun',
                'items' => [
                    [
                        'label' => 'Ubah Password',
                        'route' => 'account.password.edit',
                        'patterns' => ['account.password.*'],
                        'icon' => 'bi-shield-lock',
                        'roles' => ['hrd', 'admin', 'employee'],
                    ],
                ],
            ],
        ];

        $navigationSections = collect($navigationSections)
            ->map(
                static function (array $section) use (
                    $authenticatedUser
                ): array {
                    $section['items'] = collect($section['items'])
                        ->filter(
                            static fn (array $item): bool =>
                                $authenticatedUser !== null
                                && in_array(
                                    $authenticatedUser->role,
                                    $item['roles'],
                                    true
                                )
                                && \Illuminate\Support\Facades\Route::has(
                                    $item['route']
                                )
                                && (
                                    ! ($item['requires_branch_assignment'] ?? false)
                                    || $authenticatedUser->hasRole('hrd')
                                    || $authenticatedUser->branch_id !== null
                                )
                        )
                        ->values()
                        ->all();

                    return $section;
                }
            )
            ->filter(
                static fn (array $section): bool =>
                    $section['items'] !== []
            )
            ->values()
            ->all();
    @endphp

    <div class="app-shell">
        <aside
            class="app-sidebar"
            aria-label="Navigasi utama"
        >
            <a
                href="{{ route('dashboard') }}"
                class="app-brand"
            >
                <span class="app-brand-mark">
                    <img
                        src="{{ asset('images/logo-pt-gadai-ogan-baru.png') }}"
                        alt="Logo PT Gadai Ogan Baru"
                    >
                </span>

                <span class="app-brand-copy">
                    <span class="app-brand-title">
                        Sistem Presensi
                    </span>

                    <span class="app-brand-subtitle">
                        PT Gadai Ogan Baru
                    </span>
                </span>
            </a>

            <nav class="app-nav">
                @foreach ($navigationSections as $section)
                    <section class="app-nav-section">
                        <h2 class="app-nav-heading">
                            {{ $section['label'] }}
                        </h2>

                        @foreach ($section['items'] as $item)
                            @php
                                $isActive = request()->routeIs(
                                    ...$item['patterns']
                                );
                            @endphp

                            <a
                                href="{{ route($item['route']) }}"
                                class="app-nav-link {{
                                    $isActive ? 'active' : ''
                                }}"
                                @if ($isActive)
                                    aria-current="page"
                                @endif
                            >
                                <i
                                    class="bi {{ $item['icon'] }}
                                        app-nav-icon"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    {{ $item['label'] }}
                                </span>
                            </a>
                        @endforeach
                    </section>
                @endforeach
            </nav>

            @auth
                <div class="app-sidebar-user">
                    <div class="app-user-card">
                        <div
                            class="d-flex align-items-center
                                gap-2 mb-3"
                        >
                            <span class="app-user-avatar">
                                {{ $userInitials ?: 'PG' }}
                            </span>

                            <span class="min-w-0">
                                <span class="app-user-name d-block">
                                    {{ $authenticatedUser->name }}
                                </span>

                                <span class="app-user-role d-block">
                                    {{ $roleLabel }}
                                </span>
                            </span>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                            class="mb-0"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-sm btn-light w-100"
                            >
                                <i
                                    class="bi bi-box-arrow-right me-1"
                                    aria-hidden="true"
                                ></i>

                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <div class="app-topbar-inner">
                    <div
                        class="d-flex align-items-center
                            gap-2 min-w-0"
                    >
                        <button
                            type="button"
                            class="app-icon-button d-lg-none"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#mobileNavigation"
                            aria-controls="mobileNavigation"
                            aria-label="Buka navigasi"
                        >
                            <i
                                class="bi bi-list fs-5"
                                aria-hidden="true"
                            ></i>
                        </button>

                        <a
                            href="{{ route('dashboard') }}"
                            class="app-mobile-brand d-lg-none"
                        >
                            <span class="app-brand-mark">
                                <img
                                    src="{{ asset('images/logo-pt-gadai-ogan-baru.png') }}"
                                    alt="Logo PT Gadai Ogan Baru"
                                >
                            </span>

                            <span class="app-topbar-title">
                                Sistem Presensi
                            </span>
                        </a>

                        <div class="d-none d-lg-block min-w-0">
                            @if (
                                request()->routeIs('dashboard')
                                && in_array(
                                    $authenticatedUser?->role,
                                    ['hrd', 'admin'],
                                    true
                                )
                            )
                                <div class="app-topbar-eyebrow">
                                    Selamat datang,
                                </div>

                                <div class="app-topbar-title">
                                    {{ $authenticatedUser?->role === 'hrd' ? 'Tim HRD' : 'Admin Cabang' }}
                                </div>

                                <div class="app-topbar-subtitle">
                                    Kelola presensi karyawan dengan lebih mudah dan akurat.
                                </div>
                            @else
                                <div class="app-topbar-eyebrow">
                                    PT Gadai Ogan Baru
                                </div>

                                <div class="app-topbar-title">
                                    @yield(
                                        'title',
                                        'Sistem Presensi Karyawan'
                                    )
                                </div>
                            @endif
                        </div>
                    </div>

                    @auth
                        <div class="dropdown">
                            <button
                                type="button"
                                class="btn btn-light
                                    d-flex align-items-center
                                    gap-2 px-2 px-sm-3"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <span class="app-user-avatar">
                                    {{ $userInitials ?: 'PG' }}
                                </span>

                                <span
                                    class="d-none d-sm-block
                                        text-start"
                                >
                                    <span
                                        class="d-block fw-bold
                                            text-truncate"
                                        style="max-width: 11rem;"
                                    >
                                        {{ $authenticatedUser->name }}
                                    </span>

                                    <span
                                        class="d-block small
                                            text-secondary"
                                    >
                                        {{ $roleLabel }}
                                    </span>
                                </span>

                                <i
                                    class="bi bi-chevron-down
                                        small d-none d-sm-inline"
                                    aria-hidden="true"
                                ></i>
                            </button>

                            <ul
                                class="dropdown-menu
                                    dropdown-menu-end"
                            >
                                <li>
                                    <a
                                        href="{{ route('account.password.edit') }}"
                                        class="dropdown-item"
                                    >
                                        <i
                                            class="bi bi-shield-lock me-2"
                                            aria-hidden="true"
                                        ></i>

                                        Ubah Password
                                    </a>
                                </li>

                                <li>
                                    <span
                                        class="dropdown-item-text
                                            small text-secondary"
                                    >
                                        Masuk sebagai {{ $roleLabel }}
                                    </span>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>
                                    <form
                                        method="POST"
                                        action="{{ route('logout') }}"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="dropdown-item
                                                text-danger"
                                        >
                                            <i
                                                class="bi
                                                    bi-box-arrow-right
                                                    me-2"
                                                aria-hidden="true"
                                            ></i>

                                            Keluar
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </header>

            <main id="main-content" class="app-content" tabindex="-1">
                @if (session('success'))
                    <div
                        class="alert alert-success
                            alert-dismissible fade show app-flash"
                        role="alert"
                    >
                        <i
                            class="bi bi-check-circle-fill
                                app-flash-icon"
                            aria-hidden="true"
                        ></i>

                        <div class="fw-bold">
                            Berhasil
                        </div>

                        <div>
                            {{ session('success') }}
                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Tutup"
                        ></button>
                    </div>
                @endif

                @if (session('error'))
                    <div
                        class="alert alert-danger
                            alert-dismissible fade show app-flash"
                        role="alert"
                    >
                        <i
                            class="bi bi-exclamation-octagon-fill
                                app-flash-icon"
                            aria-hidden="true"
                        ></i>

                        <div class="fw-bold">
                            Proses gagal
                        </div>

                        <div>
                            {{ session('error') }}
                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Tutup"
                        ></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="alert alert-danger app-flash"
                        role="alert"
                    >
                        <i
                            class="bi bi-exclamation-triangle-fill
                                app-flash-icon"
                            aria-hidden="true"
                        ></i>

                        <p class="fw-bold mb-2">
                            Data belum dapat diproses.
                        </p>

                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="app-footer">
                <span>
                    © {{ now()->year }} PT Gadai Ogan Baru
                </span>

                <span class="d-none d-sm-inline">
                    · Sistem Presensi QR TOTP dan Geofencing
                </span>
            </footer>
        </div>
    </div>

    <div
        id="mobileNavigation"
        class="offcanvas offcanvas-start
            app-mobile-navigation"
        tabindex="-1"
        aria-labelledby="mobileNavigationLabel"
    >
        <div class="offcanvas-header">
            <div
                id="mobileNavigationLabel"
                class="d-flex align-items-center gap-2"
            >
                <span class="app-brand-mark">
                    <img
                        src="{{ asset('images/logo-pt-gadai-ogan-baru.png') }}"
                        alt="Logo PT Gadai Ogan Baru"
                    >
                </span>

                <span class="app-brand-copy">
                    <span class="app-brand-title">
                        Sistem Presensi
                    </span>

                    <span class="app-brand-subtitle">
                        PT Gadai Ogan Baru
                    </span>
                </span>
            </div>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"
                aria-label="Tutup navigasi"
            ></button>
        </div>

        <div class="offcanvas-body d-flex flex-column p-0">
            <nav class="app-nav">
                @foreach ($navigationSections as $section)
                    <section class="app-nav-section">
                        <h2 class="app-nav-heading">
                            {{ $section['label'] }}
                        </h2>

                        @foreach ($section['items'] as $item)
                            @php
                                $isActive = request()->routeIs(
                                    ...$item['patterns']
                                );
                            @endphp

                            <a
                                href="{{ route($item['route']) }}"
                                class="app-nav-link {{
                                    $isActive ? 'active' : ''
                                }}"
                                @if ($isActive)
                                    aria-current="page"
                                @endif
                            >
                                <i
                                    class="bi {{ $item['icon'] }}
                                        app-nav-icon"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    {{ $item['label'] }}
                                </span>
                            </a>
                        @endforeach
                    </section>
                @endforeach
            </nav>

            @auth
                <div class="app-sidebar-user">
                    <div class="app-user-card">
                        <div
                            class="d-flex align-items-center
                                gap-2 mb-3"
                        >
                            <span class="app-user-avatar">
                                {{ $userInitials ?: 'PG' }}
                            </span>

                            <span class="min-w-0">
                                <span class="app-user-name d-block">
                                    {{ $authenticatedUser->name }}
                                </span>

                                <span class="app-user-role d-block">
                                    {{ $roleLabel }}
                                </span>
                            </span>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                            class="mb-0"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-sm btn-light w-100"
                            >
                                <i
                                    class="bi bi-box-arrow-right me-1"
                                    aria-hidden="true"
                                ></i>

                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>


    @if (auth()->user()?->role === 'employee')
        <nav
            class="employee-global-nav"
            aria-label="Navigasi utama karyawan"
        >
            <a
                href="{{ route('dashboard') }}"
                class="employee-global-nav-link {{
                    request()->routeIs('dashboard')
                        ? 'active'
                        : ''
                }}"
            >
                <i
                    class="bi bi-house-door"
                    aria-hidden="true"
                ></i>

                <span>Beranda</span>
            </a>

            @if (
                \Illuminate\Support\Facades\Route::has(
                    'attendance.create'
                )
            )
                <a
                    href="{{ route('attendance.create') }}"
                    class="employee-global-nav-link {{
                        request()->routeIs(
                            'attendance.create',
                            'attendance.store'
                        )
                            ? 'active'
                            : ''
                    }}"
                >
                    <i
                        class="bi bi-qr-code-scan"
                        aria-hidden="true"
                    ></i>

                    <span>Presensi</span>
                </a>
            @endif

            @if (
                \Illuminate\Support\Facades\Route::has(
                    'attendance.history'
                )
            )
                <a
                    href="{{ route('attendance.history') }}"
                    class="employee-global-nav-link {{
                        request()->routeIs(
                            'attendance.history'
                        )
                            ? 'active'
                            : ''
                    }}"
                >
                    <i
                        class="bi bi-clock-history"
                        aria-hidden="true"
                    ></i>

                    <span>Riwayat</span>
                </a>
            @endif

            <a
                href="{{ route('dashboard') }}#employee-account"
                class="employee-global-nav-link"
            >
                <i
                    class="bi bi-person"
                    aria-hidden="true"
                ></i>

                <span>Akun</span>
            </a>
        </nav>
    @endif
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    ></script>

    @stack('scripts')
</body>
</html>
