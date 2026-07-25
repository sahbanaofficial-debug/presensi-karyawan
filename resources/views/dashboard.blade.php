@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $user = auth()->user();

    $displayName = $user?->role === 'hrd'
        ? 'HRD'
        : \Illuminate\Support\Str::title(
            $user?->name ?? 'Pengguna'
        );

    $roleLabel = match ($user?->role) {
        'hrd' => 'HRD',
        'admin' => 'Admin Operasional',
        'employee' => 'Karyawan',
        default => 'Pengguna',
    };

    $roleDescription = match ($user?->role) {
        'hrd' => 'Mengelola kebijakan, data cabang, data karyawan, jadwal, monitoring, dan laporan presensi.',
        'admin' => 'Mendukung operasional presensi, sesi QR, monitoring, dan pengelolaan jadwal.',
        'employee' => 'Melakukan presensi, memeriksa jadwal, dan melihat riwayat kehadiran.',
        default => 'Mengakses fitur Sistem Presensi Karyawan.',
    };

    $primaryAction = match ($user?->role) {
        'hrd' => [
            'label' => 'Buka monitoring',
            'route' => 'attendance-monitoring.index',
            'icon' => 'bi-activity',
        ],
        'admin' => [
            'label' => 'Kelola sesi presensi',
            'route' => 'attendance-sessions.index',
            'icon' => 'bi-qr-code-scan',
        ],
        'employee' => [
            'label' => 'Lakukan presensi',
            'route' => 'attendance.create',
            'icon' => 'bi-fingerprint',
        ],
        default => null,
    };

    $quickActions = match ($user?->role) {
        'hrd' => [
            [
                'label' => 'Data karyawan',
                'description' => 'Kelola profil dan status karyawan.',
                'route' => 'employees.index',
                'icon' => 'bi-people',
            ],
            [
                'label' => 'Jadwal karyawan',
                'description' => 'Atur penempatan jadwal harian.',
                'route' => 'employee-schedules.index',
                'icon' => 'bi-calendar-check',
            ],
            [
                'label' => 'Data cabang',
                'description' => 'Kelola konfigurasi lokasi dan geofence.',
                'route' => 'branches.index',
                'icon' => 'bi-building',
            ],
            [
                'label' => 'Log validasi',
                'description' => 'Tinjau transaksi yang diterima atau ditolak.',
                'route' => 'attendance-validation-logs.index',
                'icon' => 'bi-shield-check',
            ],
        ],
        'admin' => [
            [
                'label' => 'Sesi presensi',
                'description' => 'Buat dan pantau QR presensi aktif.',
                'route' => 'attendance-sessions.index',
                'icon' => 'bi-qr-code-scan',
            ],
            [
                'label' => 'Monitoring',
                'description' => 'Pantau aktivitas presensi karyawan.',
                'route' => 'attendance-monitoring.index',
                'icon' => 'bi-activity',
            ],
            [
                'label' => 'Pertukaran jadwal',
                'description' => 'Tinjau permintaan perubahan jadwal.',
                'route' => 'schedule-swap-requests.index',
                'icon' => 'bi-arrow-left-right',
            ],
            [
                'label' => 'Data karyawan',
                'description' => 'Lihat dan kelola data karyawan.',
                'route' => 'employees.index',
                'icon' => 'bi-people',
            ],
        ],
        'employee' => [
            [
                'label' => 'Presensi saya',
                'description' => 'Periksa lokasi dan pindai QR presensi.',
                'route' => 'attendance.create',
                'icon' => 'bi-fingerprint',
            ],
            [
                'label' => 'Riwayat presensi',
                'description' => 'Lihat catatan masuk dan pulang.',
                'route' => 'attendance.history',
                'icon' => 'bi-clock-history',
            ],
        ],
        default => [],
    };

    $quickActions = collect($quickActions)
        ->filter(
            static fn (array $action): bool =>
                \Illuminate\Support\Facades\Route::has($action['route'])
        )
        ->values();

    $primaryActionAvailable = $primaryAction !== null
        && \Illuminate\Support\Facades\Route::has($primaryAction['route']);
@endphp

@push('styles')
    <style>
        .dashboard-grid {
            display: grid;
            gap: var(--space-5);
        }

        .dashboard-welcome {
            position: relative;
            overflow: hidden;
            padding: var(--space-5);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .dashboard-welcome::before {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 0.25rem;
            background: var(--brand-500);
            content: "";
        }

        .dashboard-welcome-icon {
            display: inline-flex;
            width: 3rem;
            height: 3rem;
            flex: 0 0 3rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 1.25rem;
        }

        .dashboard-welcome-kicker {
            margin-bottom: var(--space-1);
            color: var(--brand-700);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .dashboard-welcome-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.2;
        }

        .dashboard-welcome-copy {
            max-width: 50rem;
            margin: var(--space-2) 0 0;
            color: var(--neutral-600);
            line-height: 1.7;
        }

        .dashboard-panel {
            height: 100%;
            overflow: hidden;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .dashboard-panel-header {
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--neutral-200);
        }

        .dashboard-panel-body {
            padding: var(--space-5);
        }

        .dashboard-account-list {
            display: grid;
            gap: var(--space-4);
            margin: 0;
        }

        .dashboard-account-row {
            display: grid;
            grid-template-columns: minmax(8rem, 0.85fr) minmax(0, 1.15fr);
            gap: var(--space-4);
            align-items: start;
        }

        .dashboard-account-row dt {
            margin: 0;
            color: var(--neutral-600);
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .dashboard-account-row dd {
            min-width: 0;
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .dashboard-status {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: 0.375rem 0.625rem;
            border-radius: var(--radius-pill);
            color: var(--success-700);
            background: var(--success-50);
            font-size: 0.75rem;
            font-weight: 800;
        }

        .dashboard-status::before {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background: var(--success-500);
            content: "";
        }

        .dashboard-role-card {
            padding: var(--space-5);
            border: 1px solid var(--brand-200);
            border-radius: var(--radius-lg);
            background: var(--brand-50);
        }

        .dashboard-role-icon {
            display: inline-flex;
            width: 2.75rem;
            height: 2.75rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--brand-100);
            font-size: 1.125rem;
        }

        .dashboard-role-title {
            margin: var(--space-4) 0 var(--space-2);
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
        }

        .dashboard-role-copy {
            margin: 0;
            color: var(--neutral-700);
            font-size: 0.875rem;
            line-height: 1.7;
        }

        .dashboard-action-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .dashboard-action {
            display: flex;
            min-height: 7.5rem;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            color: var(--neutral-900);
            background: var(--neutral-0);
            text-decoration: none;
            transition:
                border-color 150ms ease,
                background-color 150ms ease;
        }

        .dashboard-action:hover,
        .dashboard-action:focus {
            border-color: var(--brand-300);
            color: var(--neutral-900);
            background: var(--brand-50);
        }

        .dashboard-action-icon {
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

        .dashboard-action:hover .dashboard-action-icon,
        .dashboard-action:focus .dashboard-action-icon {
            background: var(--brand-100);
        }

        .dashboard-action-title {
            display: block;
            margin-bottom: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
        }

        .dashboard-action-copy {
            display: block;
            color: var(--neutral-600);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .dashboard-capability-list {
            display: grid;
            gap: var(--space-3);
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .dashboard-capability-item {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            color: var(--neutral-700);
            font-size: 0.8125rem;
            line-height: 1.55;
        }

        .dashboard-capability-icon {
            color: var(--success-500);
            font-size: 1rem;
        }

        @media (min-width: 992px) {
            .dashboard-main-columns {
                display: grid;
                grid-template-columns:
                    minmax(0, 1.1fr)
                    minmax(18rem, 0.9fr);
                gap: var(--space-5);
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-welcome {
                padding: var(--space-4);
            }

            .dashboard-welcome-content {
                align-items: flex-start !important;
            }

            .dashboard-welcome-icon {
                width: 2.75rem;
                height: 2.75rem;
                flex-basis: 2.75rem;
            }

            .dashboard-panel-header,
            .dashboard-panel-body {
                padding: var(--space-4);
            }

            .dashboard-account-row {
                grid-template-columns: 1fr;
                gap: var(--space-1);
            }

            .dashboard-action-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-action {
                min-height: auto;
            }
        }
    </style>
@endpush

@section('content')
    <header class="page-header">
        <h1 class="page-title">
            Dashboard
        </h1>

        <p class="page-description">
            Ringkasan akun dan akses utama Sistem Presensi
            PT Gadai Ogan Baru.
        </p>
    </header>

    <div class="dashboard-grid">
        <section class="dashboard-welcome">
            <div
                class="dashboard-welcome-content
                    d-flex flex-column flex-md-row
                    align-items-md-center justify-content-between
                    gap-4"
            >
                <div class="d-flex align-items-start gap-3 min-w-0">
                    <span class="dashboard-welcome-icon">
                        <i
                            class="bi bi-person-check"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="min-w-0">
                        <div class="dashboard-welcome-kicker">
                            Selamat datang
                        </div>

                        <h2 class="dashboard-welcome-title">
                            {{ $displayName }}
                        </h2>

                        <p class="dashboard-welcome-copy">
                            Anda masuk sebagai {{ $roleLabel }}.
                            Gunakan akses yang tersedia untuk menjalankan
                            aktivitas presensi sesuai kewenangan akun.
                        </p>
                    </div>
                </div>

                @if ($primaryActionAvailable)
                    <a
                        href="{{ route($primaryAction['route']) }}"
                        class="btn btn-primary flex-shrink-0"
                    >
                        <i
                            class="bi {{ $primaryAction['icon'] }} me-2"
                            aria-hidden="true"
                        ></i>

                        {{ $primaryAction['label'] }}
                    </a>
                @endif
            </div>
        </section>

        <div class="dashboard-main-columns">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2 class="section-title">
                        Akses utama
                    </h2>

                    <p class="section-description">
                        Fitur yang paling relevan untuk peran
                        {{ $roleLabel }}.
                    </p>
                </div>

                <div class="dashboard-panel-body">
                    @if ($quickActions->isNotEmpty())
                        <div class="dashboard-action-grid">
                            @foreach ($quickActions as $action)
                                <a
                                    href="{{ route($action['route']) }}"
                                    class="dashboard-action"
                                >
                                    <span class="dashboard-action-icon">
                                        <i
                                            class="bi {{ $action['icon'] }}"
                                            aria-hidden="true"
                                        ></i>
                                    </span>

                                    <span>
                                        <span
                                            class="dashboard-action-title"
                                        >
                                            {{ $action['label'] }}
                                        </span>

                                        <span
                                            class="dashboard-action-copy"
                                        >
                                            {{ $action['description'] }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <span class="empty-state-icon">
                                <i
                                    class="bi bi-grid"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <h3 class="section-title">
                                Belum ada akses cepat
                            </h3>

                            <p class="section-description">
                                Menu yang tersedia tetap dapat dibuka
                                melalui navigasi utama.
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <div class="dashboard-grid">
                <section class="dashboard-role-card">
                    <span class="dashboard-role-icon">
                        <i
                            class="bi bi-shield-check"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <h2 class="dashboard-role-title">
                        Akses {{ $roleLabel }}
                    </h2>

                    <p class="dashboard-role-copy">
                        {{ $roleDescription }}
                    </p>
                </section>

                <section class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <h2 class="section-title">
                            Informasi akun
                        </h2>

                        <p class="section-description">
                            Identitas akun yang sedang digunakan.
                        </p>
                    </div>

                    <div class="dashboard-panel-body">
                        <dl class="dashboard-account-list">
                            <div class="dashboard-account-row">
                                <dt>Nama pengguna</dt>

                                <dd>
                                    {{ \Illuminate\Support\Str::title(
                                        $user?->name ?? '-'
                                    ) }}
                                </dd>
                            </div>

                            <div class="dashboard-account-row">
                                <dt>Alamat email</dt>

                                <dd>
                                    {{ $user?->email ?? '-' }}
                                </dd>
                            </div>

                            <div class="dashboard-account-row">
                                <dt>Peran</dt>

                                <dd>
                                    <span class="badge text-bg-primary">
                                        {{ $roleLabel }}
                                    </span>
                                </dd>
                            </div>

                            <div class="dashboard-account-row">
                                <dt>Status akun</dt>

                                <dd>
                                    <span class="dashboard-status">
                                        Aktif
                                    </span>
                                </dd>
                            </div>

                            <div class="dashboard-account-row">
                                <dt>Waktu akses</dt>

                                <dd>
                                    {{ now()->format('d-m-Y H:i') }} WIB
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <h2 class="section-title">
                            Validasi sistem
                        </h2>

                        <p class="section-description">
                            Mekanisme utama yang melindungi transaksi.
                        </p>
                    </div>

                    <div class="dashboard-panel-body">
                        <ul class="dashboard-capability-list">
                            <li class="dashboard-capability-item">
                                <i
                                    class="bi bi-check-circle-fill
                                        dashboard-capability-icon"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    QR dinamis berbasis TOTP.
                                </span>
                            </li>

                            <li class="dashboard-capability-item">
                                <i
                                    class="bi bi-check-circle-fill
                                        dashboard-capability-icon"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Validasi geofence Formula Haversine.
                                </span>
                            </li>

                            <li class="dashboard-capability-item">
                                <i
                                    class="bi bi-check-circle-fill
                                        dashboard-capability-icon"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Pemeriksaan jadwal, waktu, dan duplikasi.
                                </span>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
