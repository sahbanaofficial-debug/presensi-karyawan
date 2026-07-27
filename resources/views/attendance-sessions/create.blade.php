@extends('layouts.app')

@section('title', 'Buka Sesi Presensi')

@push('styles')
    <style>
        .attendance-session-create-page {
            --session-create-surface: var(--neutral-0);
            --session-create-border: var(--neutral-200);
            --session-create-muted: var(--neutral-600);
            --session-create-soft: var(--brand-50);
        }

        .session-create-alert,
        .session-create-info-card,
        .session-create-form-card,
        .session-create-mechanism-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--session-create-border);
            border-radius: var(--radius-lg);
            background: var(--session-create-surface);
            box-shadow: var(--shadow-xs);
        }

        .session-create-alert {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border-color: #f0ddb0;
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .session-create-alert-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: rgba(201, 130, 0, 0.09);
            font-size: 1rem;
        }

        .session-create-alert-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .session-create-alert-copy {
            margin: 0 0 var(--space-3);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .session-create-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--session-create-border);
            background: var(--neutral-25);
        }

        .session-create-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .session-create-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--session-create-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .session-create-info-body {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .session-create-info-item {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .session-create-info-icon {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--session-create-soft);
            font-size: 0.9375rem;
        }

        .session-create-info-label {
            color: var(--session-create-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .session-create-info-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .session-create-form-body {
            padding: var(--space-4);
        }

        .session-create-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        .session-create-mechanism-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .session-create-mechanism-step {
            position: relative;
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .session-create-step-number {
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-pill);
            color: var(--brand-700);
            background: var(--brand-100);
            font-size: 0.75rem;
            font-weight: 800;
        }

        .session-create-step-title {
            margin: 0 0 var(--space-2);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .session-create-step-copy {
            margin: 0;
            color: var(--session-create-muted);
            font-size: 0.75rem;
            line-height: 1.65;
        }

        @media (min-width: 576px) {
            .session-create-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .session-create-info-body,
            .session-create-form-body,
            .session-create-mechanism-grid {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .session-create-info-body,
            .session-create-mechanism-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .session-create-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .session-create-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $availableBranchCount = $branches->count();

        $canOpenSession = $availableBranchCount > 0;

        $authenticatedUser = auth()->user();

        $creatorRoleLabel = match (
            $authenticatedUser?->role
        ) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            default => 'Pengguna',
        };
    @endphp

    <div class="attendance-session-create-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Buka Sesi Presensi
                </h1>

                <p class="page-description">
                    Buat sesi presensi masuk atau pulang dengan
                    QR Code dinamis berbasis TOTP.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route(
                        'attendance-sessions.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-list-ul me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Sesi
                </a>
            </div>
        </header>

        @if ($availableBranchCount === 0)
            <div
                class="session-create-alert"
                role="alert"
            >
                <span class="session-create-alert-icon">
                    <i
                        class="bi bi-exclamation-triangle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h2 class="session-create-alert-title">
                        Cabang yang memenuhi syarat tidak tersedia.
                    </h2>

                    <p class="session-create-alert-copy">
                        Sesi presensi belum dapat dibuka karena
                        tidak terdapat cabang aktif dengan
                        konfigurasi geofence yang lengkap.
                    </p>

                    @if ($authenticatedUser?->role === 'hrd')
                        <a
                            href="{{ route('branches.index') }}"
                            class="btn btn-sm btn-outline-dark"
                        >
                            Periksa Data Cabang
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <section
            class="session-create-info-card"
            aria-labelledby="session-information-heading"
        >
            <div class="session-create-card-header">
                <div>
                    <h2
                        id="session-information-heading"
                        class="session-create-card-title"
                    >
                        Informasi Pembukaan Sesi
                    </h2>

                    <p class="session-create-card-copy">
                        Ringkasan akun pembuat dan ketersediaan
                        cabang sebelum sesi dibuka.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    {{ $availableBranchCount }}
                    cabang tersedia
                </span>
            </div>

            <div class="session-create-info-body">
                <article class="session-create-info-item">
                    <span class="session-create-info-icon">
                        <i
                            class="bi bi-person-badge"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="session-create-info-label">
                        Peran Pembuat
                    </div>

                    <div class="session-create-info-value">
                        {{ $creatorRoleLabel }}
                    </div>
                </article>

                <article class="session-create-info-item">
                    <span class="session-create-info-icon">
                        <i
                            class="bi bi-person"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="session-create-info-label">
                        Nama Akun
                    </div>

                    <div class="session-create-info-value">
                        {{ $authenticatedUser?->name ?? '-' }}
                    </div>
                </article>

                <article class="session-create-info-item">
                    <span class="session-create-info-icon">
                        <i
                            class="bi bi-building"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="session-create-info-label">
                        Cabang Tersedia
                    </div>

                    <div class="session-create-info-value">
                        {{ $availableBranchCount }}
                        cabang aktif
                    </div>
                </article>
            </div>
        </section>

        <section
            class="session-create-form-card"
            aria-labelledby="create-attendance-session-heading"
        >
            <div class="session-create-card-header">
                <div>
                    <h2
                        id="create-attendance-session-heading"
                        class="session-create-card-title"
                    >
                        Formulir Sesi Presensi
                    </h2>

                    <p class="session-create-card-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Data wajib
                </span>
            </div>

            <div class="session-create-form-body">
                <form
                    method="POST"
                    action="{{ route(
                        'attendance-sessions.store'
                    ) }}"
                >
                    @csrf

                    @include(
                        'attendance-sessions._form',
                        [
                            'branches' => $branches,
                        ]
                    )

                    <div class="session-create-actions">
                        <a
                            href="{{ route(
                                'attendance-sessions.index'
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            @disabled(! $canOpenSession)
                        >
                            <i
                                class="bi bi-play-circle me-2"
                                aria-hidden="true"
                            ></i>

                            Buka Sesi Presensi
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section
            class="session-create-mechanism-card"
            aria-labelledby="dynamic-qr-mechanism-heading"
        >
            <div class="session-create-card-header">
                <div>
                    <h2
                        id="dynamic-qr-mechanism-heading"
                        class="session-create-card-title"
                    >
                        Mekanisme QR Code Dinamis
                    </h2>

                    <p class="session-create-card-copy">
                        Alur pembentukan dan penggunaan token
                        TOTP pada sesi presensi.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    TOTP
                </span>
            </div>

            <div class="session-create-mechanism-grid">
                <article class="session-create-mechanism-step">
                    <span class="session-create-step-number">
                        1
                    </span>

                    <h3 class="session-create-step-title">
                        1. Secret Dibuat Server
                    </h3>

                    <p class="session-create-step-copy">
                        Sistem membuat secret TOTP secara acak
                        dan menyimpannya dalam bentuk terenkripsi.
                    </p>
                </article>

                <article class="session-create-mechanism-step">
                    <span class="session-create-step-number">
                        2
                    </span>

                    <h3 class="session-create-step-title">
                        2. Token Berubah Berkala
                    </h3>

                    <p class="session-create-step-copy">
                        Kode enam digit diperbarui setiap
                        30 detik selama sesi masih aktif.
                    </p>
                </article>

                <article class="session-create-mechanism-step">
                    <span class="session-create-step-number">
                        3
                    </span>

                    <h3 class="session-create-step-title">
                        3. Payload Terbatas
                    </h3>

                    <p class="session-create-step-copy">
                        QR Code hanya memuat UUID publik sesi
                        dan token TOTP, tanpa secret internal.
                    </p>
                </article>
            </div>
        </section>
    </div>
@endsection
