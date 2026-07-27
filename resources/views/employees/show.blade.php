@extends('layouts.app')

@section('title', 'Detail Karyawan')

@push('styles')
    <style>
        .employee-detail-page {
            --employee-detail-surface: var(--neutral-0);
            --employee-detail-border: var(--neutral-200);
            --employee-detail-muted: var(--neutral-600);
            --employee-detail-soft: var(--brand-50);
        }

        .employee-detail-summary-card,
        .employee-detail-information-card,
        .employee-detail-account-card,
        .employee-detail-branch-card {
            overflow: hidden;
            border: 1px solid var(--employee-detail-border);
            border-radius: var(--radius-lg);
            background: var(--employee-detail-surface);
            box-shadow: var(--shadow-xs);
        }

        .employee-detail-summary-card {
            margin-bottom: var(--space-5);
        }

        .employee-detail-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--employee-detail-border);
            background: var(--neutral-25);
        }

        .employee-detail-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .employee-detail-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--employee-detail-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .employee-detail-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .employee-detail-summary-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .employee-detail-summary-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--employee-detail-soft);
            font-size: 1rem;
        }

        .employee-detail-summary-label {
            color: var(--employee-detail-muted);
            font-size: 0.6875rem;
            font-weight: 700;
        }

        .employee-detail-summary-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.2;
        }

        .employee-detail-summary-meta {
            margin-top: var(--space-1);
            color: var(--employee-detail-muted);
            font-size: 0.6875rem;
            line-height: 1.5;
        }

        .employee-detail-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(20rem, 0.75fr);
            gap: var(--space-4);
        }

        .employee-detail-side-stack {
            display: grid;
            gap: var(--space-4);
        }

        .employee-detail-card-body {
            padding: var(--space-4);
        }

        .employee-detail-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
        }

        .employee-detail-row {
            display: grid;
            grid-template-columns: minmax(9rem, 0.85fr) minmax(0, 1.15fr);
            gap: var(--space-3);
            align-items: start;
            margin: 0;
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--neutral-100);
        }

        .employee-detail-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .employee-detail-row dt,
        .employee-detail-row dd {
            margin: 0;
        }

        .employee-detail-row dt {
            color: var(--employee-detail-muted);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .employee-detail-row dd {
            min-width: 0;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .employee-detail-status-stack {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
        }

        .employee-detail-warning {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border: 1px solid #f0ddb0;
            border-radius: var(--radius-md);
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .employee-detail-warning-icon {
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

        .employee-detail-warning-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .employee-detail-warning-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        @media (min-width: 768px) {
            .employee-detail-summary-grid,
            .employee-detail-card-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .employee-detail-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .employee-detail-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .employee-detail-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .employee-detail-summary-grid {
                grid-template-columns: 1fr;
            }

            .employee-detail-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = $employee->user;
        $branch = $employee->branch;

        $isEmploymentActive =
            $employee->employment_status === 'active';

        $isAccountActive =
            $user?->status === 'active';

        $lastLogin = $user?->last_login_at
            ? \Illuminate\Support\Carbon::parse(
                $user->last_login_at
            )
                ->timezone('Asia/Jakarta')
                ->format('d-m-Y H:i')
            : null;
    @endphp

    <div class="employee-detail-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Detail Karyawan
                </h1>

                <p class="page-description">
                    Informasi profil, akun, cabang, dan aktivitas
                    {{ $employee->full_name }}.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
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

                @if (auth()->user()->hasRole('hrd'))
                    <a
                        href="{{ route(
                            'employees.edit',
                            $employee
                        ) }}"
                        class="btn btn-primary"
                    >
                        <i
                            class="bi bi-pencil-square me-2"
                            aria-hidden="true"
                        ></i>

                        Edit Karyawan
                    </a>
                @endif
            </div>
        </header>

        <section
            class="employee-detail-summary-card"
            aria-labelledby="employee-activity-summary-heading"
        >
            <div class="employee-detail-card-header">
                <div>
                    <h2
                        id="employee-activity-summary-heading"
                        class="employee-detail-card-title"
                    >
                        Ringkasan Aktivitas
                    </h2>

                    <p class="employee-detail-card-copy">
                        Jumlah jadwal, presensi, dan permohonan
                        pertukaran yang terkait dengan karyawan.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    {{ $employee->employee_number }}
                </span>
            </div>

            <div class="employee-detail-summary-grid">
                <article class="employee-detail-summary-item">
                    <span class="employee-detail-summary-icon">
                        <i
                            class="bi bi-calendar-week"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-detail-summary-label">
                        Jadwal Kerja
                    </div>

                    <div class="employee-detail-summary-value">
                        {{ $employee->schedules_count ?? 0 }}
                    </div>

                    <div class="employee-detail-summary-meta">
                        Data penetapan jadwal
                    </div>
                </article>

                <article class="employee-detail-summary-item">
                    <span class="employee-detail-summary-icon">
                        <i
                            class="bi bi-person-check"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-detail-summary-label">
                        Riwayat Presensi
                    </div>

                    <div class="employee-detail-summary-value">
                        {{ $employee->attendances_count ?? 0 }}
                    </div>

                    <div class="employee-detail-summary-meta">
                        Data presensi tersimpan
                    </div>
                </article>

                <article class="employee-detail-summary-item">
                    <span class="employee-detail-summary-icon">
                        <i
                            class="bi bi-arrow-left-right"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-detail-summary-label">
                        Permohonan Pertukaran
                    </div>

                    <div class="employee-detail-summary-value">
                        {{
                            $employee
                                ->requested_schedule_swaps_count
                            ?? 0
                        }}
                    </div>

                    <div class="employee-detail-summary-meta">
                        Diajukan oleh karyawan
                    </div>
                </article>

                <article class="employee-detail-summary-item">
                    <span class="employee-detail-summary-icon">
                        <i
                            class="bi bi-people"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="employee-detail-summary-label">
                        Sebagai Mitra Pertukaran
                    </div>

                    <div class="employee-detail-summary-value">
                        {{
                            $employee
                                ->partnered_schedule_swaps_count
                            ?? 0
                        }}
                    </div>

                    <div class="employee-detail-summary-meta">
                        Permohonan dari karyawan lain
                    </div>
                </article>
            </div>
        </section>

        <div class="employee-detail-main-grid">
            <section
                class="employee-detail-information-card"
                aria-labelledby="employee-information-heading"
            >
                <div class="employee-detail-card-header">
                    <div>
                        <h2
                            id="employee-information-heading"
                            class="employee-detail-card-title"
                        >
                            Informasi Karyawan
                        </h2>

                        <p class="employee-detail-card-copy">
                            Identitas dan status profil karyawan.
                        </p>
                    </div>

                    <span
                        class="badge {{
                            $isEmploymentActive
                                ? 'text-bg-success'
                                : 'text-bg-secondary'
                        }}"
                    >
                        {{
                            $isEmploymentActive
                                ? 'Aktif'
                                : 'Tidak aktif'
                        }}
                    </span>
                </div>

                <div class="employee-detail-card-body">
                    <dl class="employee-detail-list">
                        <div class="employee-detail-row">
                            <dt>
                                Nomor Karyawan
                            </dt>

                            <dd>
                                {{ $employee->employee_number }}
                            </dd>
                        </div>

                        <div class="employee-detail-row">
                            <dt>
                                Nama Lengkap
                            </dt>

                            <dd>
                                {{ $employee->full_name }}
                            </dd>
                        </div>

                        <div class="employee-detail-row">
                            <dt>
                                Jabatan
                            </dt>

                            <dd>
                                {{ $employee->position }}
                            </dd>
                        </div>

                        <div class="employee-detail-row">
                            <dt>
                                Nomor Telepon
                            </dt>

                            <dd>
                                {{ $employee->phone_number ?: '-' }}
                            </dd>
                        </div>

                        <div class="employee-detail-row">
                            <dt>
                                Status Karyawan
                            </dt>

                            <dd>
                                @if ($isEmploymentActive)
                                    <span class="badge text-bg-success">
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        Tidak aktif
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="employee-detail-row">
                            <dt>
                                Dibuat
                            </dt>

                            <dd>
                                {{
                                    $employee->created_at
                                        ?->timezone('Asia/Jakarta')
                                        ->format('d-m-Y H:i')
                                    ?? '-'
                                }}
                                WIB
                            </dd>
                        </div>

                        <div class="employee-detail-row">
                            <dt>
                                Terakhir Diperbarui
                            </dt>

                            <dd>
                                {{
                                    $employee->updated_at
                                        ?->timezone('Asia/Jakarta')
                                        ->format('d-m-Y H:i')
                                    ?? '-'
                                }}
                                WIB
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <div class="employee-detail-side-stack">
                <section
                    class="employee-detail-account-card"
                    aria-labelledby="employee-account-heading"
                >
                    <div class="employee-detail-card-header">
                        <div>
                            <h2
                                id="employee-account-heading"
                                class="employee-detail-card-title"
                            >
                                Informasi Akun
                            </h2>

                            <p class="employee-detail-card-copy">
                                Akun yang digunakan untuk masuk
                                ke sistem.
                            </p>
                        </div>
                    </div>

                    <div class="employee-detail-card-body">
                        @if ($user !== null)
                            <dl class="employee-detail-list">
                                <div class="employee-detail-row">
                                    <dt>
                                        Email
                                    </dt>

                                    <dd class="text-break">
                                        {{ $user->email }}
                                    </dd>
                                </div>

                                <div class="employee-detail-row">
                                    <dt>
                                        Role
                                    </dt>

                                    <dd>
                                        <span
                                            class="badge
                                                text-bg-primary"
                                        >
                                            Karyawan
                                        </span>
                                    </dd>
                                </div>

                                <div class="employee-detail-row">
                                    <dt>
                                        Status Akun
                                    </dt>

                                    <dd>
                                        @if ($isAccountActive)
                                            <span
                                                class="badge
                                                    text-bg-success"
                                            >
                                                Aktif
                                            </span>
                                        @else
                                            <span
                                                class="badge
                                                    text-bg-secondary"
                                            >
                                                Tidak aktif
                                            </span>
                                        @endif
                                    </dd>
                                </div>

                                <div class="employee-detail-row">
                                    <dt>
                                        Login Terakhir
                                    </dt>

                                    <dd>
                                        @if ($lastLogin !== null)
                                            {{ $lastLogin }} WIB
                                        @else
                                            <span
                                                class="text-secondary"
                                            >
                                                Belum pernah masuk
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <div
                                class="employee-detail-warning"
                                role="alert"
                            >
                                <span
                                    class="employee-detail-warning-icon"
                                >
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>
                                </span>

                                <div>
                                    <h3
                                        class="employee-detail-warning-title"
                                    >
                                        Akun tidak ditemukan
                                    </h3>

                                    <p
                                        class="employee-detail-warning-copy"
                                    >
                                        Akun pengguna tidak
                                        ditemukan.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                <section
                    class="employee-detail-branch-card"
                    aria-labelledby="employee-branch-heading"
                >
                    <div class="employee-detail-card-header">
                        <div>
                            <h2
                                id="employee-branch-heading"
                                class="employee-detail-card-title"
                            >
                                Penempatan Cabang
                            </h2>

                            <p class="employee-detail-card-copy">
                                Cabang tempat karyawan ditugaskan.
                            </p>
                        </div>
                    </div>

                    <div class="employee-detail-card-body">
                        @if ($branch !== null)
                            <dl class="employee-detail-list">
                                <div class="employee-detail-row">
                                    <dt>
                                        Kode Cabang
                                    </dt>

                                    <dd>
                                        {{ $branch->code }}
                                    </dd>
                                </div>

                                <div class="employee-detail-row">
                                    <dt>
                                        Nama Cabang
                                    </dt>

                                    <dd>
                                        {{ $branch->name }}
                                    </dd>
                                </div>

                                <div class="employee-detail-row">
                                    <dt>
                                        Alamat
                                    </dt>

                                    <dd>
                                        {{ $branch->address }}
                                    </dd>
                                </div>

                                <div class="employee-detail-row">
                                    <dt>
                                        Status Cabang
                                    </dt>

                                    <dd>
                                        @if (
                                            $branch->status
                                            === 'active'
                                        )
                                            <span
                                                class="badge
                                                    text-bg-success"
                                            >
                                                Aktif
                                            </span>
                                        @else
                                            <span
                                                class="badge
                                                    text-bg-warning"
                                            >
                                                Tidak aktif
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <div
                                class="employee-detail-warning"
                                role="alert"
                            >
                                <span
                                    class="employee-detail-warning-icon"
                                >
                                    <i
                                        class="bi
                                            bi-exclamation-triangle"
                                        aria-hidden="true"
                                    ></i>
                                </span>

                                <div>
                                    <h3
                                        class="employee-detail-warning-title"
                                    >
                                        Cabang tidak ditemukan
                                    </h3>

                                    <p
                                        class="employee-detail-warning-copy"
                                    >
                                        Data cabang tidak ditemukan.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
