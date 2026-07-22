@extends('layouts.app')

@section('title', 'Detail Jadwal Harian')

@section('content')
    @php
        $employee = $employeeSchedule->employee;
        $employeeUser = $employee?->user;
        $branch = $employee?->branch;
        $workSchedule = $employeeSchedule->workSchedule;

        $scheduleStatus = $employeeSchedule->schedule_status;

        $statusLabels = [
            'work' => 'Kerja',
            'off' => 'Libur',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $statusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];

        $statusDescriptions = [
            'work' => 'Karyawan dijadwalkan bekerja menggunakan pola jadwal yang ditetapkan.',
            'off' => 'Karyawan mendapatkan hari libur pada tanggal tersebut.',
            'permit' => 'Karyawan tercatat memiliki izin pada tanggal tersebut.',
            'sick' => 'Karyawan tercatat sakit pada tanggal tersebut.',
        ];

        $statusLabel = $statusLabels[$scheduleStatus]
            ?? ucfirst((string) $scheduleStatus);

        $statusClass = $statusClasses[$scheduleStatus]
            ?? 'text-bg-secondary';

        $statusDescription = $statusDescriptions[$scheduleStatus]
            ?? 'Status jadwal tidak dikenali.';

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l, d F Y');
            } catch (\Throwable) {
                return (string) $value;
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

            $time = (string) $value;

            return strlen($time) >= 5
                ? substr($time, 0, 5)
                : $time;
        };

        $attendanceCount = (int) (
            $employeeSchedule->attendances_count ?? 0
        );
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Detail Jadwal Harian
            </h1>

            <p class="page-description">
                Informasi penetapan jadwal harian karyawan.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a
                href="{{ route('employee-schedules.index') }}"
                class="btn btn-outline-secondary"
            >
                Kembali
            </a>

            <a
                href="{{
                    route(
                        'employee-schedules.edit',
                        $employeeSchedule
                    )
                }}"
                class="btn btn-primary"
            >
                Edit Jadwal
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <div
            class="d-flex flex-column flex-md-row
                align-items-md-start justify-content-between gap-3"
        >
            <div>
                <div class="small text-secondary mb-1">
                    Tanggal jadwal
                </div>

                <h2 class="h4 fw-bold mb-2">
                    {{
                        $formatDate(
                            $employeeSchedule->schedule_date
                        )
                    }}
                </h2>

                <p class="text-secondary mb-0">
                    {{ $statusDescription }}
                </p>
            </div>

            <div>
                <span
                    class="badge {{ $statusClass }} fs-6 px-3 py-2"
                >
                    {{ $statusLabel }}
                </span>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Data Karyawan
                    </h2>

                    <p class="small text-secondary mb-0">
                        Karyawan yang menerima penetapan jadwal.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    @if ($employee !== null)
                        <dl class="row mb-0">
                            <dt class="col-sm-4 mb-2">
                                Nomor Karyawan
                            </dt>

                            <dd class="col-sm-8 mb-3">
                                {{ $employee->employee_number }}
                            </dd>

                            <dt class="col-sm-4 mb-2">
                                Nama Lengkap
                            </dt>

                            <dd class="col-sm-8 mb-3">
                                <a
                                    href="{{
                                        route(
                                            'employees.show',
                                            $employee
                                        )
                                    }}"
                                    class="fw-semibold text-decoration-none"
                                >
                                    {{ $employee->full_name }}
                                </a>
                            </dd>

                            <dt class="col-sm-4 mb-2">
                                Jabatan
                            </dt>

                            <dd class="col-sm-8 mb-3">
                                {{ $employee->position }}
                            </dd>

                            <dt class="col-sm-4 mb-2">
                                Status Karyawan
                            </dt>

                            <dd class="col-sm-8 mb-3">
                                @if (
                                    $employee->employment_status
                                    === 'active'
                                )
                                    <span
                                        class="badge text-bg-success"
                                    >
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="badge text-bg-secondary"
                                    >
                                        Tidak Aktif
                                    </span>
                                @endif
                            </dd>

                            <dt class="col-sm-4 mb-2">
                                Email Akun
                            </dt>

                            <dd class="col-sm-8 mb-3">
                                {{ $employeeUser?->email ?? '-' }}
                            </dd>

                            <dt class="col-sm-4 mb-2">
                                Status Akun
                            </dt>

                            <dd class="col-sm-8 mb-0">
                                @if ($employeeUser?->status === 'active')
                                    <span
                                        class="badge text-bg-success"
                                    >
                                        Aktif
                                    </span>
                                @elseif ($employeeUser !== null)
                                    <span
                                        class="badge text-bg-secondary"
                                    >
                                        Tidak Aktif
                                    </span>
                                @else
                                    <span class="text-secondary">
                                        Akun tidak tersedia
                                    </span>
                                @endif
                            </dd>
                        </dl>
                    @else
                        <div
                            class="alert alert-danger mb-0"
                            role="alert"
                        >
                            Data karyawan tidak tersedia.
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Data Cabang
                    </h2>

                    <p class="small text-secondary mb-0">
                        Lokasi kerja karyawan.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    @if ($branch !== null)
                        <dl class="mb-0">
                            <dt class="mb-1">
                                Kode Cabang
                            </dt>

                            <dd class="mb-3">
                                {{ $branch->code }}
                            </dd>

                            <dt class="mb-1">
                                Nama Cabang
                            </dt>

                            <dd class="mb-3">
                                <a
                                    href="{{
                                        route(
                                            'branches.show',
                                            $branch
                                        )
                                    }}"
                                    class="fw-semibold text-decoration-none"
                                >
                                    {{ $branch->name }}
                                </a>
                            </dd>

                            <dt class="mb-1">
                                Alamat
                            </dt>

                            <dd class="mb-3">
                                {{ $branch->address ?: '-' }}
                            </dd>

                            <dt class="mb-1">
                                Status Cabang
                            </dt>

                            <dd class="mb-0">
                                @if ($branch->status === 'active')
                                    <span
                                        class="badge text-bg-success"
                                    >
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="badge text-bg-secondary"
                                    >
                                        Tidak Aktif
                                    </span>
                                @endif
                            </dd>
                        </dl>
                    @else
                        <div
                            class="alert alert-warning mb-0"
                            role="alert"
                        >
                            Data cabang tidak tersedia.
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Pola dan Ketentuan Jam Kerja
            </h2>

            <p class="small text-secondary mb-0">
                Ketentuan waktu presensi berdasarkan pola jadwal.
            </p>
        </div>

        <div class="p-3 p-md-4">
            @if (
                $scheduleStatus === 'work'
                && $workSchedule !== null
            )
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="small text-secondary mb-1">
                            Nama Pola Jadwal
                        </div>

                        <div class="fw-semibold">
                            <a
                                href="{{
                                    route(
                                        'work-schedules.show',
                                        $workSchedule
                                    )
                                }}"
                                class="text-decoration-none"
                            >
                                {{ $workSchedule->name }}
                            </a>
                        </div>

                        @if ($workSchedule->status === 'inactive')
                            <div class="mt-2">
                                <span
                                    class="badge text-bg-warning"
                                >
                                    Pola jadwal tidak aktif
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-3">
                        <div class="small text-secondary mb-1">
                            Jam Masuk
                        </div>

                        <div class="fw-semibold">
                            {{
                                $formatTime(
                                    $workSchedule->check_in_time
                                )
                            }}
                            WIB
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="small text-secondary mb-1">
                            Jam Pulang
                        </div>

                        <div class="fw-semibold">
                            {{
                                $formatTime(
                                    $workSchedule->check_out_time
                                )
                            }}
                            WIB
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div
                            class="border rounded-3 p-3 h-100"
                        >
                            <div class="small text-secondary mb-1">
                                Presensi Masuk Dibuka
                            </div>

                            <div class="fw-semibold">
                                {{
                                    $workSchedule
                                        ->check_in_open_minutes
                                }}
                                menit sebelum jam masuk
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div
                            class="border rounded-3 p-3 h-100"
                        >
                            <div class="small text-secondary mb-1">
                                Toleransi Keterlambatan
                            </div>

                            <div class="fw-semibold">
                                {{
                                    $workSchedule
                                        ->late_tolerance_minutes
                                }}
                                menit
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div
                            class="border rounded-3 p-3 h-100"
                        >
                            <div class="small text-secondary mb-1">
                                Batas Presensi Pulang
                            </div>

                            <div class="fw-semibold">
                                {{
                                    $workSchedule
                                        ->check_out_limit_minutes
                                }}
                                menit setelah jam pulang
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($scheduleStatus === 'work')
                <div
                    class="alert alert-danger mb-0"
                    role="alert"
                >
                    Status jadwal adalah kerja, tetapi pola jadwal
                    tidak tersedia.
                </div>
            @else
                <div
                    class="alert alert-light border mb-0"
                    role="alert"
                >
                    Status {{ strtolower($statusLabel) }} tidak
                    menggunakan pola jadwal kerja.
                </div>
            @endif
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Keterangan Jadwal
                    </h2>
                </div>

                <div class="p-3 p-md-4">
                    @if (
                        $employeeSchedule->notes !== null
                        && trim((string) $employeeSchedule->notes)
                            !== ''
                    )
                        <p class="mb-0">
                            {{ $employeeSchedule->notes }}
                        </p>
                    @else
                        <span class="text-secondary">
                            Tidak ada keterangan.
                        </span>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Persetujuan dan Riwayat
                    </h2>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 mb-2">
                            Ditetapkan Oleh
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            @if ($approver !== null)
                                <div class="fw-semibold">
                                    {{ $approver->name }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $approver->email }}
                                </div>
                            @else
                                <span class="text-secondary">
                                    Data pengguna tidak tersedia
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Dibuat
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $formatDateTime(
                                    $employeeSchedule->created_at
                                )
                            }}
                            WIB
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Terakhir Diubah
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            {{
                                $formatDateTime(
                                    $employeeSchedule->updated_at
                                )
                            }}
                            WIB
                        </dd>
                    </dl>
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4">
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                gap-3 p-3 p-md-4"
        >
            <div>
                <h2 class="h5 fw-bold mb-1">
                    Data Presensi
                </h2>

                <p class="small text-secondary mb-0">
                    Jumlah data presensi yang menggunakan jadwal ini.
                </p>
            </div>

            <div>
                <span
                    class="badge text-bg-light border fs-6 px-3 py-2"
                >
                    {{ $attendanceCount }} data presensi
                </span>
            </div>
        </div>
    </section>

    <section class="content-card border-danger">
        <div class="p-3 p-md-4">
            <h2 class="h5 fw-bold text-danger mb-2">
                Hapus Jadwal Harian
            </h2>

            @if ($attendanceCount > 0)
                <p class="text-secondary mb-3">
                    Jadwal tidak dapat dihapus karena sudah
                    digunakan oleh data presensi.
                </p>

                <button
                    type="button"
                    class="btn btn-outline-danger"
                    disabled
                >
                    Hapus Jadwal
                </button>
            @else
                <p class="text-secondary mb-3">
                    Jadwal dapat dihapus karena belum memiliki
                    data presensi. Tindakan ini tidak dapat dibatalkan.
                </p>

                <form
                    method="POST"
                    action="{{
                        route(
                            'employee-schedules.destroy',
                            $employeeSchedule
                        )
                    }}"
                    onsubmit="
                        return confirm(
                            'Hapus jadwal harian ini?'
                        );
                    "
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="btn btn-outline-danger"
                    >
                        Hapus Jadwal
                    </button>
                </form>
            @endif
        </div>
    </section>
@endsection