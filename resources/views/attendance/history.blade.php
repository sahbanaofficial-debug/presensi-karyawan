@extends('layouts.app')

@section('title', 'Riwayat Presensi')

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

        $punctualityLabels = [
            'on_time' => 'Tepat Waktu',
            'late' => 'Terlambat',
            'not_applicable' => 'Tidak Berlaku',
        ];

        $punctualityClasses = [
            'on_time' => 'text-bg-success',
            'late' => 'text-bg-danger',
            'not_applicable' => 'text-bg-secondary',
        ];

        $attendanceStatusLabels = [
            'present' => 'Hadir',
        ];

        $validationStatusLabels = [
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
        ];

        $validationStatusClasses = [
            'accepted' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
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

        $formatTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i:s');
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('H:i:s');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDecimal = static function (
            mixed $value,
            int $precision = 2
        ): string {
            if (
                $value === null
                || $value === ''
                || ! is_numeric($value)
            ) {
                return '-';
            }

            return number_format(
                (float) $value,
                $precision,
                ',',
                '.'
            );
        };
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Riwayat Presensi
            </h1>

            <p class="page-description">
                Lihat catatan presensi masuk dan pulang
                milik akun Anda.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('attendance.create') }}"
                class="btn btn-primary"
            >
                Buka Pemindai
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h5 fw-bold mb-3">
                    Data Karyawan
                </h2>

                <dl class="row mb-0">
                    <dt class="col-sm-5 mb-2">
                        Nomor Karyawan
                    </dt>

                    <dd class="col-sm-7 mb-3">
                        {{ $employee->employee_number }}
                    </dd>

                    <dt class="col-sm-5 mb-2">
                        Nama
                    </dt>

                    <dd class="col-sm-7 mb-3">
                        {{ $employee->full_name }}
                    </dd>

                    <dt class="col-sm-5 mb-2">
                        Jabatan
                    </dt>

                    <dd class="col-sm-7 mb-0">
                        {{ $employee->position }}
                    </dd>
                </dl>
            </div>

            <div class="col-md-6">
                <h2 class="h5 fw-bold mb-3">
                    Cabang Penempatan
                </h2>

                @if ($branch !== null)
                    <dl class="row mb-0">
                        <dt class="col-sm-5 mb-2">
                            Kode Cabang
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $branch->code }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Nama Cabang
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $branch->name }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Alamat
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            {{ $branch->address ?? '-' }}
                        </dd>
                    </dl>
                @else
                    <div class="alert alert-warning mb-0">
                        Data cabang penempatan tidak tersedia.
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Seluruh Presensi
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['total'] }}
                </div>

                <div class="small text-secondary">
                    transaksi
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-3">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Presensi Masuk
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['check_in'] }}
                </div>

                <div class="small text-secondary">
                    transaksi
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-3">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Presensi Pulang
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['check_out'] }}
                </div>

                <div class="small text-secondary">
                    transaksi
                </div>
            </section>
        </div>

        <div class="col-sm-6 col-xl-3">
            <section class="content-card p-3 h-100">
                <div class="small text-secondary mb-1">
                    Keterlambatan
                </div>

                <div class="fs-3 fw-bold">
                    {{ (int) $summary['late'] }}
                </div>

                <div class="small text-secondary">
                    presensi masuk
                </div>
            </section>
        </div>
    </div>

    <section class="content-card p-3 p-md-4 mb-4">
        <form
            method="GET"
            action="{{ route('attendance.history') }}"
        >
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
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

                <div class="col-md-4">
                    <label
                        for="attendance_date"
                        class="form-label"
                    >
                        Tanggal Presensi
                    </label>

                    <input
                        type="date"
                        id="attendance_date"
                        name="attendance_date"
                        value="{{ $selectedAttendanceDate ?? '' }}"
                        class="form-control"
                    >
                </div>

                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary flex-grow-1"
                        >
                            Terapkan
                        </button>

                        <a
                            href="{{ route('attendance.history') }}"
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
                    Daftar Riwayat
                </h2>

                <p class="small text-secondary mb-0">
                    Ditemukan
                    {{ $attendances->total() }}
                    transaksi presensi.
                </p>
            </div>

            @if (
                $selectedAttendanceType !== ''
                || $selectedAttendanceDate !== null
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
                            Tanggal dan Waktu
                        </th>

                        <th scope="col">
                            Jenis
                        </th>

                        <th scope="col">
                            Status Kehadiran
                        </th>

                        <th scope="col">
                            Ketepatan Waktu
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                        >
                            Jarak
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                        >
                            Accuracy
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Validasi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($attendances as $attendance)
                        @php
                            $attendanceType = strtolower(
                                (string) $attendance
                                    ->attendance_type
                            );

                            $punctualityStatus = strtolower(
                                (string) $attendance
                                    ->punctuality_status
                            );

                            $attendanceStatus = strtolower(
                                (string) $attendance
                                    ->attendance_status
                            );

                            $validationStatus = strtolower(
                                (string) $attendance
                                    ->validation_status
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

                            $punctualityLabel =
                                $punctualityLabels[
                                    $punctualityStatus
                                ]
                                ?? ucfirst($punctualityStatus);

                            $punctualityClass =
                                $punctualityClasses[
                                    $punctualityStatus
                                ]
                                ?? 'text-bg-secondary';

                            $attendanceStatusLabel =
                                $attendanceStatusLabels[
                                    $attendanceStatus
                                ]
                                ?? ucfirst($attendanceStatus);

                            $validationStatusLabel =
                                $validationStatusLabels[
                                    $validationStatus
                                ]
                                ?? ucfirst($validationStatus);

                            $validationStatusClass =
                                $validationStatusClasses[
                                    $validationStatus
                                ]
                                ?? 'text-bg-secondary';
                        @endphp

                        <tr>
                            <td class="text-center text-secondary">
                                {{
                                    ($attendances->firstItem() ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $formatDate(
                                            $attendance
                                                ->attendance_date
                                        )
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{
                                        $formatTime(
                                            $attendance
                                                ->attendance_time
                                        )
                                    }}
                                    WIB
                                </div>
                            </td>

                            <td>
                                <span
                                    class="badge {{
                                        $attendanceTypeClass
                                    }}"
                                >
                                    {{ $attendanceTypeLabel }}
                                </span>
                            </td>

                            <td>
                                {{ $attendanceStatusLabel }}
                            </td>

                            <td>
                                <span
                                    class="badge {{
                                        $punctualityClass
                                    }}"
                                >
                                    {{ $punctualityLabel }}
                                </span>
                            </td>

                            <td class="text-end">
                                {{
                                    $formatDecimal(
                                        $attendance->distance
                                    )
                                }}
                                meter
                            </td>

                            <td class="text-end">
                                {{
                                    $formatDecimal(
                                        $attendance->accuracy
                                    )
                                }}
                                meter
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge {{
                                        $validationStatusClass
                                    }}"
                                >
                                    {{ $validationStatusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="text-center py-5"
                            >
                                <div class="fw-semibold mb-1">
                                    Riwayat presensi belum tersedia
                                </div>

                                <div class="small text-secondary">
                                    Belum terdapat transaksi atau
                                    data tidak sesuai dengan filter.
                                </div>

                                <a
                                    href="{{ route('attendance.history') }}"
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

        @if ($attendances->hasPages())
            @php
                $startPage = max(
                    1,
                    $attendances->currentPage() - 2
                );

                $endPage = min(
                    $attendances->lastPage(),
                    $attendances->currentPage() + 2
                );
            @endphp

            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between align-items-md-center
                    gap-3 border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $attendances->firstItem() }}
                    sampai
                    {{ $attendances->lastItem() }}
                    dari
                    {{ $attendances->total() }}
                    transaksi.
                </div>

                <nav aria-label="Navigasi riwayat presensi">
                    <ul class="pagination pagination-sm mb-0">
                        <li
                            class="page-item {{
                                $attendances->onFirstPage()
                                    ? 'disabled'
                                    : ''
                            }}"
                        >
                            <a
                                href="{{
                                    $attendances
                                        ->previousPageUrl()
                                    ?? '#'
                                }}"
                                class="page-link"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        @foreach (
                            $attendances->getUrlRange(
                                $startPage,
                                $endPage
                            ) as $page => $url
                        )
                            <li
                                class="page-item {{
                                    $page
                                    === $attendances
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
                                $attendances->hasMorePages()
                                    ? ''
                                    : 'disabled'
                            }}"
                        >
                            <a
                                href="{{
                                    $attendances
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