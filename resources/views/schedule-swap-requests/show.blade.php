@extends('layouts.app')

@section('title', 'Detail Pertukaran Jadwal')

@section('content')
    @php
        $requester = $scheduleSwapRequest->requesterEmployee;
        $requesterBranch = $requester?->branch;

        $partner = $scheduleSwapRequest->partnerEmployee;
        $partnerBranch = $partner?->branch;

        $approver = $scheduleSwapRequest->approver;

        $requestStatus = strtolower(
            (string) $scheduleSwapRequest->status
        );

        $statusLabels = [
            'pending' => 'Menunggu Keputusan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        $statusClasses = [
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ];

        $scheduleStatusLabels = [
            'work' => 'Kerja',
            'off' => 'Libur',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $scheduleStatusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];

        $statusLabel = $statusLabels[$requestStatus]
            ?? ucfirst($requestStatus);

        $statusClass = $statusClasses[$requestStatus]
            ?? 'text-bg-secondary';

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l, d F Y');
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

            $time = (string) $value;

            return strlen($time) >= 5
                ? substr($time, 0, 5)
                : $time;
        };

        $requesterAttendanceCount = (int) (
            $requesterSchedule?->attendances_count ?? 0
        );

        $partnerAttendanceCount = (int) (
            $partnerSchedule?->attendances_count ?? 0
        );

        $isHrd = auth()->user()?->hasRole('hrd') === true;

        $canApprove =
            $requestStatus === 'pending'
            && $canBeDecided;

        $canReject =
            $requestStatus === 'pending';

        $approvalBlockedReason = null;

        if ($requestStatus !== 'pending') {
            $approvalBlockedReason =
                'Permohonan ini sudah memiliki keputusan.';
        } elseif (
            $requesterSchedule === null
            || $partnerSchedule === null
        ) {
            $approvalBlockedReason =
                'Salah satu jadwal sudah tidak tersedia.';
        } elseif (
            $requesterAttendanceCount > 0
            || $partnerAttendanceCount > 0
        ) {
            $approvalBlockedReason =
                'Pertukaran tidak dapat disetujui karena salah satu jadwal sudah memiliki data presensi.';
        }
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Detail Pertukaran Jadwal
            </h1>

            <p class="page-description">
                Informasi pengajuan dan keputusan pertukaran
                jadwal antarkaryawan.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{
                    route(
                        'schedule-swap-requests.index'
                    )
                }}"
                class="btn btn-outline-secondary"
            >
                Kembali ke Daftar
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-start gap-3"
        >
            <div>
                <div class="small text-secondary mb-1">
                    Status permohonan
                </div>

                <h2 class="h4 fw-bold mb-2">
                    Pertukaran Jadwal Karyawan
                </h2>

                <p class="text-secondary mb-0">
                    Permohonan dicatat pada
                    {{
                        $formatDateTime(
                            $scheduleSwapRequest->created_at
                        )
                    }}
                    WIB.
                </p>
            </div>

            <span
                class="badge {{ $statusClass }}
                    fs-6 px-3 py-2"
            >
                {{ $statusLabel }}
            </span>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Karyawan Pengaju
                    </h2>

                    <p class="small text-secondary mb-0">
                        Karyawan yang mengajukan pertukaran.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    @if ($requester !== null)
                        <dl class="row mb-0">
                            <dt class="col-sm-5 mb-2">
                                Nomor Karyawan
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $requester->employee_number }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Nama Lengkap
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                <a
                                    href="{{
                                        route(
                                            'employees.show',
                                            $requester
                                        )
                                    }}"
                                    class="fw-semibold
                                        text-decoration-none"
                                >
                                    {{ $requester->full_name }}
                                </a>
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Jabatan
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $requester->position }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Cabang
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                @if ($requesterBranch !== null)
                                    <div class="fw-semibold">
                                        {{ $requesterBranch->code }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $requesterBranch->name }}
                                    </div>
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Status Karyawan
                            </dt>

                            <dd class="col-sm-7 mb-0">
                                @if (
                                    $requester->employment_status
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
                        </dl>
                    @else
                        <div
                            class="alert alert-danger mb-0"
                            role="alert"
                        >
                            Data karyawan pengaju tidak tersedia.
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Karyawan Pasangan
                    </h2>

                    <p class="small text-secondary mb-0">
                        Karyawan yang menjadi pasangan pertukaran.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    @if ($partner !== null)
                        <dl class="row mb-0">
                            <dt class="col-sm-5 mb-2">
                                Nomor Karyawan
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $partner->employee_number }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Nama Lengkap
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                <a
                                    href="{{
                                        route(
                                            'employees.show',
                                            $partner
                                        )
                                    }}"
                                    class="fw-semibold
                                        text-decoration-none"
                                >
                                    {{ $partner->full_name }}
                                </a>
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Jabatan
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $partner->position }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Cabang
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                @if ($partnerBranch !== null)
                                    <div class="fw-semibold">
                                        {{ $partnerBranch->code }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $partnerBranch->name }}
                                    </div>
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Status Karyawan
                            </dt>

                            <dd class="col-sm-7 mb-0">
                                @if (
                                    $partner->employment_status
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
                        </dl>
                    @else
                        <div
                            class="alert alert-danger mb-0"
                            role="alert"
                        >
                            Data karyawan pasangan tidak tersedia.
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Jadwal yang Dipertukarkan
            </h2>

            <p class="small text-secondary mb-0">
                Jadwal yang tercatat pada tanggal pengajuan.
            </p>
        </div>

        <div class="p-3 p-md-4">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <h3 class="h6 fw-bold mb-3">
                            Jadwal Karyawan Pengaju
                        </h3>

                        <div class="mb-3">
                            <div class="small text-secondary">
                                Tanggal Jadwal
                            </div>

                            <div class="fw-semibold">
                                {{
                                    $formatDate(
                                        $scheduleSwapRequest
                                            ->requester_date
                                    )
                                }}
                            </div>
                        </div>

                        @if ($requesterSchedule !== null)
                            @php
                                $requesterScheduleStatus =
                                    strtolower(
                                        (string) $requesterSchedule
                                            ->schedule_status
                                    );

                                $requesterScheduleLabel =
                                    $scheduleStatusLabels[
                                        $requesterScheduleStatus
                                    ]
                                    ?? ucfirst(
                                        $requesterScheduleStatus
                                    );

                                $requesterScheduleClass =
                                    $scheduleStatusClasses[
                                        $requesterScheduleStatus
                                    ]
                                    ?? 'text-bg-secondary';

                                $requesterWorkSchedule =
                                    $requesterSchedule
                                        ->workSchedule;
                            @endphp

                            <div class="mb-3">
                                <div class="small text-secondary">
                                    Status Jadwal
                                </div>

                                <span
                                    class="badge {{
                                        $requesterScheduleClass
                                    }}"
                                >
                                    {{ $requesterScheduleLabel }}
                                </span>
                            </div>

                            <div class="mb-3">
                                <div class="small text-secondary">
                                    Pola Jadwal
                                </div>

                                @if (
                                    $requesterWorkSchedule !== null
                                )
                                    <div class="fw-semibold">
                                        {{
                                            $requesterWorkSchedule
                                                ->name
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $formatTime(
                                                $requesterWorkSchedule
                                                    ->check_in_time
                                            )
                                        }}
                                        sampai
                                        {{
                                            $formatTime(
                                                $requesterWorkSchedule
                                                    ->check_out_time
                                            )
                                        }}
                                        WIB
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        Tidak menggunakan pola jadwal
                                    </span>
                                @endif
                            </div>

                            <div>
                                <div class="small text-secondary">
                                    Data Presensi
                                </div>

                                <span
                                    class="badge text-bg-light border"
                                >
                                    {{ $requesterAttendanceCount }}
                                    data
                                </span>
                            </div>
                        @else
                            <div
                                class="alert alert-danger mb-0"
                                role="alert"
                            >
                                Jadwal karyawan pengaju sudah
                                tidak tersedia.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <h3 class="h6 fw-bold mb-3">
                            Jadwal Karyawan Pasangan
                        </h3>

                        <div class="mb-3">
                            <div class="small text-secondary">
                                Tanggal Jadwal
                            </div>

                            <div class="fw-semibold">
                                {{
                                    $formatDate(
                                        $scheduleSwapRequest
                                            ->partner_date
                                    )
                                }}
                            </div>
                        </div>

                        @if ($partnerSchedule !== null)
                            @php
                                $partnerScheduleStatus =
                                    strtolower(
                                        (string) $partnerSchedule
                                            ->schedule_status
                                    );

                                $partnerScheduleLabel =
                                    $scheduleStatusLabels[
                                        $partnerScheduleStatus
                                    ]
                                    ?? ucfirst(
                                        $partnerScheduleStatus
                                    );

                                $partnerScheduleClass =
                                    $scheduleStatusClasses[
                                        $partnerScheduleStatus
                                    ]
                                    ?? 'text-bg-secondary';

                                $partnerWorkSchedule =
                                    $partnerSchedule
                                        ->workSchedule;
                            @endphp

                            <div class="mb-3">
                                <div class="small text-secondary">
                                    Status Jadwal
                                </div>

                                <span
                                    class="badge {{
                                        $partnerScheduleClass
                                    }}"
                                >
                                    {{ $partnerScheduleLabel }}
                                </span>
                            </div>

                            <div class="mb-3">
                                <div class="small text-secondary">
                                    Pola Jadwal
                                </div>

                                @if (
                                    $partnerWorkSchedule !== null
                                )
                                    <div class="fw-semibold">
                                        {{
                                            $partnerWorkSchedule
                                                ->name
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $formatTime(
                                                $partnerWorkSchedule
                                                    ->check_in_time
                                            )
                                        }}
                                        sampai
                                        {{
                                            $formatTime(
                                                $partnerWorkSchedule
                                                    ->check_out_time
                                            )
                                        }}
                                        WIB
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        Tidak menggunakan pola jadwal
                                    </span>
                                @endif
                            </div>

                            <div>
                                <div class="small text-secondary">
                                    Data Presensi
                                </div>

                                <span
                                    class="badge text-bg-light border"
                                >
                                    {{ $partnerAttendanceCount }}
                                    data
                                </span>
                            </div>
                        @else
                            <div
                                class="alert alert-danger mb-0"
                                role="alert"
                            >
                                Jadwal karyawan pasangan sudah
                                tidak tersedia.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content-card mb-4">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Alasan Pertukaran
            </h2>
        </div>

        <div class="p-3 p-md-4">
            <p class="mb-0">
                {{ $scheduleSwapRequest->reason }}
            </p>
        </div>
    </section>

    <section class="content-card mb-4">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Keputusan Permohonan
            </h2>

            <p class="small text-secondary mb-0">
                Informasi persetujuan atau penolakan permohonan.
            </p>
        </div>

        <div class="p-3 p-md-4">
            @if ($requestStatus === 'pending')
                <div
                    class="alert alert-warning mb-0"
                    role="alert"
                >
                    Permohonan masih menunggu keputusan HRD.
                </div>
            @else
                <dl class="row mb-0">
                    <dt class="col-sm-4 mb-2">
                        Keputusan
                    </dt>

                    <dd class="col-sm-8 mb-3">
                        <span
                            class="badge {{ $statusClass }}"
                        >
                            {{ $statusLabel }}
                        </span>
                    </dd>

                    <dt class="col-sm-4 mb-2">
                        Diputuskan Oleh
                    </dt>

                    <dd class="col-sm-8 mb-3">
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

                    <dt class="col-sm-4 mb-2">
                        Waktu Keputusan
                    </dt>

                    <dd class="col-sm-8 mb-0">
                        {{
                            $formatDateTime(
                                $scheduleSwapRequest
                                    ->approved_at
                            )
                        }}
                        WIB
                    </dd>
                </dl>
            @endif
        </div>
    </section>

    @if ($isHrd)
        <section class="content-card">
            <div class="border-bottom p-3 p-md-4">
                <h2 class="h5 fw-bold mb-1">
                    Tindakan HRD
                </h2>

                <p class="small text-secondary mb-0">
                    Berikan keputusan terhadap permohonan ini.
                </p>
            </div>

            <div class="p-3 p-md-4">
                @error('decision')
                    <div
                        class="alert alert-danger"
                        role="alert"
                    >
                        {{ $message }}
                    </div>
                @enderror

                @if ($requestStatus !== 'pending')
                    <div
                        class="alert alert-light border mb-0"
                        role="alert"
                    >
                        Permohonan sudah diputuskan dan tidak dapat
                        diproses kembali.
                    </div>
                @else
                    @if ($approvalBlockedReason !== null)
                        <div
                            class="alert alert-warning"
                            role="alert"
                        >
                            {{ $approvalBlockedReason }}
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-2">
                        <form
                            method="POST"
                            action="{{
                                route(
                                    'schedule-swap-requests.decide',
                                    $scheduleSwapRequest
                                )
                            }}"
                            onsubmit="
                                return confirm(
                                    'Setujui pertukaran jadwal ini?'
                                );
                            "
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="decision"
                                value="approved"
                            >

                            <button
                                type="submit"
                                class="btn btn-success"
                                @disabled(! $canApprove)
                            >
                                Setujui dan Tukar Jadwal
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{
                                route(
                                    'schedule-swap-requests.decide',
                                    $scheduleSwapRequest
                                )
                            }}"
                            onsubmit="
                                return confirm(
                                    'Tolak permohonan pertukaran ini?'
                                );
                            "
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="decision"
                                value="rejected"
                            >

                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                                @disabled(! $canReject)
                            >
                                Tolak Permohonan
                            </button>
                        </form>
                    </div>

                    @if (! $canApprove && $canReject)
                        <p class="small text-secondary mt-3 mb-0">
                            Persetujuan tidak tersedia, tetapi HRD
                            masih dapat menolak permohonan untuk
                            menyelesaikan statusnya.
                        </p>
                    @endif
                @endif
            </div>
        </section>
    @else
        <section class="content-card">
            <div class="p-3 p-md-4">
                <div
                    class="alert alert-info mb-0"
                    role="alert"
                >
                    Admin operasional dapat melihat dan mencatat
                    permohonan. Keputusan persetujuan atau penolakan
                    hanya dapat dilakukan oleh HRD.
                </div>
            </div>
        </section>
    @endif
@endsection