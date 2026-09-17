@extends('layouts.app')

@section('title', auth()->user()?->role === 'employee' ? 'Beranda' : 'Dashboard')

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
        'hrd' => 'Mengelola data dan monitoring presensi lintas cabang.',
        'admin' => 'Mengelola kegiatan operasional pada cabang penugasan.',
        'employee' => 'Melakukan presensi, memeriksa jadwal, dan melihat riwayat pribadi.',
        default => 'Mengakses fitur Sistem Presensi Karyawan.',
    };

    $primaryAction = match ($user?->role) {
        'hrd' => [
            'label' => 'Buka monitoring',
            'route' => 'attendance-monitoring.index',
            'icon' => 'bi-activity',
        ],
        'admin' => [
            'label' => 'Buka monitoring',
            'route' => 'attendance-monitoring.index',
            'icon' => 'bi-activity',
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
                'label' => 'Monitoring presensi',
                'description' => 'Pantau presensi masuk dan pulang seluruh cabang.',
                'route' => 'attendance-monitoring.index',
                'icon' => 'bi-activity',
            ],
            [
                'label' => 'Data karyawan',
                'description' => 'Kelola profil dan status karyawan.',
                'route' => 'employees.index',
                'icon' => 'bi-people',
            ],
            [
                'label' => 'Roster mingguan',
                'description' => 'Susun dan publikasikan roster mingguan.',
                'route' => 'weekly-rosters.index',
                'icon' => 'bi-calendar3-week',
            ],
            [
                'label' => 'Terminal cabang',
                'description' => 'Kelola terminal QR pada setiap cabang.',
                'route' => 'branch-terminals.index',
                'icon' => 'bi-display',
            ],
            [
                'label' => 'Data cabang',
                'description' => 'Kelola konfigurasi lokasi dan geofence.',
                'route' => 'branches.index',
                'icon' => 'bi-building',
            ],
            [
                'label' => 'Log validasi',
                'description' => 'Tinjau presensi yang diterima atau ditolak.',
                'route' => 'attendance-validation-logs.index',
                'icon' => 'bi-shield-check',
            ],
        ],
        'admin' => [
            [
                'label' => 'Monitoring presensi',
                'description' => 'Pantau presensi pada cabang penugasan.',
                'route' => 'attendance-monitoring.index',
                'icon' => 'bi-activity',
            ],
            [
                'label' => 'Roster mingguan',
                'description' => 'Susun dan publikasikan roster mingguan cabang.',
                'route' => 'weekly-rosters.index',
                'icon' => 'bi-calendar3-week',
                'requires_branch_assignment' => true,
            ],
            [
                'label' => 'Terminal cabang',
                'description' => 'Kelola terminal QR cabang penugasan.',
                'route' => 'branch-terminals.index',
                'icon' => 'bi-display',
                'requires_branch_assignment' => true,
            ],
            [
                'label' => 'Data karyawan',
                'description' => 'Lihat karyawan pada cabang penugasan.',
                'route' => 'employees.index',
                'icon' => 'bi-people',
            ],
            [
                'label' => 'Pertukaran jadwal',
                'description' => 'Tinjau permintaan perubahan jadwal.',
                'route' => 'schedule-swap-requests.index',
                'icon' => 'bi-arrow-left-right',
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
            static function (array $action) use ($user): bool {
                if (! \Illuminate\Support\Facades\Route::has($action['route'])) {
                    return false;
                }

                if (! ($action['requires_branch_assignment'] ?? false)) {
                    return true;
                }

                return $user?->hasRole('hrd') === true
                    || $user?->branch_id !== null;
            }
        )
        ->values();

    $primaryActionAvailable = $primaryAction !== null
        && \Illuminate\Support\Facades\Route::has($primaryAction['route']);

    $isManagementDashboard = in_array(
        $user?->role,
        ['hrd', 'admin'],
        true
    );

    $summaryTotal = (int) ($attendanceSummary['total'] ?? 0);
    $summaryOnTime = (int) ($attendanceSummary['on_time'] ?? 0);
    $summaryLate = (int) ($attendanceSummary['late'] ?? 0);
    $summaryCheckOut = (int) ($attendanceSummary['check_out'] ?? 0);

    $onTimePercent = $summaryTotal > 0
        ? round(($summaryOnTime / $summaryTotal) * 100, 2)
        : 0;
    $latePercent = $summaryTotal > 0
        ? round(($summaryLate / $summaryTotal) * 100, 2)
        : 0;
    $checkOutPercent = $summaryTotal > 0
        ? round(($summaryCheckOut / $summaryTotal) * 100, 2)
        : 0;

    $lateEnd = $onTimePercent + $latePercent;
    $branchAttendanceMax = max(
        1,
        (int) ($branchAttendance?->max('attendance_count') ?? 0)
    );

    $dashboardBranches = $dashboardBranches ?? collect();
    $dashboardPeriodKey = $dashboardPeriodKey ?? 'week';
    $dashboardPeriodLabel = $dashboardPeriodLabel ?? 'Minggu ini';
    $dashboardPeriodLabelLower = \Illuminate\Support\Str::lower(
        $dashboardPeriodLabel
    );
    $selectedDashboardBranchId =
        $selectedDashboardBranchId ?? null;
@endphp

@push('styles')
    <style>
        .dashboard-stack {
            display: grid;
            gap: var(--space-5);
        }

        .dashboard-welcome,
        .dashboard-card,
        .dashboard-panel {
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .dashboard-welcome {
            position: relative;
            overflow: hidden;
            padding: var(--space-5);
        }

        .dashboard-welcome::before {
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.25rem;
            background: var(--brand-500);
            content: "";
        }

        .dashboard-welcome-icon,
        .dashboard-action-icon,
        .dashboard-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .dashboard-welcome-icon {
            width: 3rem;
            height: 3rem;
            flex: 0 0 3rem;
            font-size: 1.25rem;
        }

        .dashboard-kicker {
            margin-bottom: var(--space-1);
            color: var(--brand-700);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
        }

        .dashboard-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.035em;
        }

        .dashboard-copy {
            margin: var(--space-2) 0 0;
            color: var(--neutral-600);
            line-height: 1.7;
        }

        .dashboard-filter-panel {
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .dashboard-filter-grid {
            display: grid;
            grid-template-columns:
                minmax(10rem, 0.75fr)
                minmax(14rem, 1.25fr)
                auto;
            gap: var(--space-3);
            align-items: end;
        }

        .dashboard-filter-actions {
            display: flex;
            gap: var(--space-2);
        }

        .dashboard-filter-lock {
            display: flex;
            min-height: 2.75rem;
            align-items: center;
            gap: var(--space-2);
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            color: var(--neutral-700);
            background: var(--neutral-50);
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .dashboard-filter-caption {
            margin-top: var(--space-2);
            color: var(--neutral-600);
            font-size: 0.75rem;
        }

        .dashboard-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-4);
        }

        .dashboard-stat-card {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .dashboard-stat-icon {
            width: 3rem;
            height: 3rem;
            flex: 0 0 3rem;
            font-size: 1.15rem;
        }

        .dashboard-stat-label {
            display: block;
            color: var(--neutral-600);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dashboard-stat-value {
            display: block;
            margin-top: 0.125rem;
            color: var(--neutral-900);
            font-size: 1.65rem;
            font-weight: 850;
            line-height: 1;
        }

        .dashboard-chart-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: var(--space-5);
        }

        .dashboard-panel {
            overflow: hidden;
        }

        .dashboard-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--neutral-200);
        }

        .dashboard-panel-body {
            padding: var(--space-5);
        }

        .dashboard-scope-badge {
            display: inline-flex;
            padding: 0.35rem 0.65rem;
            border-radius: var(--radius-pill);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 0.75rem;
            font-weight: 800;
        }

        .dashboard-donut-layout {
            display: grid;
            grid-template-columns: 12rem minmax(0, 1fr);
            gap: var(--space-5);
            align-items: center;
        }

        .dashboard-donut {
            position: relative;
            width: 11rem;
            height: 11rem;
            margin: auto;
            border-radius: 50%;
            background: conic-gradient(
                var(--success-500) 0 var(--on-time-end),
                var(--warning-500) var(--on-time-end) var(--late-end),
                var(--brand-500) var(--late-end) 100%
            );
        }

        .dashboard-donut.empty {
            background: var(--neutral-200);
        }

        .dashboard-donut::after {
            position: absolute;
            inset: 1.7rem;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--neutral-0);
            content: "";
        }

        .dashboard-donut-value {
            position: absolute;
            inset: 0;
            z-index: 1;
            display: grid;
            place-content: center;
            text-align: center;
        }

        .dashboard-donut-number {
            color: var(--neutral-900);
            font-size: 2rem;
            font-weight: 850;
            line-height: 1;
        }

        .dashboard-donut-caption {
            margin-top: 0.3rem;
            color: var(--neutral-600);
            font-size: 0.72rem;
            font-weight: 700;
        }

        .dashboard-legend {
            display: grid;
            gap: var(--space-3);
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .dashboard-legend-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: var(--space-2);
            align-items: center;
            color: var(--neutral-700);
            font-size: 0.8125rem;
        }

        .dashboard-legend-dot {
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 50%;
        }

        .dashboard-legend-dot.success { background: var(--success-500); }
        .dashboard-legend-dot.warning { background: var(--warning-500); }
        .dashboard-legend-dot.brand { background: var(--brand-500); }

        .dashboard-branch-chart {
            display: grid;
            gap: var(--space-4);
        }

        .dashboard-branch-row {
            display: grid;
            grid-template-columns:
                minmax(8rem, 0.9fr)
                minmax(10rem, 1.8fr)
                minmax(6.5rem, auto);
            gap: var(--space-3);
            align-items: center;
        }

        .dashboard-branch-name {
            min-width: 0;
            color: var(--neutral-800);
            font-size: 0.8125rem;
            font-weight: 750;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-branch-track {
            height: 0.875rem;
            overflow: hidden;
            border-radius: var(--radius-pill);
            background: var(--neutral-100);
            box-shadow:
                inset 0 0 0 1px rgba(37, 42, 47, 0.035);
        }

        .dashboard-branch-bar {
            display: block;
            height: 100%;
            border-radius: inherit;
            background:
                linear-gradient(
                    90deg,
                    var(--brand-400),
                    var(--brand-600)
                );
            box-shadow:
                0 0.125rem 0.375rem rgba(201, 83, 20, 0.24);
            transition: width 180ms ease;
        }

        .dashboard-branch-count {
            display: inline-flex;
            min-height: 2rem;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.65rem;
            border: 1px solid var(--brand-100);
            border-radius: var(--radius-pill);
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 0.75rem;
            font-weight: 850;
            line-height: 1.2;
            text-align: center;
            white-space: nowrap;
        }

        .dashboard-table-wrap {
            overflow-x: auto;
        }

        .dashboard-table {
            min-width: 58rem;
            margin: 0;
        }

        .dashboard-table th {
            color: var(--neutral-600);
            background: var(--neutral-50);
            font-size: 0.75rem;
            white-space: nowrap;
        }

        .dashboard-table td {
            color: var(--neutral-800);
            font-size: 0.8125rem;
            vertical-align: middle;
        }

        .dashboard-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.55rem;
            border-radius: var(--radius-pill);
            font-size: 0.7rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .dashboard-badge.success {
            color: var(--success-700);
            background: var(--success-50);
        }

        .dashboard-badge.warning {
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .dashboard-badge.neutral {
            color: var(--neutral-700);
            background: var(--neutral-100);
        }

        .dashboard-action-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .dashboard-action {
            display: flex;
            min-height: 7rem;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            color: var(--neutral-900);
            background: var(--neutral-0);
            text-decoration: none;
            transition: border-color 150ms ease, background-color 150ms ease;
        }

        .dashboard-action:hover,
        .dashboard-action:focus {
            border-color: var(--brand-300);
            color: var(--neutral-900);
            background: var(--brand-50);
        }

        .dashboard-action-icon {
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
        }

        .dashboard-action-title {
            display: block;
            margin-bottom: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 850;
        }

        .dashboard-action-copy {
            display: block;
            color: var(--neutral-600);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .dashboard-account-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-4);
        }

        .dashboard-account-item {
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-50);
        }

        .dashboard-account-label {
            display: block;
            color: var(--neutral-600);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dashboard-account-value {
            display: block;
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        @media (max-width: 1199.98px) {
            .dashboard-filter-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .dashboard-filter-actions {
                grid-column: 1 / -1;
            }

            .dashboard-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-chart-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-action-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-filter-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-filter-actions {
                grid-column: auto;
            }

            .dashboard-filter-actions .btn {
                flex: 1 1 0;
            }

            .dashboard-welcome,
            .dashboard-panel-header,
            .dashboard-panel-body {
                padding: var(--space-4);
            }

            .dashboard-stat-grid,
            .dashboard-action-grid,
            .dashboard-account-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-donut-layout {
                grid-template-columns: 1fr;
            }

            .dashboard-branch-row {
                grid-template-columns:
                    minmax(7rem, 0.9fr)
                    minmax(7rem, 1.5fr)
                    minmax(5.75rem, auto);
            }

            .dashboard-action {
                min-height: auto;
            }
        }

        /* ====================================================
         * EMPLOYEE MOBILE HOME V2
         * Tampilan modern khusus karyawan
         * ==================================================== */

        @if ($user?->role === 'employee')

        .app-sidebar {
            display: none !important;
        }

        .app-main {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .app-topbar .app-icon-button {
            display: none !important;
        }

        .app-topbar .dropdown {
            display: none !important;
        }

        .app-footer {
            display: none !important;
        }

        .app-content {
            max-width: none;
            padding-top: 1rem;
            padding-bottom: 7rem;
        }

        .employee-home {
            width: 100%;
            max-width: 42rem;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .employee-hero {
            position: relative;
            overflow: hidden;
            padding: 1.4rem;
            border-radius: 1.5rem;
            color: #ffffff;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(255,255,255,.24),
                    transparent 42%
                ),
                linear-gradient(
                    135deg,
                    var(--brand-500),
                    var(--brand-700)
                );
            box-shadow:
                0 1rem 2.5rem rgba(201, 83, 20, .20);
        }

        .employee-hero::after {
            position: absolute;
            right: -3rem;
            bottom: -4rem;
            width: 9rem;
            height: 9rem;
            border-radius: 50%;
            background: rgba(255,255,255,.10);
            content: "";
        }

        .employee-hero-kicker {
            margin: 0 0 .25rem;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .85;
        }

        .employee-hero-name {
            margin: 0;
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -.025em;
        }

        .employee-hero-date {
            margin: .55rem 0 0;
            font-size: .85rem;
            opacity: .9;
        }

        .employee-hero-avatar {
            width: 3.4rem;
            height: 3.4rem;
            flex: 0 0 3.4rem;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 1.1rem;
            background: rgba(255,255,255,.16);
            font-size: 1rem;
            font-weight: 800;
        }

        .employee-presence-card {
            padding: 1.25rem;
            border: 1px solid var(--neutral-200);
            border-radius: 1.4rem;
            background: #ffffff;
            box-shadow:
                0 .5rem 1.8rem rgba(37, 42, 47, .06);
        }

        .employee-card-label {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .35rem;
            color: var(--neutral-600);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .employee-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1.2rem;
            font-weight: 800;
        }

        .employee-card-copy {
            margin: .45rem 0 1.1rem;
            color: var(--neutral-600);
            font-size: .85rem;
            line-height: 1.6;
        }

        .employee-presence-button {
            min-height: 3.6rem;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            border-radius: 1rem;
            font-size: .95rem;
            font-weight: 800;
            box-shadow:
                0 .65rem 1.5rem rgba(229,106,31,.20);
        }

        .employee-shortcuts {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: .8rem;
        }

        .employee-shortcut {
            min-height: 7rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1rem;
            border: 1px solid var(--neutral-200);
            border-radius: 1.25rem;
            color: var(--neutral-900);
            background: #ffffff;
            text-decoration: none;
            box-shadow:
                0 .35rem 1.25rem rgba(37,42,47,.045);
        }

        .employee-shortcut:hover {
            color: var(--neutral-900);
            border-color: var(--brand-200);
            background: var(--brand-50);
        }

        .employee-shortcut-icon {
            width: 2.75rem;
            height: 2.75rem;
            display: grid;
            place-items: center;
            border-radius: .9rem;
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 1.15rem;
        }

        .employee-shortcut-title {
            display: block;
            margin-top: .8rem;
            font-weight: 800;
        }

        .employee-shortcut-copy {
            display: block;
            margin-top: .2rem;
            color: var(--neutral-600);
            font-size: .72rem;
            line-height: 1.45;
        }

        .employee-account-card {
            scroll-margin-top: 6rem;
            padding: 1.15rem;
            border: 1px solid var(--neutral-200);
            border-radius: 1.25rem;
            background: #ffffff;
        }

        .employee-account-head {
            display: flex;
            align-items: center;
            gap: .7rem;
            margin-bottom: .8rem;
        }

        .employee-account-icon {
            width: 2.6rem;
            height: 2.6rem;
            display: grid;
            flex: 0 0 2.6rem;
            place-items: center;
            border-radius: .85rem;
            color: var(--brand-700);
            background: var(--brand-50);
        }

        .employee-account-title {
            margin: 0;
            font-size: .95rem;
            font-weight: 800;
        }

        .employee-account-copy {
            margin: .1rem 0 0;
            color: var(--neutral-600);
            font-size: .73rem;
        }

        .employee-account-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .7rem 0;
            border-top: 1px solid var(--neutral-100);
            font-size: .8rem;
        }

        .employee-account-row span {
            color: var(--neutral-600);
        }

        .employee-account-row strong {
            max-width: 65%;
            color: var(--neutral-900);
            text-align: right;
            overflow-wrap: anywhere;
        }

        .employee-bottom-nav {
            position: fixed;
            z-index: 1040;
            left: 50%;
            bottom:
                max(.7rem, env(safe-area-inset-bottom));
            transform: translateX(-50%);
            width: calc(100% - 1.1rem);
            max-width: 40rem;
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            padding: .45rem;
            border: 1px solid rgba(226,229,232,.95);
            border-radius: 1.3rem;
            background: rgba(255,255,255,.96);
            box-shadow:
                0 1rem 2.5rem rgba(37,42,47,.15);
            backdrop-filter: blur(1rem);
        }

        .employee-bottom-link {
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .15rem;
            padding: .5rem .2rem;
            border-radius: .85rem;
            color: var(--neutral-500);
            font-size: .65rem;
            font-weight: 700;
            text-decoration: none;
        }

        .employee-bottom-link i {
            font-size: 1.05rem;
        }

        .employee-bottom-link:hover,
        .employee-bottom-link.active {
            color: var(--brand-700);
            background: var(--brand-50);
        }

        @media (max-width: 575.98px) {
            .app-content {
                padding-right: .8rem;
                padding-left: .8rem;
            }

            .employee-hero {
                padding: 1.2rem;
                border-radius: 1.25rem;
            }

            .employee-presence-card {
                padding: 1rem;
            }
        }

        @endif

        @if ($user?->role === 'employee')

        .employee-shortcuts {
            display: none !important;
        }

        .employee-today-card {
            padding: 1.2rem;
            border: 1px solid var(--neutral-200);
            border-radius: 1.4rem;
            background: #ffffff;
            box-shadow:
                0 .5rem 1.8rem rgba(37, 42, 47, .06);
        }

        .employee-today-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .employee-today-label {
            margin: 0;
            color: var(--neutral-600);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .employee-today-name {
            margin: .15rem 0 0;
            color: var(--neutral-900);
            font-size: 1.15rem;
            font-weight: 800;
        }

        .employee-today-time {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-top: .35rem;
            color: var(--neutral-600);
            font-size: .82rem;
            font-weight: 600;
        }

        .employee-today-icon {
            width: 2.8rem;
            height: 2.8rem;
            flex: 0 0 2.8rem;
            display: grid;
            place-items: center;
            border-radius: .9rem;
            color: var(--brand-700);
            background: var(--brand-50);
            font-size: 1.15rem;
        }

        .employee-status-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .employee-status-box {
            padding: .9rem;
            border-radius: 1rem;
            background: var(--neutral-50);
        }

        .employee-status-label {
            display: flex;
            align-items: center;
            gap: .4rem;
            color: var(--neutral-600);
            font-size: .72rem;
            font-weight: 700;
        }

        .employee-status-value {
            display: block;
            margin-top: .35rem;
            color: var(--neutral-900);
            font-size: .95rem;
            font-weight: 800;
        }

        .employee-status-meta {
            display: block;
            margin-top: .15rem;
            color: var(--neutral-600);
            font-size: .7rem;
        }

        .employee-status-badge {
            display: inline-flex;
            margin-top: .35rem;
            padding: .25rem .5rem;
            border-radius: 999px;
            font-size: .65rem;
            font-weight: 800;
        }

        .employee-status-badge.success {
            color: var(--success-700);
            background: var(--success-50);
        }

        .employee-status-badge.warning {
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .employee-status-badge.muted {
            color: var(--neutral-600);
            background: var(--neutral-100);
        }

        .employee-no-schedule {
            padding: 1rem;
            border-radius: 1rem;
            color: var(--neutral-600);
            background: var(--neutral-50);
            text-align: center;
            font-size: .82rem;
        }

        @endif
</style>
@endpush

@section('content')
    @if ($user?->role === 'employee')

        {{-- EMPLOYEE MOBILE HOME V2 --}}

        <div class="employee-home">

            <section class="employee-hero">
                <div
                    class="d-flex align-items-center
                        justify-content-between gap-3"
                >
                    <div class="min-w-0">
                        <p class="employee-hero-kicker">
                            Selamat datang
                        </p>

                        <h1 class="employee-hero-name">
                            {{ $displayName }}
                        </h1>

                        <p class="employee-hero-date">
                            <i
                                class="bi bi-calendar3 me-1"
                                aria-hidden="true"
                            ></i>

                            {{
                                now()
                                    ->locale('id')
                                    ->translatedFormat(
                                        'l, d F Y'
                                    )
                            }}
                        </p>
                    </div>

                    <div class="employee-hero-avatar">
                        {{
                            \Illuminate\Support\Str::upper(
                                \Illuminate\Support\Str::substr(
                                    $displayName,
                                    0,
                                    2
                                )
                            )
                        }}
                    </div>
                </div>
            </section>

                        <section class="employee-today-card">
                @if ($employeeTodaySchedule)
                    @php
                        $scheduleStatus =
                            $employeeTodaySchedule
                                ->schedule_status;

                        $scheduleName =
                            $employeeTodaySchedule
                                ->workSchedule?->name
                            ?? $employeeTodaySchedule
                                ->work_schedule_name_snapshot
                            ?? 'Jadwal Kerja';

                        $checkInTime =
                            $employeeTodaySchedule
                                ->workSchedule?->check_in_time
                            ?? $employeeTodaySchedule
                                ->check_in_time_snapshot;

                        $checkOutTime =
                            $employeeTodaySchedule
                                ->workSchedule?->check_out_time
                            ?? $employeeTodaySchedule
                                ->check_out_time_snapshot;

                        $scheduleStatusLabel = match (
                            $scheduleStatus
                        ) {
                            'work' => 'Kerja',
                            'off' => 'Libur',
                            'permit' => 'Izin',
                            'sick' => 'Sakit',
                            'leave' => 'Cuti',
                            default => ucfirst(
                                (string) $scheduleStatus
                            ),
                        };
                    @endphp

                    <div class="employee-today-head">
                        <div>
                            <p class="employee-today-label">
                                Jadwal Hari Ini
                            </p>

                            <h2 class="employee-today-name">
                                {{
                                    $scheduleStatus === 'work'
                                        ? $scheduleName
                                        : $scheduleStatusLabel
                                }}
                            </h2>

                            @if (
                                $scheduleStatus === 'work'
                                && $checkInTime
                                && $checkOutTime
                            )
                                <div class="employee-today-time">
                                    <i
                                        class="bi bi-clock"
                                        aria-hidden="true"
                                    ></i>

                                    {{
                                        substr(
                                            (string) $checkInTime,
                                            0,
                                            5
                                        )
                                    }}
                                    —
                                    {{
                                        substr(
                                            (string) $checkOutTime,
                                            0,
                                            5
                                        )
                                    }}
                                    WIB
                                </div>
                            @endif
                        </div>

                        <span class="employee-today-icon">
                            <i
                                class="bi bi-calendar-check"
                                aria-hidden="true"
                            ></i>
                        </span>
                    </div>

                    @if ($scheduleStatus === 'work')
                        <div class="employee-status-grid">
                            <div class="employee-status-box">
                                <span class="employee-status-label">
                                    <i
                                        class="bi bi-box-arrow-in-right"
                                        aria-hidden="true"
                                    ></i>
                                    Masuk
                                </span>

                                @if ($employeeTodayCheckIn)
                                    <strong class="employee-status-value">
                                        {{
                                            $employeeTodayCheckIn
                                                ->attendance_time
                                                ?->format('H:i')
                                        }}
                                        WIB
                                    </strong>

                                    @if (
                                        $employeeTodayCheckIn
                                            ->punctuality_status
                                        === 'late'
                                    )
                                        <span
                                            class="employee-status-badge warning"
                                        >
                                            Terlambat
                                        </span>
                                    @else
                                        <span
                                            class="employee-status-badge success"
                                        >
                                            Tepat Waktu
                                        </span>
                                    @endif
                                @else
                                    <strong class="employee-status-value">
                                        Belum
                                    </strong>

                                    <span
                                        class="employee-status-badge muted"
                                    >
                                        Belum Presensi
                                    </span>
                                @endif
                            </div>

                            <div class="employee-status-box">
                                <span class="employee-status-label">
                                    <i
                                        class="bi bi-box-arrow-right"
                                        aria-hidden="true"
                                    ></i>
                                    Pulang
                                </span>

                                @if ($employeeTodayCheckOut)
                                    <strong class="employee-status-value">
                                        {{
                                            $employeeTodayCheckOut
                                                ->attendance_time
                                                ?->format('H:i')
                                        }}
                                        WIB
                                    </strong>

                                    <span
                                        class="employee-status-badge success"
                                    >
                                        Selesai
                                    </span>
                                @else
                                    <strong class="employee-status-value">
                                        Belum
                                    </strong>

                                    <span
                                        class="employee-status-badge muted"
                                    >
                                        Belum Presensi
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                @else
                    <div class="employee-no-schedule">
                        <i
                            class="bi bi-calendar-x me-1"
                            aria-hidden="true"
                        ></i>

                        Belum ada jadwal kerja untuk hari ini.
                    </div>
                @endif
            </section>
<section class="employee-presence-card">
                <div class="employee-card-label">
                    <i
                        class="bi bi-fingerprint"
                        aria-hidden="true"
                    ></i>

                    Presensi hari ini
                </div>

                <h2 class="employee-card-title">
                    Siap melakukan presensi?
                </h2>

                <p class="employee-card-copy">
                    Aktifkan lokasi perangkat kemudian pindai
                    QR Code yang ditampilkan terminal cabang.
                </p>

                @if (
                    \Illuminate\Support\Facades\Route::has(
                        'attendance.create'
                    )
                )
                    <a
                        href="{{ route('attendance.create') }}"
                        class="btn btn-primary
                            employee-presence-button"
                    >
                        <i
                            class="bi bi-qr-code-scan"
                            aria-hidden="true"
                        ></i>

                        Mulai Presensi
                    </a>
                @endif
            </section>

            <div class="employee-shortcuts">

                @if (
                    \Illuminate\Support\Facades\Route::has(
                        'attendance.create'
                    )
                )
                    <a
                        href="{{ route('attendance.create') }}"
                        class="employee-shortcut"
                    >
                        <span class="employee-shortcut-icon">
                            <i
                                class="bi bi-qr-code-scan"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <span>
                            <span class="employee-shortcut-title">
                                Presensi
                            </span>

                            <span class="employee-shortcut-copy">
                                Pindai QR terminal untuk
                                mencatat kehadiran.
                            </span>
                        </span>
                    </a>
                @endif

                @if (
                    \Illuminate\Support\Facades\Route::has(
                        'attendance.history'
                    )
                )
                    <a
                        href="{{ route('attendance.history') }}"
                        class="employee-shortcut"
                    >
                        <span class="employee-shortcut-icon">
                            <i
                                class="bi bi-clock-history"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <span>
                            <span class="employee-shortcut-title">
                                Riwayat
                            </span>

                            <span class="employee-shortcut-copy">
                                Lihat catatan masuk dan
                                pulang Anda.
                            </span>
                        </span>
                    </a>
                @endif

            </div>

            <section
                id="employee-account"
                class="employee-account-card"
            >
                <div class="employee-account-head">
                    <span class="employee-account-icon">
                        <i
                            class="bi bi-person-circle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h2 class="employee-account-title">
                            Akun Saya
                        </h2>

                        <p class="employee-account-copy">
                            Informasi akun yang sedang digunakan.
                        </p>
                    </div>
                </div>

                <div class="employee-account-row">
                    <span>Nama</span>

                    <strong>
                        {{
                            \Illuminate\Support\Str::title(
                                $user?->name ?? '-'
                            )
                        }}
                    </strong>
                </div>

                <div class="employee-account-row">
                    <span>Email</span>
                    <strong>
                        {{ $user?->email ?? '-' }}
                    </strong>
                </div>

                <div class="employee-account-row">
                    <span>Peran</span>
                    <strong>Karyawan</strong>
                </div>

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    class="mt-3"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-outline-danger w-100"
                    >
                        <i
                            class="bi bi-box-arrow-right me-2"
                            aria-hidden="true"
                        ></i>

                        Keluar dari Akun
                    </button>
                </form>
            </section>

        </div>

    @else

    <header class="page-header">
        <h1 class="page-title">Dashboard</h1>

        <p class="page-description">
            Ringkasan akun dan aktivitas Sistem Presensi PT Gadai Ogan Baru.
        </p>
    </header>

    <div class="dashboard-stack">
        <section class="dashboard-welcome">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                <div class="d-flex align-items-start gap-3 min-w-0">
                    <span class="dashboard-welcome-icon">
                        <i class="bi bi-person-check" aria-hidden="true"></i>
                    </span>

                    <div class="min-w-0">
                        <div class="dashboard-kicker">Selamat datang</div>

                        <h2 class="dashboard-title">{{ $displayName }}</h2>

                        <p class="dashboard-copy">
                            Anda masuk sebagai {{ $roleLabel }}.
                            {{ $roleDescription }}
                        </p>
                    </div>
                </div>

                @if ($primaryActionAvailable)
                    <a href="{{ route($primaryAction['route']) }}" class="btn btn-primary flex-shrink-0">
                        <i class="bi {{ $primaryAction['icon'] }} me-2" aria-hidden="true"></i>
                        {{ $primaryAction['label'] }}
                    </a>
                @endif
            </div>
        </section>

        @if ($isManagementDashboard && is_array($dashboardStats))
            <section
                class="dashboard-filter-panel"
                aria-labelledby="dashboard-filter-title"
            >
                <div class="mb-3">
                    <h2
                        id="dashboard-filter-title"
                        class="section-title mb-1"
                    >
                        Filter ringkasan
                    </h2>

                    <p class="section-description mb-0">
                        Pilih periode dan cabang untuk memperbarui
                        seluruh kartu, grafik, dan presensi terbaru.
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route('dashboard') }}"
                    class="dashboard-filter-grid"
                >
                    <div>
                        <label
                            for="dashboard_period"
                            class="form-label"
                        >
                            Periode
                        </label>

                        <select
                            id="dashboard_period"
                            name="period"
                            class="form-select"
                        >
                            <option
                                value="week"
                                @selected(
                                    $dashboardPeriodKey === 'week'
                                )
                            >
                                Minggu ini
                            </option>

                            <option
                                value="today"
                                @selected(
                                    $dashboardPeriodKey === 'today'
                                )
                            >
                                Hari ini
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="dashboard_branch"
                            class="form-label"
                        >
                            Cabang
                        </label>

                        @if ($user?->hasRole('hrd'))
                            <select
                                id="dashboard_branch"
                                name="branch_id"
                                class="form-select"
                            >
                                <option value="">
                                    Semua cabang
                                </option>

                                @foreach (
                                    $dashboardBranches
                                    as $dashboardBranch
                                )
                                    <option
                                        value="{{ $dashboardBranch->id }}"
                                        @selected(
                                            (string)
                                                $selectedDashboardBranchId
                                            ===
                                            (string)
                                                $dashboardBranch->id
                                        )
                                    >
                                        {{ $dashboardBranch->code }}
                                        — {{ $dashboardBranch->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div
                                id="dashboard_branch"
                                class="dashboard-filter-lock"
                                aria-label="Cabang terkunci"
                            >
                                <i
                                    class="bi bi-lock"
                                    aria-hidden="true"
                                ></i>

                                {{ $dashboardScopeLabel }}
                            </div>
                        @endif
                    </div>

                    <div class="dashboard-filter-actions">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Terapkan
                        </button>

                        <a
                            href="{{ route('dashboard') }}"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </a>
                    </div>
                </form>

                <p class="dashboard-filter-caption mb-0">
                    Periode aktif: {{ $dashboardDateLabel }} ·
                    Ruang lingkup: {{ $dashboardScopeLabel }}
                </p>
            </section>

            <section aria-labelledby="dashboard-operational-summary">
                <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-2 mb-3">
                    <div>
                        <h2 id="dashboard-operational-summary" class="section-title mb-1">
                            Ringkasan operasional
                        </h2>
                        <p class="section-description mb-0">
                            Periode {{ $dashboardDateLabel }} sesuai ruang lingkup akun.
                        </p>
                    </div>

                    <span class="dashboard-scope-badge">
                        {{ $dashboardScopeLabel }}
                    </span>
                </div>

                <div class="dashboard-stat-grid">
                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon">
                            <i class="bi bi-building" aria-hidden="true"></i>
                        </span>
                        <span>
                            <span class="dashboard-stat-label">Cabang aktif</span>
                            <strong class="dashboard-stat-value">{{ $dashboardStats['active_branches'] }}</strong>
                        </span>
                    </article>

                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon">
                            <i class="bi bi-people" aria-hidden="true"></i>
                        </span>
                        <span>
                            <span class="dashboard-stat-label">Karyawan aktif</span>
                            <strong class="dashboard-stat-value">{{ $dashboardStats['active_employees'] }}</strong>
                        </span>
                    </article>

                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon">
                            <i class="bi bi-display" aria-hidden="true"></i>
                        </span>
                        <span>
                            <span class="dashboard-stat-label">Terminal aktif</span>
                            <strong class="dashboard-stat-value">{{ $dashboardStats['active_terminals'] }}</strong>
                        </span>
                    </article>

                    <article class="dashboard-stat-card">
                        <span class="dashboard-stat-icon">
                            <i class="bi bi-fingerprint" aria-hidden="true"></i>
                        </span>
                        <span>
                            <span class="dashboard-stat-label">Presensi {{ $dashboardPeriodLabelLower }}</span>
                            <strong class="dashboard-stat-value">{{ $dashboardStats['period_attendances'] }}</strong>
                        </span>
                    </article>
                </div>
            </section>

            <div class="dashboard-chart-grid">
                <section class="dashboard-panel" aria-labelledby="attendance-composition-title">
                    <div class="dashboard-panel-header">
                        <div>
                            <h2 id="attendance-composition-title" class="section-title mb-1">
                                Komposisi presensi {{ $dashboardPeriodLabelLower }}
                            </h2>
                            <p class="section-description mb-0">
                                Presensi yang diterima oleh sistem.
                            </p>
                        </div>
                    </div>

                    <div class="dashboard-panel-body">
                        <div class="dashboard-donut-layout">
                            <div
                                class="dashboard-donut {{ $summaryTotal === 0 ? 'empty' : '' }}"
                                style="--on-time-end: {{ $onTimePercent }}%; --late-end: {{ $lateEnd }}%;"
                                role="img"
                                aria-label="{{ $summaryOnTime }} masuk tepat waktu, {{ $summaryLate }} masuk terlambat, dan {{ $summaryCheckOut }} pulang"
                            >
                                <span class="dashboard-donut-value">
                                    <span class="dashboard-donut-number">{{ $summaryTotal }}</span>
                                    <span class="dashboard-donut-caption">presensi</span>
                                </span>
                            </div>

                            <ul class="dashboard-legend">
                                <li class="dashboard-legend-item">
                                    <span class="dashboard-legend-dot success"></span>
                                    <span>Masuk tepat waktu</span>
                                    <strong>{{ $summaryOnTime }}</strong>
                                </li>
                                <li class="dashboard-legend-item">
                                    <span class="dashboard-legend-dot warning"></span>
                                    <span>Masuk terlambat</span>
                                    <strong>{{ $summaryLate }}</strong>
                                </li>
                                <li class="dashboard-legend-item">
                                    <span class="dashboard-legend-dot brand"></span>
                                    <span>Pulang</span>
                                    <strong>{{ $summaryCheckOut }}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="dashboard-panel" aria-labelledby="branch-attendance-title">
                    <div class="dashboard-panel-header">
                        <div>
                            <h2 id="branch-attendance-title" class="section-title mb-1">
                                Presensi per cabang
                            </h2>
                            <p class="section-description mb-0">
                                Perbandingan jumlah presensi pada periode aktif.
                            </p>
                        </div>
                    </div>

                    <div class="dashboard-panel-body">
                        @if ($branchAttendance->isNotEmpty())
                            <div class="dashboard-branch-chart">
                                @foreach ($branchAttendance as $branchItem)
                                    @php
                                        $barWidth = (int) $branchItem->attendance_count > 0
                                            ? max(
                                                2,
                                                round(
                                                    ((int) $branchItem->attendance_count / $branchAttendanceMax) * 100,
                                                    2
                                                )
                                            )
                                            : 0;
                                    @endphp

                                    <div class="dashboard-branch-row">
                                        <span class="dashboard-branch-name" title="{{ $branchItem->code }} — {{ $branchItem->name }}">
                                            {{ $branchItem->code }} — {{ $branchItem->name }}
                                        </span>
                                        <span class="dashboard-branch-track" aria-hidden="true">
                                            <span class="dashboard-branch-bar" style="width: {{ $barWidth }}%"></span>
                                        </span>
                                        <strong class="dashboard-branch-count">
                                            {{ $branchItem->attendance_count }} presensi
                                        </strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state py-4">
                                <span class="empty-state-icon">
                                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                                </span>
                                <h3 class="section-title">Belum ada data cabang</h3>
                                <p class="section-description mb-0">
                                    Grafik akan terisi setelah cabang aktif tersedia.
                                </p>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <section class="dashboard-panel" aria-labelledby="recent-attendance-title">
                <div class="dashboard-panel-header">
                    <div>
                        <h2 id="recent-attendance-title" class="section-title mb-1">
                            Presensi terbaru
                        </h2>
                        <p class="section-description mb-0">
                            Delapan aktivitas presensi terbaru pada periode aktif.
                        </p>
                    </div>

                    @if (\Illuminate\Support\Facades\Route::has('attendance-monitoring.index'))
                        <a href="{{ route('attendance-monitoring.index') }}" class="btn btn-sm btn-outline-primary">
                            Lihat monitoring
                        </a>
                    @endif
                </div>

                <div class="dashboard-table-wrap">
                    <table class="table dashboard-table align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal/Waktu</th>
                                <th>Karyawan</th>
                                <th>Cabang</th>
                                <th>Jenis</th>
                                <th>Ketepatan</th>
                                <th>Jarak</th>
                                <th>Akurasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAttendances as $attendance)
                                @php
                                    $punctualityLabel = match ($attendance->punctuality_status) {
                                        'on_time' => 'Tepat Waktu',
                                        'late' => 'Terlambat',
                                        default => 'Tidak Dinilai',
                                    };

                                    $punctualityClass = match ($attendance->punctuality_status) {
                                        'on_time' => 'success',
                                        'late' => 'warning',
                                        default => 'neutral',
                                    };
                                @endphp
                                <tr>
                                    <td><strong>
                                            {{
                                                $attendance
                                                    ->attendance_date
                                                    ?->locale('id')
                                                    ->translatedFormat(
                                                        'd M Y'
                                                    )
                                                ?? '-'
                                            }}
                                        </strong>
                                        <div class="text-secondary small">
                                            {{
                                                $attendance
                                                    ->attendance_time
                                                    ?->format('H:i:s')
                                                ?? '-'
                                            }}
                                        </div></td>
                                    <td>
                                        <strong>{{ $attendance->employee?->full_name ?? '-' }}</strong>
                                        <div class="text-secondary small">
                                            {{ $attendance->employee?->employee_number ?? '-' }}
                                        </div>
                                    </td>
                                    <td>{{ $attendance->branch?->code ?? '-' }}</td>
                                    <td>
                                        {{ $attendance->attendance_type === 'check_in' ? 'Masuk' : 'Pulang' }}
                                    </td>
                                    <td>
                                        <span class="dashboard-badge {{ $punctualityClass }}">
                                            {{ $punctualityLabel }}
                                        </span>
                                    </td>
                                    <td>{{ number_format((float) $attendance->distance, 2, ',', '.') }} m</td>
                                    <td>{{ number_format((float) $attendance->accuracy, 2, ',', '.') }} m</td>
                                    <td>
                                        <span class="dashboard-badge {{ $attendance->validation_status === 'accepted' ? 'success' : 'warning' }}">
                                            {{ $attendance->validation_status === 'accepted' ? 'Diterima' : 'Ditolak' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-secondary">
                                        Belum ada presensi pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="dashboard-panel" aria-labelledby="dashboard-actions-title">
            <div class="dashboard-panel-header">
                <div>
                    <h2 id="dashboard-actions-title" class="section-title mb-1">
                        Akses utama
                    </h2>
                    <p class="section-description mb-0">
                        Fitur yang relevan untuk peran {{ $roleLabel }}.
                    </p>
                </div>
            </div>

            <div class="dashboard-panel-body">
                @if ($quickActions->isNotEmpty())
                    <div class="dashboard-action-grid">
                        @foreach ($quickActions as $action)
                            <a href="{{ route($action['route']) }}" class="dashboard-action">
                                <span class="dashboard-action-icon">
                                    <i class="bi {{ $action['icon'] }}" aria-hidden="true"></i>
                                </span>
                                <span>
                                    <span class="dashboard-action-title">{{ $action['label'] }}</span>
                                    <span class="dashboard-action-copy">{{ $action['description'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <span class="empty-state-icon">
                            <i class="bi bi-grid" aria-hidden="true"></i>
                        </span>
                        <h3 class="section-title">Belum ada akses cepat</h3>
                        <p class="section-description">
                            Menu yang tersedia tetap dapat dibuka melalui navigasi utama.
                        </p>
                    </div>
                @endif
            </div>
        </section>

        <section class="dashboard-panel" aria-labelledby="dashboard-account-title">
            <div class="dashboard-panel-header">
                <div>
                    <h2 id="dashboard-account-title" class="section-title mb-1">
                        Informasi akun
                    </h2>
                    <p class="section-description mb-0">
                        Identitas akun yang sedang digunakan.
                    </p>
                </div>
            </div>

            <div class="dashboard-panel-body">
                <div class="dashboard-account-grid">
                    <div class="dashboard-account-item">
                        <span class="dashboard-account-label">Nama pengguna</span>
                        <strong class="dashboard-account-value">
                            {{ \Illuminate\Support\Str::title($user?->name ?? '-') }}
                        </strong>
                    </div>
                    <div class="dashboard-account-item">
                        <span class="dashboard-account-label">Alamat email</span>
                        <strong class="dashboard-account-value">{{ $user?->email ?? '-' }}</strong>
                    </div>
                    <div class="dashboard-account-item">
                        <span class="dashboard-account-label">Peran</span>
                        <strong class="dashboard-account-value">{{ $roleLabel }}</strong>
                    </div>
                    <div class="dashboard-account-item">
                        <span class="dashboard-account-label">Waktu akses</span>
                        <strong class="dashboard-account-value">{{ now()->format('d-m-Y H:i') }} WIB</strong>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @endif
@endsection
