<!doctype html>
<html lang="id">
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
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --primary-dark: #16324f;
            --primary-medium: #24557a;
            --primary-light: #e9f2f8;
            --page-background: #f3f6f9;
            --surface: #ffffff;
            --border-color: #dde5eb;
            --text-main: #24313d;
            --text-muted: #6c7884;
        }

        body {
            min-height: 100vh;
            color: var(--text-main);
            background-color: var(--page-background);
        }

        .app-navbar {
            background-color: var(--primary-dark);
            box-shadow: 0 0.25rem 1rem
                rgba(22, 50, 79, 0.15);
        }

        .app-brand {
            color: #ffffff;
            font-weight: 700;
            text-decoration: none;
        }

        .app-brand:hover,
        .app-brand:focus {
            color: #ffffff;
        }

        .app-navbar .nav-link {
            border-radius: 0.5rem;
            color: rgba(255, 255, 255, 0.78);
            font-weight: 500;
        }

        .app-navbar .nav-link:hover,
        .app-navbar .nav-link:focus {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .app-navbar .nav-link.active {
            color: var(--primary-dark);
            background-color: #ffffff;
        }

        .user-information {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.875rem;
        }

        .user-name {
            color: #ffffff;
            font-weight: 600;
        }

        .page-container {
            width: 100%;
            max-width: 76rem;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-title {
            margin-bottom: 0.25rem;
            font-size: 1.75rem;
            font-weight: 700;
        }

        .page-description {
            margin-bottom: 0;
            color: var(--text-muted);
        }

        .content-card {
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            background-color: var(--surface);
            box-shadow: 0 0.25rem 1rem
                rgba(22, 50, 79, 0.05);
        }

        .btn-primary {
            border-color: var(--primary-dark);
            background-color: var(--primary-dark);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            border-color: var(--primary-medium);
            background-color: var(--primary-medium);
        }

        .btn-outline-primary {
            border-color: var(--primary-dark);
            color: var(--primary-dark);
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            border-color: var(--primary-dark);
            color: #ffffff;
            background-color: var(--primary-dark);
        }

        .app-footer {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        @media (max-width: 991.98px) {
            .app-navbar .navbar-collapse {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid
                    rgba(255, 255, 255, 0.15);
            }

            .app-navbar .nav-link {
                margin-bottom: 0.25rem;
                padding-right: 0.75rem;
                padding-left: 0.75rem;
            }

            .user-panel {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid
                    rgba(255, 255, 255, 0.15);
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    @php
        $authenticatedUser = auth()->user();

        $roleLabel = match ($authenticatedUser?->role) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            'employee' => 'Karyawan',
            default => 'Pengguna',
        };
    @endphp

    <nav class="navbar navbar-expand-lg navbar-dark app-navbar">
        <div class="container page-container py-2">
            <a
                href="{{ route('dashboard') }}"
                class="navbar-brand app-brand"
            >
                Sistem Presensi Karyawan
            </a>

            <button
                type="button"
                class="navbar-toggler border-light"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavigation"
                aria-controls="mainNavigation"
                aria-expanded="false"
                aria-label="Buka navigasi"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div
                id="mainNavigation"
                class="collapse navbar-collapse"
            >
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a
                            href="{{ route('dashboard') }}"
                            class="nav-link {{
                                request()->routeIs('dashboard')
                                    ? 'active'
                                    : ''
                            }}"
                            @if (request()->routeIs('dashboard'))
                                aria-current="page"
                            @endif
                        >
                            Dashboard
                        </a>
                    </li>

                    @if (
                        $authenticatedUser !== null
                        && in_array(
                            $authenticatedUser->role,
                            ['hrd', 'admin'],
                            true
                        )
                    )
                        <li class="nav-item">
                            <a
                                href="{{ route('employees.index') }}"
                                class="nav-link {{
                                    request()->routeIs('employees.*')
                                        ? 'active'
                                        : ''
                                }}"
                                @if (
                                    request()->routeIs('employees.*')
                                )
                                    aria-current="page"
                                @endif
                            >
                                Data Karyawan
                            </a>
                        </li>
                    @endif

                    @if ($authenticatedUser?->hasRole('hrd'))
                        <li class="nav-item">
                            <a
                                href="{{ route('branches.index') }}"
                                class="nav-link {{
                                    request()->routeIs('branches.*')
                                        ? 'active'
                                        : ''
                                }}"
                                @if (
                                    request()->routeIs('branches.*')
                                )
                                    aria-current="page"
                                @endif
                            >
                                Data Cabang
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                href="{{
                                    route('work-schedules.index')
                                }}"
                                class="nav-link {{
                                    request()->routeIs(
                                        'work-schedules.*'
                                    )
                                        ? 'active'
                                        : ''
                                }}"
                                @if (
                                    request()->routeIs(
                                        'work-schedules.*'
                                    )
                                )
                                    aria-current="page"
                                @endif
                            >
                                Pola Jadwal Kerja
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                href="{{
                                    route(
                                        'employee-schedules.index'
                                    )
                                }}"
                                class="nav-link {{
                                    request()->routeIs(
                                        'employee-schedules.*'
                                    )
                                        ? 'active'
                                        : ''
                                }}"
                                @if (
                                    request()->routeIs(
                                        'employee-schedules.*'
                                    )
                                )
                                    aria-current="page"
                                @endif
                            >
                                Jadwal Harian
                            </a>
                        </li>
                    @endif
                </ul>

                @auth
                    <div
                        class="user-panel d-lg-flex
                            align-items-center gap-3"
                    >
                        <div class="user-information">
                            <span class="user-name">
                                {{ $authenticatedUser->name }}
                            </span>

                            <span class="d-block">
                                {{ $roleLabel }}
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
                                class="btn btn-sm btn-outline-light"
                            >
                                Keluar
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container page-container py-4">
        @if (session('success'))
            <div
                class="alert alert-success
                    alert-dismissible fade show"
                role="alert"
            >
                {{ session('success') }}

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
                    alert-dismissible fade show"
                role="alert"
            >
                {{ session('error') }}

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
                class="alert alert-danger"
                role="alert"
            >
                <p class="fw-semibold mb-2">
                    Data belum dapat diproses.
                </p>

                <ul class="mb-0">
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

    <footer class="container page-container pb-4">
        <div class="app-footer text-center">
            Sistem Presensi Karyawan PT Gadai Ogan Baru
        </div>
    </footer>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    ></script>

    @stack('scripts')
</body>
</html>