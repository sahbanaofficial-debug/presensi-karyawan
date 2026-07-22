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

    <title>Login | Sistem Presensi Karyawan</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >

    <style>
        :root {
            --primary-dark: #16324f;
            --primary-medium: #24557a;
            --primary-light: #e9f2f8;
            --surface: #ffffff;
            --page-background: #f3f6f9;
            --text-main: #24313d;
            --text-muted: #6c7884;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--text-main);
            background-color: var(--page-background);
        }

        .login-page {
            min-height: 100vh;
        }

        .information-panel {
            min-height: 100vh;
            padding: 4rem;
            color: #ffffff;
            background:
                linear-gradient(
                    145deg,
                    rgba(22, 50, 79, 0.97),
                    rgba(36, 85, 122, 0.92)
                );
        }

        .brand-mark {
            display: inline-flex;
            width: 4rem;
            height: 4rem;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
            background-color: rgba(255, 255, 255, 0.12);
        }

        .information-content {
            width: 100%;
            max-width: 34rem;
        }

        .information-list {
            margin-top: 2rem;
            padding-left: 1.25rem;
        }

        .information-list li {
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.86);
        }

        .form-panel {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-color: var(--surface);
        }

        .login-card {
            width: 100%;
            max-width: 28rem;
        }

        .mobile-brand {
            color: var(--primary-dark);
            font-weight: 700;
        }

        .form-control {
            min-height: 3rem;
            border-color: #ccd5dd;
        }

        .form-control:focus {
            border-color: var(--primary-medium);
            box-shadow: 0 0 0 0.2rem rgba(36, 85, 122, 0.15);
        }

        .btn-login {
            min-height: 3rem;
            border-color: var(--primary-dark);
            background-color: var(--primary-dark);
            font-weight: 600;
        }

        .btn-login:hover,
        .btn-login:focus {
            border-color: var(--primary-medium);
            background-color: var(--primary-medium);
        }

        .form-check-input:checked {
            border-color: var(--primary-dark);
            background-color: var(--primary-dark);
        }

        .support-text {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        @media (max-width: 991.98px) {
            .form-panel {
                background-color: var(--page-background);
            }

            .login-card {
                padding: 2rem;
                border: 1px solid #e0e6eb;
                border-radius: 1rem;
                background-color: var(--surface);
                box-shadow: 0 0.5rem 2rem rgba(22, 50, 79, 0.08);
            }
        }
    </style>
</head>

<body>
    <main class="container-fluid">
        <div class="row login-page">
            <section
                class="col-lg-7 d-none d-lg-flex information-panel
                    align-items-center justify-content-center"
                aria-label="Informasi sistem"
            >
                <div class="information-content">
                    <div class="brand-mark mb-4" aria-hidden="true">
                        P
                    </div>

                    <p class="mb-2 text-uppercase small fw-semibold opacity-75">
                        PT Gadai Ogan Baru
                    </p>

                    <h1 class="display-5 fw-bold mb-4">
                        Sistem Presensi Karyawan
                    </h1>

                    <p class="fs-5 opacity-75">
                        Sistem presensi berbasis web dengan validasi jadwal,
                        QR Code dinamis, dan lokasi kantor.
                    </p>

                    <ul class="information-list">
                        <li>
                            Akses sistem berdasarkan peran pengguna.
                        </li>
                        <li>
                            Data presensi tersimpan secara terpusat.
                        </li>
                        <li>
                            Aktivitas pengguna dicatat berdasarkan waktu server.
                        </li>
                    </ul>
                </div>
            </section>

            <section class="col-lg-5 form-panel">
                <div class="login-card">
                    <div class="d-lg-none mb-4">
                        <p class="mobile-brand mb-1">
                            Sistem Presensi Karyawan
                        </p>
                        <p class="support-text mb-0">
                            PT Gadai Ogan Baru
                        </p>
                    </div>

                    <div class="mb-4">
                        <h2 class="fw-bold mb-2">
                            Masuk ke sistem
                        </h2>

                        <p class="support-text mb-0">
                            Gunakan akun yang telah terdaftar.
                        </p>
                    </div>

                    @if (session('success'))
                        <div
                            class="alert alert-success"
                            role="alert"
                        >
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div
                            class="alert alert-danger"
                            role="alert"
                        >
                            Data login belum valid. Periksa kembali email
                            dan kata sandi.
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('login.store') }}"
                    >
                        @csrf

                        <div class="mb-3">
                            <label
                                for="email"
                                class="form-label fw-semibold"
                            >
                                Alamat email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="nama@perusahaan.com"
                                autocomplete="username"
                                maxlength="255"
                                required
                                autofocus
                            >

                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label
                                for="password"
                                class="form-label fw-semibold"
                            >
                                Kata sandi
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Masukkan kata sandi"
                                autocomplete="current-password"
                                required
                            >

                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-check mb-4">
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
                                class="form-check-label"
                            >
                                Ingat saya pada perangkat ini
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-login w-100"
                        >
                            Masuk
                        </button>
                    </form>

                    <p class="support-text text-center mt-4 mb-0">
                        Hubungi HRD apabila akun tidak dapat digunakan.
                    </p>
                </div>
            </section>
        </div>
    </main>
</body>
</html>