@extends('layouts.app')

@section('title', 'Koreksi Data Presensi')

@section('content')
    @php
        $attendanceDateValue =
            $attendance->attendance_date
                instanceof \DateTimeInterface
                    ? $attendance->attendance_date
                        ->format('Y-m-d')
                    : substr(
                        (string) $attendance
                            ->attendance_date,
                        0,
                        10
                    );

        $monitoringUrl = route(
            'attendance-monitoring.index',
            [
                'attendance_date' =>
                    $attendanceDateValue,

                'employee_id' =>
                    $attendance->employee_id,
            ]
        );

        $correctionHistory =
            $attendance->corrections
            ?? collect();

        $actionLabels = [
            'create' => 'Pembuatan Manual',
            'update' => 'Perubahan Data',
        ];

        $actionClasses = [
            'create' => 'text-bg-primary',
            'update' => 'text-bg-warning',
        ];

        $formatDateTime = static function (
            mixed $value
        ): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat(
                        'd F Y, H:i:s'
                    );
            } catch (\Throwable) {
                return (string) $value;
            }
        };
    @endphp

    <header
        class="page-header d-md-flex
            align-items-start justify-content-between
            gap-3"
    >
        <div>
            <h1 class="page-title">
                Koreksi Data Presensi
            </h1>

            <p class="page-description">
                Perbarui catatan presensi berdasarkan hasil
                pemeriksaan dan verifikasi HRD.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ $monitoringUrl }}"
                class="btn btn-outline-secondary"
            >
                Kembali ke Monitoring
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <div
            class="d-flex flex-column flex-lg-row
                justify-content-between gap-3"
        >
            <div>
                <h2 class="h5 fw-bold mb-2">
                    Informasi Koreksi
                </h2>

                <p class="text-secondary mb-0">
                    Setiap perubahan akan dicatat sebagai
                    riwayat audit baru tanpa menghapus
                    informasi kondisi sebelumnya.
                </p>
            </div>

            <div class="align-self-start">
                <span class="badge text-bg-warning">
                    Khusus HRD
                </span>
            </div>
        </div>

        <hr>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    ID Presensi
                </div>

                <div class="fw-semibold">
                    #{{ $attendance->id }}
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Karyawan
                </div>

                <div class="fw-semibold">
                    {{
                        $attendance->employee?->full_name
                        ?? '-'
                    }}
                </div>

                <div class="small text-secondary">
                    {{
                        $attendance
                            ->employee
                            ?->employee_number
                        ?? '-'
                    }}
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Cabang
                </div>

                <div class="fw-semibold">
                    {{
                        $attendance
                            ->employee
                            ?->branch
                            ?->name
                        ?? '-'
                    }}
                </div>

                <div class="small text-secondary">
                    {{
                        $attendance
                            ->employee
                            ?->branch
                            ?->code
                        ?? '-'
                    }}
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Riwayat Koreksi
                </div>

                <div class="fw-semibold">
                    {{ $correctionHistory->count() }}
                    tindakan
                </div>
            </div>
        </div>

        <div class="alert alert-warning mt-4 mb-0">
            Setelah perubahan disimpan, data presensi aktif
            akan memiliki sumber <strong>manual</strong>.
            Data sesi QR, koordinat, accuracy, jarak, dan radius
            tidak lagi digunakan pada data aktif. Kondisi
            sebelumnya tetap tersimpan dalam tabel audit.
        </div>
    </section>

    @if ($correctionHistory->isNotEmpty())
        <section class="content-card mb-4">
            <div class="border-bottom p-3 p-md-4">
                <h2 class="h5 fw-bold mb-1">
                    Riwayat Koreksi Sebelumnya
                </h2>

                <p class="small text-secondary mb-0">
                    Daftar tindakan koreksi yang sudah
                    dilakukan pada transaksi ini.
                </p>
            </div>

            <div class="table-responsive">
                <table
                    class="table table-hover
                        align-middle mb-0"
                >
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
                                Waktu
                            </th>

                            <th scope="col">
                                Tindakan
                            </th>

                            <th scope="col">
                                HRD
                            </th>

                            <th scope="col">
                                Alasan
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach (
                            $correctionHistory
                            as $correction
                        )
                            @php
                                $currentAction =
                                    strtolower(
                                        (string) $correction
                                            ->action
                                    );

                                $actionLabel =
                                    $actionLabels[
                                        $currentAction
                                    ]
                                    ?? \Illuminate\Support\Str::headline(
                                        $currentAction
                                    );

                                $actionClass =
                                    $actionClasses[
                                        $currentAction
                                    ]
                                    ?? 'text-bg-secondary';
                            @endphp

                            <tr>
                                <td
                                    class="text-center
                                        text-secondary"
                                >
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{
                                            $formatDateTime(
                                                $correction
                                                    ->created_at
                                            )
                                        }}
                                    </div>

                                    <div
                                        class="small
                                            text-secondary"
                                    >
                                        WIB
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="badge {{
                                            $actionClass
                                        }}"
                                    >
                                        {{ $actionLabel }}
                                    </span>
                                </td>

                                <td>
                                    {{
                                        $correction
                                            ->correctedBy
                                            ?->name
                                        ?? 'Pengguna tidak tersedia'
                                    }}
                                </td>

                                <td>
                                    {{ $correction->reason }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @include(
        'attendance-corrections._form',
        [
            'attendance' => $attendance,

            'formAction' => route(
                'attendance-corrections.update',
                [
                    'attendance' =>
                        $attendance->getKey(),
                ]
            ),

            'formMethod' => 'PUT',

            'submitLabel' =>
                'Simpan Koreksi',

            'cancelUrl' => $monitoringUrl,
        ]
    )
@endsection