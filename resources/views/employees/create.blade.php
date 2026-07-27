@extends('layouts.app')

@section('title', 'Tambah Karyawan')

@push('styles')
    <style>
        .employee-create-page {
            --employee-create-surface: var(--neutral-0);
            --employee-create-border: var(--neutral-200);
            --employee-create-muted: var(--neutral-600);
            --employee-create-soft: var(--brand-50);
        }

        .employee-create-overview-card,
        .employee-create-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--employee-create-border);
            border-radius: var(--radius-lg);
            background: var(--employee-create-surface);
            box-shadow: var(--shadow-xs);
        }

        .employee-create-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--employee-create-border);
            background: var(--neutral-25);
        }

        .employee-create-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .employee-create-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--employee-create-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .employee-create-overview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .employee-create-overview-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .employee-create-overview-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--employee-create-soft);
            font-size: 1rem;
        }

        .employee-create-overview-label {
            color: var(--employee-create-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .employee-create-overview-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
        }

        .employee-create-warning {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            margin: var(--space-4);
            padding: var(--space-4);
            border: 1px solid #f0ddb0;
            border-radius: var(--radius-md);
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .employee-create-warning-icon {
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

        .employee-create-warning-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .employee-create-warning-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .employee-create-form-body {
            padding: var(--space-4);
        }

        .employee-create-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .employee-create-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .employee-create-overview-grid,
            .employee-create-form-body {
                padding: var(--space-5);
            }

            .employee-create-warning {
                margin: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .employee-create-overview-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .employee-create-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .employee-create-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="employee-create-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Tambah Karyawan
                </h1>

                <p class="page-description">
                    Tambahkan profil karyawan dan akun untuk
                    mengakses sistem presensi.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route('employees.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Karyawan
                </a>
            </div>
        </header>

        <section
            class="employee-create-overview-card"
            aria-labelledby="employee-create-overview-heading"
        >
            <div class="employee-create-card-header">
                <div>
                    <h2
                        id="employee-create-overview-heading"
                        class="employee-create-card-title"
                    >
                        Informasi Pembuatan Karyawan
                    </h2>

                    <p class="employee-create-card-copy">
                        Profil, penempatan, dan akun login dibuat
                        dalam satu proses.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Data baru
                </span>
            </div>

            <div class="employee-create-overview-grid">
                <article class="employee-create-overview-item">
                    <span class="employee-create-overview-icon">
                        <i
                            class="bi bi-person-vcard"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-create-overview-label">
                        Profil
                    </div>

                    <div class="employee-create-overview-value">
                        Nomor, nama, jabatan, dan nomor telepon.
                    </div>
                </article>

                <article class="employee-create-overview-item">
                    <span class="employee-create-overview-icon">
                        <i
                            class="bi bi-building"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-create-overview-label">
                        Penempatan
                    </div>

                    <div class="employee-create-overview-value">
                        Cabang dan status kepegawaian.
                    </div>
                </article>

                <article class="employee-create-overview-item">
                    <span class="employee-create-overview-icon">
                        <i
                            class="bi bi-shield-lock"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-create-overview-label">
                        Akun
                    </div>

                    <div class="employee-create-overview-value">
                        Email, status akun, dan kata sandi.
                    </div>
                </article>
            </div>
        </section>

        <section
            class="employee-create-form-card"
            aria-labelledby="create-employee-heading"
        >
            <div class="employee-create-card-header">
                <div>
                    <h2
                        id="create-employee-heading"
                        class="employee-create-card-title"
                    >
                        Formulir Karyawan Baru
                    </h2>

                    <p class="employee-create-card-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    Wajib diisi
                </span>
            </div>

            @if ($branches->isEmpty())
                <div
                    class="employee-create-warning"
                    role="alert"
                >
                    <span class="employee-create-warning-icon">
                        <i
                            class="bi bi-exclamation-triangle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h3 class="employee-create-warning-title">
                            Cabang aktif tidak tersedia.
                        </h3>

                        <p class="employee-create-warning-copy">
                            Karyawan baru belum dapat ditambahkan
                            karena tidak ada cabang aktif yang dapat
                            dipilih.
                        </p>

                        <a
                            href="{{ route('branches.index') }}"
                            class="btn btn-sm
                                btn-outline-dark mt-3"
                        >
                            Kelola Cabang
                        </a>
                    </div>
                </div>
            @endif

            <div class="employee-create-form-body">
                <form
                    method="POST"
                    action="{{ route('employees.store') }}"
                >
                    @csrf

                    @include(
                        'employees._form',
                        [
                            'branches' => $branches,
                        ]
                    )

                    <div class="employee-create-actions">
                        <a
                            href="{{ route('employees.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            @disabled($branches->isEmpty())
                        >
                            <i
                                class="bi bi-save me-2"
                                aria-hidden="true"
                            ></i>

                            Simpan Karyawan
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
