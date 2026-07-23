@extends('layouts.app')

@section('title', 'Sesi Presensi')

@section('content')
    @php
        $attendanceTypeLabels = [
            'check_in' => 'Presensi Masuk',
            'check_out' => 'Presensi Pulang',
        ];

        $attendanceTypeClasses = [
            'check_in' => 'text-bg-primary',
            'check_out' => 'text-bg-info',
        ];

        $statusLabels = [
            'active' => 'Aktif',
            'closed' => 'Ditutup',
            'expired' => 'Kedaluwarsa',
        ];

        $statusClasses = [
            'active' => 'text-bg-success',
            'closed' => 'text-bg-secondary',
            'expired' => 'text-bg-danger',
        ];

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y');
            } catch (\Throwable) {
                return substr((string) $value, 0, 10);
            }
        };

        $formatDateTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y H:i');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('H:i');
            } catch (\Throwable) {
                return substr((string) $value, 0, 5);
            }
        };
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Sesi Presensi
            </h1>

            <p class="page-description">
                Kelola sesi presensi masuk dan pulang
                dengan QR Code dinamis berbasis TOTP.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('attendance-sessions.create') }}"
                class="btn btn-primary"
            >
                Buka Sesi Presensi
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <form
            method="GET"
            action="{{ route('attendance-sessions.index') }}"
        >
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-4">
                    <label
                        for="search"
                        class="form-label"
                    >
                        Pencarian
                    </label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Kode cabang, nama cabang, atau UUID"
                    >
                </div>

                <div class="col-md-6 col-xl-2">
                    <label
                        for="attendance_type"
                        class="form-label"
                    >
                        Jenis Presensi
                    </label>

                    <select
                        id="attendance_type"
                        name="attendance_type"
                        class="form-select"
                    >
                        <option value="">
                            Semua jenis
                        </option>

                        <option
                            value="check_in"
                            @selected(
                                $selectedAttendanceType
                                === 'check_in'
                            )
                        >
                            Presensi Masuk
                        </option>

                        <option
                            value="check_out"
                            @selected(
                                $selectedAttendanceType
                                === 'check_out'
                            )
                        >
                            Presensi Pulang
                        </option>
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label
                        for="status"
                        class="form-label"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-select"
                    >
                        <option value="">
                            Semua status
                        </option>

                        <option
                            value="active"
                            @selected(
                                $selectedStatus === 'active'
                            )
                        >
                            Aktif
                        </option>

                        <option
                            value="closed"
                            @selected(
                                $selectedStatus === 'closed'
                            )
                        >
                            Ditutup
                        </option>

                        <option
                            value="expired"
                            @selected(
                                $selectedStatus === 'expired'
                            )
                        >
                            Kedaluwarsa
                        </option>
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label
                        for="session_date"
                        class="form-label"
                    >
                        Tanggal Sesi
                    </label>

                    <input
                        type="date"
                        id="session_date"
                        name="session_date"
                        value="{{ $selectedSessionDate }}"
                        class="form-control"
                    >
                </div>

                <div class="col-xl-2">
                    <div class="d-flex gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary flex-grow-1"
                        >
                            Terapkan
                        </button>

                        <a
                            href="{{ route('attendance-sessions.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <section class="content-card">
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                gap-2 border-bottom p-3 p-md-4"
        >
            <div>
                <h2 class="h5 fw-bold mb-1">
                    Daftar Sesi Presensi
                </h2>

                <p class="small text-secondary mb-0">
                    Ditemukan
                    {{ $attendanceSessions->total() }}
                    sesi presensi.
                </p>
            </div>

            @if (
                $search !== ''
                || $selectedAttendanceType !== ''
                || $selectedStatus !== ''
                || $selectedSessionDate !== null
            )
                <span class="badge text-bg-info align-self-start">
                    Filter aktif
                </span>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th
                            scope="col"
                            class="text-center"
                            style="width: 65px;"
                        >
                            No.
                        </th>

                        <th scope="col">
                            Cabang
                        </th>

                        <th scope="col">
                            Jenis Sesi
                        </th>

                        <th scope="col">
                            Tanggal dan Waktu
                        </th>

                        <th scope="col">
                            Pembuat
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Presensi
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Status
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                            style="width: 110px;"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse (
                        $attendanceSessions
                        as $attendanceSession
                    )
                        @php
                            $branch =
                                $attendanceSession->branch;

                            $creator =
                                $attendanceSession->creator;

                            $attendanceType =
                                strtolower(
                                    (string) $attendanceSession
                                        ->attendance_type
                                );

                            $sessionStatus =
                                strtolower(
                                    (string) $attendanceSession
                                        ->status
                                );

                            $attendanceTypeLabel =
                                $attendanceTypeLabels[
                                    $attendanceType
                                ]
                                ?? ucfirst($attendanceType);

                            $attendanceTypeClass =
                                $attendanceTypeClasses[
                                    $attendanceType
                                ]
                                ?? 'text-bg-secondary';

                            $statusLabel =
                                $statusLabels[$sessionStatus]
                                ?? ucfirst($sessionStatus);

                            $statusClass =
                                $statusClasses[$sessionStatus]
                                ?? 'text-bg-secondary';

                            $creatorRoleLabel = match (
                                $creator?->role
                            ) {
                                'hrd' => 'HRD',
                                'admin' => 'Admin Operasional',
                                default => 'Pengguna',
                            };
                        @endphp

                        <tr>
                            <td class="text-center text-secondary">
                                {{
                                    ($attendanceSessions->firstItem()
                                        ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                @if ($branch !== null)
                                    <div class="fw-semibold">
                                        {{ $branch->code }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $branch->name }}
                                    </div>

                                    @if ($branch->status !== 'active')
                                        <span
                                            class="badge
                                                text-bg-secondary mt-1"
                                        >
                                            Cabang tidak aktif
                                        </span>
                                    @endif
                                @else
                                    <span class="text-danger">
                                        Data cabang tidak tersedia
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span
                                    class="badge {{
                                        $attendanceTypeClass
                                    }}"
                                >
                                    {{ $attendanceTypeLabel }}
                                </span>

                                <div
                                    class="small text-secondary mt-2"
                                    title="{{ $attendanceSession->public_id }}"
                                >
                                    UUID:
                                    {{
                                        \Illuminate\Support\Str::limit(
                                            (string) $attendanceSession
                                                ->public_id,
                                            18,
                                            '…'
                                        )
                                    }}
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $formatDate(
                                            $attendanceSession
                                                ->session_date
                                        )
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{
                                        $formatTime(
                                            $attendanceSession
                                                ->start_time
                                        )
                                    }}
                                    sampai
                                    {{
                                        $formatTime(
                                            $attendanceSession
                                                ->end_time
                                        )
                                    }}
                                    WIB
                                </div>

                                @if (
                                    $attendanceSession->closed_at
                                    !== null
                                )
                                    <div
                                        class="small text-secondary mt-1"
                                    >
                                        Ditutup:
                                        {{
                                            $formatDateTime(
                                                $attendanceSession
                                                    ->closed_at
                                            )
                                        }}
                                        WIB
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if ($creator !== null)
                                    <div class="fw-semibold">
                                        {{ $creator->name }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $creatorRoleLabel }}
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        Data pengguna tidak tersedia
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge text-bg-light border"
                                >
                                    {{
                                        (int) $attendanceSession
                                            ->attendances_count
                                    }}
                                </span>
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="text-end">
                                <a
                                    href="{{
                                        route(
                                            'attendance-sessions.show',
                                            $attendanceSession
                                        )
                                    }}"
                                    class="btn btn-sm
                                        btn-outline-primary"
                                >
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="text-center py-5"
                            >
                                <div class="fw-semibold mb-1">
                                    Sesi presensi tidak ditemukan
                                </div>

                                <div class="small text-secondary">
                                    Belum terdapat sesi presensi atau
                                    data tidak sesuai dengan filter.
                                </div>

                                <a
                                    href="{{ route('attendance-sessions.index') }}"
                                    class="btn btn-sm
                                        btn-outline-secondary mt-3"
                                >
                                    Reset Filter
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendanceSessions->hasPages())
            @php
                $startPage = max(
                    1,
                    $attendanceSessions->currentPage() - 2
                );

                $endPage = min(
                    $attendanceSessions->lastPage(),
                    $attendanceSessions->currentPage() + 2
                );
            @endphp

            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between align-items-md-center
                    gap-3 border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $attendanceSessions->firstItem() }}
                    sampai
                    {{ $attendanceSessions->lastItem() }}
                    dari
                    {{ $attendanceSessions->total() }}
                    sesi.
                </div>

                <nav aria-label="Navigasi sesi presensi">
                    <ul class="pagination pagination-sm mb-0">
                        <li
                            class="page-item {{
                                $attendanceSessions->onFirstPage()
                                    ? 'disabled'
                                    : ''
                            }}"
                        >
                            <a
                                href="{{
                                    $attendanceSessions
                                        ->previousPageUrl()
                                    ?? '#'
                                }}"
                                class="page-link"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        @foreach (
                            $attendanceSessions->getUrlRange(
                                $startPage,
                                $endPage
                            ) as $page => $url
                        )
                            <li
                                class="page-item {{
                                    $page
                                    === $attendanceSessions
                                        ->currentPage()
                                        ? 'active'
                                        : ''
                                }}"
                            >
                                <a
                                    href="{{ $url }}"
                                    class="page-link"
                                >
                                    {{ $page }}
                                </a>
                            </li>
                        @endforeach

                        <li
                            class="page-item {{
                                $attendanceSessions
                                    ->hasMorePages()
                                    ? ''
                                    : 'disabled'
                            }}"
                        >
                            <a
                                href="{{
                                    $attendanceSessions
                                        ->nextPageUrl()
                                    ?? '#'
                                }}"
                                class="page-link"
                            >
                                Berikutnya
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
    </section>
@endsection