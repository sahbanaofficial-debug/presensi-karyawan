@extends('layouts.app')

@section('title', 'Edit Karyawan')

@push('styles')
    <style>
        .employee-edit-page {
            --employee-edit-surface: var(--neutral-0);
            --employee-edit-border: var(--neutral-200);
            --employee-edit-muted: var(--neutral-600);
            --employee-edit-soft: var(--brand-50);
        }

        .employee-edit-summary-card,
        .employee-edit-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--employee-edit-border);
            border-radius: var(--radius-lg);
            background: var(--employee-edit-surface);
            box-shadow: var(--shadow-xs);
        }

        .employee-edit-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--employee-edit-border);
            background: var(--neutral-25);
        }

        .employee-edit-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .employee-edit-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--employee-edit-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .employee-edit-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .employee-edit-summary-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .employee-edit-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--employee-edit-soft);
            font-size: 1rem;
        }

        .employee-edit-summary-label {
            color: var(--employee-edit-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .employee-edit-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .employee-edit-warning {
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

        .employee-edit-warning-icon {
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

        .employee-edit-warning-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .employee-edit-warning-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .employee-edit-form-body {
            padding: var(--space-4);
        }

        .employee-edit-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .employee-edit-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .employee-edit-summary-grid,
            .employee-edit-form-body {
                padding: var(--space-5);
            }

            .employee-edit-warning {
                margin: var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .employee-edit-summary-grid {
                grid-template-columns: repeat(
                    2,
                    minmax(0, 1fr)
                );
            }
        }

        @media (max-width: 575.98px) {
            .employee-edit-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .employee-edit-summary-grid {
                grid-template-columns: 1fr;
            }

            .employee-edit-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $employmentStatusLabel =
            $employee->employment_status === 'active'
                ? 'Aktif'
                : 'Tidak aktif';

        $employmentStatusClass =
            $employee->employment_status === 'active'
                ? 'text-bg-success'
                : 'text-bg-secondary';

        $accountStatusLabel =
            $employee->user?->status === 'active'
                ? 'Aktif'
                : 'Tidak aktif';

        $accountStatusClass =
            $employee->user?->status === 'active'
                ? 'text-bg-success'
                : 'text-bg-secondary';
    @endphp

    <div class="employee-edit-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Edit Karyawan
                </h1>

                <p class="page-description">
                    Perbarui profil dan akun
                    {{ $employee->full_name }}.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <a
                    href="{{ route('employees.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-list-ul me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Karyawan
                </a>

                <a
                    href="{{ route(
                        'employees.show',
                        $employee
                    ) }}"
                    class="btn btn-outline-primary"
                >
                    <i
                        class="bi bi-eye me-2"
                        aria-hidden="true"
                    ></i>

                    Detail Karyawan
                </a>
            </div>
        </header>

        <section
            class="employee-edit-summary-card"
            aria-labelledby="employee-edit-summary-heading"
        >
            <div class="employee-edit-card-header">
                <div>
                    <h2
                        id="employee-edit-summary-heading"
                        class="employee-edit-card-title"
                    >
                        Ringkasan Karyawan
                    </h2>

                    <p class="employee-edit-card-copy">
                        Tinjau identitas, penempatan, dan status akun
                        sebelum melakukan perubahan.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    {{ $employee->employee_number }}
                </span>
            </div>

            <div class="employee-edit-summary-grid">
                <article class="employee-edit-summary-item">
                    <span class="employee-edit-summary-icon">
                        <i
                            class="bi bi-person"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-edit-summary-label">
                        Nama
                    </div>

                    <div class="employee-edit-summary-value">
                        {{ $employee->full_name }}
                    </div>
                </article>

                <article class="employee-edit-summary-item">
                    <span class="employee-edit-summary-icon">
                        <i
                            class="bi bi-briefcase"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-edit-summary-label">
                        Jabatan
                    </div>

                    <div class="employee-edit-summary-value">
                        {{ $employee->position }}
                    </div>
                </article>

                <article class="employee-edit-summary-item">
                    <span class="employee-edit-summary-icon">
                        <i
                            class="bi bi-building"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-edit-summary-label">
                        Cabang
                    </div>

                    <div class="employee-edit-summary-value">
                        @if ($employee->branch !== null)
                            {{ $employee->branch->code }}
                            |
                            {{ $employee->branch->name }}
                        @else
                            -
                        @endif
                    </div>
                </article>

                <article class="employee-edit-summary-item">
                    <span class="employee-edit-summary-icon">
                        <i
                            class="bi bi-shield-check"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-edit-summary-label">
                        Status
                    </div>

                    <div
                        class="employee-edit-summary-value
                            d-flex flex-wrap gap-2"
                    >
                        <span
                            class="badge
                                {{ $employmentStatusClass }}"
                        >
                            Karyawan {{ $employmentStatusLabel }}
                        </span>

                        <span
                            class="badge
                                {{ $accountStatusClass }}"
                        >
                            Akun {{ $accountStatusLabel }}
                        </span>
                    </div>
                </article>
            </div>
        </section>

        <section
            class="employee-edit-form-card"
            aria-labelledby="edit-employee-heading"
        >
            <div class="employee-edit-card-header">
                <div>
                    <h2
                        id="edit-employee-heading"
                        class="employee-edit-card-title"
                    >
                        Formulir Perubahan Karyawan
                    </h2>

                    <p class="employee-edit-card-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi. Kosongkan kata sandi apabila
                        tidak ingin mengubah kata sandi lama.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Mode edit
                </span>
            </div>

            @if (
                $employee->branch !== null
                && $employee->branch->status === 'inactive'
            )
                <div
                    class="employee-edit-warning"
                    role="alert"
                >
                    <span class="employee-edit-warning-icon">
                        <i
                            class="bi bi-exclamation-triangle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h3 class="employee-edit-warning-title">
                            Cabang saat ini tidak aktif.
                        </h3>

                        <p class="employee-edit-warning-copy">
                            Karyawan masih dapat dipertahankan pada
                            cabang tersebut atau dipindahkan ke
                            cabang aktif.
                        </p>
                    </div>
                </div>
            @endif

            <div class="employee-edit-form-body">
                <form
                    method="POST"
                    action="{{ route(
                        'employees.update',
                        $employee
                    ) }}"
                >
                    @csrf
                    @method('PUT')

                    @include(
                        'employees._form',
                        [
                            'employee' => $employee,
                            'branches' => $branches,
                        ]
                    )

                    <div class="employee-edit-actions">
                        <a
                            href="{{ route(
                                'employees.show',
                                $employee
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i
                                class="bi bi-save me-2"
                                aria-hidden="true"
                            ></i>

                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
