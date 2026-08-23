@extends('layouts.app')

@section('title', 'Ubah Password')

@push('styles')
    <style>
        .account-password-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(16rem, 0.65fr);
            gap: var(--space-5);
            align-items: start;
        }

        .account-password-card,
        .account-security-card {
            overflow: hidden;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .account-password-card-header {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-5);
            border-bottom: 1px solid var(--neutral-200);
            background: var(--neutral-25);
        }

        .account-password-icon {
            display: inline-flex;
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 1.1rem;
        }

        .account-password-heading {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .account-password-copy {
            margin: var(--space-1) 0 0;
            color: var(--neutral-600);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .account-password-form {
            padding: var(--space-5);
        }

        .account-password-field + .account-password-field {
            margin-top: var(--space-4);
        }

        .account-password-label {
            margin-bottom: var(--space-2);
            color: var(--neutral-800);
            font-size: 0.8125rem;
            font-weight: 800;
        }

        .account-password-input-group {
            position: relative;
        }

        .account-password-input-group .form-control {
            padding-right: 3rem;
        }

        .account-password-toggle {
            position: absolute;
            top: 50%;
            right: 0.55rem;
            z-index: 5;
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: var(--radius-sm);
            color: var(--neutral-600);
            background: transparent;
        }

        .account-password-toggle:hover,
        .account-password-toggle:focus-visible {
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .account-password-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: var(--space-3);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        .account-security-card {
            padding: var(--space-5);
        }

        .account-security-title {
            margin: 0 0 var(--space-3);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .account-security-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .account-security-item {
            display: flex;
            align-items: flex-start;
            gap: var(--space-2);
            color: var(--neutral-600);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .account-security-item i {
            margin-top: 0.2rem;
            color: var(--success-600);
        }

        .account-security-note {
            margin-top: var(--space-4);
            padding: var(--space-3);
            border: 1px solid #f0ddb0;
            border-radius: var(--radius-md);
            color: var(--warning-700);
            background: var(--warning-50);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        @media (max-width: 991.98px) {
            .account-password-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .account-password-card-header,
            .account-password-form,
            .account-security-card {
                padding: var(--space-4);
            }

            .account-password-actions {
                align-items: stretch;
                flex-direction: column-reverse;
            }

            .account-password-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <header class="page-header">
        <h1 class="page-title">Ubah Password</h1>

        <p class="page-description">
            Perbarui password akun yang sedang digunakan agar akses aplikasi tetap aman.
        </p>
    </header>

    <div class="account-password-layout">
        <section class="account-password-card">
            <div class="account-password-card-header">
                <span class="account-password-icon">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                </span>

                <div>
                    <h2 class="account-password-heading">
                        Keamanan akun {{ auth()->user()->name }}
                    </h2>

                    <p class="account-password-copy">
                        Masukkan password saat ini untuk memastikan perubahan dilakukan oleh pemilik akun.
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('account.password.update') }}"
                class="account-password-form"
            >
                @csrf
                @method('PUT')

                <div class="account-password-field">
                    <label
                        for="current_password"
                        class="form-label account-password-label"
                    >
                        Password saat ini
                    </label>

                    <div class="account-password-input-group">
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            autocomplete="current-password"
                            required
                            autofocus
                        >

                        <button
                            type="button"
                            class="account-password-toggle"
                            data-password-toggle="current_password"
                            aria-label="Tampilkan password saat ini"
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    @error('current_password')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="account-password-field">
                    <label
                        for="password"
                        class="form-label account-password-label"
                    >
                        Password baru
                    </label>

                    <div class="account-password-input-group">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="account-password-toggle"
                            data-password-toggle="password"
                            aria-label="Tampilkan password baru"
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    @error('password')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="account-password-field">
                    <label
                        for="password_confirmation"
                        class="form-label account-password-label"
                    >
                        Konfirmasi password baru
                    </label>

                    <div class="account-password-input-group">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="account-password-toggle"
                            data-password-toggle="password_confirmation"
                            aria-label="Tampilkan konfirmasi password baru"
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="account-password-actions">
                    <a
                        href="{{ route('dashboard') }}"
                        class="btn btn-light"
                    >
                        Batal
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-2" aria-hidden="true"></i>
                        Simpan password baru
                    </button>
                </div>
            </form>
        </section>

        <aside class="account-security-card">
            <h2 class="account-security-title">
                Ketentuan password baru
            </h2>

            <ul class="account-security-list">
                <li class="account-security-item">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>Minimal 12 karakter.</span>
                </li>
                <li class="account-security-item">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>Mengandung huruf besar dan huruf kecil.</span>
                </li>
                <li class="account-security-item">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>Mengandung angka dan simbol.</span>
                </li>
                <li class="account-security-item">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>Berbeda dari password yang sedang digunakan.</span>
                </li>
            </ul>

            <div class="account-security-note">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                Jangan memberikan password kepada orang lain atau menyimpannya di perangkat umum.
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-password-toggle]')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    const input = document.getElementById(
                        button.dataset.passwordToggle
                    );

                    if (input === null) {
                        return;
                    }

                    const passwordIsVisible = input.type === 'text';
                    input.type = passwordIsVisible ? 'password' : 'text';

                    const icon = button.querySelector('i');

                    if (icon !== null) {
                        icon.className = passwordIsVisible
                            ? 'bi bi-eye'
                            : 'bi bi-eye-slash';
                    }

                    button.setAttribute(
                        'aria-label',
                        passwordIsVisible
                            ? 'Tampilkan password'
                            : 'Sembunyikan password'
                    );
                });
            });
    </script>
@endpush
